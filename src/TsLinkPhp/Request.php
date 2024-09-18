<?php

namespace Murdej\TsLinkPhp;

class Request
{
    public function __construct(
        public string $methodName,
        public array $data,
    ) { }
}