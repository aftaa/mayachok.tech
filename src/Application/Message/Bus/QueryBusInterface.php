<?php

namespace App\Application\Message\Bus;

interface QueryBusInterface
{
    public function dispatch(object $query): mixed;
}
