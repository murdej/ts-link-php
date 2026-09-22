<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp\Dto;

class MethodArgument
{
    /**
     * @param string[] $types
     */
    public function __construct(
        public bool|null $nullable,
        public array $types,
    ) { }
}
