<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use Nette\SmartObject;

class RawData
{
    use SmartObject;

    public string $contentType = "application/octet-stream";

    public mixed $data = null;

    public ?string $filePath = null;

    public static function filePath(string $filePath): self
    {
        $res = new self();
        $res->filePath = $filePath;

        return $res;
    }

    public static function data(mixed $data): self
    {
        $res = new self();
        $res->data = $data;

        return $res;
    }
}
