<?php

// This example requires 80x24 (or bigger)

use Aspectus\Aspectus;
use Aspectus\Component;
use Aspectus\Message;
use Aspectus\Terminal\TerminalDevice;
use Aspectus\Terminal\Xterm;
use Component\HexView;
use Component\ProgressBar;

require_once \dirname(__DIR__) . '/../vendor/autoload.php';

// manual imports
require_once 'StyleBuilder.php';
require_once 'Component/HexView.php';
require_once 'Component/ProgressBar.php';

exec(command: 'stty -echo -icanon min 1 time 0 < /dev/tty', result_code: $resultCode);

// ///////////////////////////////////////

// some data
$data = <<<DATA
This is just a sample string with some random data, just to show case hex-editor functionality
built with all these packages as a text user interface. Text or terminal user interface, I wouldn't
really know.
DATA;

// model
class HexEditorComponent implements Component
{
    public const UP = 1;
    public const DOWN = 2;
    public const LEFT = 4;
    public const RIGHT = 8;

    private bool $insertMode = false;

    private int $cursorX = 1;
    private int $cursorY = 1;

    private int $pointerX = 1;
    private int $pointerY = 1;

    private ?int $singleLineToRender = null;

    private $progressBarStyle;

    public function __construct(
        private readonly HexView $hexView,
        private readonly ProgressBar $progressBar,
        private readonly Xterm $xterm
    ) {
        $this->progressBarStyle = (new StyleBuilder())
            ->fgi(14)
            ->bgi(12)
//            ->bold()
            ->write('%s')
            ->normal()
            ->buildCallable()
        ;
    }

    /**
     * Receives a Message and updates the model accordingly.
     *
     * Receiving `null` here, by convention means that you have a last chance to initialize something. All
     * subsequent calls to update() will contain a message.
     */
    public function update(Message $message): ?Message
    {
        return match ($message->type) {
            Message::KEY_PRESS => $this->handleKeyPress($message['key']),
            default => null,
        };
    }

    private function handleKeyPress(string $key): ?Message
    {
        $a = 1;
        return !$this->insertMode
            ? match (strtolower($key)) {
                'q', '<esc>'                => Message::quit(),
                'h', 'j', "\x0a", 'k', 'l'  => $this->handleUserMoved($this->mapDirection($key)),
                'i'                         => $this->handleInsertModeEnabled(),
                default                     => null
            }
            : match ($key) {
                '<ESC>'                      => $this->handleInsertModeDisabled(),
                default                     => $this->handleUserInput($key),
            };
    }

    private function mapDirection(string $key): int
    {
        return match($key) {
            'h'         => self::LEFT,
            'j', "\x0a" => self::DOWN,
            'k'         => self::UP,
            'l'         => self::RIGHT,
        };
    }

    public function handleInsertModeDisabled(): ?Message
    {
        $this->insertMode = false;
        $this->singleLineToRender = null;
        // now its only cursor movement

        $this->hexView->renderFull();
        return null;
    }

    public function handleInsertModeEnabled(): ?Message
    {
        $this->insertMode = true;
        return null;
    }

    // could it be public too ?
    private function handleUserMoved(int $direction): ?Message
    {
        $bytesPerLine = $this->hexView->getBytesPerLine();
        $totalLines = $this->hexView->getTotalLines();

        if ($direction == self::LEFT) {
            $this->pointerX = max(0, $this->pointerX - 1);
        } elseif ($direction == self::DOWN) {
            $this->pointerY = min($totalLines, $this->pointerY + 1);
        } elseif ($direction == self::UP) {
            $this->pointerY = max(1, $this->pointerY - 1);
        } elseif ($direction == self::RIGHT) {
            $this->pointerX = min($bytesPerLine - 1, $this->pointerX + 1);
        }

        $this->updateCursorFromPointer($bytesPerLine);
        $this->progressBar->setProgress((int) (($this->cursorY / $totalLines) * 100));

        return null;
    }

    private function handleUserInput(string $input): ?Message
    {
        // assumes we are in insert mode? can it be public ?
        $bytesPerLine = $this->hexView->getBytesPerLine();
        $totalLines = $this->hexView->getTotalLines();

        $offset = (($this->pointerY - 1) * $bytesPerLine) + $this->pointerX;

        $data = $this->hexView->getData();
        $data[$offset] = $input;
        $this->hexView->updateData($data);

        // renderer
        $this->singleLineToRender = $this->pointerY - 1;
        $this->hexView->renderLineOnly($this->singleLineToRender);

        // update pointer
        $offset += 1;
        $this->pointerX = $offset % $bytesPerLine;
        $this->pointerY = intval($offset / $bytesPerLine) + 1;

        $this->updateCursorFromPointer($bytesPerLine);
        $this->progressBar->setProgress((int) (($this->cursorY / $totalLines) * 100));

        return null;
    }

    private function updateCursorFromPointer($bytesPerLine): void
    {
        $this->cursorX = $bytesPerLine * 2 + $bytesPerLine + 8 + $this->pointerX + 1;
        $this->cursorY = $this->pointerY;
    }

    // maybe here we need the xterm and its a render() (component) rather than a view() (model) ?
    public function view(): string
    {
        if ($this->singleLineToRender !== null) {
            return $this->partialView();
        }

        return $this->fullView();
    }

    /**
     * We render fully every component
     */
    private function fullView(): string
    {
        $progressBarView = ($this->progressBarStyle) ($this->progressBar->view());

        $this->xterm
            ->eraseDisplay()
            ->moveCursorTo(1,1)
            ->write($this->hexView->view())
            ->moveCursorTo(22, 1)
            ->write($progressBarView);

        if ($this->insertMode) {
            $this->xterm
                ->moveCursorTo(21,1)
                ->write('-- INSERT MODE --');
        }

        return
            $this->xterm
                ->moveCursorTo($this->cursorY, $this->cursorX)
                ->getBuffered()
            ;
    }

    /**
     * We render only a single line and the progress bar
     */
    private function partialView(): string
    {
        $progressBarView = ($this->progressBarStyle) ($this->progressBar->view());
        $y = $this->cursorY - ($this->cursorY - $this->singleLineToRender - 1);

        return $this->xterm
            ->moveCursorTo($y, 1)
            ->write($this->hexView->view())
            ->moveCursorTo(22, 1)
                // here we could erase the line but we know progressBar view is fixed length
            ->write($progressBarView)
            ->moveCursorTo($this->cursorY, $this->cursorX)
            ->getBuffered();
    }
}

// setup

$xterm = new Xterm(new TerminalDevice());
$component = new HexEditorComponent(
    new HexView($data),
    new ProgressBar(50),
    $xterm
);

(new Aspectus($xterm, $component, handleInput: true))
    ->start();
