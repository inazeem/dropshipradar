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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('ebay_url')->unique();
            $table->string('amazon_url')->nullable();
            $table->decimal('ebay_price', 10, 2);
            $table->decimal('amazon_price', 10, 2);
            $table->decimal('ebay_fee', 10, 2)->default(0);
            $table->decimal('profit', 10, 2)->default(0);
            $table->decimal('roi', 8, 2)->default(0);
            $table->string('status')->default('draft');
            $table->date('listed_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
