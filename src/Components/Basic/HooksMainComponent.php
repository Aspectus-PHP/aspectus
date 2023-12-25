<?php

namespace Aspectus\Components\Basic;

use Aspectus\Aspectus;
use Aspectus\Message;
use Aspectus\Terminal\Xterm;

abstract class HooksMainComponent extends DefaultMainComponent
{
    /** @var Aspectus|null */
    protected $aspectus;

    public function __construct(
        protected Xterm $xterm
    ) {
        parent::__construct($this->xterm);
    }

    abstract public function view(): string;

    public function update(?Message $message): ?Message
    {
        if ($message === null) {
            return null;
        }

        return match(get_class($message)) {
            Message\KeyPress::class => $this->onKeyPress($message->key, $message->original),
            Message\MouseFocus::class => $this->onMouseFocus($message->focusIn),
            Message\MouseInput::class => $this->onMouseInput($message->event),
            Message\Tick::class => $this->onTick($message->identifier),
            Message\WindowResized::class => $this->onResize($message->newY, $message->newX),
            default => parent::update($message)
        };
    }

    protected function onKeyPress(string $key, string $original): ?Message
    {
        return null;
    }

    protected function onMouseFocus(bool $focusIn): ?Message
    {
        return null;
    }

    protected function onMouseInput(Xterm\Event\MouseInputEvent $event): ?Message
    {
        return null;
    }

    protected function onTick(string $identifier): ?Message
    {
        return null;
    }

    protected function onResize(int $newY, int $newX): ?Message
    {
        return null;
    }
}
