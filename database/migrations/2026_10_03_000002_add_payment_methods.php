<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'qris_image')) {
                $table->string('qris_image')->nullable();
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_method')) {
                // cash | transfer | qris
                $table->string('payment_method')->default('transfer');
            }
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('qris_image');
        });
    }
};
