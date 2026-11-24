<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Services\InquiryWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The buyer's side: the public "request a quote" form that feeds the
 * inquiry inbox. Production adds email verification, Turnstile, a
 * remembered-device cookie and Telegram alerts; the demo goes straight in.
 */
class QuoteRequestController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('public/request-quote', [
            'parts' => Part::with('uom:id,code')->orderBy('part_number')->get()
                ->map(fn (Part $part) => [
                    'id' => $part->id,
                    'part_number' => $part->part_number,
                    'description' => $part->description,
                    'uom' => $part->uom->code,
                ]),
            'sent' => $request->session()->get('quote_request.sent'),
        ]);
    }

    public function store(Request $request, InquiryWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'company' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.part_id' => ['nullable', 'integer', Rule::exists('parts', 'id')->whereNull('deleted_at')],
            'items.*.title' => ['required_without:items.*.part_id', 'nullable', 'string', 'max:200'],
            'items.*.quantity' => ['required', 'integer', 'between:1,100000'],
            'items.*.note' => ['nullable', 'string', 'max:200'],
            'website' => ['prohibited'], // honeypot
        ]);

        $parts = Part::with('uom:id,code')->whereIn('id', array_filter(array_column($data['items'], 'part_id')))->get()->keyBy('id');

        $items = array_map(function (array $item) use ($parts) {
            $part = isset($item['part_id']) ? $parts->get($item['part_id']) : null;

            return [
                'part_id' => $part?->id,
                'title' => $part->description ?? $item['title'],
                'part_number' => $part?->part_number,
                'quantity' => $item['quantity'],
                'unit' => $part?->uom->code,
                'note' => $item['note'] ?? null,
            ];
        }, $data['items']);

        $inquiry = $workflow->submit(collect($data)->except(['items', 'website'])->all(), $items);

        $request->session()->flash('quote_request.sent', ['ulid' => $inquiry->ulid, 'number' => $inquiry->number]);

        return redirect()->route('quote-request.create');
    }
}
