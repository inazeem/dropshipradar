<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Listing extends Model
{
    protected $fillable = [
        'user_id',
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
}
