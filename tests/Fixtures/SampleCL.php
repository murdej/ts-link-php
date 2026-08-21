<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp\Tests\Fixtures;

use Murdej\TsLinkPhp\ClientClass;
use Murdej\TsLinkPhp\ClientMethod;
use Murdej\TsLinkPhp\ClientMethodImport;
use Murdej\TsLinkPhp\ClientMethodType;
use Murdej\TsLinkPhp\RawData;

#[ClientClass(implements: ['MyInterface'])]
#[ClientMethodImport('./models', ['UserRole'])]
class SampleCL
{
    #[ClientMethod()]
    public function greet(string $name = 'World'): string
    {
        return "Hello $name";
    }

    public function helperNotExposed(): void
    {
        // no #[ClientMethod] attribute - must not appear in generated code
    }

    #[ClientMethod()]
    #[ClientMethodType('User', importFrom: './models')]
    public function getUser(int $id): array
    {
        return [];
    }

    #[ClientMethod()]
    #[ClientMethodType('new UserModel', importFrom: './models')]
    public function getUserModel(int $id): array
    {
        return [];
    }

    #[ClientMethod(rawResult: true)]
    public function download(): RawData
    {
        return RawData::data('x');
    }
}
