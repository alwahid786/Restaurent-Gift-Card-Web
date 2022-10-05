<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('notification_type');
            $table->float('amount');
            $table->integer('restaurent_id')->nullable();
            $table->integer('receiver_id')->nullable();
            $table->string('receiver_number');
            $table->integer('transaction_id')->nullable();
            $table->integer('gift_id')->nullable();
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
        Schema::dropIfExists('notification_logs');
    }
};
