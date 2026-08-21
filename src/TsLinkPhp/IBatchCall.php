<?php

namespace Murdej\TsLinkPhp;

interface IBatchCall
{
    public function batchCall(BatchCallEvent $event): void;
}