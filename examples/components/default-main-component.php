<?php

use Aspectus\Aspectus;
use Aspectus\Components\Basic\DefaultMainComponent;
use Aspectus\Message;
use Aspectus\Terminal\TerminalDevice;
use Aspectus\Terminal\Xterm;

require_once \dirname(__DIR__) . '/../vendor/autoload.php';

exec(command: 'stty -echo -icanon min 1 time 0 < /dev/tty', result_code: $resultCode);

// ///////////////////////////////////////

class TestMainComponent extends DefaultMainComponent
{
    public function view(): string
    {
        return $this->xterm
            ->moveCursorTo(10,10)
            ->brightYellow()
            ->blink()
            ->write('Press ANY key to quit');
    }

    public function update(?Message $message): ?Message
    {
        // Here we want to handle KEY_PRESS only and the rest
        // we leave to the predefined (INIT/TERMINATE)
        // which we will override in this example just to show
        // how that would work (it is optional to override init/terminate).
        if ($message === null) {
            return parent::update($message);
        }

        return match(get_class($message)) {
            Message\KeyPress::class => new Message\Quit(),
            default => parent::update($message)
        };
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
}

// setup
$xterm = new Xterm(new TerminalDevice());

(new Aspectus($xterm, new TestMainComponent($xterm), handleInput: true))
    ->start();
