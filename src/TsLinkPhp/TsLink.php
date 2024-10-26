<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use Closure;
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
            $srcStruct = json_decode($src, true, 512, JSON_THROW_ON_ERROR);
            $methodName = $srcStruct["name"];
            $context = $srcStruct["context"];
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
            foreach ($srcStruct["pars"] as $i => $param) {
                if (in_array($i, ($srcStruct["uploadArgs"] ?? []))) {
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
                    $pars[] = $param;
                }
            }
            $req = new Request(
                $methodName,
                $pars,
            );
            $event = new MiddlewareEvent($this, $req, $res, $this->service);
            $this->currentMiddleware = 0;

            // $event->response->response = $this->service->{$event->request->methodName}(...$event->request->data);
            $event->next();

            $res = $event->response;
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
