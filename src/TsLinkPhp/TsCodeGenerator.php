<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use ReflectionNamedType;
use ReflectionUnionType;

class TsCodeGenerator
{
    public ?string $baseClassRequire = null;

    public string $baseClassName = "BaseCL";

    public bool $exportSingleton = true;

    public static ?string $importClassPrefix = '@';

    /**
     * @var string|array<string,string>
     */
    public string|array $baseImportPath = [
        '@npm:' => '',
        '' => "../",
    ];

    /** @deprecated Use add method */
    public ?string $url = null;

    /** @deprecated Use add method */
    public ?ClassReflection $classReflection = null;

    /** @var TsCodeGeneratorSource[] */
    protected array $sources = [];

    /** @var bool Use JS modules, add export to generated classes */
    public bool $useJsModules = true;

    /** @var string Export format js/ts */
    public string $format = "ts";

    /**
     * @param string|ClassReflection $classDef Class name or reflection
     * @param string|null $endpoint Endpoint, optional
     * @param string|null $className Name of typescript class or null for detection
     *
     * Add class to generation
     */
    public function add(string|ClassReflection $classDef, string|null $endpoint = null, ?string $className = null): void
    {
        $this->sources[] = new TsCodeGeneratorSource($classDef, $endpoint, $className);
    }

    /**
     * @noinspection PsalmAdvanceCallableParamsInspection - PhpStorm bug with Closure
     *
     * Generates typescript code.
     */
    public function generateCode(): string
    {
        /** @var TsCodeGeneratorSource[] $sources */
        $sources = array_merge(
            $this->classReflection ? [new ClassReflection($this->classReflection)] : [],
            $this->sources
        );

        $res = "";
        $ln = static function (string ...$lines) use (&$res) {
            foreach ($lines as $line) {
                $res .= $line . "\n";
            }
        };
        $ln("// GENERATED FILE, DO NOT EDIT");

        if ($this->baseClassRequire) {
            $ln("import { $this->baseClassName } from \"$this->baseClassRequire\";");
        }

        $imports = [];
        foreach ($sources as $source) {
            foreach ($source->classReflection->imports as $file => $symbols) {
                $imports[$file] = array_merge(
                    $imports[$file] ?? [],
                    $symbols
                );
            }
        }

        foreach ($imports as $file => $classes) {
            $pathPrefix = "";
            if (is_string($this->baseImportPath)) {
                $pathPrefix = $this->baseImportPath;
            } else {
                foreach ($this->baseImportPath as $prefix => $pp) {
                    if (str_starts_with($file, $prefix)) {
                        $pathPrefix = $pp;
                        $file = substr($file, strlen($prefix));
                        break;
                    }
                }
            }
            // if ($this->importClassPrefix) {
                $classes = array_map(
                    fn($typeName) => (static::$importClassPrefix && str_starts_with($typeName, static::$importClassPrefix))
                        ? substr($typeName, 1)
                        : 'type ' . $typeName,
                    $classes
                );
            // }
            $ln(
                "import { "
                . implode(', ', array_unique($classes))
                . " } from \"" . $pathPrefix . $file . "\";"
            );
        }

        $isTs = $this->format === "ts";

        if (!$this->baseClassRequire) {
            $bsrc = file_get_contents(__DIR__ . '/BaseCL.' . ($isTs ? 'ts' : 'js'));
            if (!$this->useJsModules) {
                $bsrc = str_replace('export class BaseCL', 'class BaseCL', $bsrc);
            }
            $ln($bsrc);
        }

        foreach ($sources as $source) {
            $ln(
                ($this->useJsModules ? "export " : "")
                . "class $source->className extends $this->baseClassName "
                . ($source->classReflection->classImplements
                    ? 'implements ' . implode(', ', array_unique($source->classReflection->classImplements))
                    : '')
                . "{"
            );
            if ($source->endpoint) {
                $ln(
                    "\t" . 'constructor(url'
                    . ($isTs ? ":string" : '')
                    . '="' . $source->endpoint . '") { super(url); }'
                );
            }
            foreach ($source->classReflection->methods as $method) {
                $method->prepare();
                $m = "\t" . ($isTs ? "public " : '') . "$method->name(";
                $f = true;
                foreach ($method->params as $param) {
                    if ($f) {
                        $f = false;
                    } else {
                        $m .= ", ";
                    }
                    $m .= $param->name . ($isTs ? ": " . $this->phpTypeTpTS($param->dataType, $param->nullable) : "");
                    if ($param->useDefaultValue) $m .= " = " . json_encode($param->defaultValue);
                }
                $resType = $this->phpTypeTpTS($method->returnDataType, null);
                $m .= ") "
                    . ($isTs ? ": Promise<" . $resType . ">" : "")
                    . " { return this.callMethod(\"$method->name\", arguments, "
                    . json_encode($method->getCallOpts(), JSON_THROW_ON_ERROR)
                    . ($method->newResult ? ', ' . $method->returnDataType : '')
                    . "); }";
                $ln($m);
            }
            $ln("}");

            if ($this->exportSingleton) {
                $ln(
                    ($this->useJsModules ? "export " : "")
                    . "const " . lcfirst($source->className) . "= new $source->className();"
                );
            }
        }

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
            "double" => "number",
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

        if ($dataType && $dataType[0] === "?") {
            $dataType = substr($dataType, 1);
            $nullable = true;
        }
        if (str_starts_with($dataType, '@')) {
            $dataType = substr($dataType, 1);
        }

        if (isset($aliases[$dataType])) {
            $dataType = $aliases[$dataType];
        } elseif (!$isCustom) {
            $dataType = "any";
        }

        if ($nullable) {
            $dataType .= "|null";
        }

        return $dataType;
    }
}
