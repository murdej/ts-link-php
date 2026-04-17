# Unreleased

 - **TsCodeGenerator**: Added `$importClassPrefix` static property (default `'@'`) to distinguish class (value) imports from type-only imports — prefixed types are emitted as `import { ClassName }`, others as `import { type TypeName }`
 - **ClassReflection**: `new ClassName` references now carry the import class prefix so they are correctly emitted as value imports instead of `type` imports
 - **TLApplication**: JSON debugger now uses native `json_encode` with `JSON_PARTIAL_OUTPUT_ON_ERROR` instead of `Nette\Utils\Json::encode`, preventing serialization failures when exception traces contain non-serializable objects

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
