<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp\Tests;

use Murdej\TsLinkPhp\RawData;
use PHPUnit\Framework\TestCase;

class RawDataTest extends TestCase
{
    public function testFilePathFactory(): void
    {
        $raw = RawData::filePath('/tmp/x.txt');

        self::assertSame('/tmp/x.txt', $raw->filePath);
        self::assertNull($raw->data);
        self::assertSame('application/octet-stream', $raw->contentType);
    }

    public function testDataFactory(): void
    {
        $raw = RawData::data('bytes');

        self::assertSame('bytes', $raw->data);
        self::assertNull($raw->filePath);
    }
}
