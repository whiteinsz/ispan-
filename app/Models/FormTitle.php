<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormTitle extends Model
{
    use HasFactory;

    protected $table = 'form_title';

    protected $fillable = ['title_name', 'weight_1', 'weight_2', 'weight_3', 'weight_4', 'weight_5'];


    // 关联 FormQuestion (一对多)
    public function questions()
    {
        return $this->hasMany(FormQuestion::class, 'title_id', 'id');
    }
}
