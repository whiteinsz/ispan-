<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shopping_cart extends Model
{
    protected $fillable = ['user_id', 'product_id', 'quantity'];

    // 定義關聯到 Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
