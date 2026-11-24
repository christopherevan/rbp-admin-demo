<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Sales reps own their quotations up to the customer's PO; fulfilment and
 * billing are admin work. (The original only gated User Accounts; this
 * follows how the work was actually split between the two roles.)
 */
class QuotationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function update(User $user, Quotation $quotation): Response
    {
        return $this->owner($user, $quotation);
    }

    public function recordPo(User $user, Quotation $quotation): Response
    {
        return $this->owner($user, $quotation);
    }

    public function cancel(User $user, Quotation $quotation): Response
    {
        return $this->owner($user, $quotation);
    }

    /** Sales orders, delivery orders and deliveries. */
    public function fulfil(User $user, Quotation $quotation): Response
    {
        return Response::deny(self::ADMIN_ONLY);
    }

    /** Invoices and payments. */
    public function bill(User $user, Quotation $quotation): Response
    {
        return Response::deny(self::ADMIN_ONLY);
    }

    public const ADMIN_ONLY = 'Admin only. Switch to the Admin persona to try this.';

    private function owner(User $user, Quotation $quotation): Response
    {
        return $quotation->sales_user_id === $user->id
            ? Response::allow()
            : Response::deny('Only the assigned sales rep or an admin can change this quotation.');
    }
}
