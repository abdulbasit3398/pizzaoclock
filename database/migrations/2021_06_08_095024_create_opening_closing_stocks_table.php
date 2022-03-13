<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpeningClosingStocksTable extends Migration
{
    /**
     * Run the migrations.
     * Stocks of ingredient
     * @return void
     */
    public function up()
    {
        Schema::create('opening_closing_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->unsignedBigInteger('work_period_id');
            $table->unsignedBigInteger('opening_stock');
            $table->unsignedBigInteger('closing_stock')->nullable();
            $table->unsignedBigInteger('addition_to_opening')->nullable();
            $table->unsignedBigInteger('subtraction_from_opening')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('opening_closing_stocks');
    }
}
