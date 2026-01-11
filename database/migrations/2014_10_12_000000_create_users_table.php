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
        if (Schema::hasTable('proj1_user')) {
            return;
        }

        Schema::create('proj1_user', function (Blueprint $table) {
            $table->string('user_login', 1000)->primary();
            $table->string('password', 1000)->nullable();
            $table->string('employee_id', 1000)->nullable();
            $table->string('employee_name', 1000)->nullable();
            $table->string('company_code', 1000)->nullable();
            $table->string('position_code', 1000)->nullable();
            $table->date('date_create')->nullable();
            $table->string('email', 300)->nullable();
            $table->string('remember_token', 1000)->nullable();
            $table->string('password_reset_tokens', 200)->nullable();
            $table->string('role', 100)->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('proj1_user')) {
            return;
        }

        Schema::dropIfExists('proj1_user');
    }
};
