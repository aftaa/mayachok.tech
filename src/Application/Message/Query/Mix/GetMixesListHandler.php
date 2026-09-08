<?php

namespace App\Application\Message\Query\Mix;

use App\Application\Message\Query\QueryHandlerInterface;
use App\Domain\Mix\Entity\Mix;
use App\Domain\Mix\Repository\MixRepositoryInterface;
use App\Domain\Mix\Specification\PublicMixesSpecification;

class GetMixesListHandler implements QueryHandlerInterface
{
    private MixRepositoryInterface $mixRepository;

    public function __construct(
        MixRepositoryInterface $mixRepository,
    ) {
        $this->mixRepository = $mixRepository;
    }

    /**
     * @return Mix[]
     */
    public function __invoke(GetMixesListQuery $query): array
    {
        return $this->mixRepository->findMatches(new PublicMixesSpecification());
    }
}
