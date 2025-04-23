<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    public $timestamps = false;
    protected $table = "members";
    protected $primaryKey = "member_id";
    protected $fillable = [
        "member_id",
        "name",
        "email",
        "status",
        "role",
    ];
}
