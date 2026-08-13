<?php

declare(strict_types=1);

namespace App\Domain\Mix\ValueObject;

final class TotalSize
{
    private FileSize $original;
    private FileSize $stream;
    private FileSize $peaks;

    public function __construct(FileSize $original, FileSize $stream, FileSize $peaks)
    {
        $this->original = $original;
        $this->stream = $stream;
        $this->peaks = $peaks;
    }

    public static function zero(): self
    {
        return new self(
            new FileSize(0),
            new FileSize(0),
            new FileSize(0)
        );
    }

    public function getOriginal(): FileSize
    {
        return $this->original;
    }

    public function getStream(): FileSize
    {
        return $this->stream;
    }

    public function getPeaks(): FileSize
    {
        return $this->peaks;
    }

    public function total(): FileSize
    {
        return $this->original
            ->add($this->stream)
            ->add($this->peaks);
    }

    public function toBytes(): int
    {
        return $this->total()->toBytes();
    }

    public function toMegabytes(): float
    {
        return $this->total()->toMegabytes();
    }

    public function format(): string
    {
        return $this->total()->format('MB');
    }

    public function equals(self $other): bool
    {
        return $this->original->equals($other->original)
            && $this->stream->equals($other->stream)
            && $this->peaks->equals($other->peaks);
    }
}
