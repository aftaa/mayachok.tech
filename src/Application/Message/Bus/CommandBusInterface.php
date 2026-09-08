<?php

namespace App\Application\Message\Bus;

interface CommandBusInterface
{
    public function dispatch(object $command): mixed;
}
