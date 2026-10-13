<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BehaviourTestPointsResult extends Model
{
    use HasFactory;

    protected $table = 'behaviour_test_points_results';

    protected $fillable = [
        'behaviour_test_quiz_group_id',
        'slug',
        'is_active',
    ];

    public function group()
    {
        return $this->belongsTo(BehaviourTestQuizGroup::class, 'behaviour_test_quiz_group_id');
    }

    public function ranges()
    {
        return $this->hasMany(BehaviourTestPointsResultRange::class, 'behaviour_test_points_result_id')
            ->orderBy('range_order', 'asc');
    }
}
