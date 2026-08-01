<?php

namespace App\Service;

use App\Factory\RedisFactory;
use Redis;

class AbstractRedisService
{
    protected Redis $redis;

    public function __construct(string $redisDsn)
    {
        $this->redis = new RedisFactory($redisDsn)->getRedis();
    }

    protected function key(string $key): string
    {
        return $key;
    }
}
