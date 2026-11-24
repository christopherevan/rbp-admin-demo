<?php

namespace App\Enums;

enum DescriptionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
