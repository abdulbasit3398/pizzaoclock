<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('image')->nullable();
            $table->string('slug')->unique();
            $table->string('phn_no')->unique()->nullable();
            $table->string('email')->unique()->nullable(); //userType == customer ? nullable
            $table->string('user_type'); //superAdmin || admin || staff || customer
            $table->boolean('is_active'); //default 1
            $table->boolean('is_banned'); //default 0
            $table->unsignedBigInteger('permission_group_id');
            $table->unsignedBigInteger('restaurant_id')->nullable(); //userType == superAdmin ? nullable
            $table->unsignedBigInteger('branch_id')->nullable(); //userType == superAdmin/admin ? nullable
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
