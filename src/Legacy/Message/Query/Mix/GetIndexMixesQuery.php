<?php

namespace App\Legacy\Message\Query\Mix;

class GetIndexMixesQuery
{
    public function __construct(public int $limit = 50)
    {
    }
}
