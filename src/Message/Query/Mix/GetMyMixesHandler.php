<?php

namespace App\Message\Query\Mix;

use App\Repository\MixRepository;
use App\Specification\UserMixesSpecification;

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
