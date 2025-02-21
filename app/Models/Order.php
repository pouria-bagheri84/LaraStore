<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'stripe_session_id',
        'user_id',
        'total_price',
        'status',
        'online_payment_commission',
        'website_commission',
        'vendor_subtotal',
        'payment_intent',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendorUser()
    {
        return $this->belongsTo(User::class, 'vendor_user_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_user_id','user_id');
    }
}
