<?php

namespace Component;

use Aspectus\Component;
use Aspectus\Message;

class Menu implements Component
{
    public function __construct(
        private array $options
    ) {
    }

    public function view(): string
    {
        foreach ($this->options as $option) {

        }
    }

    public function update(Message $message): ?Message
    {
        // TODO: Implement update() method.
    }
}