<?php

namespace Component;

use Aspectus\Component;
use Aspectus\Message;

class HexView implements Component
{
    private array $lines = [];          // maybe $lines and the analysis should be in the update logic? isnt it view specific?
    private int $totalLines = 0;
    private ?int $lineOnly = null;

    public function __construct(private string $data, private int $bytesPerLine = 16)
    {
        $this->analyzeData();
    }

    public function updateData(string $newData): void
    {
        $this->data = $newData;
        $this->analyzeData();
    }

    public function renderLineOnly(int $line): void
    {
        // check if not exists
        $this->lineOnly = $line;
    }

    public function renderFull(): void
    {
        $this->lineOnly = null;
    }

    public function update(?Message $message): ?Message
    {
        return null;
    }

    public function view(): string
    {
        if ($this->lineOnly != null) {
            $data = $this->lines[$this->lineOnly];
            return $this->hexData($data) . "\t\t" . $this->safeString($data);
        }

        $string = '';
        foreach ($this->lines as $line) {
            $string .= $this->hexData($line) . "\t\t" . $this->safeString($line);
            $string .= "\n";    // this output needs this now
        }

        return $string;
    }

    public function getTotalLines(): int
    {
        return $this->totalLines;
    }

    public function getBytesPerLine(): int
    {
        return $this->bytesPerLine;
    }

    public function getData(): string
    {
        return $this->data;
    }

    private function hexData(string $data): string
    {
        return implode(' ', str_split(bin2hex($data), 2));
    }

    private function safeString(string $data): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/', '.', $data);
    }

    private function analyzeData(): void
    {
        $this->lines = str_split($this->data, $this->bytesPerLine);
        $this->totalLines = count($this->lines);
    }
}