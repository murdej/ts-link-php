# TS link

What this thing can do. It simply connects PHP and typescript. Just write a class in PHP:

```php
class MyClassCL {
    [CLMethod()]
    public function sayHello(string $name) : string
    {
        return "Hello $name from PHP";
    }
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

