<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

class TsCodeGeneratorSource
{
    public string $className;

    public ClassReflection $classReflection;

    /**
     * @param ClassReflection|string $classDef
     * @param string|null $endpoint
     */
    public function __construct(
        $classDef,
        public ?string $endpoint = null,
        ?string $className = null
    ) {
        $this->classReflection = is_string($classDef)
            ? new ClassReflection($classDef)
            : $classDef;
        $this->className = ($className === null)
            ? $this->classReflection->classShortName
            : $className;
    }
}
