<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use Attribute;
use ReflectionNamedType;
use ReflectionUnionType;

#[Attribute]
class ClientMethodType
{
    public ReflectionNamedType|ReflectionUnionType|null|string $type;

    public ?bool $nullable;

    public function __construct(ReflectionNamedType|ReflectionUnionType|null|string $type, ?bool $nullable = null)
    {
        $this->type = $type;

        $this->nullable = $nullable;
    }
}
