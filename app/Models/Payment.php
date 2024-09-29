<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'pidx', 'purchase_order_id', 'transaction_id', 'status', 'amount', 'mobile'
    ];

    // Define relationship with orders if needed
    public function order()
    {
        return $this->belongsTo(Order::class, 'purchase_order_id', 'order_id');
    }
}
