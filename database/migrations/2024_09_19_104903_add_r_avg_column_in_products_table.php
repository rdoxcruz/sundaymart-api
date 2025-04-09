<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRAvgColumnInProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->double('r_avg')->index()->default(0);
            $table->double('r_count')->index()->default(0);
            $table->double('o_count')->index()->default(0);
            $table->double('od_count')->index()->default(0);
            $table->double('age_limit')->index()->default(0);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->double('r_avg')->index()->default(0);
            $table->double('r_count')->index()->default(0);
            $table->double('o_count')->index()->default(0);
            $table->double('od_count')->index()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('r_avg');
            $table->dropColumn('r_count');
            $table->dropColumn('o_count');
            $table->dropColumn('od_count');
            $table->dropColumn('age_limit');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('r_avg');
            $table->dropColumn('r_count');
            $table->dropColumn('o_count');
            $table->dropColumn('od_count');
        });
    }
}
