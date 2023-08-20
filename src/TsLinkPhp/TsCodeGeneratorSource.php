<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

class TsCodeGeneratorSource
{
    public string $className;

    public ClassReflection $classReflection;

    public function __construct(
        ClassReflection|string $classDef,
        public ?string $endpoint = null,
        ?string $className = null
    ) {
        $this->classReflection = is_string($classDef)
            ? new ClassReflection($classDef)
            : $classDef;
        $this->className = $className ?? $this->classReflection->classShortName;
    }
}
