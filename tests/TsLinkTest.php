<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp\Tests;

use Murdej\TsLinkPhp\Tests\Fixtures\DummyService;
use Murdej\TsLinkPhp\TsLink;
use PHPUnit\Framework\TestCase;

class TsLinkTest extends TestCase
{
    private function request(string $name, array $pars = [], array $context = [], array $uploadArgs = []): string
    {
        return json_encode([
            'name' => $name,
            'context' => $context,
            'pars' => $pars,
            'uploadArgs' => $uploadArgs,
        ]);
    }

    public function testSingleCallReturnsResponse(): void
    {
        $tl = new TsLink(new DummyService());
        $res = $tl->processRequest($this->request('getUser', [16]));

        $data = json_decode($res->getTextContent(), true);
        self::assertSame('ok', $data['status']);
        self::assertSame(['id' => 16, 'name' => 'John'], $data['response']);
        self::assertSame(['serverTouched' => true], $data['context']);
    }

    public function testEmptyRequestThrows(): void
    {
        $tl = new TsLink(new DummyService());

        $this->expectException(\InvalidArgumentException::class);
        $tl->processRequest('');
    }

    public function testMissingNameThrows(): void
    {
        $tl = new TsLink(new DummyService());

        $this->expectException(\InvalidArgumentException::class);
        $tl->processRequest(json_encode(['pars' => []]));
    }

    public function testMissingParsThrows(): void
    {
        $tl = new TsLink(new DummyService());

        $this->expectException(\InvalidArgumentException::class);
        $tl->processRequest(json_encode(['name' => 'getUser']));
    }

    public function testContextIsMergedIntoService(): void
    {
        $service = new DummyService();
        $tl = new TsLink($service);
        $tl->processRequest($this->request('getUser', [1], ['token' => 'abc']));

        self::assertSame('abc', $service->context['token']);
    }

    public function testDateTimeStringParamIsConverted(): void
    {
        $tl = new TsLink(new DummyService());
        $res = $tl->processRequest($this->request('echoDate', ['2024-05-01']));

        $data = json_decode($res->getTextContent(), true);
        self::assertSame('2024-05-01', $data['response']);
    }

    public function testSendExceptionTrueRethrows(): void
    {
        $tl = new TsLink(new DummyService());
        $tl->sendException = true;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('kaboom');
        $tl->processRequest($this->request('fail'));
    }

    public function testSendExceptionFalseReturnsExceptionStatus(): void
    {
        $tl = new TsLink(new DummyService());
        $tl->sendException = false;
        $res = $tl->processRequest($this->request('fail'));

        $data = json_decode($res->getTextContent(), true);
        self::assertSame('exception', $data['status']);
        self::assertStringContainsString('kaboom', $data['exception']);
    }

    public function testOnErrorIsCalledOnFailure(): void
    {
        $tl = new TsLink(new DummyService());
        $tl->sendException = false;
        $seen = null;
        $tl->onError = function (string $raw, \Throwable $e) use (&$seen) {
            $seen = $e;
        };
        $tl->processRequest($this->request('fail'));

        self::assertInstanceOf(\RuntimeException::class, $seen);
    }

    public function testSingleFileUpload(): void
    {
        $tl = new TsLink(new DummyService());
        $res = $tl->processRequest(
            $this->request('upload', [0], [], [0]),
            [0 => 'FILE_CONTENTS']
        );

        $data = json_decode($res->getTextContent(), true);
        self::assertSame('FILE_CONTENTS', $data['response']);
    }

    public function testMultiFileUpload(): void
    {
        $tl = new TsLink(new DummyService());
        $res = $tl->processRequest(
            $this->request('uploadMany', [[0, 1]], [], [0]),
            [0 => 'A', 1 => 'B']
        );

        $data = json_decode($res->getTextContent(), true);
        self::assertSame(['A', 'B'], $data['response']);
    }

    public function testBatchSuccessReturnsAllResultsAndRunsBatchCallHook(): void
    {
        $service = new DummyService();
        $tl = new TsLink($service);
        $tl->sendException = false;

        $res = $tl->processRequest(json_encode([
            'batch' => [
                ['id' => 1, 'name' => 'getUser', 'context' => [], 'pars' => [16], 'uploadArgs' => []],
                ['id' => 2, 'name' => 'getCart', 'context' => [], 'pars' => [], 'uploadArgs' => []],
            ],
        ]));

        $data = json_decode($res->getTextContent(), true);
        self::assertCount(2, $data['batch']);
        self::assertSame(1, $data['batch'][0]['id']);
        self::assertSame('ok', $data['batch'][0]['status']);
        self::assertSame(['id' => 16, 'name' => 'John'], $data['batch'][0]['response']);
        self::assertSame(2, $data['batch'][1]['id']);
        self::assertSame(['items' => []], $data['batch'][1]['response']);
        self::assertSame(['serverTouched' => true], $data['context']);

        self::assertSame(['1/2:1:getUser', '2/2:2:getCart'], $service->batchLog);
    }

    public function testBatchIsolatesFailingItemWhenSendExceptionFalse(): void
    {
        $tl = new TsLink(new DummyService());
        $tl->sendException = false;

        $res = $tl->processRequest(json_encode([
            'batch' => [
                ['id' => 1, 'name' => 'getUser', 'context' => [], 'pars' => [1], 'uploadArgs' => []],
                ['id' => 2, 'name' => 'fail', 'context' => [], 'pars' => [], 'uploadArgs' => []],
                ['id' => 3, 'name' => 'getCart', 'context' => [], 'pars' => [], 'uploadArgs' => []],
            ],
        ]));

        $data = json_decode($res->getTextContent(), true);
        self::assertSame('ok', $data['batch'][0]['status']);
        self::assertSame('exception', $data['batch'][1]['status']);
        self::assertStringContainsString('kaboom', $data['batch'][1]['exception']);
        self::assertSame('ok', $data['batch'][2]['status']);
    }

    public function testBatchAbortsEntirelyWhenSendExceptionTrue(): void
    {
        $tl = new TsLink(new DummyService());
        $tl->sendException = true;

        $this->expectException(\RuntimeException::class);
        $tl->processRequest(json_encode([
            'batch' => [
                ['id' => 1, 'name' => 'getUser', 'context' => [], 'pars' => [1], 'uploadArgs' => []],
                ['id' => 2, 'name' => 'fail', 'context' => [], 'pars' => [], 'uploadArgs' => []],
            ],
        ]));
    }

    public function testBatchFileUploadsAreNamespacedById(): void
    {
        $tl = new TsLink(new DummyService());
        $tl->sendException = false;

        $res = $tl->processRequest(
            json_encode([
                'batch' => [
                    ['id' => 1, 'name' => 'upload', 'context' => [], 'pars' => [0], 'uploadArgs' => [0]],
                    ['id' => 2, 'name' => 'upload', 'context' => [], 'pars' => [0], 'uploadArgs' => [0]],
                ],
            ]),
            ['1_0' => 'FILE_A', '2_0' => 'FILE_B']
        );

        $data = json_decode($res->getTextContent(), true);
        self::assertSame('FILE_A', $data['batch'][0]['response']);
        self::assertSame('FILE_B', $data['batch'][1]['response']);
    }

    public function testBatchPerItemContextIsMerged(): void
    {
        $service = new DummyService();
        $tl = new TsLink($service);
        $tl->sendException = false;

        $tl->processRequest(json_encode([
            'batch' => [
                ['id' => 1, 'name' => 'getUser', 'context' => ['token' => 'first'], 'pars' => [1], 'uploadArgs' => []],
                ['id' => 2, 'name' => 'getCart', 'context' => ['token' => 'second'], 'pars' => [], 'uploadArgs' => []],
            ],
        ]));

        self::assertSame('second', $service->context['token']);
    }
}
