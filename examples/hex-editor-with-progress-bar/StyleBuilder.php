<?php

class StyleBuilder
{
    private const CSI_PLACEHOLDER = '%CSI%';
    private const TERMINATOR = 'm';

    /** @var string[] */
    private array $modifiers = [];

    public function build(bool $s8mode = false, bool $reset = true): string
    {
        $csi = $s8mode ? "\x9b" : "\x1b[";

        $sequenceState = false;

        $seq = '';
        foreach ($this->modifiers as $entry) {
            if (array_key_exists('data', $entry)) {
                if ($sequenceState) {
                    $seq .= 'm';
                }
                $sequenceState = false;
                $seq .= $entry['data'];
                continue;
            }

            if (!$sequenceState) {
                $sequenceState = true;
                $seq .= $csi . $entry['sequence'];
                continue;
            }

            $seq .= ';' . $entry['sequence'];
        }

        if ($sequenceState) {
            $seq .= 'm';
        }

        if ($reset) {
            $this->reset();
        }

        return $seq;
    }

    /**
     * Build a callable that will accept placeholders as its arguments.
     * Useful when you use `printf` placeholders in between sequences;
     * To add in between sequences use the `write()` method
     *
     * @param bool $s8mode
     * @param bool $reset
     * @return callable
     */
    public function buildCallable(bool $s8mode = false, bool $reset = true): callable
    {
        $sequence = $this->build($s8mode, $reset);

        return function () use (&$sequence) {
            return sprintf($sequence, ...func_get_args());
        };
    }

    public function reset(): self
    {
        $this->modifiers = [];
        return $this;
    }

    public function write(string $data): self
    {
        $this->modifiers[] = ['data' => $data];
        return $this;
    }

    protected function add(string $data): self
    {
        $this->modifiers[] = ['sequence' => $data];
        return $this;
    }

    public function normal(): self
    {
        return $this->add("0");
    }

    public function bold(): self
    {
        return $this->add("1");
    }

    public function faint(): self
    {
        return $this->add("2");
    }

    public function italic(): self
    {
        return $this->add("3");
    }

    public function underline(): self
    {
        return $this->add("4");
    }

    public function blink(): self
    {
        return $this->add("5");
    }

    public function inverse(): self
    {
        return $this->add("7");
    }

    public function invisible(): self
    {
        return $this->add("8");
    }

    public function strikethrough(): self
    {
        return $this->add("9");
    }

    public function doubleUnderline(): self
    {
        return $this->add("21");
    }

    public function notBoldNotFaint(): self
    {
        return $this->add("22");
    }

    public function notItalic(): self
    {
        return $this->add("23");
    }

    public function notUnderline(): self
    {
        return $this->add("24");
    }

    public function notBlink(): self
    {
        return $this->add("25");
    }

    public function steady(): self
    {
        return $this->add("25");
    }

    public function notInverse(): self
    {
        return $this->add("27");
    }

    public function positive(): self
    {
        return $this->add("27");
    }

    public function notInvisible(): self
    {
        return $this->add("28");
    }

    public function visible(): self
    {
        return $this->add("28");
    }

    public function notStrikethrough(): self
    {
        return $this->add("29");
    }

    public function black(): self
    {
        return $this->add("30");
    }

    public function red(): self
    {
        return $this->add("31");
    }

    public function green(): self
    {
        return $this->add("32");
    }

    public function yellow(): self
    {
        return $this->add("33");
    }

    public function blue(): self
    {
        return $this->add("34");
    }

    public function magenta(): self
    {
        return $this->add("35");
    }

    public function cyan(): self
    {
        return $this->add("36");
    }

    public function white(): self
    {
        return $this->add("37");
    }

    public function default(): self
    {
        return $this->add("39");
    }

    public function bgBlack(): self
    {
        return $this->add("40");
    }

    public function bgRed(): self
    {
        return $this->add("41");
    }

    public function bgGreen(): self
    {
        return $this->add("42");
    }

    public function bgYellow(): self
    {
        return $this->add("43");
    }

    public function bgBlue(): self
    {
        return $this->add("44");
    }

    public function bgMagenta(): self
    {
        return $this->add("45");
    }

    public function bgCyan(): self
    {
        return $this->add("46");
    }

    public function bgWhite(): self
    {
        return $this->add("47");
    }

    public function bgDefault(): self
    {
        return $this->add("49");
    }

    public function brightBlack(): self
    {
        return $this->add("90");
    }

    public function brightRed(): self
    {
        return $this->add("91");
    }

    public function brightGreen(): self
    {
        return $this->add("92");
    }

    public function brightYellow(): self
    {
        return $this->add("93");
    }

    public function brightBlue(): self
    {
        return $this->add("94");
    }

    public function brightMagenta(): self
    {
        return $this->add("95");
    }

    public function brightCyan(): self
    {
        return $this->add("96");
    }

    public function brightWhite(): self
    {
        return $this->add("97");
    }

    public function brightBgBlack(): self
    {
        return $this->add("100");
    }

    public function brightBgRed(): self
    {
        return $this->add("101");
    }

    public function brightBgGreen(): self
    {
        return $this->add("102");
    }

    public function brightBgYellow(): self
    {
        return $this->add("103");
    }

    public function brightBgBlue(): self
    {
        return $this->add("104");
    }

    public function brightBgMagenta(): self
    {
        return $this->add("105");
    }

    public function brightBgCyan(): self
    {
        return $this->add("106");
    }

    public function brightBgWhite(): self
    {
        return $this->add("107");
    }

    public function fgi(int $color): self
    {
        return $this->add("38;5;$color");
    }

    public function bgi(int $color): self
    {
        return $this->add("48;5;$color");
    }

    public function rgb(int $red, int $green, int $blue): self
    {
        return $this->add("38;2;$red;$green;$blue");
    }

    public function bgRgb(int $red, int $green, int $blue): self
    {
        return $this->add("48;2;$red;$green;$blue");
    }
}
