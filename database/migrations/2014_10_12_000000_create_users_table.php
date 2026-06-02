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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        
            //  Basic
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique(); //  phải nullable
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable(); //  login tele không cần password
        
            //  TELEGRAM (core)
            $table->unsignedBigInteger('telegram_id')->unique()->nullable();
            $table->string('telegram_username')->nullable()->index();
            $table->string('telegram_first_name')->nullable();
            $table->string('telegram_last_name')->nullable();
            $table->string('telegram_language', 10)->nullable();
        
            //  Extra
            $table->boolean('telegram_is_bot')->default(false);
            $table->string('telegram_photo')->nullable();
        
            //  SECURITY (quan trọng)
            $table->timestamp('telegram_last_login')->nullable();
            $table->string('telegram_ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->float('diem')->default(0);
            //  ANTI SPAM
            $table->integer('login_count')->default(0);
            $table->boolean('is_blocked')->default(false);
        
            //  ROLE (sau này dùng ngay)
            $table->string('role')->default('user'); // user | admin
        
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
