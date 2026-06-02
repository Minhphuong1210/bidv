<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('user_bank_accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();

            // số tài khoản
            $table->string('stk');

            // tên bank (Vietcombank, BIDV...)
            $table->string('bank');

            // tên chủ tài khoản
            $table->string('name');

            $table->timestamps();

            $table->index(['user_id', 'stk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bank_accounts');
    }
};