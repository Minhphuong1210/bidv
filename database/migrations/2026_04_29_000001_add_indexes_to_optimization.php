<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
        });

        // The 'transactions' table might exist from a generic migration
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['user_id', 'is_redeemed']);
                $table->index('completion_time');
                $table->index('tx_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
        });

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'is_redeemed']);
                $table->dropIndex(['completion_time']);
                $table->dropIndex(['tx_id']);
            });
        }
    }
};
