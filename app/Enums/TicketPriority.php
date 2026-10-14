<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Urgent = 'urgent';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
}
