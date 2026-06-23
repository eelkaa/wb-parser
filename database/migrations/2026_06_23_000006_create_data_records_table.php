<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('endpoint', 50)->comment('orders|sales|stocks|incomes|reportDetail');
            $table->date('record_date');
            $table->json('payload')->nullable();
            $table->timestamps();

            // Индекс для быстрого поиска свежих данных по дате
            $table->index(['account_id', 'endpoint', 'record_date'], 'idx_account_endpoint_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_records');
    }
};
