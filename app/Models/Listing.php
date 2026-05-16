<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Listing extends Model
{
    protected $fillable = [
        'user_id',
        'image_url',
        'adjustment_percentage',
        'ebay_url',
        'amazon_url',
        'ebay_price',
        'amazon_price',
        'ebay_fee',
        'profit',
        'roi',
        'status',
        'listed_on',
        'notes',
    ];

    protected $casts = [
        'adjustment_percentage' => 'decimal:2',
        'ebay_price' => 'decimal:2',
        'amazon_price' => 'decimal:2',
        'ebay_fee' => 'decimal:2',
        'profit' => 'decimal:2',
        'roi' => 'decimal:2',
        'listed_on' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function amazonThumbnailUrl(): ?string
    {
        return filter_var($this->image_url, FILTER_VALIDATE_URL) ? $this->image_url : null;
    }

    private static function extractAmazonAsin(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        preg_match('/(?:dp|gp\/product|gp\/aw\/d|product)\/([A-Z0-9]{10})/i', $url, $matches);

        return isset($matches[1]) ? strtoupper($matches[1]) : null;
    }
}
