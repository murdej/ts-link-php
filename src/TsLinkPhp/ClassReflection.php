<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;

class ClassReflection
{
    /** @var ClassReflectionMethod[] */
    public array $methods = [];

    /** @var string[][] */
    public array $imports = [];

    public string $classShortName;

    public function parseClass(string $className)
    {
        $refl = new ReflectionClass($className);
        $this->classShortName = $refl->getShortName();
        foreach ($refl->getMethods() as $method) {
            $cms = $method->getAttributes(ClientMethod::class);
            if (!$cms) {
                continue;
            }
            $cmi = reset($cms)->newInstance();
            $crm = new ClassReflectionMethod();
            $crm->name = $method->name;
            $crm->params = [];
            foreach ($method->getParameters() as $parameter) {
                $crmp = new ClassReflectionMethodParam();
                $crmp->name = $parameter->name;
                $crmp->nullable = $parameter->allowsNull();
                $cmps = $parameter->getAttributes(ClientMethodType::class);
                if ($cmps) {
                    /** @var ClientMethodType $cmpi */
                    $cmpi = reset($cmps)->newInstance();
                    $crmp->dataType = $cmpi->type;
                } else {
                    $crmp->dataType = $parameter->getType();
                }

                $crm->params[$crmp->name] = $crmp;
            }

            $cmps = $method->getAttributes(ClientMethodType::class);
            $crm->rawResult = $cmi->rawResult;
            if ($cmi->rawResult) {
                $crm->returnDataType = 'ByteArray';
            } elseif ($cmps) {
                /** @var ClientMethodType $cmpi */
                $cmpi = reset($cmps)->newInstance();
                $crm->returnDataType = $cmpi->type;
            } else {
                $crm->returnDataType = $method->getReturnType();
            }

            $this->methods[] = $crm;

            $this->getImportsFromAttributes($method->getAttributes(ClientMethodImport::class));
        }

        $this->getImportsFromAttributes($refl->getAttributes(ClientMethodImport::class));
        /* foreach ($refl->getAttributes(ClientMethodImport::class) as $attribute) {
            /** @var ClientMethodImport $imp * /
            $imp = $attribute->newInstance();
            foreach ($imp->types as $type) {
                if (!isset($this->imports[$imp->from])) $this->imports[$imp->from] = [];
                $this->imports[$imp->from][] = $type;
            }
        } */
        // dump($this->methods);
    }

    private function getImportsFromAttributes(array $attributes)
    {
        foreach ($attributes as $attribute) {
            /** @var ClientMethodImport $imp */
            $imp = $attribute->newInstance();
            foreach ($imp->types as $type) {
                if (!isset($this->imports[$imp->from])) {
                    $this->imports[$imp->from] = [];
                }
                $this->imports[$imp->from][] = $type;
            }
        }
    }

    public function __construct(string|null $className = null)
    {
        if ($className) {
            $this->parseClass($className);
        }
    }
}

class ClassReflectionMethod
{
    public string $name;

    /** @var ClassReflectionMethodParam[] */
    public array $params;

    public ReflectionNamedType|ReflectionUnionType|null|string $returnDataType;

    public bool $rawResult = false;

    public bool $newResult = false;

    public function prepare()
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

class ClassReflectionMethodParam
{
    public string $name;

    public bool $nullable;

    public ReflectionNamedType|ReflectionUnionType|null|string $dataType;
}
