<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('amazon_url', 500)->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('amazon_order_no', 500)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('amazon_url')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('amazon_order_no', 500)->nullable()->change();
        });
    }
};
