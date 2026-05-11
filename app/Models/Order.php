<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_date',
        'buyer_name',
        'ebay_order_no',
        'amazon_order_no',
        'note',
        'status',
        'amazon_cost',
        'ebay_receipts',
        'profit',
        'roi',
    ];

    protected $casts = [
        'order_date'    => 'date',
        'amazon_cost'   => 'decimal:2',
        'ebay_receipts' => 'decimal:2',
        'profit'        => 'decimal:2',
        'roi'           => 'decimal:2',
    ];

    public const STATUSES = [
        'Delivered',
        'Order Placed',
        'Refunded',
        'Out of Stock',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
