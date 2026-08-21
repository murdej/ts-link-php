<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp\Tests\Fixtures;

use Murdej\TsLinkPhp\BatchCallEvent;
use Murdej\TsLinkPhp\IBatchCall;
use Murdej\TsLinkPhp\IContextUpdate;

class DummyService implements IBatchCall, IContextUpdate
{
    public array $context = [];

    /** @var string[] */
    public array $batchLog = [];

    public function getUser(int $id): array
    {
        return ['id' => $id, 'name' => 'John'];
    }

    public function getCart(): array
    {
        return ['items' => []];
    }

    public function echoDate(\DateTime $date): string
    {
        return $date->format('Y-m-d');
    }

    public function fail(): void
    {
        throw new \RuntimeException('kaboom');
    }

    public function upload(mixed $file): mixed
    {
        return $file;
    }

    public function uploadMany(array $files): array
    {
        return $files;
    }

    public function batchCall(BatchCallEvent $event): void
    {
        $this->batchLog[] = "{$event->currentNum}/{$event->count}:{$event->id}:{$event->request->methodName}";
    }

    public function getContextUpdates(): array
    {
        return ['serverTouched' => true];
    }
}
