<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('order_date');
            $table->string('buyer_name');
            $table->string('ebay_order_no')->nullable();
            $table->string('amazon_order_no')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('Order Placed'); // Delivered, Order Placed, Refunded, Out of Stock
            $table->decimal('amazon_cost', 10, 2)->default(0);
            $table->decimal('ebay_receipts', 10, 2)->default(0);
            $table->decimal('profit', 10, 2)->default(0);
            $table->decimal('roi', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
