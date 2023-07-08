<?php

namespace Aspectus;

use Aspectus\Internal\Runtime;
use Aspectus\Render\StandardRenderer;
use Aspectus\Terminal\Event\InputEvent;
use Aspectus\Terminal\Xterm;
use Aspectus\Terminal\Xterm\Event\MouseFocusEvent;
use Aspectus\Terminal\Xterm\Event\MouseInputEvent;
use Aspectus\Terminal\Xterm\Event\SpecialKeyEvent;
use Revolt\EventLoop;
use Revolt\EventLoop\UnsupportedFeatureException;

final class Aspectus
{
    /** @var string[] */
    private array $callbackIds = [];

    /** @var array<string, float> */
    private array $tickers = [];

    private readonly Runtime $runtime;

    private readonly Renderer $renderer;

    private bool $subscribedToInput = false;

    private bool $subscribedToMouseInput = false;

    private bool $subscribedToMouseFocus= false;

    /** @throws UnsupportedFeatureException */
    public function __construct(
        public readonly Xterm $xterm,
        Component $component,
        ?Renderer $renderer = null,
        bool $handleInput = false,              // rename to handleKeyboardInput ?
        bool $handleMouseInput = false,
        bool $handleMouseFocus = false,
    ) {
        $this->runtime = new Runtime($component);
        $this->renderer = $renderer ?? new StandardRenderer($this->xterm);

        if ($handleInput) {
            $this->enableInputHandling();
        }

        if ($handleMouseInput) {
            $this->enableMouseInputHandling();
        }

        if ($handleMouseFocus) {
            $this->enableMouseFocusHandling();
        }

        if (\defined('SIGINT')) {
            // maybe we can do something depending on the driver, if ext-pcntl is missing
            $this->callbackIds[] = EventLoop::onSignal(\SIGINT, $this->shutdown(...));
        }
    }

    /**
     * Enables Aspectus to handle input and dispatch messages
     *
     * todo: support disableInput (somehow) ?
     */
    public function enableInputHandling(): self
    {
        if ($this->subscribedToInput) {
            return $this;
        }

        $this->xterm->subscribe(InputEvent::class, $this->handleInputEvents(...));
        $this->xterm->subscribe(SpecialKeyEvent::class, $this->handleSpecialInputEvents(...));
        $this->subscribedToInput = true;
        return $this;
    }

    /**
     * Enables Aspectus to handle mouse input and dispatch messages
     *
     * todo: support disableMouseInput (somehow) ?
     */
    public function enableMouseInputHandling(): self
    {
        if ($this->subscribedToMouseInput) {
            return $this;
        }

        $this->xterm->subscribe(MouseInputEvent::class, $this->handleMouseInputEvents(...));
        $this->subscribedToMouseInput = true;
        return $this;
    }

    /**
     * Enables Aspectus to handle mouse focus and dispatch messages accordingly
     *
     * todo: support disableMouseInput (somehow) ?
     */
    public function enableMouseFocusHandling(): self
    {
        if ($this->subscribedToMouseFocus) {
            return $this;
        }

        $this->xterm->subscribe(MouseFocusEvent::class, $this->handleMouseFocusEvents(...));
        $this->subscribedToMouseFocus = true;
        return $this;
    }

    /**
     * Boots up Aspectus according to the configuration so far and initiates the update cycle
     */
    public function start(): void
    {
        $newMessage = $this->runtime->update(Message::init($this));
        // todo: should we handle the new message?
        $this->renderer->render($this->runtime);
        EventLoop::run();
    }

    public function shutdown(): void
    {
        foreach ($this->callbackIds as $callbackId) {
            EventLoop::unreference($callbackId);
            EventLoop::cancel($callbackId);
        }

        $this->xterm->close();
    }

    private function handleInputEvents(InputEvent $event): void
    {
        // todo: naive here with str_split, would that work for multibyte?
        // this has problems with function keys as well, and possibly other things
        // that have "escaped" being treated as special (all those start with 0x1b probably)
        foreach (str_split($event->data, 1) as $char) {
            $this->updateCycle(Message::keyPress($char));
        }
    }

    private function handleSpecialInputEvents(SpecialKeyEvent $event): void
    {
        $this->updateCycle(Message::keyPress($event->data, $event->originalData));
    }

    private function handleMouseInputEvents(MouseInputEvent $event): void
    {
        $this->updateCycle(Message::mouseInput($event));
    }

    private function handleMouseFocusEvents(MouseFocusEvent $event): void
    {
        $this->updateCycle($event->focus() ? Message::mouseFocusIn() : Message::mouseFocusOut());
    }

    private function updateCycle(Message $message): void
    {
        while ($newMessage = $this->runtime->update($message)) {
            $this->handleAspectusMessages($newMessage);
            $this->renderer->render($this->runtime);
            $message = $newMessage;
        }

        $this->renderer->render($this->runtime);
    }

    public function handleAspectusMessages(Message $message): void
    {
        // $message maybe contains Aspectus related messages, like Quit, or for example maybe ResizeTriggered.. whatever
        // we care about anyway. Or maybe we have another one that says SkipRender which will simply skip the renderer

        if ($message->type == Message::QUIT) {
            $this->runtime->update(Message::terminate($this));
            // todo: no more message processing ? what if they want to say goodbye?

            $this->shutdown();
        }
    }

    public function repeat(float $interval, string $identifier): self
    {
        if (isset($this->tickers[$identifier])) {
            // todo: for now, but maybe we should re-set ?
            if ($this->tickers[$identifier] != $interval) {
                throw new \Exception('Tick identifier `' . $identifier . '` has already been registered with a different interval.');
            }

            return $this;
        }

        $this->callbackIds[$identifier] = EventLoop::repeat(
            $interval,
            function ($callbackId) use (&$identifier) {
                static $message;
                if (!$message) {
                    $message = Message::tick($identifier);
                }

                if ($newMessage = $this->runtime->update($message)) {
                    $this->handleAspectusMessages($newMessage);
                }
                $this->renderer->render($this->runtime);
            }
        );

        $this->tickers[$identifier] = $interval;

        return $this;
    }

    public function cancelRepeat(string $identifier): self
    {
        if (isset($this->tickers[$identifier])) {
            $callbackId = $this->callbackIds[$identifier];

            EventLoop::unreference($callbackId);
            EventLoop::cancel($callbackId);

            unset($this->callbackIds[$identifier], $this->tickers[$identifier]);
        }

        return $this;
    }

    // todo: find xterm drivers/parameters?
    // todo: stty ? or in terminal device?
}
