<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->nullable(); //for SaaS

            $table->unsignedBigInteger('work_period_id');
            $table->unsignedBigInteger('user_id')->nullable(); //who took this order
            $table->string('user_name')->nullable(); //who took this order


            //order details
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('branch_name')->nullable();

            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name')->nullable();

            $table->unsignedBigInteger('table_id')->nullable();
            $table->string('table_name')->nullable();

            $table->unsignedBigInteger('waiter_id')->nullable();
            $table->string('waiter_name')->nullable();

            $table->unsignedBigInteger('dept_tag_id')->nullable();
            $table->string('dept_tag_name')->nullable();

            $table->string('token');
            $table->string('total_guest');

            $table->unsignedBigInteger('service_charge')->nullable();
            $table->unsignedBigInteger('discount')->nullable();
            $table->unsignedBigInteger('dept_commission')->nullable();


            //save after all item save in order items in update version
            $table->string('order_bill')->nullable();
            $table->string('vat')->nullable();
            $table->string('vat_system')->nullable();
            $table->string('cgst')->nullable();
            $table->string('sgst')->nullable();
            $table->string('total_payable')->nullable(); //orderBill + vat + serviceCharge - discount

            $table->longText('bill_distribution')->nullable(); //for each payment type => amount
            $table->string('paid_amount')->nullable();
            $table->string('return_amount')->nullable();
            $table->boolean('is_paid');
            //save after all item save in order items in update version

            $table->boolean('is_accepted'); //accepted by kitchen
            $table->boolean('is_cancelled'); //cancelled by the order taker
            $table->boolean('is_settled'); //settled by the order taker
            $table->boolean('is_ready'); //confirmed by the kitchen user
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
        Schema::dropIfExists('order_groups');
    }
}
