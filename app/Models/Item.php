<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price_per_day',
        'deposit',
        'condition',
        'latitude',
        'longitude',
        'address',
        'is_available',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'category_id' => 'integer',
        'price_per_day' => 'decimal:2',
        'deposit' => 'decimal:2',
        'is_available' => 'boolean',
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
    ];

    /**
     * Get the user that owns the item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the owner alias that owns the item.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the category of the item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all images for the item.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ItemImage::class);
    }

    /**
     * Get the primary image for the item.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ItemImage::class)->where('is_primary', true);
    }

    /**
     * Get all bookings for the item.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get all reviews for the item.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_hidden', false)->where('direction', 'renter_to_owner');
    }

    /**
     * Get the average rating of the item.
     */
    public function getAverageRatingAttribute(): float
    {
        $avg = $this->reviews()->avg('rating');

        return $avg !== null ? round((float) $avg, 1) : 0.0;
    }

    /**
     * Check if item is available for a specific date range.
     *
     * @param string|\DateTimeInterface $startDate
     * @param string|\DateTimeInterface $endDate
     * @return bool
     */
    public function isAvailableForDates($startDate, $endDate): bool
    {
        if (!$this->is_available) {
            return false;
        }

        $conflict = $this->bookings()
            ->whereIn('status', ['approved', 'active'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
            })
            ->exists();

        return !$conflict;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Retrieve the model for a bound value (supports both slug and id).
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('slug', $value)
            ->orWhere('id', $value)
            ->firstOrFail();
    }
}
