<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memindahkan data dari file SQLite lama ke database yang sedang aktif di .env (mis. MySQL di WAMP).
 * File SQLite hanya DIBACA, tidak diubah, jadi aman dan bisa diulang.
 */
class CopySqliteToMysql extends Command
{
    protected $signature = 'sewain:copy-sqlite
                            {path=database/database.sqlite : Lokasi file SQLite lama (relatif terhadap folder proyek)}
                            {--force : Kosongkan tabel tujuan terlebih dahulu jika sudah berisi data}';

    protected $description = 'Salin semua data dari file SQLite lama ke database aktif (MySQL). SQLite hanya dibaca.';

    /** Urutan penting karena ada relasi antar tabel. */
    private array $tables = [
        'users', 'categories', 'items', 'item_images', 'bookings',
        'reviews', 'complaints', 'notifications', 'personal_access_tokens', 'password_resets',
    ];

    public function handle(): int
    {
        if (config('database.default') === 'sqlite') {
            $this->error('Database aktif di .env masih SQLite. Ubah dulu DB_CONNECTION=mysql (lihat PINDAH-KE-MYSQL.txt), lalu jalankan: php artisan config:clear');
            return self::FAILURE;
        }

        $arg = $this->argument('path');
        $file = is_file($arg) ? $arg : base_path($arg);
        if (!is_file($file)) {
            $this->error("File SQLite tidak ditemukan: {$file}");
            return self::FAILURE;
        }

        config(['database.connections.sqlite_lama' => [
            'driver' => 'sqlite',
            'database' => $file,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        $old = DB::connection('sqlite_lama');
        $new = DB::connection();
        $oldSchema = $old->getSchemaBuilder();
        $newSchema = $new->getSchemaBuilder();

        foreach ($this->tables as $table) {
            if (!$newSchema->hasTable($table)) {
                $this->error("Tabel '{$table}' belum ada di database tujuan. Jalankan dulu: php artisan migrate");
                return self::FAILURE;
            }
        }

        $filled = $new->table('users')->count() > 0;
        if ($filled && !$this->option('force')) {
            $this->warn('Database tujuan sudah berisi data (tabel users tidak kosong).');
            $this->warn('Jika ingin menimpanya dengan data dari SQLite, jalankan: php artisan sewain:copy-sqlite --force');
            return self::FAILURE;
        }

        // Longgarkan mode ketat MySQL hanya untuk sesi penyalinan ini
        if ($new->getDriverName() === 'mysql') {
            $new->statement("SET SESSION sql_mode=''");
        }

        Schema::disableForeignKeyConstraints();
        $rows = [];

        try {
            foreach ($this->tables as $table) {
                if (!$oldSchema->hasTable($table)) {
                    $rows[] = [$table, '-', '-', 'tidak ada di SQLite'];
                    continue;
                }

                $cols = array_values(array_intersect(
                    $oldSchema->getColumnListing($table),
                    $newSchema->getColumnListing($table)
                ));

                // TRUNCATE di MySQL otomatis menutup transaksi (commit implisit), jadi dilakukan SEBELUM transaksi dimulai.
                $new->table($table)->truncate();

                $new->transaction(function () use ($old, $new, $table, $cols, &$rows) {
                    $batch = [];
                    $total = 0;
                    foreach ($old->table($table)->select($cols)->cursor() as $row) {
                        $batch[] = $this->clean((array) $row);
                        $total++;
                        if (count($batch) >= 200) {
                            $new->table($table)->insert($batch);
                            $batch = [];
                        }
                    }
                    if ($batch) {
                        $new->table($table)->insert($batch);
                    }

                    $inNew = $new->table($table)->count();
                    $rows[] = [$table, $total, $inNew, $total === $inNew ? 'OK' : 'JUMLAH BEDA'];
                });
            }
        } catch (\Throwable $e) {
            Schema::enableForeignKeyConstraints();
            $this->error('Gagal menyalin: ' . $e->getMessage());
            $this->line('Data SQLite lama tidak berubah. Tabel MySQL mungkin terisi sebagian; setelah masalahnya diperbaiki, jalankan ulang dengan: php artisan sewain:copy-sqlite --force');
            return self::FAILURE;
        }

        Schema::enableForeignKeyConstraints();

        $this->table(['Tabel', 'Di SQLite', 'Di MySQL', 'Status'], $rows);
        $this->info('Selesai. Data lama sudah dipindahkan ke database aktif.');

        return self::SUCCESS;
    }

    /** Teks kosong pada kolom tanggal diubah menjadi NULL agar diterima MySQL. */
    private function clean(array $row): array
    {
        foreach ($row as $col => $value) {
            if ($value === '' && (str_ends_with($col, '_at') || str_ends_with($col, '_date') || $col === 'date')) {
                $row[$col] = null;
            }
        }
        return $row;
    }
}
