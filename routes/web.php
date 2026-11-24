<?php

use App\Http\Controllers\Admin\CatalogDescriptionController;
use App\Http\Controllers\Admin\CatalogTypeController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\PartController;
use App\Http\Controllers\Admin\PrintController;
use App\Http\Controllers\Admin\QuotationActionController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\QuotationLineController;
use App\Http\Controllers\Admin\UomController;
use App\Http\Controllers\Demo\EntryController;
use App\Http\Controllers\Demo\ResetController;
use App\Http\Controllers\QuoteRequestController;
use Illuminate\Support\Facades\Route;

// Public pages: no sandbox, no database.
Route::inertia('/', 'welcome')->name('home');
Route::inertia('about', 'about')->name('about');

// The only way in, and the only route that creates a sandbox.
Route::post('demo/enter', [EntryController::class, 'store'])
    ->middleware(['guest', 'throttle:demo-enter'])
    ->name('demo.enter');

// The buyer's side of the inquiry flow, writing into the visitor's sandbox.
Route::middleware('sandbox')->group(function () {
    Route::get('request-quote', [QuoteRequestController::class, 'create'])->name('quote-request.create');
    Route::post('request-quote', [QuoteRequestController::class, 'store'])
        ->middleware('throttle:quote-request')
        ->name('quote-request.store');
});

Route::middleware(['auth', 'throttle:sandbox-writes'])->group(function () {
    Route::post('logout', [EntryController::class, 'destroy'])->name('logout');
    Route::post('demo/switch-role', [EntryController::class, 'switchRole'])->name('demo.switch-role');
    Route::post('demo/reset', ResetController::class)->name('demo.reset');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('customers', CustomerController::class)
        ->except('show')
        ->withTrashed(['edit', 'update']);
    Route::post('customers/{customer}/restore', [CustomerController::class, 'restore'])
        ->withTrashed()
        ->name('customers.restore');

    Route::get('parts/search', [PartController::class, 'search'])->name('parts.search');
    Route::post('parts/{part}/stock', [PartController::class, 'adjustStock'])->name('parts.stock');
    Route::post('parts/{part}/restore', [PartController::class, 'restore'])
        ->withTrashed()
        ->name('parts.restore');
    Route::resource('parts', PartController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('uoms', UomController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('quotations', QuotationController::class)->except('destroy');
    Route::put('quotations/{quotation}/lines', [QuotationLineController::class, 'update'])->name('quotations.lines.update');

    Route::controller(QuotationActionController::class)->group(function () {
        Route::post('quotations/{quotation}/customer-po', 'recordPo')->name('quotations.po.store');
        Route::post('quotations/{quotation}/sales-order', 'createSalesOrder')->name('quotations.sales-order.store');
        Route::delete('quotations/{quotation}/sales-order', 'deleteSalesOrder')->name('quotations.sales-order.destroy');
        Route::post('quotations/{quotation}/delivery-order', 'createDeliveryOrder')->name('quotations.delivery-order.store');
        Route::post('quotations/{quotation}/delivery-order/complete', 'completeDelivery')->name('quotations.delivery-order.complete');
        Route::post('quotations/{quotation}/invoices', 'createInvoice')->name('quotations.invoices.store');
        Route::post('quotations/{quotation}/cancel', 'cancel')->name('quotations.cancel');
        Route::post('invoices/{invoice}/payments', 'recordPayment')->name('invoices.payments.store');
    });

    Route::get('quotations/{quotation}/print/{document}', [PrintController::class, 'show'])
        ->whereIn('document', ['quotation', 'delivery-order', 'invoice', 'down-payment'])
        ->name('quotations.print');

    Route::get('inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
    Route::get('inquiries/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
    Route::controller(InquiryController::class)->group(function () {
        Route::post('inquiries/{inquiry}/assign', 'assign')->name('inquiries.assign');
        Route::post('inquiries/{inquiry}/link-customer', 'linkCustomer')->name('inquiries.link-customer');
        Route::post('inquiries/{inquiry}/approve', 'approve')->name('inquiries.approve');
        Route::post('inquiries/{inquiry}/reject', 'reject')->name('inquiries.reject');
        Route::post('inquiries/{inquiry}/convert', 'convert')->name('inquiries.convert');
    });

    Route::controller(CatalogDescriptionController::class)->group(function () {
        Route::get('catalog/descriptions', 'index')->name('catalog.descriptions.index');
        Route::post('catalog/descriptions/bulk-approve', 'bulkApprove')->name('catalog.descriptions.bulk-approve');
        Route::put('catalog/descriptions/{product}', 'update')->name('catalog.descriptions.update');
        Route::post('catalog/descriptions/{product}/decision', 'decide')->name('catalog.descriptions.decide');
    });

    Route::prefix('catalog/types')->name('catalog.types.')->controller(CatalogTypeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('apply', 'apply')->name('apply');
        Route::get('{type}', 'show')->name('show');
        Route::put('{type}', 'update')->name('update');
        Route::delete('{type}', 'destroy')->name('destroy');
        Route::post('{type}/categories', 'syncCategories')->name('categories');
        Route::post('{type}/attributes', 'storeKeyAttribute')->name('attributes.store');
        Route::put('attributes/{keyAttribute}', 'updateKeyAttribute')->name('attributes.update');
        Route::delete('attributes/{keyAttribute}', 'destroyKeyAttribute')->name('attributes.destroy');
    });
});
