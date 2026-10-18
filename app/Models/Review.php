<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'reviewer_id',
        'reviewee_id',
        'item_id',
        'rating',
        'comment',
        'direction',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'booking_id' => 'integer',
        'reviewer_id' => 'integer',
        'reviewee_id' => 'integer',
        'item_id' => 'integer',
        'rating' => 'integer',
        'is_hidden' => 'boolean',
    ];

    /**
     * Get the booking that was reviewed.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who submitted the review.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the user who received the review.
     */
    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    /**
     * Get the item being reviewed.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
