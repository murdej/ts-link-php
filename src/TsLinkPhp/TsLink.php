<?php declare(strict_types=1);

namespace Murdej\TsLinkPhp;

class TsLink
{
	public ClassReflection $classReflection;

	public object $service;

	public bool $sendException = true;

	public function __construct(object $service)
	{
		$this->service = $service;
	}

	public function processRequest(string $src) : Response
	{
		$res = new Response();
		try {
			$srcStruct = json_decode($src, true);
			$methodName = $srcStruct["name"];
			$context = $srcStruct["context"];
			if ($context && isset($this->service->context)) {
				foreach ($context as $k => $v)
					if (is_array($this->service->context))
						$this->service->context[$k] = $v;
					else
						$this->service->context->$k = $v;
			}
			$pars = $srcStruct["pars"];
			$res->response = $this->service->$methodName(...$pars);
            if ($this->service instanceof IContextUpdate)
                $res->context = $this->service->getContextUpdates();
		} catch (\Exception $exception) {
			if ($this->sendException) throw $exception;
			$res->exception = $exception;
		}

		return $res;
		/* $jsonS = json_encode($res, JSON_PRETTY_PRINT);

		return $jsonS; */
	}
}
