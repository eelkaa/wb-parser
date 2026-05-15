<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('rrd_id')->nullable()->unique()->comment('ID строки в детализации WB');
            $table->timestamp('date_from')->nullable();
            $table->timestamp('date_to')->nullable();
            $table->timestamp('create_dt')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('doc_type_name')->nullable()->comment('Тип документа');
            $table->integer('quantity_x')->nullable();
            $table->decimal('retail_price', 12, 2)->nullable();
            $table->decimal('retail_amount', 12, 2)->nullable();
            $table->decimal('sale_percent', 8, 2)->nullable();
            $table->decimal('commission_percent', 8, 2)->nullable();
            $table->string('supplier_oper_name')->nullable();
            $table->timestamp('order_dt')->nullable();
            $table->timestamp('sale_dt')->nullable();
            $table->timestamp('rr_dt')->nullable();
            $table->bigInteger('shk_id')->nullable();
            $table->decimal('retail_price_withdisc_rub', 12, 2)->nullable();
            $table->integer('delivery_amount')->nullable();
            $table->integer('return_amount')->nullable();
            $table->decimal('delivery_rub', 12, 2)->nullable();
            $table->string('gi_box_type_name')->nullable();
            $table->decimal('product_discount_for_report', 8, 2)->nullable();
            $table->decimal('supplier_promo', 12, 2)->nullable();
            $table->decimal('ppvz_spp_prc', 8, 2)->nullable();
            $table->decimal('ppvz_kvw_prc_base', 8, 2)->nullable();
            $table->decimal('ppvz_kvw_prc', 8, 2)->nullable();
            $table->decimal('ppvz_sales_commission', 12, 2)->nullable();
            $table->decimal('ppvz_for_pay', 12, 2)->nullable();
            $table->decimal('ppvz_reward', 12, 2)->nullable();
            $table->decimal('ppvz_vw', 12, 2)->nullable();
            $table->decimal('ppvz_vw_nds', 12, 2)->nullable();
            $table->string('ppvz_office_name')->nullable();
            $table->bigInteger('ppvz_supplier_id')->nullable();
            $table->string('ppvz_supplier_name')->nullable();
            $table->string('ppvz_inn')->nullable();
            $table->string('declaration_number')->nullable();
            $table->string('bonus_type_name')->nullable();
            $table->string('sticker_id')->nullable();
            $table->string('site_country')->nullable();
            $table->decimal('penalty', 12, 2)->nullable();
            $table->decimal('additional_payment', 12, 2)->nullable();
            $table->bigInteger('nm_id')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('subject_name')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_details');
    }
};
