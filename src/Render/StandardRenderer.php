<?php

namespace Aspectus\Render;

use Aspectus\Component;
use Aspectus\Renderer;
use Aspectus\Terminal\Xterm;

class StandardRenderer implements Renderer
{
    public function __construct(
        private Xterm $xterm
    ) {
    }

    public function render(Component $component): void
    {
        $this->xterm->write($component->view($component))
            ->flush();  // thats self-referential here :D visitor maybe?
    }
}