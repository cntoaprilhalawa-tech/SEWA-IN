<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    /** Status yang memblokir tanggal (pending ikut memblokir agar tidak terjadi double booking). */
    public const BLOCKING_STATUSES = ['pending', 'approved', 'active'];

    /** Tarif denda keterlambatan per hari (Rp). */
    public const LATE_FEE_PER_DAY = 15000;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_code',
        'item_id',
        'renter_id',
        'owner_id',
        'start_date',
        'end_date',
        'total_days',
        'price_per_day',
        'total_price',
        'deposit',
        'status',
        'note',
        'cancelled_reason',
        'payment_proof',
        'payment_method',
        'payment_status',
        'paid_at',
        'returned_at',
        'late_days',
        'late_fee',
        'damage_note',
        'deposit_deduction',
        'deposit_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'item_id' => 'integer',
        'renter_id' => 'integer',
        'owner_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'integer',
        'price_per_day' => 'decimal:2',
        'total_price' => 'decimal:2',
        'deposit' => 'decimal:2',
        'paid_at' => 'datetime',
        'returned_at' => 'date',
        'late_days' => 'integer',
        'late_fee' => 'decimal:2',
        'deposit_deduction' => 'decimal:2',
    ];

    /**
     * Get the item associated with this booking.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the renter of the item.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return ['cash' => 'Tunai (COD)', 'transfer' => 'Transfer bank', 'qris' => 'QRIS'][$this->payment_method ?? 'transfer'] ?? 'Transfer bank';
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    /**
     * Get the owner of the item.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the review for this booking.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Generate unique booking code.
     *
     * @return string
     */
    public static function generateBookingCode(): string
    {
        do {
            $code = 'SWI-' . strtoupper(Str::random(8));
        } while (static::where('booking_code', $code)->exists());

        return $code;
    }

    /**
     * Scope a query to only include active/approved bookings.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['approved', 'active']);
    }

    /**
     * Scope: booking yang memblokir tanggal (pending, approved, active).
     */
    /**
     * Ulasan dua arah: satu dari peminjam dan satu dari pemilik.
     */
    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', self::BLOCKING_STATUSES);
    }

    public function getDepositStatusLabelAttribute(): string
    {
        return [
            'none' => 'Tanpa deposit',
            'held' => 'Ditahan',
            'returned' => 'Dikembalikan penuh',
            'deducted' => 'Dipotong (kerusakan)',
        ][$this->deposit_status ?? 'held'] ?? 'Ditahan';
    }

    /**
     * Check if booking status is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if booking status is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if booking status is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if booking status is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if booking status is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if booking status is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
