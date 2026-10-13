<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BehaviourTestQuiz extends Model
{
    use HasFactory;

    protected $table = 'behaviour_test_quizzes';

    protected $fillable = [
        'question',
        'behaviour_test_quiz_group_id',
        'amplitude_word',
        'slug',
        'quiz_position',
        'is_active',
        'is_multiple_choice',
    ];

    public function group()
    {
        return $this->belongsTo(BehaviourTestQuizGroup::class, 'behaviour_test_quiz_group_id');
    }

    public function answers()
    {
        return $this->hasMany(BehaviourTestQuizAnswer::class, 'behaviour_test_quiz_id');
    }
}
