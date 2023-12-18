<?php

namespace Aspectus\Components\Basic;

use Aspectus\Aspectus;
use Aspectus\Component;
use Aspectus\Message;
use Aspectus\Terminal\Xterm;

class BasicMainComponent implements Component
{
    public function __construct(
        protected Xterm $xterm
    ) {
    }

    public function view(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function update(?Message $message): ?Message
    {
        return match($message->type) {
            Message::INIT => $this->onInit($message['reference']),
            Message::TERMINATE => $this->onTerminate($message['reference']),
            default => null
        };
    }

    protected function onInit(Aspectus $aspectus): ?Message
    {
        $this->xterm
            ->saveCursorAndEnterAlternateScreenBuffer()
            ->hideCursor()
            ->flush();
        return null;
    }

    protected function onTerminate(Aspectus $aspectus): ?Message
    {
        $this->xterm
            ->restoreCursorAndEnterNormalScreenBuffer()
            ->showCursor()
            ->flush();
        return null;
    }
}