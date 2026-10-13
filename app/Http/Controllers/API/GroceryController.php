<?php

namespace App\Http\Controllers\API;

use App\Models\CommonGroceryIngredient;
use App\Models\GroceryRecipe;
use App\Models\IngredientCategory;
use App\Models\RecepieIngredients;
use App\Models\Recipe;
use App\Models\RecipeNutrition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class GroceryController extends ApiController
{

    public function addIngredientsToGrocery(Request $request)
    {
        try {
            // Validation
            $request->validate([
                'recipe_id' => ['required', 'numeric'],
                'no_of_person' => ['nullable', 'numeric'],
                'nutrition_id' => ['required', 'numeric'],
                'is_add_from_grocery' => ['nullable', 'numeric', 'in:1']
            ], [
                'recipe_id.required' => 'Recipe Id is required',
                'recipe_id.numeric' => 'Recipe Id should be a number',
                'no_of_person.numeric' => 'Persons should be a number',
                'nutrition_id.required' => 'Nutrition Id field is required',
                'nutrition_id.numeric' => 'Nutrition Id should be a number',
                'is_add_from_grocery.numeric' => 'Is add from grocery field should be a number',
                'is_add_from_grocery.in' => 'Is add from grocery field should have value as 1 only',
            ]);

            if (isset($request->is_add_from_grocery) || !empty($request->is_add_from_grocery)) {
                $groceryRecipes = GroceryRecipe::where(['recipe_id' => $request->recipe_id, 'nutrition_id' => $request->nutrition_id])->count();
                if ($groceryRecipes == 0) {
                    return $this->notFoundResponse('Grocery recipe not found', (object)[], 200);
                }
            }

            // Retrieve recipe nutrition
            $recipeNutrition = RecipeNutrition::where([
                'recipe_id' => $request->recipe_id,
                'id' => $request->nutrition_id,
            ])->first();
            if (!$recipeNutrition) {
                return $this->notFoundResponse('Recipe not found', (object)[], 200);
            }

            // Retrieve recipe ingredients
            $recipeIngredients = RecepieIngredients::where([
                'recepie_id' => $request->recipe_id,
                'nutrition_id' => $request->nutrition_id,
            ])->get();

            foreach ($recipeIngredients as $ingredient) {
                $numberOfPersons = $request->no_of_person ?? $this->getDefaultNumberOfPersons($request->recipe_id);
                $commonGrocery = $this->findCommonGroceryList($request->recipe_id, $ingredient);

                if ($commonGrocery) {
                    if ((isset($request->is_add_from_grocery) || !empty($request->is_add_from_grocery)) && $request->is_add_from_grocery === 1) {
                        $quantity = $commonGrocery->ingredient_quantity + $ingredient->quantity;
                        $numberOfPersons = $commonGrocery->no_of_person + 1;
                    } else {
                        if (isset($request->no_of_person) || !empty($request->no_of_person)) {
                            $quantity = $commonGrocery->ingredient_quantity + ($ingredient->quantity * $request->no_of_person);
                            $numberOfPersons += $commonGrocery->no_of_person;
                        } else {
                            $quantity = $commonGrocery->ingredient_quantity + $ingredient->quantity;
                            $numberOfPersons += $commonGrocery->no_of_person;
                        }
                    }

                    $commonGrocery->update([
                        'ingredient_quantity' => $quantity,
                        'no_of_person' => $numberOfPersons,
                        'isPurchased' => 0,
                    ]);
                } else {
                    if (isset($request->no_of_person) || !empty($request->no_of_person)) {
                        $quantity = $ingredient->quantity * $request->no_of_person;
                    }
                    CommonGroceryIngredient::create([
                        'recipe_id' => $request->recipe_id,
                        'recipe_ingredient_id' => $ingredient->ingredient_id,
                        'ingredient_category_id' => $ingredient->category_id,
                        'ingredient_nutrition_id' => $ingredient->nutrition_id,
                        'user_id' => Auth::user()->id,
                        'ingredient_name' => $ingredient->name,
                        'ingredient_quantity' => $quantity,
                        'ingredient_unit' => $ingredient->unit,
                        'no_of_person' => $numberOfPersons,
                        'isPurchased' => 0,
                    ]);
                }
            }

            // Add recipe to grocery
            $this->addRecipeToGrocery($request->recipe_id, $request->nutrition_id, $request->no_of_person);

            $response = $this->getIgredientCategoryWiseIngredients();
            return $this->getSingleResponse('Ingredient added to grocery list', $response, 200);
        } catch (\Throwable $th) {
            return $this->throwableError('Something went wrong', $th->getMessage(), 500);
            die;
        }
    }

    private function findCommonGroceryList($recipeId, $ingredient)
    {
        // Check if ingredient array has the necessary keys
        if (!isset($ingredient['ingredient_id']) || !isset($ingredient['category_id'])) {
            // Log or return an error if keys are missing
            Log::error('Ingredient array does not have the expected keys', ['ingredient' => $ingredient]);
            return null;
        }

        return CommonGroceryIngredient::where([
            'user_id' => Auth::user()->id,
            'recipe_id' => $recipeId,
            'recipe_ingredient_id' => $ingredient['ingredient_id'],
            'ingredient_category_id' => $ingredient['category_id'],
            'ingredient_nutrition_id' => $ingredient['nutrition_id']
        ])->first();
    }

    private function addRecipeToGrocery($recipeId, $nutritionId, $no_of_person)
    {
        $groceryRecipe = GroceryRecipe::firstOrNew(
            [
                'user_id' => Auth::user()->id,
                'recipe_id' => $recipeId,
                'nutrition_id' => $nutritionId
            ]
        );
        
        $groceryRecipe->no_of_person = ($groceryRecipe->exists ? $groceryRecipe->no_of_person : 0) + $no_of_person;
        
        $groceryRecipe->save();
        
        return $groceryRecipe;
    }

    private function getDefaultNumberOfPersons($recipeId)
    {
        $recipe = Recipe::find($recipeId);
        if ($recipe->no_of_person == 0) {
            return 1;
        } else {
            return $recipe->no_of_person;
        }
    }

    public function removeRecipeFromGrocery(Request $request)
    {
        try {
            // Validate Data
            $request->validate([
                'recipe_id' => ['required', 'numeric'],
                'nutrition_id' => ['nullable', 'numeric'],
            ], [
                'recipe_id.required' => 'Recipe id is required',
                'recipe_id.numeric' => 'Recipe id should be in numbers',
                'nutrition_id.required' => 'Nutrition id is required',
                'nutrition_id.numeric' => 'Nutrition id should be in numbers',
            ]);

            // Check if grocery exists
            if (isset($request->nutrition_id) || !empty($request->nutrition_id)) {
                $groceryCount = CommonGroceryIngredient::where(['recipe_id' => $request->recipe_id, 'ingredient_nutrition_id' => $request->nutrition_id])
                    ->where('user_id', Auth::user()->id)
                    ->count();
            } else {
                $groceryCount = CommonGroceryIngredient::where('recipe_id', $request->recipe_id)
                    ->where('user_id', Auth::user()->id)
                    ->count();
            }
            if ($groceryCount > 0) {
                // If there are matching ingredients, proceed to delete or update them
                if (isset($request->nutrition_id) || !empty($request->nutrition_id)) {
                    // Get Recipe Ingredients matching the recipe_id and nutrition_id
                    $nutritionWiseRecipeIngredients = RecepieIngredients::where(['recepie_id' => $request->recipe_id, 'nutrition_id' => $request->nutrition_id])->get();

                    if (!$nutritionWiseRecipeIngredients->isEmpty()) {
                        // Get Grocery Ingredients matching the recipe_id and nutrition_id
                        $getGroceryIngredients = CommonGroceryIngredient::where(['recipe_id' => $request->recipe_id, 'ingredient_nutrition_id' => $request->nutrition_id])->get();

                        foreach ($nutritionWiseRecipeIngredients as $recipeIngredient) {
                            foreach ($getGroceryIngredients as $groceryIngredient) {
                                if ($recipeIngredient['ingredient_id'] == $groceryIngredient['recipe_ingredient_id']) {
                                    // Update grocery ingredient quantity and number of persons
                                    $groceryIngredientQuantity = $groceryIngredient['ingredient_quantity'] - $recipeIngredient['quantity'];
                                    $groceryNumberOfPerson = $groceryIngredient['no_of_person'] - 1;

                                    CommonGroceryIngredient::where(['id' => $groceryIngredient['id']])->update([
                                        'ingredient_quantity' => $groceryIngredientQuantity,
                                        'no_of_person' => $groceryNumberOfPerson
                                    ]);

                                    // Check if ingredient quantity is zero or negative, and delete if necessary
                                    if ($groceryIngredientQuantity <= 0) {
                                        $groceryIngredient->delete();

                                        GroceryRecipe::where(['recipe_id' => $request->recipe_id, 'nutrition_id' => $request->nutrition_id])->delete();
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // If nutrition_id is not provided, delete all grocery ingredients for the recipe
                    CommonGroceryIngredient::where(['recipe_id' => $request->recipe_id, 'user_id' => Auth::user()->id])->delete();
                    GroceryRecipe::where(['recipe_id' => $request->recipe_id])->delete();
                }

                $response = $this->getIgredientCategoryWiseIngredients();

                return $this->successResponse("Grocery recipe removed successfully", $response, 200);
            } else {
                return $this->errorResponse("Grocery Recipe Not Found.", [], 200);
            }
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function markGroceryAsPurchased(Request $request)
    {
        try {
            // Validation

            $validation = [
                'recipe_ingredient_id' => ['nullable', 'numeric'],
                'grocery_id' => ['nullable', 'numeric'],
                'isPurchased' => ['required', 'numeric', 'in:0,1']
            ];

            $message = [
                'recipe_ingredient_id.numeric' => 'Grocery Recipe Ingredient Id should be a number',
                'grocery_id.numeric' => 'Grocery ingredient id should be a number',
                'isPurchased.numeric' => 'Is purchased should be a number',
                'isPurchased.required' => 'Is purchased is required',
            ];

            if ((!isset($request->recipe_ingredient_id) || empty($request->recipe_ingredient_id)) && (!isset($request->grocery_id) || empty($request->grocery_id))) {
                $message = "Please provide grocery_id or recipe_ingredient_id any one of them";
                return $this->validationError("Fail", $message, 200);
            }

            $validator = Validator::make($request->all(), $validation, $message);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            if (isset($request->recipe_ingredient_id) && !empty($request->recipe_ingredient_id)) {
                $groceryCount = CommonGroceryIngredient::where(['recipe_ingredient_id' => $request->recipe_ingredient_id, 'user_id' => Auth::user()->id])->count();
                if ($groceryCount > 0) {
                    CommonGroceryIngredient::where(['recipe_ingredient_id' => $request->recipe_ingredient_id, 'user_id' => Auth::user()->id])->update(['isPurchased' => $request->isPurchased]); // To mark grocery as purchased
                    // CommonGroceryIngredient::where(['recipe_ingredient_id' => $request->recipe_ingredient_id, 'user_id' => Auth::user()->id])->delete(); // To remove the ingredient
                } else {
                    return $this->errorResponse("Grocery Not Found.", (object)[], 200);
                }
            }

            if (isset($request->grocery_id) && !empty($request->grocery_id)) {
                $groceryCount = CommonGroceryIngredient::where(['id' => $request->grocery_id, 'user_id' => Auth::user()->id])->count();
                if ($groceryCount > 0) {
                    CommonGroceryIngredient::where(['id' => $request->grocery_id, 'user_id' => Auth::user()->id])->update(['isPurchased' => $request->isPurchased]); // To mark grocery as purchased
                    // CommonGroceryIngredient::where(['id' => $request->grocery_id, 'user_id' => Auth::user()->id])->delete(); // To remove the ingredient
                } else {
                    return $this->errorResponse("Grocery Not Found.", (object)[], 200);
                }
            }

            $response = $this->getIgredientCategoryWiseIngredients();
            return $this->successResponse("Ingredient marked as purchased in grocery list.", $response, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function getIgredientCategoryWiseIngredients()
    {
        $getGroceryList = CommonGroceryIngredient::with('ingredient_category')->where(['user_id' => Auth::user()->id, 'is_personalised' => 0])->get()->toArray();

        // Initialize an empty array to store aggregated results
        $aggregatedIngredients = [];

        // Iterate through each ingredient in the original array
        foreach ($getGroceryList as $ingredient) {
            // Generate a unique key based on recipe_ingredient_id and ingredient_category_id
            $key = $ingredient['recipe_ingredient_id'] . '-' . $ingredient['ingredient_category_id'];

            // Check if the key exists in $aggregatedIngredients
            if (isset($aggregatedIngredients[$key])) {
                // If exists, add the quantities and number of persons
                $aggregatedIngredients[$key]['ingredient_quantity'] += $ingredient['ingredient_quantity'];
                $aggregatedIngredients[$key]['no_of_person'] += $ingredient['no_of_person'];
                $aggregatedIngredients[$key]['recipe_id'] = isset($aggregatedIngredients[$key]['recipe_id']) 
                                                    ? $aggregatedIngredients[$key]['recipe_id'] . ',' . $ingredient['recipe_id']
                                                    : $ingredient['recipe_id'];
            } else {
                // If doesn't exist, initialize with current values
                $aggregatedIngredients[$key] = $ingredient;
            }
        }

        // Convert aggregated array back to sequential indexed array
        $aggregatedIngredients = array_values($aggregatedIngredients);

        $personalisedIngredients = CommonGroceryIngredient::with('ingredient_category')->where(['user_id' => Auth::user()->id, 'is_personalised' => 1])->get()->toArray();

        $categoryList = IngredientCategory::get();

        $response = $this->getUpdatedGroceryList($categoryList, $aggregatedIngredients, $personalisedIngredients);

        return $response;
    }

    private function getUpdatedGroceryList($categoryList, $getGroceryList, $personalisedIngredients)
    {
        $categorizedGroceryList = [];
        $addedIngredients = [];

        // Initialize categorized grocery list with all categories
        foreach ($categoryList as $category) {
            $categoryId = $category->id;

            // Initialize the category structure
            $categorizedGroceryList[$categoryId] = [
                'id' => $categoryId,
                'category_name' => $category->category_name,
                'category_image' => asset(Storage::url($category->category_image)),
                'ingredients' => [],
                'personalised_ingredients' => [],
            ];
        }

        // Process regular ingredients from $getGroceryList
        foreach ($getGroceryList as $item) {
            $categoryId = $item['ingredient_category_id'];
            $ingredientKey = $item['recipe_ingredient_id'] . '-' . $categoryId;

            // Split the comma-separated recipe_ids into an array
            $recipeIds = explode(',', $item['recipe_id']);

            // Fetch the titles for all the recipe_ids
            $recipeTitles = Recipe::whereIn('id', $recipeIds)->pluck('title')->toArray();

            // Join the titles into a comma-separated string
            $recipe_name = implode(', ', $recipeTitles);

            // Add the regular ingredient to the category
            $categorizedGroceryList[$categoryId]['ingredients'][] = [
                'id' => $item['id'],
                'recipe_name' => $recipe_name,
                'recipe_ingredient_id' => $item['recipe_ingredient_id'],
                'ingredient_category_id' => $item['ingredient_category_id'],
                'user_id' => $item['user_id'],
                'ingredient_name' => $item['ingredient_name'],
                'ingredient_quantity' => $item['ingredient_quantity'],
                'ingredient_unit' => $item['ingredient_unit'],
                'no_of_person' => $item['no_of_person'],
                'isPurchased' => $item['isPurchased'],
                'is_personalised' => $item['is_personalised'],
                'created_at' => $item['created_at'],
                'updated_at' => $item['updated_at'],
            ];

            // Mark this ingredient as added
            $addedIngredients[$ingredientKey] = true;
        }

        // Process personalized ingredients from $personalisedIngredients
        foreach ($personalisedIngredients as $personalized) {
            $categoryId = $personalized['ingredient_category_id'];
            $personalizedKey = $personalized['id'] . '-' . $categoryId;
            // Add the personalized ingredient to the category
            $categorizedGroceryList[$categoryId]['personalised_ingredients'][] = [
                'id' => $personalized['id'],
                'ingredient_category_id' => $personalized['ingredient_category_id'],
                'user_id' => $personalized['user_id'],
                'ingredient_name' => $personalized['ingredient_name'],
                'ingredient_quantity' => $personalized['ingredient_quantity'],
                'ingredient_unit' => $personalized['ingredient_unit'],
                'isPurchased' => $personalized['isPurchased'],
                'is_personalised' => $personalized['is_personalised'],
                'created_at' => $personalized['created_at'],
                'updated_at' => $personalized['updated_at'],
            ];

            // Mark this personalized ingredient as added
            $addedIngredients[$personalizedKey] = true;
        }

        $groceryRecipes = GroceryRecipe::with('recipes', 'recipe_nutrition', 'grocery_ingredients')->where(['user_id' => Auth::user()->id])->get()->toArray();

        $recipesList = [];

        foreach ($groceryRecipes as $recipe) {
            $recipeId = $recipe['recipe_id'];

            // Initialize recipe details if it doesn't exist
            if (!isset($recipesList[$recipeId])) {
                $allPurchased = CommonGroceryIngredient::where('user_id', $recipe['user_id'])
                    ->where('recipe_id', $recipe['recipe_id'])
                    ->where('isPurchased', '!=', 1)  // Check if any record is not purchased
                    ->exists();  // This returns true if any record exists where isPurchased != 1

                $allItemPurchased = $allPurchased ? "0" : "1";  // If any item is not purchased, set it to "0", else "1"

                $recipesList[$recipeId] = [
                    'grocery_id' => $recipe['id'],
                    'user_id' => $recipe['user_id'],
                    'recipe_id' => $recipe['recipe_id'],
                    'recipe_name' => $recipe['recipes']['title'],
                    'recipe_cooking_time' => $recipe['recipes']['cooking_time'],
                    'recipe_image' => asset(Storage::url($recipe['recipes']['picture'])),
                    'no_of_person' => $recipe['no_of_person'],
                    'all_item_purchased' => $allItemPurchased,
                    // 'total_ingredient_count' => 0,
                    // 'purchased_ingredient_count' => 0,
                    'recipe_nutrition' => [], // Initialize an empty array for nutrition
                ];
            }

            // Add nutrition details to the respective recipe
            $recipesList[$recipeId]['recipe_nutrition'][] = [
                'nutrition_id' => $recipe['nutrition_id'],
                'kcal' => $recipe['recipe_nutrition']['kcal'],
                'protien' => $recipe['recipe_nutrition']['protien'],
                'fat' => $recipe['recipe_nutrition']['fat'],
                'carbs' => $recipe['recipe_nutrition']['carbs'],
            ];
        }

        // Finally, prepare your response structure
        $response = [
            [
                'name' => 'IngredientCategory',
                'categorylist' => array_values($categorizedGroceryList),
            ],
            [
                'name' => 'Recipes',
                'recipes' => array_values($recipesList),
            ],
        ];

        return $response;
    }

    public function addPersonalisedGrocery(Request $request)
    {
        try {
            // Validation
            $request->validate([
                'ingredient_name' => ['required', 'string'],
                'ingredient_quantity' => ['required', 'numeric'],
                'ingredient_unit' => ['required', 'in:g,ml,pcs,spoon'],
                'ingredient_category_id' => ['required', 'numeric']
            ], [
                'ingredient_name.required' => "Ingredient Name is required",
                'ingredient_quantity.required' => "Ingredient Quantity is required",
                'ingredient_quantity.numeric' => "Ingredient Quantity should be in numbers",
                'ingredient_unit.required' => "Ingredient Unit is required",
                'ingredient_unit.numeric' => "Ingredient Unit should be in g, ml, pcs, spoon",
                'ingredient_category_id.required' => "Ingredient Category Id is required",
                'ingredient_category_id.required' => "Ingredient Category Id should be in numbers",
            ]);

            CommonGroceryIngredient::create([
                'user_id' => Auth::user()->id,
                'ingredient_name' => $request->ingredient_name,
                'ingredient_quantity' => $request->ingredient_quantity,
                'ingredient_unit' => $request->ingredient_unit,
                'ingredient_category_id' => $request->ingredient_category_id,
                'is_personalised' => 1,
            ]);
            // Get Grocery List For Response
            $response = $response = $this->getIgredientCategoryWiseIngredients();
            return $this->successResponse('Ingredient added successfully', $response, 200);
        } catch (Throwable $th) {
            return $this->throwableError('Something went wrong', $th->getMessage(), 500);
        }
    }

    public function groceryList(Request $request)
    {
        try {
            $response = $this->getIgredientCategoryWiseIngredients();
            return $this->successResponse('Grocery Found', $response, 200);
        } catch (\Throwable $th) {
            return $this->throwableError('Something went wrong', $th->getMessage(), 500);
        }
    }

    public function countPurchasedGrocery()
    {
        try {
            $groceryList = CommonGroceryIngredient::where(['user_id' => Auth::user()->id])->get();
            // Initialize an array to store counts per recipe
            $recipeCounts = [];

            foreach ($groceryList as $ingredient) {
                $recipeId = $ingredient->recipe_id;

                if (!empty($recipeId)) {
                    // Count total ingredients for each recipe
                    if (!isset($recipeCounts[$recipeId]['totalIngredients'])) {
                        $recipeCounts[$recipeId]['totalIngredients'] = 0;
                    }
                    $recipeCounts[$recipeId]['totalIngredients']++;

                    // Count total purchased ingredients for each recipe
                    if (!isset($recipeCounts[$recipeId]['totalPurchasedIngredients'])) {
                        $recipeCounts[$recipeId]['totalPurchasedIngredients'] = 0;
                    }
                    if ($ingredient->isPurchased == 1) {
                        $recipeCounts[$recipeId]['totalPurchasedIngredients']++;
                    }
                }
            }

            // Output the counts for each recipe
            foreach ($recipeCounts as $recipeId => $counts) {
                echo "Recipe ID: $recipeId\n";
                echo "Total ingredients: {$counts['totalIngredients']}\n";
                echo "Total purchased ingredients: {$counts['totalPurchasedIngredients']}\n";
                echo "\n";
            }
        } catch (Throwable $th) {
            return $this->throwableError('Something went wrong', $th->getMessage(), 500);
        }
    }

    public function finishTheRace(Request $request)
    {
        try {
            if (CommonGroceryIngredient::where(['user_id' => Auth::user()->id])->count() > 0) {
                CommonGroceryIngredient::where(['user_id' => Auth::user()->id])->delete();
                GroceryRecipe::where(['user_id' => Auth::user()->id])->delete();
                $response = $this->getIgredientCategoryWiseIngredients();

                return $this->successResponse("Ingredient marked as purchased in grocery list.", $response, 200);
            } else {
                return $this->successResponse("No Data Fount", (object)[], 200);
            }
        } catch (\Throwable $th) {
            return $this->throwableError('Something went wrong', $th->getMessage(), 500);
        }
    }
}
