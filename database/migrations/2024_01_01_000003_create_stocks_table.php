<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->timestamp('last_change_date')->nullable();
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('quantity')->nullable()->comment('Количество доступное для продажи');
            $table->boolean('is_supply')->nullable();
            $table->boolean('is_realization')->nullable();
            $table->integer('quantity_full')->nullable()->comment('Полное количество');
            $table->integer('quantity_not_in_orders')->nullable();
            $table->string('warehouse_name')->nullable();
            $table->integer('in_way_to_client')->nullable();
            $table->integer('in_way_from_client')->nullable();
            $table->bigInteger('nm_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('sc_code')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->integer('discount')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['nm_id', 'warehouse_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
