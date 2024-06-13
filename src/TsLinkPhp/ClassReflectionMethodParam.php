<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use ReflectionNamedType;
use ReflectionUnionType;

class ClassReflectionMethodParam
{
    public string $name;

    public bool $nullable;

    public ReflectionNamedType|ReflectionUnionType|null|string $dataType;

    public bool $useDefaultValue = false;

    public mixed $defaultValue;
}
