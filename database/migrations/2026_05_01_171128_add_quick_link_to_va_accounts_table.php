<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('va_accounts', function (Blueprint $table) {
            $table->text('quick_link')->after('ma_don_hang')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('va_accounts', function (Blueprint $table) {
            $table->dropColumn('quick_link');
        });
    }
};
