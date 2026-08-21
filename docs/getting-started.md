# Getting started

## Install

```bash
composer require murdej/ts-link-php
```

## 1. Write a service class

Any class can be exposed to the frontend. Mark each method you want to call from TypeScript with the
`#[ClientMethod()]` attribute:

```php
use Murdej\TsLinkPhp\ClientMethod;

class MyClassCL
{
    #[ClientMethod()]
    public function sayHello(string $name): string
    {
        return "Hello $name from PHP " . date('Y-m-d H:i:s') . ".";
    }
}
```

Only attributed methods are exposed — everything else on the class stays private to the backend.
Parameter and return types are read via reflection and translated to TypeScript automatically (`string`,
`int`, `bool`, `float`, arrays, nullable types, your own classes, `DateTime`, ...). See
[Client methods & types](client-methods.md) if you need more control over how a type is generated.

## 2. Create an endpoint

The simplest possible endpoint (`endpoint.php`):

```php
use Murdej\TsLinkPhp\TsLink;

$service = new MyClassCL();
$tl = new TsLink($service);

$rawPost = file_get_contents('php://input');
$response = $tl->processRequest($rawPost);

header('Content-type: ' . $response->getContentType());
echo $response;
```

If you're on Nette, you don't need to write this by hand — see
[Nette & Symfony integration](nette-bridge.md) for a ready-made HTTP endpoint that handles routing, file
uploads and CORS for you.

## 3. Generate the TypeScript client

```php
use Murdej\TsLinkPhp\TsCodeGenerator;

$tsg = new TsCodeGenerator();
// Add a class and, optionally, the URL of its endpoint. Repeat for every class you want to expose.
$tsg->add(MyClassCL::class, './endpoint.php');

// "ts" (default) or "js"
$tsg->format = 'ts';

file_put_contents('./tslClasses.ts', $tsg->generateCode());
```

Re-run this whenever you add, remove, or change the signature of a `#[ClientMethod]`. See
[Code generation options](code-generation.md) for the available settings (module format, base class
sharing, import paths, ...).

## 4. Call it from the frontend

```typescript
import { myClassCL } from './tslClasses';
// or: const myClass = new MyClassCL();

const message = await myClassCL.sayHello('TypeScript');
console.log(message);
```

```html
<h1 id="message"></h1>
<script src="tslClasses.js"></script>
<script>
    const myClass = new MyClassCL();
    (async () => {
        document.getElementById('message').innerText = await myClass.sayHello('TypeScript');
    })();
</script>
```

Calling a method returns a `Promise` that resolves with whatever the PHP method returned, or rejects with
an `Error` if the PHP side threw an exception (or the call otherwise failed) — see
[Error handling](advanced-features.md#error-handling).

## Where to go next

- Need to share request-scoped data (auth token, tenant id, ...) between calls? See
  [Context](advanced-features.md#context).
- Need to accept file uploads or return a file/binary response? See
  [File uploads & downloads](advanced-features.md#file-uploads--downloads).
- Want to run several calls in one HTTP round trip? See [Batch mode](batch-mode.md).
