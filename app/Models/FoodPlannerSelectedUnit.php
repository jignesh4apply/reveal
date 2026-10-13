<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodPlannerSelectedUnit extends Model
{
    use HasFactory;
    protected $fillable = [
        'food_planner_id',
        'selected_unit_json'
    ];
    protected $casts = [
        'selected_unit_json' => 'array',
    ];
}
