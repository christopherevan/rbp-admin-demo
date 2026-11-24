<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Enums\QuotationStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DeliveryOrder;
use App\Models\Inquiry;
use App\Models\Part;
use App\Models\Quotation;
use App\Models\QuotationLine;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $latestQuotation = Quotation::with('customer:id,name')->latest('id')->first();
        $latestInquiry = Inquiry::latest('id')->first();

        return Inertia::render('dashboard', [
            'quotationCounts' => $this->counts(Quotation::class, QuotationStatus::cases()),
            'inquiryCounts' => $this->counts(Inquiry::class, InquiryStatus::cases()),
            'mostQuoted' => Part::withTrashed()
                ->with('uom:id,code')
                ->whereHas('quotationLines')
                ->withCount('quotationLines')
                ->orderByDesc('quotation_lines_count')
                ->orderBy('part_number')
                ->limit(10)
                ->get()
                ->map(fn (Part $part) => [
                    'id' => $part->id,
                    'part_number' => $part->part_number,
                    'description' => $part->description,
                    'uom' => $part->uom->code,
                    'quotations' => $part->quotation_lines_count,
                ]),
            'onDelivery' => DeliveryOrder::where('status', DeliveryOrder::ON_DELIVERY)->count(),
            'latestQuotation' => $latestQuotation ? [
                'id' => $latestQuotation->id,
                'ulid' => $latestQuotation->ulid,
                'number' => $latestQuotation->number,
                'customer' => $latestQuotation->customer->name,
                'status' => $latestQuotation->status,
                'created_at' => $latestQuotation->created_at,
            ] : null,
            'latestInquiry' => $latestInquiry ? [
                'id' => $latestInquiry->id,
                'ulid' => $latestInquiry->ulid,
                'number' => $latestInquiry->number,
                'name' => $latestInquiry->name,
                'company' => $latestInquiry->company,
                'status' => $latestInquiry->status,
                'created_at' => $latestInquiry->created_at,
            ] : null,
            'recentActivity' => ActivityLog::with(['user:id,name', 'loggable'])
                ->whereIn('loggable_type', ['quotation', 'inquiry'])
                ->latest('created_at')
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (ActivityLog $log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user' => $log->user?->name,
                    'created_at' => $log->created_at,
                    'subject' => [
                        'type' => $log->loggable_type,
                        'id' => $log->loggable_id,
                        'key' => $log->loggable?->getRouteKey() ?? $log->loggable_id,
                        'number' => $log->loggable?->number,
                    ],
                ]),
            'priceHistory' => Inertia::optional(fn () => $this->priceHistory($request->integer('part'))),
        ]);
    }

    /**
     * Every quotation line for one part, oldest first: what it was quoted
     * at, how many, and to whom. Loaded only when a part is clicked.
     *
     * @return array<string, mixed>|null
     */
    private function priceHistory(int $partId): ?array
    {
        $part = Part::withTrashed()->with('uom:id,code')->find($partId);

        if (! $part) {
            return null;
        }

        $points = QuotationLine::query()
            ->where('part_id', $part->id)
            ->join('quotations', 'quotations.id', '=', 'quotation_lines.quotation_id')
            ->join('customers', 'customers.id', '=', 'quotations.customer_id')
            ->orderBy('quotations.created_at')
            ->get([
                'quotations.id as quotation_id',
                'quotations.number',
                'quotations.created_at as date',
                'customers.name as customer',
                'quotation_lines.quantity',
                'quotation_lines.unit_price',
            ]);

        return [
            'part' => [
                'id' => $part->id,
                'part_number' => $part->part_number,
                'description' => $part->description,
                'uom' => $part->uom->code,
                'list_price' => $part->list_price,
                'stock_qty' => $part->stock_qty,
            ],
            'averagePrice' => (int) round($points->avg('unit_price') ?? 0),
            'points' => $points->map(fn ($point) => [
                ...$point->only(['quotation_id', 'number', 'customer', 'quantity', 'unit_price']),
                'date' => CarbonImmutable::parse($point->date)->toIso8601String(),
            ]),
        ];
    }

    /**
     * @param  class-string<Quotation|Inquiry>  $model
     * @param  list<QuotationStatus|InquiryStatus>  $statuses
     * @return array<string, int>
     */
    private function counts(string $model, array $statuses): array
    {
        $counts = $model::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect($statuses)->mapWithKeys(fn ($status) => [$status->value => (int) ($counts[$status->value] ?? 0)])->all();
    }
}
