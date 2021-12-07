<?php

namespace Murdej\TsLinkPhp;

use Nette\SmartObject;

class RawData
{
	use SmartObject;

	public string $contentType = "application/octet-stream";

	public mixed $data = null;

	public ?string $filePath = null;

	public static function filePath(string $filePath) : RawData
	{
		$res = new RawData();
		$res->filePath = $filePath;

		return $res;
	}

	public static function data(mixed $data) : RawData
	{
		$res = new RawData();
		$res->data = $data;

		return $res;
	}

}