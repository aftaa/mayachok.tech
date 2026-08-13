<?php

namespace App\Application\Bus;

interface QueryBusInterface
{
    public function dispatch(object $query): mixed;
}
