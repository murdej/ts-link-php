<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use ReflectionClass;

class ClassReflection
{
    /** @var ClassReflectionMethod[] */
    public array $methods = [];

    /** @var string[][] */
    public array $imports = [];

    /** @var string[] */
    public array $classImplements = [];

    public string $classShortName;

    public function __construct(?string $className = null)
    {
        if ($className) {
            $this->parseClass($className);
        }
    }

    public function parseClass(string $className): void
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
                if ($parameter->isDefaultValueAvailable()) {
                    $crmp->defaultValue = $parameter->getDefaultValue();
                    $crmp->useDefaultValue = true;
                }
                // if ($parameter->isDefaultValueConstant()) dump($parameter->getDefaultValueConstantName());

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

        $refl2 = $refl;
        while ($refl2) {
            $this->getImportsFromAttributes($refl2->getAttributes(ClientMethodImport::class));
            $classAttributes = $refl2->getAttributes(ClientClass::class);
            if ($classAttributes) {
                /** @var ClientClass $classAttribute */
                $classAttribute = reset($classAttributes)->newInstance();
                $this->classImplements = array_merge($this->classImplements, $classAttribute->implements);
            }
            $refl2 = $refl2->getParentClass();
        }
    }

    private function getImportsFromAttributes(array $attributes): void
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
}
