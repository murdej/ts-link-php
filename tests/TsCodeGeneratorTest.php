<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp\Tests;

use Murdej\TsLinkPhp\Tests\Fixtures\SampleCL;
use Murdej\TsLinkPhp\TsCodeGenerator;
use PHPUnit\Framework\TestCase;

class TsCodeGeneratorTest extends TestCase
{
    private function generate(?callable $configure = null): string
    {
        $tsg = new TsCodeGenerator();
        $tsg->add(SampleCL::class);
        if ($configure) {
            $configure($tsg);
        }

        return $tsg->generateCode();
    }

    public function testGeneratesMethodWithDefaultValue(): void
    {
        $code = $this->generate();

        self::assertStringContainsString(
            'public greet(name: string = "World") : Promise<string> { return this.callMethod("greet", arguments, {"rawResult":false}); }',
            $code
        );
    }

    public function testOnlyAttributedMethodsAreExposed(): void
    {
        $code = $this->generate();

        self::assertStringNotContainsString('helperNotExposed', $code);
    }

    public function testClientMethodTypeOverridesReturnTypeAndAddsImport(): void
    {
        $code = $this->generate();

        self::assertStringContainsString(': Promise<User>', $code);
        self::assertStringContainsString('type User', $code);
    }

    public function testClientMethodImportOnClassIsIncluded(): void
    {
        $code = $this->generate();

        self::assertStringContainsString('type UserRole', $code);
    }

    public function testNewPrefixConstructsTypedResultClientSide(): void
    {
        $code = $this->generate();

        self::assertStringContainsString(
            'public getUserModel(id: number) : Promise<UserModel> { return this.callMethod("getUserModel", arguments, {"rawResult":false}, UserModel); }',
            $code
        );
        // value import (no "type " prefix), unlike a plain ClientMethodType('User', ...)
        self::assertStringContainsString('UserModel', $code);
        self::assertStringNotContainsString('type UserModel', $code);
    }

    public function testRawResultOptionIsPassedThroughAndReturnsByteArray(): void
    {
        $code = $this->generate();

        self::assertStringContainsString(
            'public download() : Promise<ByteArray> { return this.callMethod("download", arguments, {"rawResult":true}); }',
            $code
        );
    }

    public function testClientClassImplementsInterface(): void
    {
        $code = $this->generate();

        self::assertStringContainsString('export class SampleCL extends BaseCL implements MyInterface', $code);
    }

    public function testJsFormatOmitsTypeAnnotations(): void
    {
        $code = $this->generate(function (TsCodeGenerator $tsg) {
            $tsg->format = 'js';
        });

        self::assertStringContainsString('greet(name = "World")', $code);
        self::assertStringNotContainsString(': string', $code);
    }

    public function testBaseClassRequireSkipsInliningBaseCL(): void
    {
        $code = $this->generate(function (TsCodeGenerator $tsg) {
            $tsg->baseClassRequire = '../BaseCL';
        });

        self::assertStringContainsString('import { BaseCL } from "../BaseCL";', $code);
        self::assertStringNotContainsString('export class BaseCL', $code);
    }

    public function testExportSingletonCanBeDisabled(): void
    {
        $withSingleton = $this->generate();
        self::assertStringContainsString('export const sampleCL', $withSingleton);

        $withoutSingleton = $this->generate(function (TsCodeGenerator $tsg) {
            $tsg->exportSingleton = false;
        });
        self::assertStringNotContainsString('export const sampleCL', $withoutSingleton);
    }
}
