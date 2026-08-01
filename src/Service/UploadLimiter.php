<?php

namespace App\Service;

use App\Factory\RedisFactory;
use DateMalformedStringException;
use DateTime;

class UploadLimiter extends AbstractRedisService
{
    private const int LIMIT_PER_HOUR = 5;
    private const int TTL = 3600;
    private string $key;

    /**
     * @throws DateMalformedStringException
     */
    public function checkAndIncrement(int $userId): array
    {
        $key = $this->key("user:{$userId}:uploads:hourly:" . date('Y-m-d-H'));

        $count = $this->redis->incr($key);

        if ($count === 1) {
            $this->redis->expire($key, self::TTL);
        }

        $ttl = $this->redis->ttl($key);

        return [
            'allowed' => $count <= self::LIMIT_PER_HOUR,
            'count' => (int) $count,
            'limit' => self::LIMIT_PER_HOUR,
            'reset_in' => (int) $ttl,
            'reset_at' => $ttl > 0
                ? new DateTime()->modify("+{$ttl} seconds")->format('H:i:s')
                : null,
        ];
    }

    /**
     * @throws DateMalformedStringException
     */
    public function getStatus(int $userId): array
    {
        $key = $this->key("user:{$userId}:uploads:hourly:" . date('Y-m-d-H'));
        $count = (int) $this->redis->get($key);
        $ttl = $this->redis->ttl($key);

        return [
            'used' => $count,
            'limit' => self::LIMIT_PER_HOUR,
            'remaining' => max(0, self::LIMIT_PER_HOUR - $count),
            'reset_in' => (int) $ttl,
            'reset_at' => $ttl > 0
                ? new DateTime()->modify("+{$ttl} seconds")->format('H:i:s')
                : null,
        ];
    }
}
