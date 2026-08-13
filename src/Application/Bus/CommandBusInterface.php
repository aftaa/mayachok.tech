<?php

namespace App\Application\Bus;

interface CommandBusInterface
{
    public function dispatch(object $command): mixed;
}
