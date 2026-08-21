# Client methods & types

## `#[ClientMethod]`

Exposes a method to the generated client.

```php
use Murdej\TsLinkPhp\ClientMethod;

class MyClassCL
{
    #[ClientMethod()]
    public function getUser(int $id): string { /* ... */ }
}
```

`#[ClientMethod(rawResult: true)]` marks the method as returning raw data (a file download or arbitrary
bytes) instead of a JSON value — see
[File uploads & downloads](advanced-features.md#file-uploads--downloads).

## Parameter and return types

Scalar PHP types are translated to TypeScript automatically:

| PHP                       | TypeScript          |
|----------------------------|----------------------|
| `string`                   | `string`             |
| `int`, `float`              | `number`             |
| `bool`                      | `boolean`             |
| nullable (`?string`, ...)  | adds `\|null`          |
| union of the above          | union type (`A\|B`)    |

Anything else — `array`, `object`, your own classes, `DateTime`, ... — comes out as `any` unless you tell
the generator what it should actually be with `#[ClientMethodType]` below. This is normal and expected:
scalars are inferred, everything richer is declared explicitly.

Default parameter values are picked up from the PHP signature and reproduced in the generated TypeScript
signature, so `function greet(string $name = 'World')` becomes `greet(name: string = "World")`.

## `#[ClientMethodType]` — declaring a real type

Attach this to a method (its return type) or a parameter whenever you want something more specific than
`any` on the TypeScript side — a class, an array of a class, a generic collection, a union PHP can't
type-hint, or a type that lives in another TS file:

```php
use Murdej\TsLinkPhp\ClientMethod;
use Murdej\TsLinkPhp\ClientMethodType;

class MyClassCL
{
    #[ClientMethod()]
    #[ClientMethodType('User', importFrom: './models')]
    public function getUser(int $id): User { /* ... */ }

    #[ClientMethod()]
    #[ClientMethodType('User[]', importFrom: './models')]
    public function listUsers(): array { /* ... */ }

    #[ClientMethod()]
    public function findUsersByRole(
        #[ClientMethodType('UserRole', importFrom: './models')] string $role
    ): array { /* ... */ }
}
```

- Put the attribute on the **method** to override the return type, or on a **parameter** to override that
  parameter's type.
- `importFrom` adds a matching `import { ... } from '...'` to the generated file (see
  [imports](#clientmethodimport--adding-imports) below).
- On a **method** (return type) only: prefixing the type with `new ` (e.g. `new UserModel`) tells the
  generator to construct that class client-side from the raw JSON response (`new UserModel(response)`),
  instead of just typing the response — handy for response classes with their own constructor/parsing
  logic. This has no special effect when used on a parameter.

## `#[ClientMethodImport]` — adding imports

Attach arbitrary TypeScript imports to a method or to the whole class (repeatable, and inherited from
parent classes too):

```php
use Murdej\TsLinkPhp\ClientMethodImport;

#[ClientMethodImport('./models', ['User', 'UserRole'])]
class MyClassCL
{
    // ...
}
```

This is useful when a type is referenced only inside a hand-written `#[ClientMethodType]` string and the
generator has no other way to know it needs importing.

## `#[ClientClass]` — extra TS interfaces

Makes the generated TypeScript class also `implement` one or more interfaces:

```php
use Murdej\TsLinkPhp\ClientClass;

#[ClientClass(implements: ['MyClientInterface'])]
class MyClassCL
{
    // ...
}
```

Combine with `#[ClientMethodImport]` if `MyClientInterface` needs importing.
