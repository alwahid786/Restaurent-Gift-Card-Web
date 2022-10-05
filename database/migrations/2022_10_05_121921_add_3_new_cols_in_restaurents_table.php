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
        Schema::table('restaurents', function (Blueprint $table) {
            $table->float('total_balance')->nullable();
            $table->float('released_balance')->nullable();
            $table->float('pending_balance')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('restaurents', function (Blueprint $table) {
            $table->dropColumn('total_balance');
            $table->dropColumn('released_balance');
            $table->dropColumn('pending_balance');
        });
    }
};
