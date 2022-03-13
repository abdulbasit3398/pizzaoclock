<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnlineOrderGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('online_order_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->nullable(); //for SaaS

            $table->unsignedBigInteger('work_period_id');
            $table->unsignedBigInteger('user_id'); //who made this order
            $table->string('user_name'); //who made this order
            $table->unsignedBigInteger('pos_user_id')->nullable(); //who accepted/cancelled this order

            //order details
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('branch_name')->nullable();
            $table->text('token')->nullable();

            //save after all item save in order items in update version
            $table->string('order_bill')->nullable();
            $table->string('vat')->nullable();
            $table->string('vat_system')->nullable();
            $table->string('cgst')->nullable();
            $table->string('sgst')->nullable();
            $table->string('total_payable')->nullable(); //orderBill + vat + serviceCharge - discount
            $table->string('payment_method');
            $table->string('payment_details')->nullable();

            $table->string('time_to_deliver')->nullable();
            $table->unsignedBigInteger('delivery_boy_id')->nullable();//for future update //who will deliver->waiter id till delivery boy crud be done
            $table->string('delivery_boy_name')->nullable();//for future update

            $table->string('note_to_rider')->nullable();
            $table->string('delivery_phn_no')->nullable();
            $table->text('delivery_address')->nullable();

            $table->boolean('is_accepted'); //accepted by pos user
            $table->boolean('is_accepted_by_kitchen'); //accepted by kitchen user
            $table->boolean('is_cancelled'); //cancelled by the pos
            $table->boolean('is_ready'); //confirmed by the kitchen user
            $table->boolean('is_settled'); //settled by the order taker

            $table->text('reason_of_cancel')->nullable(); //cancelled by the pos user
            $table->boolean('is_delivered'); //confirmed by the pos user
            $table->text('delivery_status')->nullable();

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
        Schema::dropIfExists('online_order_groups');
    }
}
