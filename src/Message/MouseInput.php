<?php

namespace Aspectus\Message;

use Aspectus\Message;
use Aspectus\Terminal\Xterm\Event\MouseInputEvent;

class MouseInput implements Message
{
    public function __construct(
        public readonly MouseInputEvent $event
    ) {
    }

    public function getType(): string
    {
        return self::MOUSE_INPUT;
    }
}
