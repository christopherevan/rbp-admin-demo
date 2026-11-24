<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\QuotationWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class QuotationLineController extends Controller
{
    public function update(Request $request, Quotation $quotation, QuotationWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $quotation);

        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1', 'max:'.config('demo.max_quotation_lines')],
            'lines.*.part_id' => ['required', 'integer', Rule::exists('parts', 'id')->whereNull('deleted_at')],
            'lines.*.quantity' => ['required', 'integer', 'between:1,100000'],
            'lines.*.unit_price' => ['required', 'integer', 'between:0,1000000000000'],
        ], [
            'lines.*.part_id.exists' => 'Line :position: this part has been discontinued.',
        ]);

        $wasDraft = $quotation->status->step() === 1;

        $workflow->saveLines($quotation, array_map(fn ($line) => [
            'part_id' => (int) $line['part_id'],
            'quantity' => (int) $line['quantity'],
            'unit_price' => (int) $line['unit_price'],
        ], $data['lines']), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => $wasDraft
            ? 'Line items saved and the quotation sent to the customer.'
            : 'Line items saved.']);

        return back();
    }
}
