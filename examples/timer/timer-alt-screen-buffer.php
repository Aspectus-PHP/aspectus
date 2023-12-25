<?php

use Aspectus\Aspectus;
use Aspectus\Component;
use Aspectus\Message;
use Aspectus\Terminal\TerminalDevice;
use Aspectus\Terminal\Xterm;

require_once \dirname(__DIR__) . '/../vendor/autoload.php';

exec('stty -echo -icanon min 1 time 0 < /dev/tty');

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
        if ($message === null) {
            return null;
        }

        return match (get_class($message)) {
            Message\Init::class => $this->init($message->aspectus),
            Message\KeyPress::class => new Message\Quit(),
            Message\Tick::class => $this->tick(),
            Message\Terminate::class => $this->terminate($message->aspectus),
            default => null,
        };
    }

    public function tick(): ?Message
    {
        $this->ticksLeft -= 1;
        return $this->ticksLeft <= 0 ? new Message\Quit() : null;
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