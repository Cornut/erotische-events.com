<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Organizer = 'organizer';
    case Admin = 'admin';
}
