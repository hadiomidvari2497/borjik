<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case Review = 'review';
    case Doing = 'doing';
    case Waiting = 'waiting';
    case Done = 'done';
    case Closed = 'closed';
}
