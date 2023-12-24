<?php

namespace Aspectus\Message;

use Aspectus\Message;

class MouseFocus implements Message
{
    public function __construct(
        /**
         * A value of `false` indicates that the mouse has focused out
         * of the terminal window.
         */
        public bool $focusIn
    ) {

    }

    public function getType(): string
    {
        return self::MOUSE_FOCUS;
    }
}
