<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nim',
        'phone',
        'avatar',
        'bio',
        'latitude',
        'longitude',
        'address',
        'bank_name',
        'qris_image',
        'account_number',
        'ktm_image',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'verified_at' => 'datetime',
        'suspended_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'average_rating',
        'avatar_url',
    ];

    /**
     * Get the items listed by the user.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'user_id');
    }

    /**
     * Get bookings where this user is the renter.
     */
    public function bookingsAsRenter(): HasMany
    {
        return $this->hasMany(Booking::class, 'renter_id');
    }

    /**
     * Alias for bookings as renter.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'renter_id');
    }

    /**
     * Get bookings for items owned by this user.
     */
    public function bookingsAsOwner(): HasMany
    {
        return $this->hasMany(Booking::class, 'owner_id');
    }

    /**
     * Get reviews submitted by this user.
     */
    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /**
     * Get reviews received by this user.
     */
    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id')->where('is_hidden', false);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function verificationLabel(): string
    {
        return [
            'unverified' => 'Belum diverifikasi',
            'pending' => 'Menunggu verifikasi',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
        ][$this->verification_status ?? 'unverified'] ?? 'Belum diverifikasi';
    }

    /**
     * Kirim notifikasi dalam aplikasi.
     */
    public function notifyApp(string $title, string $body, ?string $url = null): void
    {
        $this->notify(new \App\Notifications\AppNotification($title, $body, $url));
    }

    /** Batas teguran sebelum akun otomatis disuspend. */
    public const MAX_WARNINGS = 3;

    public function applyWarning(string $reason): void
    {
        $this->warning_count = ($this->warning_count ?? 0) + 1;
        $this->save();

        $this->notifyApp('Teguran dari admin', $reason . ' (teguran ke-' . $this->warning_count . ' dari ' . self::MAX_WARNINGS . ')');

        if ($this->warning_count >= self::MAX_WARNINGS && !$this->isSuspended()) {
            $this->suspendAccount('Mencapai batas ' . self::MAX_WARNINGS . ' teguran.');
        }
    }

    public function suspendAccount(string $reason): void
    {
        $this->suspended_at = now();
        $this->suspend_reason = $reason;
        $this->save();

        $this->notifyApp('Akun disuspend', $reason);
    }

    public function unsuspendAccount(): void
    {
        $this->suspended_at = null;
        $this->suspend_reason = null;
        $this->save();

        $this->notifyApp('Suspend dicabut', 'Akun Anda sudah aktif kembali.');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Mode aktif akun ini. Admin selalu 'admin'. Akun biasa bisa berperan sebagai
     * peminjam maupun pemilik; mode aktif disimpan di kolom users.active_mode.
     * Tanpa pilihan, dipakai peran awal dari pendaftaran.
     */
    public function activeRole(): string
    {
        if ($this->role === 'admin') {
            return 'admin';
        }

        // Mode aktif disimpan di database, jadi tidak hilang saat login ulang atau ganti perangkat.
        // Tanpa pilihan, dipakai peran awal dari pendaftaran.
        $mode = $this->active_mode;

        return in_array($mode, ['peminjam', 'pemilik'], true)
            ? $mode
            : ($this->role === 'pemilik' ? 'pemilik' : 'peminjam');
    }

    public function isPemilik(): bool
    {
        return $this->activeRole() === 'pemilik';
    }

    public function isPeminjam(): bool
    {
        return $this->activeRole() === 'peminjam';
    }

    /**
     * Nama route halaman utama sesuai peran (aktor).
     */
    public function homeRoute(): string
    {
        if ($this->isAdmin()) {
            return 'admin.moderation';
        }

        // Peminjam dan pemilik masing-masing punya dashboard sendiri (isinya mengikuti mode aktif).
        return 'dashboard';
    }

    public function roleLabel(): string
    {
        return ['admin' => 'Admin', 'pemilik' => 'Pemilik Barang'][$this->activeRole()] ?? 'Peminjam';
    }

    /**
     * Calculate average rating received by this user.
     */
    public function getAverageRatingAttribute(): float
    {
        $avg = $this->reviewsReceived()->avg('rating');

        return $avg !== null ? round((float) $avg, 1) : 0.0;
    }

    /**
     * Get the user's avatar URL or default placeholder.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
                return $this->avatar;
            }
            return Storage::url($this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?? 'User') . '&background=0D8ABC&color=fff';
    }
}
