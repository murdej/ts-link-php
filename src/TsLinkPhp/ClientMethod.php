<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use Attribute;

#[Attribute]
class ClientMethod
{
    public bool $rawResult = false;

    public function __construct(bool $rawResult = false)
    {
        $this->rawResult = $rawResult;
    }

    public function getCallOpts()
    {
        return (array)$this;
    }
}
