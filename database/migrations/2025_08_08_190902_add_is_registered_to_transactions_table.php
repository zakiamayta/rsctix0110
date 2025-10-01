<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('transactions', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->boolean('is_registered')->default(false);
            $table->timestamp('registered_at')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('transactions', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->dropColumn(['is_registered', 'registered_at']);
        });
    }

};
