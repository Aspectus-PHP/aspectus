<?php

use Aspectus\Aspectus;
use Aspectus\Components\Basic\HooksMainComponent;
use Aspectus\Message;
use Aspectus\Terminal\TerminalDevice;
use Aspectus\Terminal\Xterm;

require_once \dirname(__DIR__) . '/../vendor/autoload.php';

exec('stty -echo -icanon min 1 time 0 < /dev/tty');

// ///////////////////////////////////////

class HooksTestMainComponent extends HooksMainComponent
{
    public function view(): string
    {
        return (string) $this->xterm
            ->moveCursorTo(10,10)
            ->brightYellow()
            ->blink()
            ->write('Press ANY key to quit');
    }

    protected function onInit(Aspectus $aspectus): ?Message
    {
        // Here we want after everything has started correctly to
        // add something in the screen. To do that we run parent's
        // init first and store the message. Receiving NULL from init
        // means that everything is OK to proceed.

        $message = parent::onInit($aspectus);

        $this->xterm
            ->moveCursorTo(1, 1)
            ->bgBlue()
            ->yellow()
            ->write('Hi!')
            ->normal()
            ->flush();

        return $message;
    }

    protected function onTerminate(Aspectus $aspectus): ?Message
    {
        // Here we want as an example to add something before the normal
        // termination sequence
        $this->xterm
            ->normal()
            ->green()
            ->write('Terminating!')
            ->flush();

        // Then we call the predefined termination sequence (closing alt. buffer etc)
        $terminateMessage = parent::onTerminate($aspectus);

        // We output something more
        $this->xterm
            ->write("Bye!\n")
            ->flush();

        // And return the message to aspectus
        return $terminateMessage;
    }

    protected function onKeyPress(string $key, string $original): ?Message
    {
        return new Message\Quit();
    }
}

// setup
$xterm = new Xterm(new TerminalDevice());

(new Aspectus($xterm, new HooksTestMainComponent($xterm), handleInput: true))
    ->start();
