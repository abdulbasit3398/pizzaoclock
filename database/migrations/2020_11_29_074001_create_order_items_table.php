<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_group_id');
            $table->string('food_item');
            $table->string('food_group');
            $table->text('variation')->nullable();
            $table->longText('properties')->nullable();
            $table->unsignedBigInteger('quantity');
            $table->text('price')->nullable(); //food price or variation price * quantity

            $table->boolean('is_cooking');
            $table->boolean('is_ready');
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
        Schema::dropIfExists('order_items');
    }
}
