<?php

namespace Aspectus\Internal;

// I think the class Aspectus IS the actual Runtime and this class should go away
// however i keep it because could mark it internal to separate the front facing interface
// from the internal runtime stuff. To review that later.
use Aspectus\Component;
use Aspectus\Message;

/**
 * @internal
 * @psalm-internal Aspectus
 */
final class Runtime implements Component
{
    public function __construct(
        private readonly Component $component,
    ) {
    }

    public function init(): ?Message
    {
        return $this->component->update(null);
    }

    public function update(?Message $message): ?Message
    {
        // should we deal with the subscriptions here?

        return $this->component->update($message);
    }

    public function view(): string
    {
        return $this->component->view();
    }
}