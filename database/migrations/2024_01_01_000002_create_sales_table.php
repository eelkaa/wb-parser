<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_id')->nullable()->unique()->comment('ID продажи (saleID из WB)');
            $table->timestamp('date')->nullable();
            $table->timestamp('last_change_date')->nullable();
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->integer('discount_percent')->nullable();
            $table->boolean('is_supply')->nullable();
            $table->boolean('is_realization')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->string('oblast')->nullable();
            $table->string('income_id')->nullable();
            $table->bigInteger('odid')->nullable();
            $table->bigInteger('nm_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->decimal('for_pay', 12, 2)->nullable()->comment('К перечислению поставщику');
            $table->decimal('finished_price', 12, 2)->nullable()->comment('Фактическая цена');
            $table->decimal('price_with_disc', 12, 2)->nullable()->comment('Цена со скидкой');
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index('nm_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
