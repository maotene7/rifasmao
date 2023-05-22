<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plans extends Model
{
    protected $guarded = ['id'];
    protected $table = "plans";

    protected $casts = [
        'user_data' => 'object',
    ];
}
