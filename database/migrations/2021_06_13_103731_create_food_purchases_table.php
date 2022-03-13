<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFoodPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('food_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('supplier_name');
            $table->string('invoice_number');
            $table->string('purchase_date');
            $table->string('desc')->nullable();
            $table->string('payment_type')->nullable();
            $table->unsignedBigInteger('total_bill')->nullable();
            $table->unsignedBigInteger('paid_amount')->nullable();
            $table->unsignedBigInteger('credit_amount')->nullable();
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
        Schema::dropIfExists('food_purchases');
    }
}
