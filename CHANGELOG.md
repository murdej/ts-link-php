# Unreleased

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
