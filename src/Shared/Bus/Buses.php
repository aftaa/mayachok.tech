<?php

namespace App\Shared\Bus;

enum Buses : string
{
    case Command = 'command.bus';
    case Query = 'query.bus';
    case Event = 'event.bus';
    case Default = 'messenger.bus.default';
}
