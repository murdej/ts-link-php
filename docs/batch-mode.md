# Batch mode

Batch mode lets a client coalesce several method calls made against the same client instance into a
single HTTP request. Instead of firing one request per call, the calls are queued and sent together;
each call still gets back its own promise, resolved or rejected independently once the server replies.

This is useful when a page needs several independent pieces of data at once (user, cart, notifications, ...)
and you don't want to pay for N separate round trips.

## Enabling batch mode

There are three ways to use it, all available on every generated client class (they're inherited from
`BaseCL`).

### 1. `useBatch(callback)` — scoped batch

Pass a callback; it runs synchronously against the client, and the batch is sent as soon as the callback
returns. `useBatch()` returns whatever the callback returned — typically an array of the pending call
promises, ready for `Promise.all`.

```typescript
const [user, cart, notifies] = await Promise.all(
    new AppTL().useBatch(tl => [
        tl.getUser(),
        tl.getCart(),
        tl.getNotifies(),
    ])
);
```

### 2. `useBatch()` — manual batch

Called without a callback, it just turns batch mode on and returns the instance. Every subsequent method
call is queued instead of sent; you decide when to flush by calling `.send()`.

```typescript
const tl = new AppTL().useBatch();
tl.getUser().then(data => showUser(data));
tl.getCart().then(data => showCart(data));
tl.getNotifies().then(data => showNotifies(data));
tl.send();
```

### 3. `useBatchAuto(config)` — automatic batch

Returns a **clone** of the instance with batch mode on and an auto-flush policy. The original instance is
left untouched (still sends requests immediately); only the returned clone batches. Queued calls are sent
automatically, whichever comes first:

- `config.sleepTimeout` ms have passed since the *last* queued call (debounce), or
- `config.maxTimeout` ms have passed since the *first* call in the current queue (ceiling), or
- the queue has reached `config.maxRequests` items.

```typescript
type BatchConfig = {
    sleepTimeout: number,
    maxTimeout: number,
    maxRequests: number,
}

const tl = new AppTL().useBatchAuto({ sleepTimeout: 100, maxTimeout: 1000, maxRequests: 10 });
tl.getUser().then(data => showUser(data));
tl.getCart().then(data => showCart(data));
tl.getNotifies().then(data => showNotifies(data));
// sent automatically once the batch settles, hits the max timeout, or fills up
```

Passing `null` to `useBatchAuto` returns a clone with batching turned off.

## Wire protocol

A single (non-batch) call is unchanged:

```jsonc
// request
{
    "name": "getUser",
    "context": {},
    "pars": [16],
    "uploadArgs": []
}

// response
{
    "status": "ok",
    "response": { "name": "John" }
}
```

A batch request wraps any number of those same per-call objects (each carrying its own `context`) under a
`batch` key, with an `id` added so the client can match each response back to its promise:

```jsonc
// request
{
    "batch": [
        { "id": 1, "name": "getUser", "context": {}, "pars": [16], "uploadArgs": [] },
        { "id": 2, "name": "getCart",  "context": {}, "pars": [16], "uploadArgs": [] }
    ]
}

// response
{
    "batch": [
        { "id": 1, "status": "ok", "response": { "name": "John" } },
        { "id": 2, "status": "ok", "response": [] }
    ]
}
```

Each item's `status`/`response`/`exception` shape is identical to a single-call response — a failed item
just gets `"status": "exception"` while the rest of the batch still returns normally (see
[Error handling](#error-handling) below).

If the service implements `IContextUpdate`, a single top-level `"context"` key (reflecting the service's
state after the *whole* batch finished) is added alongside `"batch"`, the same way it's added alongside
`"status"`/`"response"` for a single call.

## Backend dispatch

On the PHP side nothing changes for a plain `TsLink`/`TLApplication` setup — `TsLink::processRequest()`
detects the `batch` key and dispatches accordingly:

```php
$tl = new TsLink($service);
$response = $tl->processRequest($rawPost, $files);
```

If the request contains a `batch` key instead of `name`, each item is processed the same way a single
request would be (context merge, parameter/upload/`DateTime` resolution, middleware chain, method call),
and all the results are returned together in one response.

### `IBatchCall` — per-item hook

If `$service` implements `IBatchCall`, its `batchCall()` method is invoked once per batch item, right
after that item's `Request` has been resolved but **before** the middleware chain / method call runs. This
is the place to do things like clear a per-request cache or reset service state between items:

```php
use Murdej\TsLinkPhp\BatchCallEvent;
use Murdej\TsLinkPhp\IBatchCall;

class MyServiceCL implements IBatchCall
{
    public function batchCall(BatchCallEvent $event): void
    {
        // $event->id         - the batch item's id, as sent by the client
        // $event->currentNum - 1-based position of this item within the batch
        // $event->count      - total number of items in the batch
        // $event->request    - the resolved Request (methodName + args) about to run
        // $event->tsLink     - the TsLink instance handling the request

        $this->cache->clear();
    }
}
```

`batchCall()` is not a filter — it can't skip or short-circuit the call, it's purely a lifecycle hook.

## Error handling

Batch item failures don't abort the whole batch by default. This is governed by the existing
`TsLink::$sendException` flag, extended consistently to batches:

- **`sendException = false`** (typical for production APIs that always answer with a JSON envelope): each
  item is caught independently. A failing item gets `"status": "exception"` in its own response slot; every
  other item in the batch still runs and returns normally.
- **`sendException = true`** (e.g. local/dev debugging): the first item that throws aborts the entire
  batch — the exception propagates out of `processRequest()` exactly like a single-request failure would.

`TsLink::$onError`, when set, is still invoked for every failing item (with that item's JSON as the "raw"
payload) before the isolate-or-abort decision is applied.

## File uploads in a batch

Uploading a `File`/`FileList` argument inside a batched call works the same way it does for a single call,
just namespaced per item so multiple items' uploads don't collide in the merged multipart request. On the
client, each queued item's local upload field names (`0`, `1`, ...) are prefixed with that item's batch
`id` when they're appended to the shared `FormData` (e.g. `1_0`, `2_0`); the `pars`/`uploadArgs` inside
each item's own JSON keep the plain local numbers. The server slices the incoming files back apart by that
same `{id}_` prefix before resolving each item, so application code never has to know batching is involved.

## Notes and limitations

- Batch mode is a client-side (`BaseCL`) concern layered on top of the existing single-call protocol —
  `TsCodeGenerator` requires no changes and no regeneration beyond picking up the updated `BaseCL` source.
- The `onPrepareRequest` / `onLoading` / `onLoaded` / `onError` hooks on `BaseCL` are only invoked for
  single (non-batch) sends; a batched `.send()` does not call them, to avoid changing their existing
  single-call type signatures.
- `useBatchAuto()`'s clone shares nothing mutable with the instance it was cloned from — each has its own
  queue and timers, so batching one doesn't affect the other.
