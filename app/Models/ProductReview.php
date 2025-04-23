<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rating',
        'review',
        'status',
    ];

    public $timestamps = false; // ✅ 停用 `updated_at`，保持方案2

    /**
     * 關聯到 `User` 表（用戶）
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // ✅ 關聯 `users` 表
    }
}
