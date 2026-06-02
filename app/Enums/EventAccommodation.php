<?php

namespace App\Enums;

enum EventAccommodation: string
{
    case None = 'none';
    case Optional = 'optional';
    case Mandatory = 'mandatory';
    case External = 'external';
}
