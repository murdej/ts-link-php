<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE|Attribute::TARGET_METHOD|Attribute::TARGET_CLASS)]
class ClientMethodImport
{
    public string $from;

    public array $types;

    public function __construct(string $from, array $types)
    {
        $this->from = $from;
        $this->types = $types;
    }
}
