<?php

namespace Aspectus;

interface Renderer
{
    /**
     * Renders a Component
     */
    public function render(Component $component): void;
}