<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function category()
    {
        // Product 屬於一個 Category 
        return $this->belongsTo(Category::class);
    }
    // ProductReview (model檔案名稱)
    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function productImageGalleries()
    {
        return $this->hasMany(ProductImageGallery::class);
    }
}
