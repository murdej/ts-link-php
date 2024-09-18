<?php

namespace Murdej\TsLinkPhp;

interface MiddlewareInterface
{
    function process(MiddlewareEvent $event);
}