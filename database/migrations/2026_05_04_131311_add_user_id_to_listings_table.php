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
            // Allow null temporarily so existing rows don't violate NOT NULL
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Assign orphan rows to the first admin, fall back to any user
        $adminId = DB::table('users')->where('role', 'admin')->value('id')
            ?? DB::table('users')->value('id');

        if ($adminId) {
            DB::table('listings')->whereNull('user_id')->update(['user_id' => $adminId]);
        }

        // Now make the column non-nullable
        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        // The old global unique constraint on ebay_url must go — uniqueness is now per-user
        Schema::table('listings', function (Blueprint $table) {
            $table->dropUnique(['ebay_url']);
            $table->unique(['user_id', 'ebay_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'ebay_url']);
            $table->unique(['ebay_url']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
