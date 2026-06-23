<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('api_service_id')->constrained('api_services')->cascadeOnDelete();
            $table->foreignId('token_type_id')->constrained('token_types')->cascadeOnDelete();
            $table->string('token_value', 512);
            $table->timestamps();

            // Один аккаунт — один токен одного типа для одного сервиса
            $table->unique(['account_id', 'api_service_id', 'token_type_id'], 'uq_account_service_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
