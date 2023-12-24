<?php

namespace Aspectus\Message;

use Aspectus\Message;

class Tick implements Message
{
    public function __construct(
        /**
         * The tick identifier
         */
        public readonly string $identifier
    ) {

    }

    public function getType(): string
    {
        return self::TICK;
    }
}
