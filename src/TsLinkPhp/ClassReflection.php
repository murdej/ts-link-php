<?php declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use ReflectionNamedType;
use ReflectionUnionType;

class ClassReflection
{
	/**
	 * @var ClassReflectionMethod[]
	 */
	public array $methods = [];

	public function parseClass(string $className)
	{
		$refl = new \ReflectionClass($className);
		foreach ($refl->getMethods() as $method)
		{
			$cms = $method->getAttributes(ClientMethod::class);
			if (!$cms) continue;
			$cmi = reset($cms)->newInstance();
			$crm = new ClassReflectionMethod();
			$crm->name = $method->name;
			$crm->params = [];
			foreach ($method->getParameters() as $parameter)
			{
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
				$crm->returnDataType = "ByteArray";
			} else if ($cmps) {
				/** @var ClientMethodType $cmpi */
				$cmpi = reset($cmps)->newInstance();
				$crm->returnDataType = $cmpi->type;
			} else {
				$crm->returnDataType = $method->getReturnType();
			}

			$this->methods[] = $crm;
		}

		// dump($this->methods);
	}
}

class ClassReflectionMethod
{
	public string $name;

	/**
	 * @var ClassReflectionMethodParam[]
	 */
	public array $params;

	public ReflectionNamedType|ReflectionUnionType|null|string $returnDataType;

	public bool $rawResult = false;

	public function getCallOpts() : array {
		return [
			"rawResult" => $this->rawResult
		];
	}
}

class ClassReflectionMethodParam
{
	public string $name;

	public bool $nullable;

	public ReflectionNamedType|ReflectionUnionType|null|string $dataType;

}
