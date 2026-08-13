<?php

namespace App\Legacy\Message\Query\Mix;

final readonly class GetMyMixesQuery
{
    public function __construct(
        public int $userId,
    ) {

    }
}
