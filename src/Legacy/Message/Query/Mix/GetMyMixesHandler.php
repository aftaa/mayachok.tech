<?php

namespace App\Legacy\Message\Query\Mix;

use App\Legacy\Repository\MixRepository;
use App\Legacy\Specification\UserMixesSpecification;

final readonly class GetMyMixesHandler
{
    public function __construct(
        public MixRepository $repository,
    ) {

    }

    public function __invoke(GetMyMixesQuery $query): array
    {
        return $this->repository->findMatches(new UserMixesSpecification($query->userId));
    }
}
