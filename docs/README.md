# TS Link documentation

TS Link connects a PHP backend to a TypeScript/JavaScript frontend: you write plain PHP methods, mark them
with an attribute, and the library generates a typed client class you call directly from the frontend —
no manual fetch calls, no hand-written DTOs.

- [Getting started](getting-started.md) — install, write your first method, generate the client, call it
- [Client methods & types](client-methods.md) — attributes that control what gets exposed and how types
  are translated to TypeScript
- [Context, middleware, uploads & errors](advanced-features.md) — shared request context, middleware,
  file upload/download, error handling
- [Batch mode](batch-mode.md) — send several method calls in a single HTTP request
- [Nette & Symfony integration](nette-bridge.md) — the ready-made HTTP endpoint and console command
- [Code generation options](code-generation.md) — configuring `TsCodeGenerator`

If you just want the shortest possible example, see the root [README](../README.md).
