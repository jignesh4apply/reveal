<?php

namespace App\Http\Controllers\API;

use App\Models\CommonGroceryIngredient;
use App\Models\Grocery;
use App\Models\GroceryRecipe;
use App\Models\PersonalisedGrocery;
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
            ], [
                'recipe_id.required' => 'Recipe Id is required',
                'recipe_id.numeric' => 'Recipe Id should be a number',
                'no_of_person.numeric' => 'Persons should be a number',
                'nutrition_id.required' => 'Nutrition Id field is required',
                'nutrition_id.numeric' => 'Nutrition Id should be a number',
            ]);

            // Retrieve recipe nutrition
            $recipeNutrition = RecipeNutrition::where([
                'recipe_id' => $request->recipe_id,
                'id' => $request->nutrition_id,
            ])->first();
            if (!$recipeNutrition) {
                return $this->successResponse('Recipe not found', [], 200);
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
                    $quantity = $commonGrocery->ingredient_quantity + $ingredient->quantity;
                    $numberOfPersons += $commonGrocery->no_of_person;

                    $commonGrocery->update([
                        'ingredient_quantity' => $quantity,
                        'no_of_person' => $numberOfPersons,
                    ]);
                } else {
                    CommonGroceryIngredient::create([
                        'recipe_id' => $request->recipe_id,
                        'recipe_ingredient_id' => $ingredient->ingredient_id,
                        'ingredient_category_id' => $ingredient->category_id,
                        'ingredient_nutrition_id' => $ingredient->nutrition_id,
                        'user_id' => Auth::user()->id,
                        'ingredient_name' => $ingredient->name,
                        'ingredient_quantity' => $ingredient->quantity,
                        'ingredient_unit' => $ingredient->unit,
                        'no_of_person' => $numberOfPersons,
                        'isPurchased' => 0,
                    ]);
                }
            }

            // Aggregate data
            $aggregatedData = CommonGroceryIngredient::whereIn('recipe_ingredient_id', $recipeIngredients->pluck('ingredient_id'))
                ->where('user_id', Auth::user()->id)
                ->get()
                ->groupBy('recipe_ingredient_id')
                ->map(function ($group) {
                    return [
                        'ingredient_quantity' => $group->sum('ingredient_quantity'),
                        'no_of_person' => $group->sum('no_of_person'),
                        'ingredient_name' => $group->first()->ingredient_name,
                        'ingredient_unit' => $group->first()->ingredient_unit,
                        'ingredient_category_id' => $group->first()->ingredient_category_id,
                    ];
                });

            // Update or create grocery items
            foreach ($aggregatedData as $recipeIngredientId => $data) {
                Grocery::updateOrCreate([
                    'recipe_ingredient_id' => $recipeIngredientId,
                    'ingredient_category_id' => $data['ingredient_category_id'],
                    'user_id' => Auth::user()->id,
                ], [
                    'ingredient_name' => $data['ingredient_name'],
                    'ingredient_quantity' => $data['ingredient_quantity'],
                    'ingredient_unit' => $data['ingredient_unit'],
                    'no_of_person' => $data['no_of_person'],
                ]);
            }

            // Add recipe to grocery
            $this->addRecipeToGrocery($request->recipe_id, $request->nutrition_id);
            // Get Grocery List For Response
            $getGroceryList = Grocery::with('ingredient_category')->where(['user_id' => Auth::user()->id])->get()->toArray();

            $response = $this->getUpdateGroceryList($getGroceryList);
            return $this->getSingleResponse('Ingredient added to grocery list', $response, 200);
        } catch (\Throwable $th) {
            return $this->throwableError('Something went wrong', $th->getMessage(), 500);
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

    private function addRecipeToGrocery($recipeId, $nutritionId)
    {
        return GroceryRecipe::firstOrCreate([
            'user_id' => Auth::user()->id,
            'recipe_id' => $recipeId,
            'nutrition_id' => $nutritionId
        ]);
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
                // Delete common grocery ingredients
                if (isset($request->nutrition_id) || !empty($request->nutrition_id)) {
                    CommonGroceryIngredient::where(['recipe_id' => $request->recipe_id, 'ingredient_nutrition_id' => $request->nutrition_id])
                        ->where('user_id', Auth::user()->id)
                        ->delete();
                } else {
                    CommonGroceryIngredient::where('recipe_id', $request->recipe_id)
                        ->where('user_id', Auth::user()->id)
                        ->delete();
                }

                // Aggregate data for grocery items
                $aggregatedData = CommonGroceryIngredient::where('user_id', Auth::user()->id)
                    ->get()
                    ->groupBy('recipe_ingredient_id')
                    ->map(function ($group) {
                        return [
                            'ingredient_quantity' => $group->sum('ingredient_quantity'),
                            'no_of_person' => $group->sum('no_of_person'),
                            'ingredient_name' => $group->first()->ingredient_name,
                            'ingredient_unit' => $group->first()->ingredient_unit,
                            'ingredient_category_id' => $group->first()->ingredient_category_id,
                        ];
                    });

                // Update or create grocery items
                foreach ($aggregatedData as $recipeIngredientId => $data) {
                    Grocery::updateOrCreate(
                        [
                            'recipe_ingredient_id' => $recipeIngredientId,
                            'user_id' => Auth::user()->id,
                            'ingredient_category_id' => $data['ingredient_category_id'],
                        ],
                        [
                            'ingredient_name' => $data['ingredient_name'],
                            'ingredient_quantity' => $data['ingredient_quantity'],
                            'ingredient_unit' => $data['ingredient_unit'],
                            'no_of_person' => $data['no_of_person'],
                        ]
                    );
                }

                if (isset($request->nutrition_id) || !empty($request->nutrition_id)) {
                    GroceryRecipe::where(['recipe_id' => $request->recipe_id, 'nutrition_id' => $request->nutrition_id])->delete();
                } else {
                    GroceryRecipe::where(['recipe_id' => $request->recipe_id])->delete();
                }
                // Get Grocery List For Response
                $getGroceryList = Grocery::with('ingredient_category')->where(['user_id' => Auth::user()->id])->get()->toArray();

                $response = $this->getUpdateGroceryList($getGroceryList);

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
                'grocery_id' => ['required', 'array'],
                'isPurchased' => ['required', 'in:0,1'],
            ];

            $message = [
                'grocery_id.required' => 'Grocery id is required',
                'grocery_id.numeric' => 'Grocery id should be in numbers',
                'isPurchased.required' => "Is Purchased field can't be empty",
                'isPurchased.in' => "Is Purchase field should contains only 0 or 1. 0:Not Purchased 1:Purchased.",
            ];

            $validator = Validator::make($request->all(), $validation, $message);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $purchasedGroceriesList = [];
            foreach ($request->grocery_id as $grocery_id) {
                $reqGroceryId = explode(",", $grocery_id);
                foreach ($reqGroceryId as $id) {
                    // Check if grocery exists or not
                    $groceryCount = Grocery::where(['id' => $id, 'user_id' => Auth::user()->id])->count();
                    if ($groceryCount > 0) {
                        Grocery::where(['id' => $id, 'user_id' => Auth::user()->id])->update(['isPurchased' => $request->isPurchased]);

                        $purchasedGroceries = Grocery::where(['id' => $id, 'user_id' => Auth::user()->id])->first()->toArray();

                        $purchasedGroceriesList[] = $purchasedGroceries;
                    } else {
                        return $this->errorResponse("Grocery Not Found.", [], 200);
                    }
                }
            }
            if ($request->isPurchased == 1) {
                return $this->successResponse("Ingredient marked as purchased in grocery list.", $purchasedGroceriesList, 200);
            } else {
                return $this->successResponse("Ingredient marked as not purchased in grocery list.", $purchasedGroceriesList, 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function increaseGroceryIngredientsQuantity(Request $request)
    {
        try {
            // validation
            $validation = [
                'recipe_id' => ['required', 'numeric'],
                'quantity' => ['required', 'numeric'],
                'operation' => ['required', 'numeric', 'in:0,1'],
            ];

            $message = [
                'recipe_id.required' => "Recipe Id is required",
                'recipe_id.numeric' => "Recipe Id should be a number",
                'quantity.numeric' => "Persons should be a number",
            ];

            $validator = Validator::make($request->all(), $validation, $message);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $commonGroceryIngredients = CommonGroceryIngredient::where(['recipe_id' => $request->recipe_id, 'user_id' => Auth::user()->id])->get();
            // dd($commonGroceryIngredients);
            if ($commonGroceryIngredients->isNotEmpty()) {
                foreach ($commonGroceryIngredients as $ingredient) {
                    $ingredientQuantity = ($ingredient['ingredient_quantity'] * $request->quantity) + $ingredient['ingredient_quantity'];
                    CommonGroceryIngredient::where(['recipe_id' => $request->recipe_id, 'recipe_ingredient_id' => $ingredient['recipe_ingredient_id'], 'user_id' => Auth::user()->id, 'ingredient_category_id' => $ingredient['ingredient_category_id']])->update(['ingredient_quantity' => $ingredientQuantity]);
                }

                $aggregatedData = [];
                $commonGroceryData = [];
                $commonGroceryData[] = CommonGroceryIngredient::where(['user_id' => Auth::user()->id])->get()->toArray();
                // Iterate over the common grocery data to aggregate quantities and other data by recipe_ingredient_id
                foreach ($commonGroceryData as $group) {
                    foreach ($group as $ingredient) {
                        $recipeIngredientId = $ingredient['recipe_ingredient_id'];

                        if (!isset($aggregatedData[$recipeIngredientId])) {
                            // Store the first row's data
                            $aggregatedData[$recipeIngredientId] = $ingredient;
                            $aggregatedData[$recipeIngredientId]['total_quantity'] = 0;
                        }

                        // Sum the ingredient quantity
                        $aggregatedData[$recipeIngredientId]['total_quantity'] += $ingredient['ingredient_quantity'];
                    }
                }

                foreach ($aggregatedData as $recipeIngredientId => $ingredient) {
                    // Grocery Data
                    $groceryData = Grocery::where(['recipe_ingredient_id' => $ingredient['recipe_ingredient_id'], 'user_id' => Auth::user()->id, 'ingredient_category_id' => $ingredient['ingredient_category_id']])->first();
                    if (!empty($groceryData)) {
                        Grocery::where(['recipe_ingredient_id' => $ingredient['recipe_ingredient_id'], 'ingredient_category_id' => $ingredient['ingredient_category_id'], 'user_id' => Auth::user()->id])->update(['ingredient_name' => $ingredient['ingredient_name'], 'ingredient_quantity' => $ingredient['total_quantity'], 'ingredient_unit' => $ingredient['ingredient_unit'], 'no_of_person' => $ingredient['no_of_person']]);
                    } else {
                        Grocery::create([
                            'recipe_ingredient_id' => $ingredient['recipe_ingredient_id'],
                            'ingredient_category_id' => $ingredient['ingredient_category_id'],
                            'user_id' => Auth::user()->id,
                            'ingredient_name' => $ingredient['ingredient_name'],
                            'ingredient_quantity' => $ingredient['total_quantity'],
                            'ingredient_unit' => $ingredient['ingredient_unit'],
                            'no_of_person' => $ingredient['no_of_person'],
                        ]);
                    }
                }
                $getGroceryList = Grocery::with('ingredient_category')->where(['user_id' => Auth::user()->id])->get()->toArray();

                return $this->successResponse("Ingredients updated to the grocery list", $getGroceryList, 200);
            } else {
                return $this->successResponse("Grocery not found", (object) [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function groceryList(Request $request)
    {
        try {
            /* Validate Data */
            $validation = [
                'search' => ['nullable', 'string'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;

                $getGroceryList = Grocery::where(function ($query) use ($search) {
                    $query->where('ingredient_name', 'Like', "%{$search}%");
                })
                    ->with('ingredient_category')
                    ->where(['user_id' => Auth::user()->id])
                    ->get()->toArray();
            } else {
                $getGroceryList = Grocery::with('ingredient_category')->where(['user_id' => Auth::user()->id])->get()->toArray();
            }

            $categorizedGroceryList = [];
            $recipesList = [];

            // Fetch personalized ingredients
            $getPersonalisedIngredient = PersonalisedGrocery::where(['user_id' => Auth::user()->id])->get()->toArray();

            // Merge personalized ingredients into $getGroceryList based on ingredient_category_id
            foreach ($getGroceryList as &$item) {
                $categoryId = $item['ingredient_category_id'];

                // Find personalized ingredients for the current category
                $personalizedIngredients = array_filter($getPersonalisedIngredient, function ($personalized) use ($categoryId) {
                    return $personalized['ingredient_category_id'] == $categoryId;
                });

                // Append personalized ingredients to the existing ingredients list
                foreach ($personalizedIngredients as $ingredient) {
                    $item['personalizedIngredients'][] = [
                        'id' => $ingredient['id'],
                        'ingredient_category_id' => $ingredient['ingredient_category_id'],
                        'user_id' => $ingredient['user_id'],
                        'ingredient_name' => $ingredient['ingredient_name'],
                        'ingredient_quantity' => $ingredient['ingredient_quantity'],
                        'ingredient_unit' => $ingredient['ingredient_unit'],
                        'created_at' => $ingredient['created_at'],
                        'updated_at' => $ingredient['updated_at'],
                    ];
                }
            }

            // Now, modify your response building part
            $categorizedGroceryList = [];

            foreach ($getGroceryList as $item) {
                $categoryName = $item['ingredient_category']['category_name'];
                $categoryId = $item['ingredient_category']['id'];
                $categoryImage = asset(Storage::url($item['ingredient_category']['category_image']));

                if (!isset($categorizedGroceryList[$categoryId])) {
                    $categorizedGroceryList[$categoryId] = [
                        'id' => $categoryId,
                        'category_name' => $categoryName,
                        'category_image' => $categoryImage,
                        'ingredients' => [],
                        'personalised_ingredients' => [], // Initialize an array for personalised ingredients
                    ];
                }

                $categorizedGroceryList[$categoryId]['ingredients'][] = [
                    'id' => $item['id'],
                    'recipe_ingredient_id' => $item['recipe_ingredient_id'],
                    'ingredient_category_id' => $item['ingredient_category_id'],
                    'user_id' => $item['user_id'],
                    'ingredient_name' => $item['ingredient_name'],
                    'ingredient_quantity' => $item['ingredient_quantity'],
                    'ingredient_unit' => $item['ingredient_unit'],
                    'no_of_person' => $item['no_of_person'],
                    'isPurchased' => $item['isPurchased'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ];

                // Add personalised ingredients if they exist
                if (isset($item['personalizedIngredients'])) {
                    $categorizedGroceryList[$categoryId]['personalisedIngredients'] = $item['personalizedIngredients'];
                }
            }

            // Assuming there's a Recipe model and relationship to get recipes
            $groceryRecipes = GroceryRecipe::with('recipes', 'recipe_nutrition')->where(['user_id' => Auth::user()->id])->get()->toArray();
            // echo "<pre>"; print_r($groceryRecipes); die;

            $recipesList = []; // Initialize the main array to store recipes
            foreach ($groceryRecipes as $recipe) {
                $recipeId = $recipe['recipe_id'];

                // Initialize an empty array for nutrition if it doesn't exist
                if (!isset($recipesList[$recipeId])) {
                    $recipesList[$recipeId] = [
                        'grocery_id' => $recipe['id'],
                        'user_id' => $recipe['user_id'],
                        'recipe_id' => $recipe['recipe_id'],
                        // 'nutrition_id' => $recipe['nutrition_id'],
                        'recipe_name' => $recipe['recipes']['title'],
                        'recipe_image' => asset(Storage::url($recipe['recipes']['picture'])),
                        'no_of_person' => $recipe['recipes']['no_of_person'],
                        'recipe_nutrition' => [] // Initialize an empty array for nutrition
                    ];
                }

                // Add nutrition details to the respective recipe
                $recipesList[$recipeId]['recipeNutrition'][] = [
                    'nutrition_id' => $recipe['nutrition_id'],
                    'recipe_id' => $recipe['recipe_nutrition']['recipe_id'],
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

            return $this->successResponse("Grocery Found", $response, 200);
        } catch (\Throwable $th) {
            return $this->throwableError('Something went wrong', $th->getMessage(), 500);
        }
    }

    private function getUpdateGroceryList($getGroceryList)
    {
        $categorizedGroceryList = [];
        $recipesList = [];

        // Fetch personalized ingredients
        $getPersonalisedIngredient = PersonalisedGrocery::where(['user_id' => Auth::user()->id])->get()->toArray();

        // Merge personalized ingredients into $getGroceryList based on ingredient_category_id
        foreach ($getGroceryList as &$item) {
            $categoryId = $item['ingredient_category_id'];

            // Find personalized ingredients for the current category
            $personalizedIngredients = array_filter($getPersonalisedIngredient, function ($personalized) use ($categoryId) {
                return $personalized['ingredient_category_id'] == $categoryId;
            });

            // Append personalized ingredients to the existing ingredients list
            foreach ($personalizedIngredients as $ingredient) {
                $item['personalizedIngredients'][] = [
                    'id' => $ingredient['id'],
                    'ingredient_category_id' => $ingredient['ingredient_category_id'],
                    'user_id' => $ingredient['user_id'],
                    'ingredient_name' => $ingredient['ingredient_name'],
                    'ingredient_quantity' => $ingredient['ingredient_quantity'],
                    'ingredient_unit' => $ingredient['ingredient_unit'],
                    'created_at' => $ingredient['created_at'],
                    'updated_at' => $ingredient['updated_at'],
                ];
            }
        }

        // Now, modify your response building part
        $categorizedGroceryList = [];

        foreach ($getGroceryList as $item) {
            $categoryName = $item['ingredient_category']['category_name'];
            $categoryId = $item['ingredient_category']['id'];
            $categoryImage = asset(Storage::url($item['ingredient_category']['category_image']));

            if (!isset($categorizedGroceryList[$categoryId])) {
                $categorizedGroceryList[$categoryId] = [
                    'id' => $categoryId,
                    'category_name' => $categoryName,
                    'category_image' => $categoryImage,
                    'ingredients' => [],
                    'personalised_ingredients' => [], // Initialize an array for personalised ingredients
                ];
            }

            $categorizedGroceryList[$categoryId]['ingredients'][] = [
                'id' => $item['id'],
                'recipe_ingredient_id' => $item['recipe_ingredient_id'],
                'ingredient_category_id' => $item['ingredient_category_id'],
                'user_id' => $item['user_id'],
                'ingredient_name' => $item['ingredient_name'],
                'ingredient_quantity' => $item['ingredient_quantity'],
                'ingredient_unit' => $item['ingredient_unit'],
                'no_of_person' => $item['no_of_person'],
                'isPurchased' => $item['isPurchased'],
                'created_at' => $item['created_at'],
                'updated_at' => $item['updated_at'],
            ];

            // Add personalised ingredients if they exist
            if (isset($item['personalizedIngredients'])) {
                $categorizedGroceryList[$categoryId]['personalisedIngredients'] = $item['personalizedIngredients'];
            }
        }

        // Assuming there's a Recipe model and relationship to get recipes
        $groceryRecipes = GroceryRecipe::with('recipes', 'recipe_nutrition')->where(['user_id' => Auth::user()->id])->get()->toArray();
        // echo "<pre>"; print_r($groceryRecipes); die;

        $recipesList = []; // Initialize the main array to store recipes
        foreach ($groceryRecipes as $recipe) {
            $recipeId = $recipe['recipe_id'];

            // Initialize an empty array for nutrition if it doesn't exist
            if (!isset($recipesList[$recipeId])) {
                $recipesList[$recipeId] = [
                    'grocery_id' => $recipe['id'],
                    'user_id' => $recipe['user_id'],
                    'recipe_id' => $recipe['recipe_id'],
                    // 'nutrition_id' => $recipe['nutrition_id'],
                    'recipe_name' => $recipe['recipes']['title'],
                    'recipe_image' => asset(Storage::url($recipe['recipes']['picture'])),
                    'no_of_person' => $recipe['recipes']['no_of_person'],
                    'recipe_nutrition' => [] // Initialize an empty array for nutrition
                ];
            }

            // Add nutrition details to the respective recipe
            $recipesList[$recipeId]['recipeNutrition'][] = [
                'nutrition_id' => $recipe['nutrition_id'],
                'recipe_id' => $recipe['recipe_nutrition']['recipe_id'],
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

            Grocery::create([
                'user_id' => Auth::user()->id,
                'ingredient_name' => $request->ingredient_name,
                'ingredient_quantity' => $request->ingredient_quantity,
                'ingredient_unit' => $request->ingredient_unit,
                'ingredient_category_id' => $request->ingredient_category_id,
            ]);
            // Get Grocery List For Response
            $getGroceryList = Grocery::with('ingredient_category')->where(['user_id' => Auth::user()->id])->get()->toArray();
            $response = $this->getUpdateGroceryList($getGroceryList);
            return $this->successResponse('Ingredient added successfully', $response, 200);
        } catch (Throwable $th) {
            return $this->throwableError('Something went wrong', $th->getMessage(), 500);
        }
    }
}
