<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Verifikasi "saya bukan robot" tanpa layanan pihak ketiga (pola proof-of-work, seperti ALTCHA).
 *
 * Alur:
 *  1. Server membuat tantangan: salt (berisi waktu kedaluwarsa) + angka rahasia N,
 *     lalu mengirim hash sha256(salt . N) dan tanda tangan HMAC-nya. N tidak dikirim.
 *  2. Browser mencari N dengan mencoba satu per satu (butuh sekitar 0,1 - 0,5 detik),
 *     lalu mengirim kembali salt, N, challenge, dan signature saat form disubmit.
 *  3. Server memeriksa tanda tangan, kedaluwarsa, kecocokan hash, dan memastikan
 *     tantangan hanya dipakai sekali (mencegah replay).
 *
 * Satu pengunjung manusia hanya perlu menyelesaikan satu tantangan; bot yang mengirim
 * ribuan percobaan harus membayar biaya komputasi yang sama untuk setiap percobaan.
 */
class HumanCheck
{
    /** Batas atas angka rahasia (rata-rata ~25.000 percobaan hash di browser). */
    public const MAX_NUMBER = 50000;

    /** Masa berlaku tantangan, dalam detik. */
    public const TTL = 600;

    private static function key(): string
    {
        return hash('sha256', 'sewain-human-check|' . config('app.key'));
    }

    public static function challenge(): array
    {
        $salt = bin2hex(random_bytes(10)) . '.' . (time() + self::TTL);
        $number = random_int(1000, self::MAX_NUMBER);
        $challenge = hash('sha256', $salt . $number);

        return [
            'salt' => $salt,
            'challenge' => $challenge,
            'signature' => hash_hmac('sha256', $challenge, self::key()),
            'maxnumber' => self::MAX_NUMBER,
        ];
    }

    public static function verify($payload): bool
    {
        if (!is_string($payload) || $payload === '' || strlen($payload) > 2000) {
            return false;
        }

        $json = base64_decode($payload, true);
        $data = $json !== false ? json_decode($json, true) : null;
        if (!is_array($data)) {
            return false;
        }

        foreach (['salt', 'number', 'challenge', 'signature'] as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        $salt = (string) $data['salt'];
        $challenge = (string) $data['challenge'];
        $signature = (string) $data['signature'];
        $number = (int) $data['number'];

        // Kedaluwarsa (waktu ada di dalam salt, jadi ikut terlindungi oleh hash)
        $parts = explode('.', $salt);
        if (count($parts) !== 2 || !ctype_digit($parts[1]) || (int) $parts[1] < time()) {
            return false;
        }

        // Tanda tangan harus asli buatan server ini
        if (!hash_equals(hash_hmac('sha256', $challenge, self::key()), $signature)) {
            return false;
        }

        // Jawaban harus benar
        if ($number < 0 || $number > self::MAX_NUMBER) {
            return false;
        }
        if (!hash_equals($challenge, hash('sha256', $salt . $number))) {
            return false;
        }

        // Sekali pakai: Cache::add mengembalikan false bila kunci sudah ada
        return Cache::add('human-check:' . $challenge, 1, self::TTL + 60);
    }

    /** Aturan validasi untuk dipakai di $request->validate(). */
    public static function rule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (!self::verify($value)) {
                $fail('Verifikasi "saya bukan robot" gagal atau sudah kedaluwarsa. Centang lagi lalu coba kembali.');
            }
        };
    }
}
