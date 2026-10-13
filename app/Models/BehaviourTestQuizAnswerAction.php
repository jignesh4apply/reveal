<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BehaviourTestQuizAnswerAction extends Model
{
    use HasFactory;

    protected $table = 'behaviour_test_quiz_answer_actions';

    protected $fillable = [
        'behaviour_test_quiz_answer_id',
        'behaviour_test_quiz_action_id',
    ];

    public function answer()
    {
        return $this->belongsTo(BehaviourTestQuizAnswer::class, 'behaviour_test_quiz_answer_id');
    }

    public function action()
    {
        return $this->belongsTo(BehaviourTestQuizAction::class, 'behaviour_test_quiz_action_id');
    }
}
