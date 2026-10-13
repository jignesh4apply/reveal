<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'session_exercise_id',
        'exercise_form',
        'rest_period',
        'duration',
        'repetition',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class, 'session_exercise_id');
    }
}
