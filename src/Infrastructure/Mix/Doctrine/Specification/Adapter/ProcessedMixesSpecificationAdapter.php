<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification\Adapter;

use App\Domain\Mix\Specification\ProcessedMixesSpecification;
use App\Infrastructure\Mix\Doctrine\Specification\Contract\DoctrineSpecificationInterface;

final class ProcessedMixesSpecificationAdapter implements DoctrineSpecificationInterface
{
    public function __construct(
        private readonly ProcessedMixesSpecification $specification,
    ) {}

    public function toDQL(string $alias): string
    {
        return sprintf(
            '%s.isProcessed = true OR %s.status = :processingStatus',
            $alias,
            $alias
        );
    }

    public function getParameters(): array
    {
        return [
            'processingStatus' => 'processing',
        ];
    }

    public function getJoins(): array
    {
        return [];
    }

    public static function key(): string
    {
        return ProcessedMixesSpecification::class;
    }
}
