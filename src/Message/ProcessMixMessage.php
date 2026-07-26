<?php

namespace App\Message;

class ProcessMixMessage implements AsyncMessageInterface
{
    public function __construct(
        public int $mixId,
        public string $filename,
    ) { }
}
