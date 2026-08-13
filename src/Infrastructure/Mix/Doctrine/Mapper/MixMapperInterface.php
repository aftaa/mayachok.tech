<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Mapper;

use App\Domain\Mix\Entity\Mix;
use App\Infrastructure\Doctrine\Entity\Mix as MixDoctrine;

interface MixMapperInterface
{
    /**
     * Конвертировать Domain Mix → Doctrine Mix
     */
    public function toDoctrine(Mix $mix): MixDoctrine;

    /**
     * Конвертировать Doctrine Mix → Domain Mix
     */
    public function toDomain(MixDoctrine $entity): Mix;
}
