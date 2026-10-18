<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('peminjam'); // peminjam | pemilik | admin
            }
            if (!Schema::hasColumn('users', 'bank_name')) {
                $table->string('bank_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'account_number')) {
                $table->string('account_number')->nullable();
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_proof')) {
                $table->string('payment_proof')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'payment_status')) {
                // unpaid | waiting | paid | rejected
                $table->string('payment_status')->default('unpaid');
            }
            if (!Schema::hasColumn('bookings', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'is_hidden')) {
                $table->boolean('is_hidden')->default(false);
            }
        });

        if (!Schema::hasTable('complaints')) {
            Schema::create('complaints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
                $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reported_id')->constrained('users')->cascadeOnDelete();
                $table->text('content');
                // new | reviewing | resolved
                $table->string('status')->default('new');
                $table->timestamps();

                $table->index('status');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('complaints');

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_proof', 'payment_status', 'paid_at']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'bank_name', 'account_number']);
        });
    }
};
