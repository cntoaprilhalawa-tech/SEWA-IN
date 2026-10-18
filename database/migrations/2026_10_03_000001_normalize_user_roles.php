<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Akun lama: yang sudah punya barang jadi "pemilik", sisanya "peminjam".
     */
    public function up()
    {
        DB::table('users')->where('role', 'user')->update(['role' => 'peminjam']);

        $ownerIds = DB::table('items')->distinct()->pluck('user_id');
        DB::table('users')
            ->whereIn('id', $ownerIds)
            ->where('role', '!=', 'admin')
            ->update(['role' => 'pemilik']);
    }

    public function down()
    {
        //
    }
};
