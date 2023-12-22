<?php

use Aspectus\Aspectus;
use Aspectus\Component;
use Aspectus\Message;
use Aspectus\Terminal\TerminalDevice;
use Aspectus\Terminal\Xterm;

require_once \dirname(__DIR__) . '/../vendor/autoload.php';

exec(command: 'stty -echo -icanon min 1 time 0 < /dev/tty', result_code: $resultCode);

// ///////////////////////////////////////

/**
 * DefaultMainComponent does by default handle the alt screen buffer
 */
class Timer implements Component
{
    private int $ticksLeft = 10;
    private string $message = "This message will self-destruct in %s seconds.";

    private string $instructions = 'Press ANY key to cancel!';

    public function __construct(
        private readonly Xterm $xterm
    ) {
    }

    public function view(): string
    {
        return $this->xterm
            // we reset colors in the beginning
            ->default()
            ->bgDefault()
            ->normal()
            ->eraseDisplay()

            // position for first message
            ->moveCursorTo(5,5)

            // set style
            ->red()
            ->bgWhite()

            // write the message
            ->write(sprintf($this->message, $this->ticksLeft))

            // position for second message
            ->moveCursorTo(8, 10)

            // set style
            ->bold()
            ->brightYellow()
            ->bgBlue()

            // return instructions
            ->write($this->instructions)

            // get the string
            ->getBuffered()
            ;
    }

    public function update(?Message $message): ?Message
    {
        return match ($message->type) {
            Message::INIT => $this->init($message['reference']),
            Message::KEY_PRESS => Message::quit(),
            Message::TICK => $this->tick(),
            Message::TERMINATE => $this->terminate($message['reference']),
            default => null,
        };
    }

    public function tick(): ?Message
    {
        $this->ticksLeft -= 1;
        return $this->ticksLeft <= 0 ? Message::quit() : null;
    }

    public function init(Aspectus $aspectus): ?Message
    {
        $aspectus->xterm
            ->setPrivateModeSaveCursorAndEnterAlternateScreenBuffer()
            ->hideCursor()
            ->flush();

        return null;
    }

    public function terminate(Aspectus $aspectus): ?Message
    {
        $aspectus->xterm
            ->setPrivateModeRestoreCursorAndEnterNormalScreenBuffer()
            ->showCursor()
            ->flush();

        return null;
    }
}

// setup
$xterm = new Xterm(new TerminalDevice());
$timer = new Timer($xterm);

(new Aspectus($xterm, $timer, handleInput: true))
    // identifier (2nd argument) does not matter here
    ->repeat(1, 'per-second')
    ->start();