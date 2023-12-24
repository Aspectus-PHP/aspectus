<?php

namespace Aspectus\Message;

use Aspectus\Message;

/**
 * When this message is sent, Aspectus will begin the termination process and
 * send a TERMINATE message.
 */
class Quit implements Message
{
    public function __construct(
        /**
         * An optional quit message
         */
        public readonly ?string $message = null,

        /**
         * An optional error code
         */
        public readonly ?int $errorCode = 0
    ) {
    }

    public function getType(): string
    {
        return self::QUIT;
    }
}
