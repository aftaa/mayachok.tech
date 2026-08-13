<?php

namespace App\Application\Query\Mix;

use App\Domain\Mix\Entity\Mix;
use App\Domain\Mix\Repository\MixRepositoryInterface;
use App\Domain\Mix\Specification\PublicMixesSpecification;
use App\Shared\Bus\Buses;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: Buses::Query->value)]
class GitMixesListHandler
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
