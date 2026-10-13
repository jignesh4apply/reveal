<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BehaviourTestQuizAction extends Model
{
    use HasFactory;

    protected $table = 'quiz_actions';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'tag',
        'video_link',
        'extra_link',
        'action_button_text',
        'trigger_email',
        'is_active',
    ];
}
