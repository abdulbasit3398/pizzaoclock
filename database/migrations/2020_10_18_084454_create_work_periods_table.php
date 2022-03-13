<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkPeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_periods', function (Blueprint $table) {
            $table->id();
            $table->string('date');
            $table->string('branch_name');
            $table->string('started_by');
            $table->string('started_at');
            $table->string('ended_at')->nullable();
            $table->string('ended_by')->nullable();
            $table->string('branch_id')->nullable();
            $table->unsignedBigInteger('token');
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
        Schema::dropIfExists('work_periods');
    }
}
