<?php

namespace App\Message;

use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final readonly class QueryBus
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function dispatch(object $query): mixed
    {
        $envelope = $this->queryBus->dispatch($query);
        return $envelope->last(HandledStamp::class)->getResult();
    }
}
