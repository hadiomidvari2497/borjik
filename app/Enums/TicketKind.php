<?php

namespace App\Enums;

enum TicketKind: string
{
    case Task = 'task';
    case Request = 'request';
}
