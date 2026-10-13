<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\RecipeController as APIRecipeControllerV1;
use App\Http\Controllers\Controller;
use App\Models\FavouriteRecipe;
use App\Models\GroceryRecipe;
use App\Models\MealType;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RecipeController extends APIRecipeControllerV1
{
    public function userFavouriteList(Request $request)
    {
        try {
            // Validate pagination parameters
            $validated = $request->validate([
                'limit' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            $limit = (int) ($validated['limit'] ?? 15);
            $page = (int) ($validated['page'] ?? 1);

            // Fetch user's favourite recipes with pagination
            $getUserFavouriteList = FavouriteRecipe::with('recipe')
                ->where(['user_id' => Auth::user()->id])
                ->paginate($limit, ['*'], 'page', $page);

            $favRecipe = [];

            // If there are recipes, populate the favRecipe array
            if ($getUserFavouriteList->count() > 0) {
                foreach ($getUserFavouriteList as $fav) {
                    $mealTypes = MealType::whereIn('id', array_unique(explode("|", $fav['recipe']['meal_type_id'])))->pluck('meal_type', 'id')->toArray();

                    $mealTypeIds = explode("|", $fav['recipe']['meal_type_id']);
                    $mealTitle = array_map(fn($id) => $mealTypes[$id] ?? 'Unknown', $mealTypeIds);
                    $mealType = implode("|", $mealTitle);

                    $isRecipeAdded = GroceryRecipe::where([
                        'user_id' => Auth::user()->id,
                        'recipe_id' => $fav['recipe_id'],
                    ])->exists() ? 1 : 0;

                    $recipe = Recipe::find($fav['recipe_id']);

                    // Handle the nutrition values - if any are null, replace with "0.00"
                    $nutritionData = $fav['recipe']['nutrition'] ?? [];
                    $nutrition = [];

                    // If there is nutrition data, loop through each record and check for null
                    foreach ($nutritionData as $nutrient) {
                        $nutrition[] = [
                            'id' => $nutrient['id'],
                            'kcal' => $nutrient['kcal'] ?? '0.00',
                            'protien' => $nutrient['protien'] ?? '0.00',
                            'fat' => $nutrient['fat'] ?? '0.00',
                            'carbs' => $nutrient['carbs'] ?? '0.00',
                        ];
                    }

                    $favRecipe[] = [
                        'favId'             => $fav['id'],
                        'userName'          => Auth::user()->name,
                        'recipeId'          => $fav['recipe_id'],
                        'recipeName'        => $fav['recipe']['title'],
                        'tags'              => explode(",", $fav['recipe']['tags']),
                        'isRecipeAdded'     => $isRecipeAdded,
                        'cooking_time'      => $recipe['cooking_time'],
                        'no_of_person'      => $recipe['no_of_person'],
                        'mealType'          => $mealType,
                        'recipeImage'       => asset(Storage::url($fav['recipe']['picture'])),
                        'freeAccess'        => $fav['recipe']['free_access'],
                        'recipeNutrition'   => $nutrition, // Set nutrition data here
                    ];
                }
            }

            // Prepare the pagination response with or without recipes
            $response = [
                'recipes' => $favRecipe,
                'current_page' => $getUserFavouriteList->currentPage(),
                'total_pages' => $getUserFavouriteList->lastPage(),
                'total_items' => $getUserFavouriteList->total(),
            ];

            return $this->successResponse("Favourite Recipes", $response, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}
