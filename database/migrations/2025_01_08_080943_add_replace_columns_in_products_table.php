<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplaceColumnsInProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->unsignedBigInteger('replace_stock_id')->nullable()->index();
            $table->integer('replace_quantity')->nullable();
            $table->string('replace_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->dropColumn('replace_stock_id');
            $table->dropColumn('replace_quantity');
            $table->dropColumn('replace_note');
        });
    }
}
