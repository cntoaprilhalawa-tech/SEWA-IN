<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'returned_at')) {
                $table->date('returned_at')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'late_days')) {
                $table->unsignedInteger('late_days')->default(0);
            }
            if (!Schema::hasColumn('bookings', 'late_fee')) {
                $table->decimal('late_fee', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('bookings', 'damage_note')) {
                $table->text('damage_note')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'deposit_deduction')) {
                $table->decimal('deposit_deduction', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('bookings', 'deposit_status')) {
                // none | held | returned | deducted
                $table->string('deposit_status')->default('held');
            }
        });

        // Booking lama: tanpa deposit -> 'none'; yang sudah selesai -> deposit dianggap dikembalikan
        DB::table('bookings')->where('deposit', '<=', 0)->update(['deposit_status' => 'none']);
        DB::table('bookings')->where('deposit', '>', 0)->where('status', 'completed')->update(['deposit_status' => 'returned']);
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['returned_at', 'late_days', 'late_fee', 'damage_note', 'deposit_deduction', 'deposit_status']);
        });
    }
};
