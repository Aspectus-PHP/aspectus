<?php

namespace Aspectus;

interface View
{
    /**
     * Turns a Model into an actual string
     *
     * Note that if you find yourself mutating the model here, you are probably doing something wrong.
     */
    public function view(Model $model): string;
}
