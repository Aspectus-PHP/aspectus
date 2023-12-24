<?php

namespace Aspectus;

interface Message
{
    /** @var string */
    final public const INIT = 'innit';

    /** @var string */
    final public const KEY_PRESS = 'keyPress';

    /** @var string */
    final public const MOUSE_INPUT = 'mouse';

    /** @var string */
    final public const MOUSE_FOCUS = 'mouseFocus';

    /** @var string */
    public const TICK = 'tick';

    /** @var string */
    public const RESIZED = 'resized';

    /** @var string */
    public const TERMINATE = 'terminate';

    /** @var string */
    public const QUIT = 'quit';

    public function getType(): string;
}
