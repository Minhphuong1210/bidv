<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('va_accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();

            $table->string('va_number')->unique();
            $table->string('merchant_name')->nullable();

            $table->string('bank')->nullable();
            $table->string('bank_full')->nullable();

            // type VA (1/2/3)
            $table->tinyInteger('type')->default(1);

            // tiền thực tế
            $table->decimal('amount', 18, 2)->default(0);

            // tiền integer để xử lý logic
            $table->bigInteger('amount_int')->default(0);

            $table->integer('bill_count')->default(0);

            // 1 = active, 0 = lock
            $table->tinyInteger('status')->default(1);

            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->integer('fee_rate')->default(0);

            $table->string('custom_excel_file')->nullable();

            $table->timestamps();

            // index tối ưu query
            $table->index(['user_id', 'va_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('va_accounts');
    }
};