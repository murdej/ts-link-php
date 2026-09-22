<?php

namespace Murdej\TsLinkPhp\Dto;

class ArgumentPreparerEvent
{
    /**
     * @var bool If a callback sets this property to false, subsequent callbacks will no longer be called.
     */
    public bool $callNext = true;
    public function __construct(
        /**
         * @var mixed This property contains the value of the argument. The callback can convert it.
         */
        public mixed $value,
        public MethodArgument $argument,
    ) { }
}