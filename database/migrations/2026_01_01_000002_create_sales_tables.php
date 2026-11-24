<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Money is whole rupiah in unsigned bigints: IDR has no minor unit in
     * practice, and integers keep totals exact.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('number', 40)->unique();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('contact_id')->nullable()->constrained();
            $table->foreignId('sales_user_id')->constrained('users');
            $table->string('status', 20)->index();
            $table->string('delivery_point', 60);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->unsignedTinyInteger('ppn_rate')->default(11);
            $table->unsignedSmallInteger('lead_time_value');
            $table->string('lead_time_unit', 10);
            $table->unsignedSmallInteger('validity_value');
            $table->string('validity_unit', 10);
            $table->string('payment_method', 10);
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
            $table->unsignedBigInteger('dp_amount')->default(0);
            $table->text('notes')->nullable();
            $table->string('customer_po_number', 60)->nullable();
            $table->string('customer_po_file', 160)->nullable();
            $table->timestamp('customer_po_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_id')->constrained();
            $table->unsignedSmallInteger('position');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('number', 50)->unique();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_line_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('number', 50)->unique();
            $table->string('ship_to_name', 120);
            $table->string('ship_to_address', 320);
            $table->string('notes', 500)->nullable();
            $table->string('status', 20);
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('number', 50)->unique();
            $table->string('bill_to_name', 120);
            $table->string('bill_to_address', 320);
            $table->unsignedBigInteger('amount_due');
            $table->string('status', 10);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['quotation_id', 'type']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('note', 200)->nullable();
            $table->string('proof_file_name', 160)->nullable();
            $table->foreignId('received_by')->constrained('users');
            $table->timestamp('received_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('quotation_lines');
        Schema::dropIfExists('quotations');
    }
};
