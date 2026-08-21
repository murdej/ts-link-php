# Context, middleware, uploads & errors

## Context

Context is small piece of state sent along with **every** request from a given client instance — typically
an auth token, tenant id, locale, or similar. It's just a plain object on the client:

```typescript
const myClass = new MyClassCL();
myClass.context.authToken = 'xyz';
await myClass.someMethod(); // context is sent along automatically
```

On the backend, if your service exposes a public `$context` property (array or object), the client's
context is merged into it before the method call runs:

```php
class MyClassCL
{
    public array $context = [];

    #[ClientMethod()]
    public function whoAmI(): string
    {
        return $this->context['authToken'] ?? 'anonymous';
    }
}
```

If your service implements `IContextUpdate`, its `getContextUpdates()` return value is attached to the
raw server response after every call — useful for e.g. rotating a token. This value isn't merged into the
client's `context` automatically; read it yourself (e.g. from the `onLoaded` hook on the client) if you
need to keep it in sync:

```php
use Murdej\TsLinkPhp\IContextUpdate;

class MyClassCL implements IContextUpdate
{
    public array $context = [];

    public function getContextUpdates(): array
    {
        return ['serverTime' => time()];
    }
}
```

## Middleware

Middleware runs around every call — useful for authentication, logging, or validation that applies across
many methods. Implement `MiddlewareInterface` and register it on `TsLink` (or on the Nette bridge, which
applies it to every registered class):

```php
use Murdej\TsLinkPhp\MiddlewareInterface;
use Murdej\TsLinkPhp\MiddlewareEvent;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(MiddlewareEvent $event): void
    {
        if (!$this->isAuthorized($event->request, $event->service)) {
            throw new \RuntimeException('Unauthorized');
        }

        $event->next(); // continue to the next middleware / the actual method call
    }
}

$tl->addMiddleware(new AuthMiddleware());
```

`$event` exposes the resolved `request` (method name + arguments), the mutable `response`, the `service`
instance, and the reflected `methodParams`. Not calling `$event->next()` short-circuits the call — the
method is never invoked and whatever you left on `$event->response` is what gets sent back.

Middleware runs for both single requests and, once per item, for every item inside a
[batch request](batch-mode.md).

## File uploads & downloads

### Uploads

Typing a parameter as `File` or `FileList` in the generated TypeScript client is enough — `BaseCL`
automatically detects such arguments and sends the request as `multipart/form-data` instead of JSON.

```typescript
await myClass.uploadAvatar(fileInputElement.files[0]);
```

On the backend, uploaded files are made available through the `$files` array passed to
`TsLink::processRequest($src, $files)` — with the Nette bridge this is wired up for you automatically
(files come from the incoming multipart request). What object type you receive per file depends on how
your endpoint populates `$files` (with the Nette bridge, `Nette\Http\FileUpload` instances).

### Downloads / raw responses

To return something other than a JSON value — a file download, an image, arbitrary bytes — mark the
method `rawResult: true` and return a `RawData`:

```php
use Murdej\TsLinkPhp\ClientMethod;
use Murdej\TsLinkPhp\RawData;

class MyClassCL
{
    #[ClientMethod(rawResult: true)]
    public function downloadReport(): RawData
    {
        $raw = RawData::filePath('/path/to/report.pdf');
        $raw->contentType = 'application/pdf';
        return $raw;
    }
}
```

`RawData::filePath(...)` streams an existing file; `RawData::data($bytes)` returns raw bytes you already
have in memory. Either way, the response bypasses the normal `{status, response}` JSON envelope — the
bridge sends the file/content directly, using the `contentType` you set.

> Note: the client's default `dataFetcher` only treats a response as a binary `Blob` when the response
> `Content-Type` is exactly `octed/stream`; any other non-JSON content type is read as plain text. If you
> need proper binary handling for a different content type, override the client's `dataFetcher` property.

## Error handling

By default (`TsLink::$sendException = true`), an exception thrown inside a service method propagates out
of `processRequest()` — convenient for local development, since your normal PHP error handler / debugger
sees the real exception.

Set `$sendException = false` for a production API that should always answer with a JSON envelope instead:
the exception is caught and turned into `{"status": "exception", "exception": "..."}`, which the client
turns into a rejected promise (`new Error(...)`) instead of throwing an unhandled error.

```php
$tl = new TsLink($service);
$tl->sendException = false;
$tl->onError = function (string $rawRequest, \Throwable $exception) {
    $logger->error($exception->getMessage(), ['request' => $rawRequest]);
};
```

`onError`, when set, is always called on any exception (regardless of `sendException`), so it's a good
place to centralize logging.

The same `sendException`/`onError` settings apply, per item, to [batch requests](batch-mode.md#error-handling) —
one failing item doesn't have to sink the rest of the batch.
