<?php

use Aspectus\Aspectus;
use Aspectus\Component;
use Aspectus\Message;
use Aspectus\Terminal\TerminalDevice;
use Aspectus\Terminal\Xterm;

require_once \dirname(__DIR__) . '/../vendor/autoload.php';

exec(command: 'stty -echo -icanon min 1 time 0 < /dev/tty', result_code: $resultCode);

// ///////////////////////////////////////

class Timer implements Component
{
    private int $ticksLeft = 100;
    private string $message = "This message will self-destruct in %s seconds.";

    private string $instructions = 'Press ANY key to cancel!';

    public function view(): string
    {
        return Xterm::eraseDisplay()
            . Xterm::moveCursorTo(5,5)
            . sprintf($this->message, $this->ticksLeft)
            . Xterm::moveCursorTo(8, 10)
            . $this->instructions
            ;
    }

    public function update(Message $message): ?Message
    {
        return match ($message->type) {
            Message::KEY_PRESS => Message::quit(),
            Message::TICK => $this->tick(),
            default => null,
        };
    }

    public function tick(): ?Message
    {
        $this->ticksLeft -= 1;
        return $this->ticksLeft <= 0 ? Message::quit() : null;
    }
}

// setup
$timer = new Timer();

(new Aspectus(
    new Xterm(new TerminalDevice()),
    $timer
))
    // identifier (2nd argument) does not matter here
    ->repeat(1, 'per-second')
    ->start();