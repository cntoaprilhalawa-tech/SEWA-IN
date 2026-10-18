<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up()
    {
        // ---- Users: verifikasi KTM/NIM, teguran, suspend ----
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'verification_status')) {
                // unverified | pending | verified | rejected
                $table->string('verification_status')->default('unverified');
            }
            if (!Schema::hasColumn('users', 'ktm_image')) {
                $table->string('ktm_image')->nullable();
            }
            if (!Schema::hasColumn('users', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'verification_note')) {
                $table->string('verification_note')->nullable();
            }
            if (!Schema::hasColumn('users', 'warning_count')) {
                $table->unsignedInteger('warning_count')->default(0);
            }
            if (!Schema::hasColumn('users', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'suspend_reason')) {
                $table->string('suspend_reason')->nullable();
            }
        });

        // Akun yang sudah ada sebelum fitur ini dianggap terverifikasi.
        DB::table('users')->where('verification_status', 'unverified')->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        // ---- Reviews: dua arah (peminjam -> pemilik dan pemilik -> peminjam) ----
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'direction')) {
                // renter_to_owner | owner_to_renter
                $table->string('direction')->default('renter_to_owner');
            }
            $table->unique(['booking_id', 'reviewer_id'], 'reviews_booking_reviewer_unique');
        });
        // Unique lama (satu review per booking) dilepas di langkah terpisah:
        // di MySQL index baru harus ada dulu supaya foreign key tetap punya index.
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_booking_id_unique');
        });

        // ---- Keluhan: bukti lampiran + tindakan admin ----
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'evidence')) {
                $table->string('evidence')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'admin_action')) {
                // none | warning | suspend | dismissed
                $table->string('admin_action')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'admin_note')) {
                $table->text('admin_note')->nullable();
            }
        });

        // ---- Notifikasi (tabel bawaan Laravel) ----
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // ---- Bukti bayar: pindahkan dari disk publik ke disk privat ----
        try {
            $paths = DB::table('bookings')->whereNotNull('payment_proof')->pluck('payment_proof');
            foreach ($paths as $path) {
                if (Storage::disk('public')->exists($path) && !Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->put($path, Storage::disk('public')->get($path));
                    Storage::disk('public')->delete($path);
                }
            }
        } catch (\Throwable $e) {
            // Gagal memindahkan tidak menggagalkan migrasi; controller tetap membaca dari kedua disk.
        }
    }

    public function down()
    {
        Schema::dropIfExists('notifications');

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn(['evidence', 'admin_action', 'admin_note']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique('booking_id', 'reviews_booking_id_unique');
        });
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_booking_reviewer_unique');
            $table->dropColumn('direction');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verification_status', 'ktm_image', 'verified_at', 'verification_note', 'warning_count', 'suspended_at', 'suspend_reason']);
        });
    }
};
