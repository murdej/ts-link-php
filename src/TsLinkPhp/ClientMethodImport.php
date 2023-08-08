<?php

namespace Murdej\TsLinkPhp;

use Attribute;

#[Attribute]
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
