<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BehaviourTestQuizGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'quiz_group_order',
        'quiz_group_image',
    ];

    public function quizzes()
    {
        return $this->hasMany(BehaviourTestQuiz::class, 'behaviour_test_quiz_group_id');
    }
}
