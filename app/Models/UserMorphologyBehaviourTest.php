<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMorphologyBehaviourTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ques_ans_json',
        'report_json',
        'status',
        'report_generated_at',
        'report_screenshots',
    ];

    protected $casts = [
        'ques_ans_json' => 'array',
        'report_json' => 'array',
        'report_screenshots' => 'array',
    ];

}
