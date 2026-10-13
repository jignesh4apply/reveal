<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BehaviourTestQuizAnswer extends Model
{
    use HasFactory;

    protected $table = 'behaviour_test_quiz_answers';

    protected $fillable = [
        'behaviour_test_quiz_id',
        'answer',
        'points',
    ];

    protected $casts = [
        'points' => 'float',
    ];

    public function quiz()
    {
        return $this->belongsTo(BehaviourTestQuiz::class, 'behaviour_test_quiz_id');
    }

    public function answerActions()
    {
        return $this->hasMany(BehaviourTestQuizAnswerAction::class, 'behaviour_test_quiz_answer_id');
    }

    public function actions()
    {
        return $this->belongsToMany(
            BehaviourTestQuizAction::class,
            'behaviour_test_quiz_answer_actions',
            'behaviour_test_quiz_answer_id',
            'behaviour_test_quiz_action_id'
        );
    }
}
