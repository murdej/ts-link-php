<?php

declare(strict_types=1);

namespace Murdej\TsLinkPhp\Bridges\PhpLibs;

use Murdej\DTO\SmartDTO;
use Murdej\TsLinkPhp\Dto\ArgumentPreparerEvent;

/**
 * Converts an array argument into a Murdej\DTO\SmartDTO-derived object, based on the
 * method parameter's declared type. Register it, e.g. in neon:
 *
 * services:
 *     - Murdej\TsLinkPhp\Bridges\PhpLibs\SmartDtoArgumentPreparer
 *
 *     tsLink:
 *         setup:
 *             - addArgumentPreparer(@Murdej\TsLinkPhp\Bridges\PhpLibs\SmartDtoArgumentPreparer)
 */
class SmartDtoArgumentPreparer
{
    public function __invoke(ArgumentPreparerEvent $event): void
    {
        if (!is_array($event->value)) {
            return;
        }

        foreach ($event->argument->types as $type) {
            if (is_a($type, SmartDTO::class, true)) {
                $event->value = $type::fromArray($event->value);
                return;
            }
        }
    }
}
