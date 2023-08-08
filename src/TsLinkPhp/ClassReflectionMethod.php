<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use ReflectionNamedType;
use ReflectionUnionType;

class ClassReflectionMethod
{
    public string $name;

    /** @var ClassReflectionMethodParam[] */
    public array $params;

    public ReflectionNamedType|ReflectionUnionType|null|string $returnDataType;

    public bool $rawResult = false;

    public bool $newResult = false;

    public function prepare(): void
    {
        if (is_string($this->returnDataType) && str_starts_with($this->returnDataType, 'new ')) {
            $this->returnDataType = substr($this->returnDataType, 4);
            $this->newResult = true;
        }
    }

    public function getCallOpts(): array
    {
        return [
            'rawResult' => $this->rawResult,
        ];
    }
}
