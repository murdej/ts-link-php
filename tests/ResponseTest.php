<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp\Tests;

use Murdej\TsLinkPhp\RawData;
use Murdej\TsLinkPhp\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testOkResponse(): void
    {
        $res = new Response(response: ['a' => 1]);

        self::assertSame(['status' => 'ok', 'response' => ['a' => 1]], $res->jsonSerialize());
    }

    public function testExceptionResponse(): void
    {
        $res = new Response(exception: new \RuntimeException('boom'));
        $data = $res->jsonSerialize();

        self::assertSame('exception', $data['status']);
        self::assertStringContainsString('boom', $data['exception']);
        self::assertArrayNotHasKey('response', $data);
    }

    public function testContextIsIncludedWhenSet(): void
    {
        $res = new Response(response: 1, context: ['x' => 1]);

        self::assertSame(['x' => 1], $res->jsonSerialize()['context']);
    }

    public function testContextIsOmittedWhenEmpty(): void
    {
        $res = new Response(response: 1);

        self::assertArrayNotHasKey('context', $res->jsonSerialize());
    }

    public function testBatchResponseShape(): void
    {
        $res = new Response();
        $res->batch = [
            ['id' => 1, 'response' => new Response(response: 'a')],
            ['id' => 2, 'response' => new Response(exception: new \RuntimeException('x'))],
        ];
        $res->context = ['touched' => true];

        $data = $res->jsonSerialize();

        self::assertSame(['id' => 1, 'status' => 'ok', 'response' => 'a'], $data['batch'][0]);
        self::assertSame('id', array_key_first($data['batch'][1]));
        self::assertSame(2, $data['batch'][1]['id']);
        self::assertSame('exception', $data['batch'][1]['status']);
        self::assertStringContainsString('x', $data['batch'][1]['exception']);
        self::assertSame(['touched' => true], $data['context']);
    }

    public function testGetTextContentProducesValidJson(): void
    {
        $res = new Response(response: ['a' => 1]);

        self::assertSame(
            ['status' => 'ok', 'response' => ['a' => 1]],
            json_decode($res->getTextContent(), true)
        );
    }

    public function testGetFilePathAndContentTypeWithoutRawData(): void
    {
        $res = new Response(response: ['a' => 1]);

        self::assertNull($res->getFilePath());
        self::assertSame('application/json', $res->getContentType());
    }

    public function testGetFilePathReturnsPathForRawData(): void
    {
        $res = new Response(response: RawData::filePath('/tmp/foo.pdf'));

        self::assertSame('/tmp/foo.pdf', $res->getFilePath());
    }

    public function testGetContentTypeUsesRawDataContentType(): void
    {
        $raw = RawData::data('bytes');
        $raw->contentType = 'application/pdf';
        $res = new Response(response: $raw);

        self::assertSame('application/pdf', $res->getContentType());
    }
}
