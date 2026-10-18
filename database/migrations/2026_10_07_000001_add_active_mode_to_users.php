<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mode aktif (peminjam / pemilik) disimpan per akun supaya tidak hilang saat login ulang.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'active_mode')) {
                $table->string('active_mode', 20)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'active_mode')) {
                $table->dropColumn('active_mode');
            }
        });
    }
};
