<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case NotSubmitted = 'not_submitted';
    case WaitingCustomer = 'waiting_customer';
    case OnProgress = 'on_progress';
    case WaitingPayment = 'waiting_payment';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NotSubmitted => 'Not submitted',
            self::WaitingCustomer => 'Waiting customer',
            self::OnProgress => 'On progress',
            self::WaitingPayment => 'Waiting payment',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Position on the five-step progress stepper. A cancelled quotation keeps
     * the step it reached, which the stepper works out from its documents.
     */
    public function step(): ?int
    {
        return match ($this) {
            self::NotSubmitted => 1,
            self::WaitingCustomer => 2,
            self::OnProgress => 3,
            self::WaitingPayment => 4,
            self::Completed => 5,
            self::Cancelled => null,
        };
    }

    /** Still editable: the customer has not sent a purchase order yet. */
    public function isOpenForEditing(): bool
    {
        return in_array($this, [self::NotSubmitted, self::WaitingCustomer], true);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
