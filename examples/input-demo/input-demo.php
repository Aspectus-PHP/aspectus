<?php

use Aspectus\Aspectus;
use Aspectus\Component;
use Aspectus\Message;
use Aspectus\Terminal\TerminalDevice;
use Aspectus\Terminal\Xterm;
use Component\HexView;

require_once \dirname(__DIR__) . '/../vendor/autoload.php';

// manual imports
require_once 'Component/HexView.php';

exec(command: 'stty -echo -icanon min 1 time 0 < /dev/tty', result_code: $resultCode);

// ///////////////////////////////////////

class MouseInputViewer implements Component
{
    private string $received = '';

    private ?string $mode = 'keyboard';

    private ?string $encoding = 'normal';

    private bool $focusTracking = false;
    private bool $focused = true;

    /** @var Xterm\Event\MouseInputEvent|null */
    private $mouseEvent;

    /** @var HexView */
    private $hexView;

    public function __construct(
        private readonly Xterm $xterm
    ) {
        $this->hexView = new HexView('');
    }

    public function view(): string
    {
        if (!$this->focused) {
            return $this->xterm
                ->bgWhite()
                ->brightBlue()
                ->eraseAll()
                ->moveCursorTo(10, 25)
                ->write('Waiting on you to get back here..')
                ->getBuffered();
        }

        $mouseOutput = '';
        $binaryView = '';

        $received = $this->received;

        if ($this->mouseEvent) {
            $mouseOutput = "\t" . 'X:' . $this->mouseEvent->x . ' Y:' . $this->mouseEvent->y
                    . "\t"
                . ($this->mouseEvent->button1() ? ' B1 ' : '')
                . ($this->mouseEvent->button2() ? ' B2 ' : '')
                . ($this->mouseEvent->button3() ? ' B3 ' : '')
                . ($this->mouseEvent->released() ? ' REL ' : '')
                . ($this->mouseEvent->wheelUp() ? ' WUP ' : '')
                . ($this->mouseEvent->wheelDown() ? ' WDN ' : '')
                . ($this->mouseEvent->motion() ? ' MOT ' : '')
                . ($this->mouseEvent->shift() ? ' SHF ' : '')
                . ($this->mouseEvent->meta() ? ' META ' : '')
                . ($this->mouseEvent->ctrl() ? ' CTRL ' : '')
            ;

            $this->hexView->updateData($this->mouseEvent->data);

            $binaryChars = substr($this->mouseEvent->data, 3);
            $received = null;       // override so we dont output the esc seq again
            foreach (str_split($binaryChars, 1) as $char) {
                $binaryView .= decbin(ord($char)) . ' ';
            }
        }

        // todo: make a menu component

        return $this->xterm
            // we reset colors in the beginning
            ->brightYellow()
            ->bgBlue()
            ->eraseDisplay()

            ->moveCursorTo(1,1)
            ->write('Keyboard keys and Mouse tracking example')

            ->moveCursorTo(3, 10)
            ->write(' K. No mouse tracking (keyboard only)')
            ->moveCursorTo(5, 10)
            ->write(' 1. Normal VT200 tracking (1000)')
            ->moveCursorTo(7, 10)
            ->write(' 2. Any button event tracking (1003)')
            ->moveCursorTo(9, 10)
            ->write(' 3. (Encoding) SGR EXT tracking (1006)')
            ->moveCursorTo(11, 10)
            ->write(' 4. Mouse focus tracking (1004)')
//            ->moveCursorTo(11, 10)
//            ->write(' 4. X10 compatibility (9)')
//            ->moveCursorTo(13, 10)
//            ->write(' 4. X10 compatibility (9)')
            ->moveCursorTo(15, 10)
            ->write(' Q. Quit')

            ->moveCursorTo(20, 1)
            ->bgRed()
            ->white()
            ->eraseLineToRight()
            ->moveCursorTo(20, 1)
            ->write("Mode: $this->mode Encoding: $this->encoding " .  $mouseOutput)
            ->moveCursorTo(20, 50)
            ->write($received ? "\tRECV: $received" : '')

            ->white()
            ->bgBlue()
            ->moveCursorTo(21, 1)
            ->write('RAW:')
            ->moveCursorTo(22, 1)
            ->brightYellow()
            ->write($this->hexView->view())

            ->moveCursorTo(23, 1)
            ->white()
            ->write($binaryView)

            // get the string
            ->getBuffered()
            ;
    }

    public function update(Message $message): ?Message
    {
        // reset some of the state
        $this->mouseEvent = null;
        $this->received = '';

        return match ($message->type) {
            Message::INIT => $this->init($message['reference']),
            Message::KEY_PRESS => $this->handleKeyPress($message['key'], $message['original']),
            Message::MOUSE_INPUT => $this->handleMouseInput($message['event']),
            Message::MOUSE_FOCUS_IN => $this->handleFocus(true),
            Message::MOUSE_FOCUS_OUT => $this->handleFocus(false),
            Message::TERMINATE => $this->terminate($message['reference']),
            default => null,
        };
    }

    private function handleKeyPress(string $key, ?string $original = null): ?Message
    {
        return match (strtolower($key)) {
            'q' => Message::quit(),
            'k' => $this->changeMode('keyboard'),
            '1' => $this->changeMode('normal'),
            '2' => $this->changeMode('anyButton'),
            '3' => $this->changeEncoding('sgr'),
            '4' => $this->toggleFocusTracking(),
            default => $this->displayKey($key, $original),
        };
    }

    private function handleFocus(bool $focused): ?Message
    {
        $this->focused = $focused;
        return null;
    }

    private function toggleFocusTracking(): ?Message
    {
        $this->focusTracking = !$this->focusTracking;
        $this->xterm->write("\e[?1004" . ($this->focusTracking ? 'h' : 'l'))
            ->flush();
        return null;
    }

    private function changeMode(string $mode): ?Message
    {
        if ($mode === 'keyboard') {
            if ($this->mode === 'normal') {
                $this->xterm->unsetPrivateModeTrackMouseOnPressAndRelease()->flush();
            }

            if ($this->mode === 'anyButton') {
                $this->xterm->write("\e[?1003l")->flush();    // todo: missing from Xterm
            }

            $this->mode = 'keyboard';
        }

        if ($mode === 'normal') {
            $this->xterm->setPrivateModeTrackMouseOnPressAndRelease()->flush();
            $this->mode = 'normal';
            $this->changeEncoding('normal');
        }

        if ($mode === 'anyButton') {
            $this->xterm->setPrivateModeTrackMouseAll()->flush();
            $this->mode = 'anyButton';
            $this->changeEncoding('normal');
        }

        return null;
    }

    private function changeEncoding(string $encoding): ?Message
    {
        if ($encoding === 'sgr') {
            $this->encoding = 'sgr';

            $this->xterm->write("\e[?1006h")->flush();    // todo: missing from Xterm
        }

        if ($encoding === 'normal') {
            $this->encoding = 'normal';
            $this->xterm->write("\e[?1006l")->flush();    // todo: missing from Xterm
        }

        return null;
    }

    private function displayKey(string $key, ?string $original = null): ?Message
    {
        $this->received = $key;
        $this->hexView->updateData($original ?? $key);

        return null;
    }

    private function handleMouseInput(Xterm\Event\MouseInputEvent $event): ?Message
    {
        $this->mouseEvent = $event;
        $this->addReceived((string) $event);
        return null;
    }

    private function addReceived(string $sequence): void
    {
        $this->received = $sequence;
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
            ->reset()
            ->flush();

        return null;
    }
}

// setup
$xterm = new Xterm(new TerminalDevice());
$mouseViewer = new MouseInputViewer($xterm);

(new Aspectus($xterm, $mouseViewer, handleInput: true, handleMouseInput: true, handleMouseFocus: true))
    ->start();