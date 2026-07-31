<?php

namespace App\Message\Async;

class ProcessMixMessage implements AsyncMessageInterface
{
    public function __construct(
        public int $mixId,
        public string $filename,
    ) { }
}
