<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLevelReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'level',
        'report_type',
        'report_id',
        'report_category',
    ];
}
