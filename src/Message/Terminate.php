<?php

namespace Aspectus\Message;

use Aspectus\Aspectus;
use Aspectus\Message;

/**
 * This message is sent when components needs to terminate, after some component
 * emits a QUIT message.
 */
class Terminate implements Message
{
    public function __construct(
        /**
         * Holds a reference to the instance of Aspectus that initiated the TERMINATE sequence.
         */
        public Aspectus $aspectus
    ) {
    }

    public function getType(): string
    {
        return self::TERMINATE;
    }
}
