<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BehaviourTestPointsResultRange extends Model
{
    use HasFactory;

    protected $table = 'behaviour_test_points_result_ranges';

    protected $fillable = [
        'behaviour_test_points_result_id',
        'range_start',
        'range_end',
        'result_text',
        'range_order',
    ];

    public function pointsResult()
    {
        return $this->belongsTo(BehaviourTestPointsResult::class, 'behaviour_test_points_result_id');
    }
}
