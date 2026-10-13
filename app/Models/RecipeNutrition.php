<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeNutrition extends Model
{
    use HasFactory;

    public function nutritioningredients()
    {
        return $this->hasMany(RecepieIngredients::class, 'nutrition_id', 'id');
    }
}
