<?php

namespace App\Message;

use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final readonly class CommandBus
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) { }

    /**
     * @throws ExceptionInterface
     */
    public function dispatch(object $command): mixed
    {
        $envelope = $this->commandBus->dispatch($command);
        $stamp = $envelope->last(HandledStamp::class);

        return $stamp?->getResult();
    }
}
