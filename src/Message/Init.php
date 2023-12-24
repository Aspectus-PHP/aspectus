<?php

namespace Aspectus\Message;

use Aspectus\Aspectus;
use Aspectus\Message;

class Init implements Message
{
    public function __construct(
        /**
         * Init message holds a reference to the Aspectus instance
         * that emitted the message.
         */
        public Aspectus $aspectus
    ) {
    }

    public function getType(): string
    {
        return self::INIT;
    }
}
