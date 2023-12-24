<?php

namespace Aspectus\Components\Basic;

use Aspectus\Aspectus;
use Aspectus\Component;
use Aspectus\Message;
use Aspectus\Terminal\Xterm;

abstract class DefaultMainComponent implements Component
{
    /** @var Aspectus|null */
    protected $aspectus;

    public function __construct(
        protected Xterm $xterm
    ) {
    }

    abstract public function view(): string;

    public function update(?Message $message): ?Message
    {
        if ($message === null) {
            return null;
        }

        return match(get_class($message)) {
            Message\Init::class => $this->onInit($message->aspectus),
            Message\Terminate::class => $this->onTerminate($message->aspectus),
            default => null
        };
    }

    protected function onInit(Aspectus $aspectus): ?Message
    {
        $this->aspectus = $aspectus;
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
