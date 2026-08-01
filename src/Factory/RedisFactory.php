<?php

namespace App\Factory;

use Redis;

class RedisFactory
{
    private Redis $redis;

    public function __construct(string $redisDsn)
    {
        $this->redis = new Redis();
        $this->connect($redisDsn);
    }

    private function connect(string $dsn): void
    {
        $parts = parse_url($dsn);

        $host = $parts['host'] ?? 'redis';
        $port = $parts['port'] ?? 6379;
        $password = $parts['pass'] ?? null;
        $database = isset($parts['path']) && $parts['path'] !== '/'
            ? (int) ltrim($parts['path'], '/')
            : 0;

        $this->redis->connect($host, $port);

        if ($password) {
            $this->redis->auth($password);
        }

        if ($database > 0) {
            $this->redis->select($database);
        }
    }

    public function getRedis(): Redis
    {
        return $this->redis;
    }
}
