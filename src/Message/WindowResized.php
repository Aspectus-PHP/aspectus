<?php

namespace Aspectus\Message;

use Aspectus\Message;

class WindowResized implements Message
{
    public function __construct(
        public readonly int $newY,
        public readonly int $newX
    ) {
    }

    public function getType(): string
    {
        return self::RESIZED;
    }
}
