<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'total_price', 'status','receivers','address','zipcode','phone','order_no'];

    public function orderitems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
