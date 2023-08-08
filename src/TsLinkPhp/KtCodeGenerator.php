<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use ReflectionNamedType;
use ReflectionUnionType;

class KtCodeGenerator
{
    public string $baseClassName = "BaseCL";

    public ?string $className = null;

    public ClassReflection $classReflection;

    /** @noinspection PsalmAdvanceCallableParamsInspection - PhpStorm bug with Closure */
    public function generateCode(): string
    {
        $res = "";
        $ln = function (string ...$lines) use (&$res) {
            foreach ($lines as $line) {
                $res .= $line . "\n";
            }
        };
        $ln("// GENERATED FILE, DO NOT EDIT");
        if ($this->baseClassRequire) {
            $ln("import { $this->baseClassName } from \"$this->baseClassRequire\";");
        }
        $ln("export class $this->className extends $this->baseClassName {");
        foreach ($this->classReflection->methods as $method) {
            $m = "\tpublic $method->name(";
            $f = true;
            foreach ($method->params as $param) {
                if ($f) {
                    $f = false;
                } else {
                    $m .= ", ";
                }
                $m .= $param->name . ": " . $this->phpTypeTpTS($param->dataType, $param->nullable);
            }
            $resType = $this->phpTypeTpTS($method->returnDataType, null);
            $m .= ") : Promise<" . $resType . "> { return this.callMethod(\"$method->name\", arguments, "
                . json_encode($method->getCallOpts())
                . "); }";
            $ln($m);
        }
        $ln("}");

        return $res;
    }

    public function phpTypeTpTS(ReflectionNamedType|ReflectionUnionType|string|null $dataType, ?bool $nullable): string
    {
        if (!$dataType) {
            return "any";
        }
        if ($dataType instanceof ReflectionUnionType) {
            return implode(
                "|",
                array_map(
                    fn(ReflectionNamedType $dt) => $this->phpTypeTpTS($dt, $nullable),
                    $dataType->getTypes()
                )
            );
        }
        $aliases = [
            "bool" => "boolean",
            "int" => "number",
            "integer" => "number",
            "double" => "boolean",
            "float" => "number",
            "string" => "string",
            "array" => "any",
            "object" => "any",
            "dict" => "Record<number|string|boolean, any>",
            "list" => "any[]",
        ];
        $isCustom = is_string($dataType);
        if ($dataType instanceof ReflectionNamedType) {
            $nullable = $dataType->allowsNull();
            $dataType = $dataType->getName();
        }
        if ($dataType && $dataType[0] == "?") {
            $dataType = substr($dataType, 1);
            $nullable = true;
        }
        if (isset($aliases[$dataType])) {
            $dataType = $aliases[$dataType];
        } else {
            if (!$isCustom) {
                $dataType = "any";
            }
        }
        if ($nullable) {
            $dataType = $dataType . "|null";
        }

        return $dataType;
    }
}
