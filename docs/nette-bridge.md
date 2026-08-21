# Nette & Symfony integration

Writing the raw `processRequest()` endpoint by hand (as in [Getting started](getting-started.md)) works
anywhere, but if you're on Nette (or Symfony, via its console command) there's a ready-made HTTP endpoint
that handles routing, multipart uploads, CORS, and on-demand code generation for you.

## `TLApplication` (Nette)

Register your service classes once, then let `TLApplication` dispatch requests:

```php
use Murdej\TsLinkPhp\Bridges\Nette\TLApplication;

$tlApplication = new TLApplication(
    urlPrefix: '/api/',
    httpRequest: $httpRequest,
    httpResponse: $httpResponse,
    cors: true, // or an array of allowed origins, or false to disable
);

$tlApplication->add(new UserCL());
$tlApplication->add(new CartCL());

// wire this into your router for the /api/* path, then:
$tlApplication->run();
```

Each registered class gets its own URL, `urlPrefix + <name>` — by default derived from the class name
(stripped of `codeGenCNRemovePrefix`/`codeGenCNRemoveSufix`, default suffix `"TL"`), or pass an explicit
name as the second argument to `add()`. `run()` reads the request body (JSON, or multipart when uploads
are involved), calls `processRequest()` on the matching service, and sends the response — including raw
file responses (see [downloads](advanced-features.md#downloads--raw-responses)).

If a registered service defines a `startup()` method, it's called once before the request is processed —
a convenient place for per-request setup that doesn't belong in the constructor.

### On-demand code generation

Two extra routes are available under the same prefix:

- `<prefix>@code-gen` — (re)generates the TypeScript client and writes it to `codeGenFile`. Requires
  `codeGenFile` to be set and `codeGenEnabled` to be `true` (both are opt-in).
- `<prefix>@code-dump` — generates the client and returns it directly as the HTTP response body, without
  writing to disk. Handy while developing: point your browser at it to grab the current client code.

```php
$tlApplication->codeGenFile = __DIR__ . '/../www/tslClasses.ts';
$tlApplication->codeGenFormat = 'ts';
$tlApplication->codeGenEnabled = true;
```

### CORS

```php
$tlApplication->corsAllowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
$tlApplication->corsAllowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'Cookie'];
$tlApplication->corsMaxAge = 300;
```

Pass `cors: true` to allow any origin, or an array of allowed origins, to the constructor.

### Error output

`$tlApplication->debugger` controls how an uncaught error is rendered:

- `TLApplication::Debugger_Nette` (default) — hands off to Tracy's exception handler.
- `TLApplication::Debugger_Json` — a JSON error payload (code, message, file, trace).
- `TLApplication::Debugger_Text` — a plain-text stack trace.
- `TLApplication::Debugger_Hide` — a bare 500 with no body.

### Middleware

Register middleware once on the application and it applies to every registered class:

```php
$tlApplication->addMiddleware(new AuthMiddleware());
```

## Symfony console command

`GenTlCommand` (`Murdej\TsLinkPhp\Bridges\Symfony\GenTlCommand`) is a `gen:ts-link` console command that
regenerates the TypeScript client from all classes registered on a `TLApplication` instance:

```yaml
# services.yaml
Murdej\TsLinkPhp\Bridges\Symfony\GenTlCommand:
    arguments:
        $tlFilePath: '%kernel.project_dir%/assets/tslClasses.ts'
        $generatorOpts:
            useJsModules: true
```

```bash
php bin/console gen:ts-link
```

`$generatorOpts` is applied directly onto the underlying `TsCodeGenerator` instance (any public property,
see [Code generation options](code-generation.md)) — by default the command points `baseClassName`/
`baseClassRequire` at a project-local `MyBaseTL` class rather than inlining `BaseCL`, so adjust those to
match your project layout if you use the command as-is.
