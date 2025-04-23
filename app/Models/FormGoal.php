<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormGoal extends Model
{
    use HasFactory;

    protected $table = 'form_goal';

    protected $fillable = ['goal_name'];

    // 关联 FormQuestion (一对多)
    public function questions()
    {
        return $this->hasMany(FormQuestion::class, 'goal_id', 'id');
    }
}
