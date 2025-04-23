<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormQuestion extends Model
{
    use HasFactory;

    protected $table = 'form_question'; // 指定表名（可省略，若符合 Laravel 约定）

    protected $fillable = ['question', 'option1', 'option2', 'option3', 'title_id', 'goal_id','value_option1','value_option2','value_option3'];

    // 关联 FormTitle (多对一)
    public function title()
    {
        return $this->belongsTo(FormTitle::class, 'title_id', 'id')->select(['id','title_name','weight_1','weight_2','weight_3','weight_4','weight_5']);
    }
    
    // 关联 FormGoal (多对一)
    public function goal()
    {
        return $this->belongsTo(FormGoal::class, 'goal_id', 'id');
    }
}
