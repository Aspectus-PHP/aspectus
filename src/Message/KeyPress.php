<?php

namespace Aspectus\Message;

use Aspectus\Message;

class KeyPress implements Message
{
    public function __construct(
        public readonly string $key,
        public readonly string $original
    ) {
    }

    public function getType(): string
    {
        return self::KEY_PRESS;
    }
}
