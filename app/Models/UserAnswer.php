<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    use HasFactory;

    protected $table = 'user_answers';

    protected $fillable = ['user_id', 'question_id', 'answer', 'values', 'weights'];

    protected $casts = [
        'values' => 'array',
        'weights' => 'array',
    ];


    // 關聯 User (多對一)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
