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
    
    public ?string $importFrom;

    public function __construct(ReflectionNamedType|ReflectionUnionType|null|string $type, ?bool $nullable = null, ?string $importFrom = null)
    {
        $this->type = $type;
        $this->nullable = $nullable;
        $this->importFrom = $importFrom;
    }
}
