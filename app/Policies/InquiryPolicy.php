<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InquiryPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /** Triage (assigning, approving, rejecting) is an admin call. */
    public function triage(User $user, Inquiry $inquiry): Response
    {
        return Response::deny(QuotationPolicy::ADMIN_ONLY);
    }

    /** The assigned rep can link the customer and draft the quotation. */
    public function work(User $user, Inquiry $inquiry): Response
    {
        return $inquiry->assigned_to === $user->id
            ? Response::allow()
            : Response::deny('Only the assigned sales rep or an admin can work on this inquiry.');
    }
}
