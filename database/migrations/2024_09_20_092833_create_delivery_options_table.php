<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('delivery_options', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Название доставки');
            $table->foreignId('shop_id')->comment('Магазин')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->double('price')->index()->default(0)->comment('Начальная цена');
            $table->double('price_per_km')->index()->default(0)->comment('Цена за каждый км');
            $table->tinyInteger('type')->default(1)->comment('Тип времени');
            $table->smallInteger('time_from')->default(0)->comment('Время от');
            $table->smallInteger('time_to')->default(0)->comment('Время до');
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->jsonb('delivery_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_options');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivery_option_id');
            $table->dropColumn('delivery_option');
        });
    }
}
