<?php

namespace Aspectus;

use Aspectus\Terminal\Xterm\Event\MouseInputEvent;

/**
 * @template-implements \ArrayAccess<string, mixed>
 */
final class Message implements \Stringable, \ArrayAccess
{
    /** @var string */
    public const INIT = 'innit';

    /** @var string */
    public const KEY_PRESS = 'keyPress';

    /** @var string */
    public const MOUSE_INPUT = 'mouse';

    /** @var string */
    public const MOUSE_FOCUS_IN = 'mouseFocusIn';

    /** @var string */
    public const MOUSE_FOCUS_OUT = 'mouseFocusOut';

    /** @var string */
    public const TICK = 'tick';

    /** @var string */
    public const TERMINATE = 'terminate';

    /** @var string */
    public const QUIT = 'quit';

    public function __construct(
        public readonly string $type,
        private array $data = []
    ) {
    }

    public function is(string $type): bool
    {
        return $this->type === $type;
    }

    public static function init(Aspectus $aspectus): self
    {
        return new self(self::INIT, ['reference' => $aspectus]);
    }

    // todo: probably need something better than just a string like that. Xterm pkg should provide
    // better handling for characters
    public static function keyPress(string $key, ?string $originalData = null): self
    {
        return new self(self::KEY_PRESS, ['key' => $key, 'original' => $originalData]);
    }

    public static function mouseInput(MouseInputEvent $event): self
    {
        return new self(self::MOUSE_INPUT, ['event' => $event]);
    }

    public static function mouseFocusIn(): self
    {
        return new self(self::MOUSE_FOCUS_IN);
    }

    public static function mouseFocusOut(): self
    {
        return new self(self::MOUSE_FOCUS_OUT);
    }

    public static function tick(string $identifier): self
    {
        return new self(self::TICK, ['id' => $identifier]);
    }

    public static function quit(): self
    {
        return new self(self::QUIT);
    }

    public static function terminate(Aspectus $aspectus): self
    {
        return new self(self::TERMINATE, ['reference' => $aspectus]);
    }

    public function __toString(): string
    {
        return $this->type;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset !== null) {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}
