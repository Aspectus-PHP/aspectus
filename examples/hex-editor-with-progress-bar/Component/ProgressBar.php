<?php

namespace Component;

use Aspectus\Component;
use Aspectus\Message;

class ProgressBar implements Component
{
    public const DEFAULT_EMPTY_BLOCK = '.';
    public const DEFAULT_COMPLETED_BLOCK = '▒';     // for now

    private float $amount = 0.0;

    public function __construct(
        private readonly int $width,
        private string $emptyBlock = self::DEFAULT_EMPTY_BLOCK,
        private string $completedBlock = self::DEFAULT_COMPLETED_BLOCK,
    ) {
    }

    public function subscribes(): array
    {
        return [];
    }

    public function reset(): void
    {
        $this->amount = 0;
    }

    public function advance(int $amount): void
    {
        $this->amount = min(100, $this->amount + $amount);
    }

    public function setProgress(int $progress): void
    {
        $this->amount = min(100, $progress);
    }

    public function update(?Message $message): ?Message
    {
        return null;
    }

    // public function getOutput(Model $model): string          this gives us a bit of an issue because Model HAS to be in a specific way now
    public function view(): string
    {
        $completedBlocks = (int) ($this->width * $this->amount / 100);
        $emptyBlocks = $this->width - $completedBlocks;

        $emptyBlocksOutput = $emptyBlocks >= 0 ? str_repeat($this->emptyBlock, $emptyBlocks) : '';
        $completedBlocksOutput = $completedBlocks >= 0 ?  str_repeat($this->completedBlock, $completedBlocks) : '';

        return $completedBlocksOutput . $emptyBlocksOutput;
    }
}
