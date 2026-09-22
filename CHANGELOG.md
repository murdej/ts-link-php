# Version 1.8.0

 - **TsLink**: Added batch mode — a request with a `batch` key instead of `name` runs several calls in
   one request; `processRequest()` now dispatches to new `processItem()`/`processBatch()` internals
 - **TsLink**: Added `IBatchCall`/`BatchCallEvent` — services implementing `IBatchCall` get a `batchCall()`
   hook invoked once per batch item (after the item's `Request` is resolved, before the middleware/method
   call), useful for resetting per-request state between items
 - **TsLink**: Batch item failures are isolated per item when `$sendException` is `false` (one bad call
   doesn't sink the rest of the batch); with `$sendException = true` the first failure aborts the whole
   batch, consistent with single-request behavior
 - **TsLink**: File uploads inside a batch item are resolved per item via a new `extractBatchItemFiles()`,
   matching the `{id}_{localKey}` field naming used by the client
 - **Response**: Added `$batch` property; `jsonSerialize()` emits the `{"batch": [...]}` envelope when set
 - **Response**: `$exception` property now also accepts `Throwable`, not just `Exception`
 - **TsLink**: Added argument preparers (`$argumentPreparers`, `addArgumentPreparer()`) — pluggable
   callbacks that transform an incoming argument before it reaches the method, replacing the previous
   hardcoded `DateTime`-only conversion; the built-in preparer now also converts to `DateTimeImmutable`
 - **TsLink**: Added `Dto\MethodArgument` (replaces the internal `['nullable' => ..., 'types' => ...]`
   array previously returned by `getMethodArguments()`) and `Dto\ArgumentPreparerEvent`
   (`value`/`argument`/`callNext`), used by argument preparers
 - **Bridges\PhpLibs**: Added `SmartDtoArgumentPreparer` — converts an array argument into a
   `murdej/php-libs` `Murdej\DTO\SmartDTO`-derived DTO based on the parameter's declared type
 - **Bridges\Nette\TLApplication**: Added `$argumentPreparers`/`addArgumentPreparer()`, forwarded to
   the per-request `TsLink` instance in `run()` — mirrors how `$middlewares` is already forwarded, and
   is required for argument preparers (e.g. `SmartDtoArgumentPreparer`) to take effect when using the
   Nette bridge
 - **Bridges\Symfony**: Removed unused `App\Services\ItemFinder` import from `GenTlCommand`
 - Added `nette/application`, `nette/http`, `tracy/tracy`, `symfony/console`, and `murdej/php-libs` to
   `require-dev` — needed to develop/test the classes under `Bridges/`
 - **BaseCL**: Added client-side batch mode — `useBatch(callback?)`, `useBatchAuto(config)`, and `send()`
   queue/coalesce several method calls into a single HTTP request
 - Added a test suite (PHPUnit, `tests/`) and `.gitattributes` (`export-ignore` for `tests/`, `docs/`,
   and other files not needed in the installed package)
 - Added usage documentation under `docs/`

# Version 1.7.1

 - Fix no arguments bug

# Version 1.7.0

 - **TsCodeGenerator**: Added `$importClassPrefix` static property (default `'@'`) to distinguish class (value) imports from type-only imports — prefixed types are emitted as `import { ClassName }`, others as `import { type TypeName }`
 - **ClassReflection**: `new ClassName` references now carry the import class prefix so they are correctly emitted as value imports instead of `type` imports
 - **TLApplication**: JSON debugger now uses native `json_encode` with `JSON_PARTIAL_OUTPUT_ON_ERROR` instead of `Nette\Utils\Json::encode`, preventing serialization failures when exception traces contain non-serializable objects
 - **TLApplication**: CORS headers (`allowedMethods`, `allowedHeaders`, `maxAge`) are now configurable via public properties instead of being hardcoded
 - **TLApplication**: Fixed `getLinkForClass` URL to consistently include `/` separator between prefix and name (same as `getLinkForName`)
 - **TLApplication**: Fixed null dereference when `Content-Type` header is missing in multipart detection
 - **TLApplication**: Added URL prefix validation — throws 404 when request path does not start with configured prefix
 - **TsCodeGenerator**: Fixed deprecated `$classReflection` path — no longer wraps already-reflected object in a second `ClassReflection`
 - **TsLink**: Added validation for empty/missing request body, `name` field, and `pars` field
 - **TsLink**: Fixed fatal error in `getMethodArguments` when a parameter has no type declaration (`$type` was null)

# Version 1.6.0

 - **BaseCL**: Added `EventMethodCallData` and `EventError` typed exports for strongly-typed event callbacks
 - **BaseCL**: Added `dataFetcher` property — an overridable fetch implementation (`defaultDataFetcher`) for customizing HTTP requests
 - **BaseCL**: `onPrepareRequest`, `onLoading`, and `onError` callbacks now use typed parameters (`EventMethodCallData`, `EventError`)
 - **BaseCL**: Added `DataFetcherResponse` type
 - **BaseCL**: Fixed `FileList` guard to work in non-browser environments (`typeof FileList !== 'undefined'`)
 - **TLApplication**: Added CORS support via `cors` constructor parameter (accepts `true` or an array of allowed origins)
 - **TLApplication**: Added `debugger` property with modes: `Nette` (default), `Json`, `Text`, `Hide` — controls error output format
 - **TLApplication**: Fixed `@code-dump` route to work even when `codeGenFile` is not set

# Version 1.5.0

 - Middleware event has service field
 - Mutlipart content type and file transfer

# Version 1.4.0

 - Middeware
 - Option to define the interface implemented by the generated class.
 - Attributes of the php class are also read from its ancestors.

# Version 1.3.0

 - Support for default values of method arguments.
