<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Quoted = 'quoted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InReview => 'In review',
            self::Approved => 'Approved',
            self::Quoted => 'Quoted',
            self::Rejected => 'Rejected',
        };
    }
}
