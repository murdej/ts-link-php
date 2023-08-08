<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use Exception;
use JsonSerializable;

class Response implements JsonSerializable
{
    public Exception|null $exception = null;

    public mixed $response = null;

    public ?array $context = null;

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        $res = ["status" => $this->exception ? "exception" : "ok"];
        if ($this->exception) {
            $res["exception"] = $this->exception->__toString();
        } else {
            $res["response"] = $this->response;
        }
        if ($this->context) {
            $res["context"] = $this->context;
        }
        return $res;
    }

    public function getRaw(): ?RawData
    {
        return $this->response instanceof RawData ? $this->response : null;
    }

    public function getFilePath(): ?string
    {
        $raw = $this->getRaw();
        return ($raw && $raw->filePath) ? $raw->filePath : null;
    }

    public function getContentType(): string
    {
        $raw = $this->getRaw();
        return $raw ? $raw->contentType : "application/json";
    }

    public function getTextContent(): ?string
    {
        return json_encode($this);
    }

    public function __toString(): string
    {
        return json_encode($this);
    }
}
