<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp;

use Attribute;

#[Attribute]
class ClientClass
{
    public function __construct(
        public array $implements = []
    ) { }

    public function getCallOpts(): array
    {
        return (array)$this;
    }
}
