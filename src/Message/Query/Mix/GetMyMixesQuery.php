<?php

namespace App\Message\Query\Mix;

final readonly class GetMyMixesQuery
{
    public function __construct(
        public int $userId,
    ) {

    }
}
