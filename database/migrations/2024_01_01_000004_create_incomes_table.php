<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('income_id')->nullable()->unique()->comment('Номер поставки WB');
            $table->string('number')->nullable()->comment('Номер УПД');
            $table->timestamp('date')->nullable()->comment('Дата поступления');
            $table->timestamp('last_change_date')->nullable();
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->timestamp('date_close')->nullable()->comment('Дата принятия поставки');
            $table->string('warehouse_name')->nullable();
            $table->bigInteger('nm_id')->nullable();
            $table->string('status')->nullable()->comment('Текущий статус поставки');
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
