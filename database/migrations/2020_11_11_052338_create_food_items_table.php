<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFoodItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('food_items', function (Blueprint $table) {
            $table->id();
            $table->string('food_group_id');
            $table->string('name');
            $table->string('price')->nullable();
            $table->string('image');
            $table->string('has_property');
            $table->string('property_group_ids')->nullable();
            $table->string('has_variation');
            $table->string('isSpecial');
            $table->string('slug')->unique();
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
        Schema::dropIfExists('food_items');
    }
}
