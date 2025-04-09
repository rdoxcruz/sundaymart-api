<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTitleColumnInProductPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('product_properties', function (Blueprint $table) {
            $table->dropColumn('key');
            $table->dropColumn('value');
        });

        Schema::table('product_properties', function (Blueprint $table) {
            $table->string('title')->nullable()->index();
            $table->string('value')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('product_properties', function (Blueprint $table) {
            //
        });
    }
}
