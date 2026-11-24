<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Exceptions\WorkflowException;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\User;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\DB;

/**
 * Web inquiries from the public quote form, through triage to a quotation:
 *
 *   NEW ─assign─▶ IN REVIEW ─approve─▶ APPROVED ─convert─▶ QUOTED
 *    └──────────────┴──reject (reason)──▶ REJECTED
 */
class InquiryWorkflow
{
    public function __construct(private QuotationWorkflow $quotations) {}

    /**
     * @param  array<string, mixed>  $contact
     * @param  list<array{part_id: int|null, title: string, part_number: string|null, quantity: int, unit: string|null, note: string|null}>  $items
     */
    public function submit(array $contact, array $items): Inquiry
    {
        return DB::transaction(function () use ($contact, $items) {
            $inquiry = new Inquiry($contact);
            $inquiry->number = DocumentNumber::inquiry();
            $inquiry->status = InquiryStatus::New;
            $inquiry->save();

            $inquiry->items()->createMany($items);

            $inquiry->log('submitted', 'Submitted through the public quote form.');

            return $inquiry;
        });
    }

    /** Assigning a rep is what moves a new inquiry into review. */
    public function assign(Inquiry $inquiry, User $assignee, User $by): void
    {
        $this->ensure(in_array($inquiry->status, [InquiryStatus::New, InquiryStatus::InReview], true), 'Only open inquiries can be assigned.');

        DB::transaction(function () use ($inquiry, $assignee, $by) {
            $inquiry->assigned_to = $assignee->id;

            if ($inquiry->status === InquiryStatus::New) {
                $inquiry->status = InquiryStatus::InReview;
            }

            $inquiry->save();

            $inquiry->log('assigned', "Assigned to {$assignee->name}; now in review.", $by);
        });
    }

    public function linkCustomer(Inquiry $inquiry, Customer $customer, ?Contact $contact, User $by): void
    {
        $this->ensure($inquiry->status !== InquiryStatus::Quoted, 'The inquiry has already been turned into a quotation.');
        $this->ensure($contact === null || $contact->customer_id === $customer->id, 'That contact belongs to a different customer.');

        DB::transaction(function () use ($inquiry, $customer, $contact, $by) {
            $inquiry->customer_id = $customer->id;
            $inquiry->contact_id = $contact?->id;
            $inquiry->save();

            $inquiry->log('customer_linked', "Linked to {$customer->name}".($contact ? " ({$contact->name})" : '').'.', $by);
        });
    }

    public function approve(Inquiry $inquiry, User $by): void
    {
        $this->ensure($inquiry->status === InquiryStatus::InReview, 'Assign the inquiry to a sales rep before approving it.');

        DB::transaction(function () use ($inquiry, $by) {
            $inquiry->status = InquiryStatus::Approved;
            $inquiry->decided_by = $by->id;
            $inquiry->decided_at = now();
            $inquiry->save();

            $inquiry->log('approved', 'Approved for quotation.', $by);
        });
    }

    public function reject(Inquiry $inquiry, string $reason, User $by): void
    {
        $this->ensure(in_array($inquiry->status, [InquiryStatus::New, InquiryStatus::InReview], true), 'Only open inquiries can be rejected.');

        DB::transaction(function () use ($inquiry, $reason, $by) {
            $inquiry->status = InquiryStatus::Rejected;
            $inquiry->rejection_reason = $reason;
            $inquiry->decided_by = $by->id;
            $inquiry->decided_at = now();
            $inquiry->save();

            $inquiry->log('rejected', "Rejected: {$reason}", $by);
        });
    }

    /**
     * Draft a quotation from the inquiry: the linked customer and contact,
     * plus every requested item that matched a part, at list price. It stays
     * NOT SUBMITTED so the sales rep reviews prices before it goes out.
     */
    public function convert(Inquiry $inquiry, User $by): Quotation
    {
        $this->ensure($inquiry->status === InquiryStatus::Approved, 'Approve the inquiry before turning it into a quotation.');
        $this->ensure($inquiry->customer_id !== null, 'Link the inquiry to a customer first.');

        return DB::transaction(function () use ($inquiry, $by) {
            $previous = Quotation::where('customer_id', $inquiry->customer_id)->latest('id')->first();

            $quotation = $this->quotations->create([
                'customer_id' => $inquiry->customer_id,
                'contact_id' => $inquiry->contact_id,
                'sales_user_id' => $inquiry->assigned_to ?? $by->id,
                'delivery_point' => $previous->delivery_point ?? config('demo.delivery_points')[0],
                'discount_pct' => 0,
                'lead_time_value' => 2,
                'lead_time_unit' => 'weeks',
                'validity_value' => 2,
                'validity_unit' => 'weeks',
                'payment_method' => 'invoice',
                'payment_terms_days' => 30,
                'dp_amount' => 0,
                'notes' => "From web inquiry {$inquiry->number}.",
            ], $by);

            $lines = $inquiry->items()->with('part')->get()
                ->filter(fn ($item) => $item->part && ! $item->part->trashed())
                ->map(fn ($item) => [
                    'part_id' => $item->part_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->part->list_price,
                ])
                ->unique('part_id')
                ->values()
                ->all();

            if ($lines !== []) {
                $this->quotations->saveLines($quotation, $lines, $by, submit: false);
            }

            $inquiry->status = InquiryStatus::Quoted;
            $inquiry->quotation_id = $quotation->id;
            $inquiry->save();

            $inquiry->log('converted', "Quotation {$quotation->number} drafted with ".count($lines).' matched '.str('item')->plural(count($lines)).'.', $by);
            $quotation->log('from_inquiry', "Drafted from web inquiry {$inquiry->number}.", $by);

            return $quotation;
        });
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new WorkflowException($message);
        }
    }
}
