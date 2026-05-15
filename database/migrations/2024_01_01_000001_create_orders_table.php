<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable()->unique()->comment('ID заказа из WB');
            $table->timestamp('date')->nullable()->comment('Дата заказа');
            $table->timestamp('last_change_date')->nullable()->comment('Дата последнего изменения');
            $table->string('supplier_article')->nullable()->comment('Артикул поставщика');
            $table->string('tech_size')->nullable()->comment('Размер');
            $table->string('barcode')->nullable()->comment('Баркод');
            $table->decimal('total_price', 12, 2)->nullable()->comment('Цена до скидок');
            $table->integer('discount_percent')->nullable()->comment('Скидка %');
            $table->string('warehouse_name')->nullable()->comment('Склад');
            $table->string('oblast')->nullable()->comment('Область доставки');
            $table->string('income_id')->nullable()->comment('Номер поставки');
            $table->bigInteger('odid')->nullable()->comment('Уникальный идентификатор позиции заказа');
            $table->bigInteger('nm_id')->nullable()->comment('Артикул WB');
            $table->string('subject')->nullable()->comment('Предмет');
            $table->string('category')->nullable()->comment('Категория');
            $table->string('brand')->nullable()->comment('Бренд');
            $table->boolean('is_cancel')->default(false)->comment('Отмена');
            $table->timestamp('cancel_dt')->nullable()->comment('Дата отмены');
            $table->json('raw')->nullable()->comment('Исходный JSON из API');
            $table->timestamps();

            $table->index('nm_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
