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

    public function jsonSerialize(): array
    {
        $res = ["status" => $this->exception ? "exception" : "ok"];

        if ($this->exception) {
            $res["exception"] = (string)$this->exception;
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
        return $raw->filePath ?? null;
    }

    public function getContentType(): string
    {
        $raw = $this->getRaw();
        return $raw->contentType ?? "application/json";
    }

    public function getTextContent(): ?string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }

    public function __toString(): string
    {
        return $this->getTextContent();
    }
}
