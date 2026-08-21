<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use Closure;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

class TsLink
{
    public object $service;

    public bool $sendException = true;

    public Closure|null $onError = null;

    public function __construct(object $service)
    {
        $this->service = $service;
    }

    /** @var MiddlewareInterface[] */
    public array $middlewares = [];

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function processRequest(string $src, array $files = []): Response
    {
        $res = new Response();
        try {
            if (!$src) throw new \InvalidArgumentException('Request is empty.');
            $srcStruct = json_decode($src, true, 512, JSON_THROW_ON_ERROR);

            $res = isset($srcStruct['batch'])
                ? $this->processBatch($srcStruct['batch'], $files)
                : $this->processItem($srcStruct, $files);

            if ($this->service instanceof IContextUpdate) {
                $res->context = $this->service->getContextUpdates();
            }
        } catch (Throwable $exception) {
            if ($this->onError) {
                ($this->onError)($src, $exception);
            }
            if ($this->sendException) {
                throw $exception;
            }
            $res->exception = $exception;
        }

        return $res;
    }

    protected function processItem(array $itemStruct, array $files, ?Closure $onRequestReady = null): Response
    {
        $res = new Response();
        $methodName = $itemStruct["name"] ?? null;
        $context = $itemStruct["context"] ?? [];
        if ($methodName === null) throw new \InvalidArgumentException('Missing method name (field name) in payload.');
        if (!isset($itemStruct["pars"])) throw new \InvalidArgumentException('Missing method arguments (field pars) in payload.');
        if ($context && isset($this->service->context)) {
            foreach ($context as $k => $v) {
                if (is_array($this->service->context)) {
                    $this->service->context[$k] = $v;
                } else {
                    $this->service->context->$k = $v;
                }
            }
        }
        $pars = [];
        $methodArguments = $this->getMethodArguments($this->service, $methodName);
        foreach ($itemStruct["pars"] as $i => $param) {
            if (in_array($i, ($itemStruct["uploadArgs"] ?? []))) {
                if (is_array($param)) {
                    $newParam = [];
                    foreach ($param as $fileId) {
                        $newParam[] = $files[$fileId];
                    }
                    $pars[] = $newParam;
                } else {
                    $pars[] = $files[$param];
                }
            } else {
                $arg = $methodArguments[$i] ?? null;
                if (in_array('DateTime', $arg['types']) && !in_array('string', $arg['types']) && is_string($param)) {
                    $param = new \DateTime($param);
                }
                $pars[] = $param;
            }
        }
        $req = new Request(
            $methodName,
            $pars,
        );

        if ($onRequestReady) {
            $onRequestReady($req);
        }

        $event = new MiddlewareEvent(
            $this,
            $req,
            $res,
            $this->service,
            $methodArguments,
        );
        $this->currentMiddleware = 0;
        $event->next();

        return $event->response;
    }

    protected function processBatch(array $batchItems, array $files): Response
    {
        $count = count($batchItems);
        $results = [];
        foreach ($batchItems as $currentNum => $item) {
            $id = $item['id'] ?? throw new \InvalidArgumentException('Missing id in batch item.');
            $itemFiles = $this->extractBatchItemFiles($files, $id);

            $onRequestReady = function (Request $req) use ($id, $currentNum, $count) {
                if ($this->service instanceof IBatchCall) {
                    $this->service->batchCall(new BatchCallEvent($this, $req, $id, $currentNum + 1, $count));
                }
            };

            try {
                $itemRes = $this->processItem($item, $itemFiles, $onRequestReady);
            } catch (Throwable $exception) {
                if ($this->onError) {
                    ($this->onError)(json_encode($item), $exception);
                }
                if ($this->sendException) {
                    throw $exception;
                }
                $itemRes = new Response(exception: $exception);
            }

            $results[] = ['id' => $id, 'response' => $itemRes];
        }

        $res = new Response();
        $res->batch = $results;
        return $res;
    }

    protected function extractBatchItemFiles(array $files, mixed $id): array
    {
        $prefix = "{$id}_";
        $itemFiles = [];
        foreach ($files as $key => $file) {
            if (str_starts_with((string)$key, $prefix)) {
                $itemFiles[substr((string)$key, strlen($prefix))] = $file;
            }
        }
        return $itemFiles;
    }

    protected function getMethodArguments(object $service, string $methodName): array
    {
        $methodReflection = new \ReflectionMethod($service, $methodName);

        return array_map(
            function (ReflectionParameter $parameter) use ($methodReflection) {
                $type = $parameter->getType();
                return [
                    'nullable' => $type?->allowsNull(),
                    'types' => $type
                        ? array_map(
                            fn($ref) => $ref->getName(),
                            $type instanceof ReflectionNamedType
                                ? [ $type ]
                                : $type->getTypes()
                        )
                        : [],
                ];
            },
            $methodReflection->getParameters(),
        );
    }

    protected int $currentMiddleware = 0;

    public function callNextMiddleware(MiddlewareEvent $event)
    {
        $this->currentMiddleware++;
        if ($this->currentMiddleware <= count($this->middlewares)) {
            $this->middlewares[$this->currentMiddleware - 1]->process($event);
        } else {
            $event->response->response = $this->service->{$event->request->methodName}(...$event->request->data);
        }
    }
}
