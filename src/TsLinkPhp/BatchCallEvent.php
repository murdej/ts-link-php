<?php

namespace Murdej\TsLinkPhp;

class BatchCallEvent
{
    public function __construct(
        public TsLink $tsLink,
        public Request $request,
        public mixed $id,
        public int $currentNum,
        public int $count,
    )
    {
    }
}