<?php

namespace Aspectus;

interface Component extends Model
{
    public function view(): string;
}
