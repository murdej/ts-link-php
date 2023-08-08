# TS link

What this thing can do. It simply connects PHP and typescript. Just write a class in PHP:

```php
public function sayHello(string $name) : string
{
    return "Hello $name from PHP " . date('Y-m-d H:i:s') . ".";
}
```

and call in typescript:

```javascript
const cl = new MyClassCL();
const value = await cl.sayHello("typescript");
```

## How to use?

Install `murdej/ts-link-php` package with composer.

```bash
composer require murdej/ts-link-php
```

Create a class that will contain the methods you will use from js/ts. These methods must have the `#[ClientMethod()]` attribute from the namespace `Murdej\TsLinkPhp\`.

```php
use Murdej\TsLinkPhp\ClientMethod;

class MyClassCL {
    #[ClientMethod()]
    public function sayHello(string $name) : string
    {
        return "Hello $name from PHP " . date('Y-m-d H:i:s') . ".";
    }
}
```

Create endpoint (in `endpoint.php`):

```php
// Create instance of your service
$service = new MyClassCL();
// Create an instance of TsLink and pass your service to the constructor.
$tl = new TsLink($service);
// Get raw post content
$rawPost = file_get_contents('php://input');
// Call processRequest and pass contents, 
$response = $tl->processRequest($rawPost);
// Result sent as json
header('Content-type: ' . $response->getContentType());
echo $response;
```

Generate js or ts code

```php
$tsg = new TsCodeGenerator();
// Add a PHP class, optionally also the endpoint address. This step can be repeated.
$tsg->add(MyClassCL::class, './endpoint.php');
// Export format, can be ts or js. Default is ts
$tsg->format = "js";
// Enable or disable js modules (https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Modules)
$tsg->useExport = false;
// Create and save ts/js sources
$source = $tsg->generateCode();
file_put_contents('./tslClasses.js', $source);
```
The generation of js/ts must be started after creating or modifying the header of the method to be accessed from js/ts.

Then you can call PHP methods from js/ts.

```html
<h1 id="message"></h1>
<script src="tslClasses.js"></script>
<script>
    const myClass = new MyClassCL();
    (async () => {
        const res = await myClass.sayHello("TypeScript");
        document.getElementById("message").innerText = res;
    })();
</script>
```
