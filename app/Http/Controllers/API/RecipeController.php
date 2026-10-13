<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FavouriteRecipe;
use App\Models\FoodPlanner;
use App\Models\FoodPlannerSelectedUnit;
use App\Models\Grocery;
use App\Models\GroceryRecipe;
use App\Models\Ingredient;
use App\Models\MealType;
use App\Models\Nutrition;
use App\Models\NutritionFavourite;
use App\Models\NutritionIngredientBarcode;
use App\Models\NutritionIngredientUnit;
use App\Models\RecepieIngredients;
use App\Models\Recipe;
use App\Models\RecipeMaterial;
use App\Models\RecipeNutrition;
use App\Models\RecipePreparationSteps;
use App\Models\NutritionIngredient;
use App\Models\StepsGoal;
use App\Models\ToAccompany;
use App\Models\UnitConverter;
use App\Models\User;
use App\Models\UsersCurrentMeasurement;
use App\Models\UsersInitialMeasurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class RecipeController extends ApiController
{
    /*public function recipeList(Request $request)
    {
        try {
            // Validate Data
            $validator = Validator::make($request->all(), [
                'search' => ['nullable', 'string'],
            ]);
    
            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }
    
            $query = Recipe::with('nutrition')->where('status', 1);
    
            if ($request->filled('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }
    
            $getRecipeList = $query->get()->toArray();
    
            // Pagination
            $limit = 20;
            $page = $request->input('page', 1); // Default to 1 if not provided
    
            $result = $this->pagination($getRecipeList, $page, $limit);
    
            if (!empty($result['data'])) {
                $userFavourites = FavouriteRecipe::where('user_id', Auth::id())->pluck('recipe_id')->toArray();
                $isRecipeAddedList = GroceryRecipe::where('user_id', Auth::id())->pluck('recipe_id')->toArray();
                $allMealTypes = MealType::get()->toArray();
    
                $response = [];
                foreach ($allMealTypes as $meal) {
                    $response[] = [
                        'title' => $meal['title'],
                        'id' => $meal['id'],
                        'image' => $meal['image'] ? asset(Storage::url($meal['image'])) : '',
                        'recipe' => $this->processRecipes(array_values($result['data']), $userFavourites, $meal['meal_type'], $allMealTypes, $isRecipeAddedList),
                    ];
                }
    
                // Add the "Explore Meal Ideas" section last
                $response[] = [
                    'title' => 'Explore Meal Ideas',
                    'id' => 0,
                    'recipe' => $this->processRecipes(array_values($result['data']), $userFavourites, null, $allMealTypes, $isRecipeAddedList),
                ];
    
                return $this->successResponse("Data Found", $response, 200);
            } else {
                return $this->successResponse("Data Not Found", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
    
    public function processRecipes($recipeList, $userFavourites, $mealTypeFilter = null, $allMealTypes, $isRecipeAddedList)
    {
        $processedRecipes = [];
    
        foreach ($recipeList as $recipe) {
            $is_fav = in_array($recipe['id'], $userFavourites) ? 1 : 0;
            $isRecipeAdded = in_array($recipe['id'], $isRecipeAddedList) ? 1 : 0;
    
            // Map meal type IDs to names
            $mealTypeIds = explode("|", $recipe['meal_type_id']);
            $mealTitle = array_map(fn($id) => $allMealTypes[$id]['meal_type'] ?? 'Unknown', $mealTypeIds); // Adjust as necessary
    
            $mealType = implode("|", $mealTitle);
    
            // Check if the mealTypeFilter is null or if it matches any of the meal types
            if ($mealTypeFilter === null || in_array($mealTypeFilter, $mealTitle)) {
                $processedRecipes[] = [
                    'id' => $recipe['id'],
                    'title' => $recipe['title'],
                    'tags' => $recipe['tags'],
                    'meal_type' => $mealType,
                    'slug' => $recipe['slug'],
                    'picture' => asset(Storage::url($recipe['picture'])),
                    'cooking_time' => $recipe['cooking_time'],
                    'overall_time' => $recipe['overall_time'],
                    'about' => $recipe['about'],
                    'prep_video' => asset(Storage::url($recipe['prep_video'])),
                    'is_person' => $recipe['is_person'],
                    'no_of_person' => $recipe['no_of_person'],
                    'free_access' => $recipe['free_access'],
                    'is_fav' => $is_fav,
                    'isRecipeAdded' => $isRecipeAdded,
                    'status' => $recipe['status'],
                    'recipe_nutrition' => $recipe['nutrition'],
                ];
            }
        }
        // echo "<pre>";print_r($processedRecipes);die;
        if(!empty($processedRecipes)){
            return $processedRecipes;
            echo "if";die;
        }
        // else{
        //     echo "else";die;
        // }
        //     return $processedRecipes;
    }*/
    public function recipeList(Request $request)
    {
        try {
            // Validate Data
            $validator = Validator::make($request->all(), [
                'search' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            // Create a query for recipes with nutrition and ordered by ID
            $query = Recipe::with('nutrition')->where('status', 1)->orderBy('id', 'asc');

            if ($request->filled('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            $getRecipeList = $query->get()->toArray();

            if (!empty($getRecipeList)) {
                $userFavourites = FavouriteRecipe::where('user_id', Auth::id())->pluck('recipe_id')->toArray();
                $isRecipeAddedList = GroceryRecipe::where('user_id', Auth::id())->pluck('recipe_id')->toArray();
                $allMealTypes = MealType::get()->toArray();

                $response = [];
                foreach ($allMealTypes as $meal) {
                    // Filter and process recipes for the current meal type
                    $recipes = $this->processRecipes(array_values($getRecipeList), $userFavourites, $meal['meal_type'], $allMealTypes, $isRecipeAddedList);

                    // Limit to 10 recipes per meal type
                    $recipes = array_slice($recipes, 0, 10);

                    // Only add the meal type to response if it has recipes
                    if (!empty($recipes)) {
                        $response[] = [
                            'title' => $meal['title'] ?? '',
                            'meal_type' => $meal['slug'] ?? '',
                            'id' => $meal['id'],
                            'image' => $meal['image'] ? asset(Storage::url($meal['image'])) : '',
                            'is_popular' => $meal['is_popular'],
                            'recipe' => $recipes,
                        ];
                    }
                }

                // // Add the "Explore Meal Ideas" section last
                // $exploreRecipes = $this->processRecipes(array_values($getRecipeList), $userFavourites, null, $allMealTypes, $isRecipeAddedList);
                // $exploreRecipes = array_slice($exploreRecipes, 0, 10); // Limit to 10 recipes

                // if (!empty($exploreRecipes)) {
                //     $response[] = [
                //         'title' => 'Explore Meal Ideas',
                //         'meal_type' => 'all',
                //         'id' => 0,
                //         'image' => asset('adminAssets/image/Icons.png'),
                //         'recipe' => $exploreRecipes,
                //     ];
                // }

                return $this->successResponse("Data Found", $response, 200);
            } else {
                return $this->successResponse("Data Not Found", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function processRecipes($recipeList, $userFavourites, $mealTypeFilter = null, $allMealTypes, $isRecipeAddedList)
    {
        $processedRecipes = [];

        $mealTypeLookup = [];
        foreach ($allMealTypes as $meal) {
            $mealTypeLookup[$meal['id']] = $meal['meal_type'];
        }

        foreach ($recipeList as $recipe) {
            $is_fav = in_array($recipe['id'], $userFavourites) ? 1 : 0;
            $isRecipeAdded = in_array($recipe['id'], $isRecipeAddedList) ? 1 : 0;

            $mealTypeIds = explode("|", $recipe['meal_type_id']);
            $mealTitle = [];

            foreach ($mealTypeIds as $id) {
                if (isset($mealTypeLookup[$id])) {
                    $mealTitle[] = $mealTypeLookup[$id];
                }
            }

            $mealType = implode("|", $mealTitle);

            // Check if the mealTypeFilter is null or if it matches any of the meal types
            if ($mealTypeFilter === null || in_array($mealTypeFilter, $mealTitle)) {
                $processedRecipes[] = [
                    'id' => $recipe['id'],
                    'title' => $recipe['title'],
                    'tags' => $recipe['tags'],
                    'meal_type' => $mealType,
                    'slug' => $recipe['slug'],
                    'picture' => $recipe['picture'] ? asset(Storage::url($recipe['picture'])) : "",
                    'cooking_time' => $recipe['cooking_time'],
                    'overall_time' => $recipe['overall_time'],
                    'about' => $recipe['about'],
                    'prep_video' => $recipe['prep_video'] ?? "",
                    'is_person' => $recipe['is_person'],
                    'no_of_person' => $recipe['no_of_person'],
                    'free_access' => $recipe['free_access'],
                    'is_fav' => $is_fav,
                    'isRecipeAdded' => $isRecipeAdded,
                    'status' => $recipe['status'],
                    'recipe_nutrition' => $recipe['nutrition'],
                ];
            }
        }

        return $processedRecipes;
    }

    public function recipeDetail(Request $request)
    {
        try {
            $validation = [
                'recipe_id' => ['required', 'numeric'],
                'nutrition_id' => ['required', 'numeric'],
                'quantity' => ['nullable', 'numeric'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $recipeId = $request->input('recipe_id');
            $nutritionId = $request->input('nutrition_id');
            $userId = Auth::user()->id;

            $actualQuantity = RecipeNutrition::with('nutritioningredients')
                ->where('recipe_id', $recipeId)
                ->where('id', $nutritionId)
                ->get()
                ->flatMap(function ($nutrition) {
                    return $nutrition->nutritioningredients->map(fn($ingredient) => $ingredient->quantity);
                })->sum();
            $quantity = round(floatval($request->input('quantity')), 2);
            $multiplyBy = round($quantity / $actualQuantity, 2);
            if (!$quantity) {
                $quantity = round($actualQuantity, 2);
                $multiplyBy = 1.00;
            }
            // Recipe Details
            $favCount = FavouriteRecipe::where(['recipe_id' => $recipeId])->count();

            $recipe = Recipe::find($recipeId);
            if (!$recipe) {
                return $this->successResponse("No Recipe Found.", (object)[], 200);
            }

            $isFav = FavouriteRecipe::where([
                'user_id' => $userId,
                'recipe_id' => $recipeId
            ])->exists() ? 1 : 0;

            $isRecipeAdded = GroceryRecipe::where([
                'user_id' => $userId,
                'recipe_id' => $recipeId
            ])->exists() ? 1 : 0;

            $recipe['favCount'] = $favCount;
            $recipe['is_user_fav'] = $isFav;
            $recipe['isRecipeAdded'] = $isRecipeAdded;


            $mealTypes = MealType::whereIn('id', array_unique(explode("|", $recipe['meal_type_id'])))->pluck('meal_type', 'id')->toArray();

            $mealTypeIds = explode("|", $recipe['meal_type_id']);
            $mealTitle = array_map(fn($id) => $mealTypes[$id] ?? 'Unknown', $mealTypeIds);
            $mealType = implode("|", $mealTitle);

            $recipeInfo = [
                'id' => $recipe['id'],
                'title' => $recipe['title'],
                'tags' => $recipe['tags'],
                'meal_type' => explode("|", $mealType),
                'slug' => $recipe['slug'],
                'quantity' => $quantity,
                'picture' => $recipe['picture'] ? asset(Storage::url($recipe['picture'])) : "",
                'cooking_time' => $recipe['cooking_time'],
                'overall_time' => $recipe['overall_time'],
                'about' => $recipe['about'],
                'prep_video' => $recipe['prep_video']?? "",
                'is_person' => $recipe['is_person'],
                'no_of_person' => $recipe['no_of_person'],
                'free_access' => $recipe['free_access'],
                'is_user_fav' => $recipe['is_user_fav'],
                'isRecipeAdded' => $recipe['isRecipeAdded'],
                'favCount' => $recipe['favCount'],
                'status' => $recipe['status']
            ];

            // Calorie
            // Nutrition wise Recipe Ingredients
            $nutritionData = RecipeNutrition::with('nutritioningredients')->where('recipe_id', $recipeId)->get()->map(function ($nutrition) use ($multiplyBy) {
                
                $nutritionKcal = number_format(round($nutrition->kcal * $multiplyBy, 2), 2, '.', '');
                $nutritionProtien = number_format(round($nutrition->protien * $multiplyBy, 2), 2, '.', '');
                $nutritionFat = number_format(round($nutrition->fat * $multiplyBy, 2), 2, '.', '');
                $nutritionCarbs = number_format(round($nutrition->carbs * $multiplyBy, 2), 2, '.', '');
                
                // Calculate total grams of carbs, protein, and fat
                $total = $nutritionCarbs + $nutritionProtien + $nutritionFat;

                // Calculate percentages
                $carbsPercentage = $total ? ($nutritionCarbs / $total) * 100 : 0;
                $proteinPercentage = $total ? ($nutritionProtien / $total) * 100 : 0;
                $fatPercentage = $total ? ($nutritionFat / $total) * 100 : 0;

                $nutritionData = $this->calculateNutritionGoals();
                $targetCalorie = $nutritionData['targetCalorie'];
                $targetCarbsGrams = $nutritionData['targetCarbsGrams'];
                $targetProteinsGrams = $nutritionData['targetProteinsGrams'];
                $targetFatsGrams = $nutritionData['targetFatsGrams'];   

                $overallCarbsPercentage = round(($nutritionCarbs / $targetCarbsGrams) * 100);
                $overallProteinsPercentage = round(($nutritionProtien / $targetProteinsGrams) * 100);
                $overallFatsPercentage = round(($nutritionFat / $targetFatsGrams) * 100);
                $overallKcalPercentage = round(($nutritionKcal / $targetCalorie) * 100);
        
                $overallCarbsPercentage = min($overallCarbsPercentage, 100);
                $overallProteinsPercentage = min($overallProteinsPercentage, 100);
                $overallFatsPercentage = min($overallFatsPercentage, 100);
                $overallKcalPercentage = min($overallKcalPercentage, 100);


                return [
                    'id' => $nutrition->id,
                    'recipe_id' => $nutrition->recipe_id,
                    'kcal' => $nutritionKcal,
                    'protien' => $nutritionProtien,
                    'fat' => $nutritionFat,
                    'carbs' => $nutritionCarbs,
                    'percentages' => [
                        'carbs' => round($carbsPercentage, 2) . '%',
                        'protien' => round($proteinPercentage, 2) . '%',
                        'fat' => round($fatPercentage, 2) . '%',
                    ],
                    'macrosPercentage' => [
                        'kcalPercentage' => $overallKcalPercentage . '%',
                        'proteinPercentage' => $overallProteinsPercentage . '%',
                        'fatsPercentage' => $overallFatsPercentage . '%',
                        'carbsPercentage' => $overallCarbsPercentage . '%',
                    ],
                ];
            });

            // Ingredients
            $ingredientData = RecipeNutrition::with('nutritioningredients')
                ->where('recipe_id', $recipeId)
                ->where('id', $request->nutrition_id)
                ->get()
                ->flatMap(function ($nutrition) {
                    return $nutrition->nutritioningredients->map(function ($ingredient) {
                        return $this->mapIngredient($ingredient);
                    });
                });

            // Recipe To Accompany Details
            $recipeToAccompanyData = ToAccompany::where('recipe_id', $recipeId)->get(['id', 'recipe_id', 'ingredient_name', 'quantity', 'unit']);

            // Recipe Material Details
            $recipeMaterialData = RecipeMaterial::where('recipe_id', $recipeId)->get(['id', 'recipe_id', 'material_name as ingredient_name', 'quantity', 'unit']);

            // Recipe Preparation Details
            $recipePreparationSteps = RecipePreparationSteps::where('recipe_id', $recipeId)->get()->map(function ($step, $key) {
                return [
                    'id' => $step->id,
                    'recipeId' => $step->recipe_id,
                    'step' => $step->prep_steps,
                    'description' => $step->prep_description
                ];
            });

            $about = [
                "title" => "About",
                "details" => $recipe->about
            ];

            $response['information'] = $recipeInfo;
            $response['calorie'] = $nutritionData;
            $response["ingredients"]['recipe'] = $ingredientData;
            $response["ingredients"]['to_accompany'] = $recipeToAccompanyData;
            $response["ingredients"]['material'] = $recipeMaterialData;
            $response["ingredients"]['about'] = $about;
            $response['preparation_steps'] = $recipePreparationSteps;


            return $this->successResponse("Recipe Found", $response, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function recipeListByMealType(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                // 'mealType' => ['required', 'integer', 'in:0,1,2,3,4'],
                'mealType' => ['required', 'integer'],
                'search' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $allMealTypes = MealType::get()->toArray();
            $mealTypes = [];
            foreach ($allMealTypes as $meal) {
                $mealTypes[$meal['id']] =  $meal['slug'];
            }
            $mealTypes[0] = 'all';
            // Define meal types
            // $mealTypes = [
            //     0 => "breakfast",
            //     1 => "lunch",
            //     2 => "snacks",
            //     3 => "dinner",
            //     4 => "all"
            // ];

            if (empty($mealTypes[$request->mealType])) {
                return $this->successResponse("Data Found", [], 200);
            }

            $mealType = $mealTypes[$request->mealType];

            $query = Recipe::where('status', 1);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('title', 'like', '%' . $search . '%');
            }

            if ($mealType !== "all") {
                $mealTypeId = array_search($mealType, $mealTypes);

                if ($mealTypeId !== false) {
                    $query->whereRaw('LOWER(meal_type_id) REGEXP ?', ['(^|[|])' . strtolower($mealTypeId) . '($|[|])']);
                }
            }

            $limit = $request->input('limit', 15);
            $recipes = $query->paginate($limit);

            $recipeList = $recipes->getCollection()->map(function ($recipe) {
                $mealTypes = MealType::whereIn('id', array_unique(explode("|", $recipe['meal_type_id'])))->pluck('meal_type', 'id')->toArray();

                $mealTypeIds = explode("|", $recipe['meal_type_id']);
                $mealTitle = array_map(fn($id) => $mealTypes[$id] ?? 'Unknown', $mealTypeIds);
                $mealType = implode("|", $mealTitle);

                $is_fav = FavouriteRecipe::where(['user_id' => Auth::user()->id, 'recipe_id' => $recipe['id']])->exists() ? 1 : 0;
                $isRecipeAdded = GroceryRecipe::where([
                    'user_id' => Auth::user()->id,
                    'recipe_id' => $recipe->id
                ])->exists() ? 1 : 0;

                $nutritionData = RecipeNutrition::with('nutritioningredients')->where('recipe_id', $recipe->id)->get()->map(function ($nutrition) {
                    return [
                        'id' => $nutrition->id,
                        'recipe_id' => $nutrition->recipe_id,
                        'kcal' => $nutrition->kcal,
                        'protien' => $nutrition->protien,
                        'fat' => $nutrition->fat,
                        'carbs' => $nutrition->carbs,
                    ];
                });
                return [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'tags' => explode(",", $recipe->tags),
                    'meal_type' => $mealType,
                    'slug' => $recipe->slug,
                    'picture' => $recipe->picture ? asset(Storage::url($recipe->picture)) : "",
                    'cooking_time' => $recipe->cooking_time,
                    'overall_time' => $recipe->overall_time,
                    'about' => $recipe->about,
                    'prep_video' => $recipe->prep_video ?? "",
                    'is_person' => $recipe->is_person,
                    'no_of_person' => $recipe->no_of_person,
                    'free_access' => $recipe['free_access'],
                    'is_fav' => $is_fav,
                    'isRecipeAdded' => $isRecipeAdded,
                    'status' => $recipe->status,
                    'calorie' => $nutritionData
                ];
            })->toArray();

            $response = [
                'recipeList' => $recipeList,
                'current_page' => $recipes->currentPage(),
                'total_pages' => $recipes->lastPage(),
                'total_items' => $recipes->total(),
            ];

            return $this->successResponse("Data Found", $response, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function mapIngredient($ingredient)
    {
        // Build and return the ingredient data array
        return [
            'id' => $ingredient->id,
            'recipeId' => $ingredient->recepie_id,
            'nutritionId' => $ingredient->nutrition_id,
            'ingredientName' => $ingredient->name,
            'ingredientQuantity' => $ingredient->quantity,
            'ingredientUnit' => $ingredient->unit,
        ];
    }

    public function addAndRemoveRecipeToFavourite(Request $request)
    {
        try {
            /* Validate Data */
            $validation = [
                'recipe_id' => ['required', 'numeric'],
                'isFav' => ['nullable', 'in:0,1']
            ];

            $message = [
                'recipe_id.required' => 'Recipe id is required',
                'recipe_id.numeric' => 'Recipe id should be in numbers',
                // 'isFav.required' => "Is Fav field can't be empty",
                'isFav.in' => "Is Fav field should contains only 0 or 1. 0:Recipe removed from your favourite list. And 1:Recipe added in your favourite list.",
            ];

            $validator = Validator::make($request->all(), $validation, $message);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            // Check if Recipe exists or not
            $recipeCount = Recipe::where(['id' => $request->recipe_id])->count();
            // Check if recipe already exists in favourite list
            if ($recipeCount > 0) {
                $favRecipe = FavouriteRecipe::where(['recipe_id' => $request->recipe_id, 'user_id' => Auth::user()->id])->first();
                if (collect($favRecipe)->count() > 0) {
                    FavouriteRecipe::where(['recipe_id' => $request->recipe_id, 'user_id' => Auth::user()->id])->delete();

                    return $this->getSingleResponse("Recipe removed to your favourites", (object)[], 200);
                } else {
                    $fav = new FavouriteRecipe;
                    $fav->user_id = Auth::user()->id;
                    $fav->recipe_id = $request->recipe_id;
                    $fav->save();
                    return $this->getSingleResponse("Recipe added to your favourites", $fav, 200);
                }
            } else {
                return $this->notFoundResponse("Recipe not found", (object)[], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function clearUserFavouriteRecipe(Request $request)
    {
        try {
            $favList = FavouriteRecipe::where(['user_id' => Auth::user()->id])->get();
            if (count($favList) > 0) {
                FavouriteRecipe::where(['user_id' => Auth::user()->id])->delete();
                return $this->getSingleResponse("Cleared your favourite list", (object)[], 200);
            } else {
                return $this->notFoundResponse("Recipe not found", (object)[], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function userFavouriteList(Request $request)
    {
        try {
            $getUserFavouriteList = FavouriteRecipe::with('recipe')->where(['user_id' => Auth::user()->id])->get()->toArray();

            (int) $limit = 15;
            if (!isset($request->page) && (!isset($request->page))) {
                (int) $page = 1;
            } else {
                (int) $page = $request->page;
            }
            $favRecipe = [] ;
            $result = $this->pagination($getUserFavouriteList, $page, $limit);
            foreach ($result['data'] as $fav) {

                $mealTypes = MealType::whereIn('id', array_unique(explode("|", $fav['recipe']['meal_type_id'])))->pluck('meal_type', 'id')->toArray();

                $mealTypeIds = explode("|", $fav['recipe']['meal_type_id']);
                $mealTitle = array_map(fn($id) => $mealTypes[$id] ?? 'Unknown', $mealTypeIds);
                $mealType = implode("|", $mealTitle);

                $isRecipeAdded = GroceryRecipe::where([
                    'user_id' => Auth::user()->id,
                    'recipe_id' => $fav['recipe_id'],
                ])->exists() ? 1 : 0;

                $recipe = Recipe::find($fav['recipe_id']);
                // dd($recipe);

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
                    'recipeNutrition'   => $fav['recipe']['nutrition']
                ];
            }
            // $favRecipe[] = ['totalFavRecipe' => count($getUserFavouriteList)];

            if (count($getUserFavouriteList) > 0) {
                return $this->successResponse("Favoutite Recipe", $favRecipe, 200);
            } else {
                return $this->successResponse("This user does not have any recipe in his favourite.", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function totalFavCountOnRecipe(Request $request)
    {
        try {
            /* Validate Data */
            $validation = [
                'recipe_id' => ['required', 'numeric'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }
            $data = $request->all();

            $countFavOnRecipe = FavouriteRecipe::where(['recipe_id' => $data['recipe_id']])->count();

            if ($countFavOnRecipe > 0) {
                $count['totalFavCountOnRecipe'] = $countFavOnRecipe;
                return $this->successResponse("Data Found", $count, 200);
            } else {
                return $this->errorResponse("This Recipe is not found in favourite.", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function ingredientList(Request $request)
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

            if (Auth::check()) {
                if ($request->has('search') && !empty($request->search)) {
                    $search = $request->search;
                    $getIngredientList = NutritionIngredient::where(function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%')
                            ->orWhere('slug', 'like', '%' . $search . '%');
                    })
                        ->where(['status' => 1])
                        ->get()->toArray();
                } else {
                    $getIngredientList = NutritionIngredient::where(['status' => 1])->get()->toArray();
                }
                (int) $limit = 15;
                if (!isset($request->page) && (!isset($request->page))) {
                    (int) $page = 1;
                } else {
                    (int) $page = $request->page;
                }

                $result = $this->pagination($getIngredientList, $page, $limit);

                if (count($getIngredientList) > 0) {
                    return $this->successResponse("Ingredient Found", $result['data'], 200);
                } else {
                    return $this->errorResponse("Ingredient Not Found", [], 200);
                }
            } else {
                return $this->errorResponse("You does not have permission.", [], 400);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
    public function openFoodFactsingredientList(Request $request)
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

            if (Auth::check()) {
                if ($request->has('search') && !empty($request->search)) {
                    $search = $request->search;
                    $getIngredientList = NutritionIngredient::where(function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%')
                            ->orWhere('slug', 'like', '%' . $search . '%');
                    })
                        ->where(['status' => 1])
                        ->get()->toArray();
                } else {
                    $getIngredientList = NutritionIngredient::where(['status' => 1])->get()->toArray();
                }
                (int) $limit = 15;
                if (!isset($request->page) && (!isset($request->page))) {
                    (int) $page = 1;
                } else {
                    (int) $page = $request->page;
                }

                $result = $this->pagination($getIngredientList, $page, $limit);

                if (count($getIngredientList) > 0) {
                    return $this->successResponse("Ingredient Found", $result['data'], 200);
                } else {
                    return $this->errorResponse("Ingredient Not Found", [], 200);
                }
            } else {
                return $this->errorResponse("You does not have permission.", [], 400);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function ingredientListByRecipe(Request $request)
    {
        try {
            /* Validate Data */
            $validation = [
                'recipe_id' => ['required', 'numeric'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $data = $request->all();

            $getRecipeIngerdients = RecepieIngredients::where(['recepie_id' => $data['recipe_id']])->get()->toArray();

            (int) $limit = 15;
            if (!isset($request->page) && (!isset($request->page))) {
                (int) $page = 1;
            } else {
                (int) $page = $request->page;
            }

            $result = $this->pagination($getRecipeIngerdients, $page, $limit);

            if (count($getRecipeIngerdients) > 0) {
                return $this->successResponse("Recipe Ingredient Found", $result['data'], 200);
            } else {
                return $this->errorResponse("Recipe Ingredient Not Found", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function topRecipes(Request $request)
    {
        try {
            // Validate Data
            $validator = Validator::make($request->all(), [
                'search' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $getRecipeList = Recipe::where(function ($query) use ($search) {
                    $query->where('title', 'like', '%' . $search . '%');
                })
                    ->where(['status' => 1])
                    ->get()->toArray();
            } else {
                $getRecipeList = Recipe::where(['status' => 1])->get()->toArray();
            }

            if (!empty($getRecipeList)) {
                $response = [];

                $processedRecipes = [];
                foreach ($getRecipeList as $topRecipes) {
                    // Check if recipe is in user favourite list
                    if (FavouriteRecipe::where(['user_id' => Auth::user()->id, 'recipe_id' => $topRecipes['id']])->count() > 0) {
                        $is_fav = 1;
                    } else {
                        $is_fav = 0;
                    }

                    $mealTypes = explode('|', $topRecipes['meal_type_id']);

                    $processedRecipes[] = [
                        'id' => $topRecipes['id'],
                        'title' => $topRecipes['title'],
                        'tags' => $topRecipes['tags'], // Assuming tags are already an array
                        'meal_type' => $topRecipes['meal_type_id'],
                        'slug' => $topRecipes['slug'],
                        'picture' => $topRecipes['picture'] ? asset(Storage::url($topRecipes['picture'])) : "",
                        'cooking_time' => $topRecipes['cooking_time'],
                        'overall_time' => $topRecipes['overall_time'],
                        'about' => $topRecipes['about'],
                        'prep_video' => $topRecipes['prep_video'] ?? "",
                        'is_person' => $topRecipes['is_person'],
                        'no_of_person' => $topRecipes['no_of_person'],
                        'free_access' => $topRecipes['free_access'],
                        'is_fav' => $is_fav,
                        'status' => $topRecipes['status']
                    ];
                }
                // All Category
                $response = $processedRecipes;
                return $this->successResponse("Data Found", $response, 200);
            } else {
                return $this->successResponse("Data Not Found", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function nutritionDetail(Request $request){
        try {
            $validation = [
                'id' => ['required', 'numeric'],
                'meal_type_id' => ['nullable', 'numeric'],
                'quantity' => ['nullable', 'numeric'],
            ];
            $validator = Validator::make($request->all(), $validation);
    
            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }
    
            $nutritionId = $request->input('id');
    
            $nutrition = NutritionIngredient::find($nutritionId);
            if (!$nutrition) {
                return $this->successResponse("No Nutrition Found.", (object)[], 200);
            }

            $nutritionData = $this->calculateNutritionGoals();

            $targetCalorie = $nutritionData['targetCalorie'] ?: 2000; // Default: 2000 kcal/day
            $targetCarbsGrams = $nutritionData['targetCarbsGrams'] ?: 250; // Default: 250g carbs/day
            $targetProteinsGrams = $nutritionData['targetProteinsGrams'] ?: 60; // Default: 60g protein/day
            $targetFatsGrams = $nutritionData['targetFatsGrams'] ?: 55; // Default: 55g fat/day

            
            // Calculate total grams of carbs, protein, and fat
            $total = $nutrition->carbs + $nutrition->protein + $nutrition->fats;

            if($request->quantity){
                $nutrition->kcal    = $nutrition->kcal    * ($request->quantity / 100);
                $nutrition->carbs   = $nutrition->carbs   * ($request->quantity / 100);
                $nutrition->protein = $nutrition->protein * ($request->quantity / 100);
                $nutrition->fats    = $nutrition->fats    * ($request->quantity / 100);

                $total = $nutrition->carbs + $nutrition->protein + $nutrition->fats;
            }
            
            $carbsPercentage = round(($nutrition->carbs / $targetCarbsGrams) * 100);
            $proteinsPercentage = round(($nutrition->protein / $targetProteinsGrams) * 100);
            $fatsPercentage = round(($nutrition->fats / $targetFatsGrams) * 100);
            $kcalPercentage = round(($nutrition->kcal / $targetCalorie) * 100);
    
            $carbsPercentage = min($carbsPercentage, 100);
            $proteinsPercentage = min($proteinsPercentage, 100);
            $fatsPercentage = min($fatsPercentage, 100);
            $kcalPercentage = min($kcalPercentage, 100);
            
            // Calculate percentages
            if ($total > 0) {
                $macrosCarbsPercentage = ($nutrition->carbs / $total) * 100;
                $macrosProteinsPercentage = ($nutrition->protein / $total) * 100;
                $macrosFatsPercentage = ($nutrition->fats / $total) * 100;
            } else {
                $macrosCarbsPercentage = 0;
                $macrosProteinsPercentage = 0;
                $macrosFatsPercentage = 0;
            }
            
            $isfavourite = NutritionFavourite::where('user_id', Auth::user()->id)->where('nutrition_ingredient_id', $nutrition->id)->first() ? 1 : 0;
            $mealTypeName = MealType::find($request->meal_type_id) ? MealType::find($request->meal_type_id)->slug : '';
            $isUserCreated = 0;
            if($nutrition->user_id != null){
                $isUserCreated = 1;
            }
             // Fetch barcodes for the ingredient
            $barcodes = NutritionIngredientBarcode::where('nutrition_ingredient_id', $nutritionId)->pluck('barcode');
            // Fetch unit options
            $servingUnits = NutritionIngredientUnit::where('nutrition_ingredient_id', $nutritionId)
                ->select('size_key', 'value', 'units')
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($item) {
                    // Extract only the text part before the last underscore and number, if present
                    if (preg_match('/^(.+?)_\d+g$/', $item->size_key, $matches)) {
                        $displayKey = $matches[1];
                    } else {
                        $displayKey = $item->size_key;
                    }
                    $item->display = $displayKey . ' - ' . $item->value;
                    return $item;
                });
            $nutritionData = [
                'id' => $nutrition->id,
                'user_id' => Auth::user()->id,
                'title' => $nutrition->name,
                'meal_type' => $mealTypeName,
                'image_url' => $nutrition->image_url ?? "",
                'small_image_url' => $nutrition->small_image_url ?? "",
                'quantity' => $request->quantity ? intval($request->quantity) : 100,
                'kcal' => number_format($nutrition->kcal,1),
                'description' => $nutrition->description ?? "",
                'is_favorite' => $isfavourite,
                'macros' => [
                    'protein' => number_format($nutrition->protein, 1),
                    'fats' => number_format($nutrition->fats, 1),
                    'carbs' => number_format($nutrition->carbs, 1),
                    'percentages' => [
                        'carbs' => round($macrosCarbsPercentage,2) . '%',
                        'protein' => round($macrosProteinsPercentage,2) . '%',
                        'fats' => round($macrosFatsPercentage,2) . '%',
                    ]
                ],
                'macrosPercentage' => [
                    'kcalPercentage' => $kcalPercentage . '%',
                    'proteinPercentage' => $proteinsPercentage . '%',
                    'fatsPercentage' => $fatsPercentage . '%',
                    'carbsPercentage' => $carbsPercentage . '%',
                ],
                'serving_units' => $servingUnits,
                'barcodes' => $barcodes,
                'isUserCreated' => $isUserCreated,
            ];

            return $this->successResponse("Nutrition Found", $nutritionData, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function aiFoodDetail(Request $request)
    {
        try {

            $validation = [
                'fp_id' => ['required', 'numeric'],
                'meal_type_id' => ['nullable', 'numeric'],
                'quantity' => ['nullable', 'numeric'],
            ];

            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $foodPlanner = FoodPlanner::where('id', $request->fp_id)
                ->where('user_id', Auth::user()->id)
                ->where('is_ingredient', 4)
                ->first();

            if (!$foodPlanner) {
                return $this->successResponse("No AI Food Found.", (object)[], 200);
            }

            $nutritionData = $this->calculateNutritionGoals();

            $targetCalorie = $nutritionData['targetCalorie'] ?: 2000;
            $targetCarbsGrams = $nutritionData['targetCarbsGrams'] ?: 250;
            $targetProteinsGrams = $nutritionData['targetProteinsGrams'] ?: 60;
            $targetFatsGrams = $nutritionData['targetFatsGrams'] ?: 55;

            /*
            |--------------------------------------------------------------------------
            | Convert stored values to per 100g
            |--------------------------------------------------------------------------
            */

            $portion = (float)$foodPlanner->portion;

            $kcal = $portion > 0 ? ($foodPlanner->kcal / $portion) * 100 : 0;
            $carbs = $portion > 0 ? ($foodPlanner->carbs / $portion) * 100 : 0;
            $protein = $portion > 0 ? ($foodPlanner->proteins / $portion) * 100 : 0;
            $fats = $portion > 0 ? ($foodPlanner->fats / $portion) * 100 : 0;

            /*
            |--------------------------------------------------------------------------
            | Quantity based calculation
            |--------------------------------------------------------------------------
            */

            $quantity = $request->quantity ? intval($request->quantity) : 100;

            if ($quantity != 100) {

                $kcal = $kcal * ($quantity / 100);
                $carbs = $carbs * ($quantity / 100);
                $protein = $protein * ($quantity / 100);
                $fats = $fats * ($quantity / 100);
            }

            /*
            |--------------------------------------------------------------------------
            | Macro percentages
            |--------------------------------------------------------------------------
            */

            $total = $carbs + $protein + $fats;

            $carbsPercentage = round(($carbs / $targetCarbsGrams) * 100);
            $proteinsPercentage = round(($protein / $targetProteinsGrams) * 100);
            $fatsPercentage = round(($fats / $targetFatsGrams) * 100);
            $kcalPercentage = round(($kcal / $targetCalorie) * 100);

            $carbsPercentage = min($carbsPercentage, 100);
            $proteinsPercentage = min($proteinsPercentage, 100);
            $fatsPercentage = min($fatsPercentage, 100);
            $kcalPercentage = min($kcalPercentage, 100);

            if ($total > 0) {

                $macrosCarbsPercentage = ($carbs / $total) * 100;
                $macrosProteinsPercentage = ($protein / $total) * 100;
                $macrosFatsPercentage = ($fats / $total) * 100;

            } else {

                $macrosCarbsPercentage = 0;
                $macrosProteinsPercentage = 0;
                $macrosFatsPercentage = 0;
            }

            $mealTypeName = MealType::find($request->meal_type_id)
                ? MealType::find($request->meal_type_id)->slug
                : '';

            $selectedUnit = FoodPlannerSelectedUnit::where('food_planner_id', $foodPlanner->id)->first();

            $selectedUnits = [];

            if (!empty($selectedUnit) && !empty($selectedUnit->selected_unit_json)) {
                $unit = $selectedUnit->selected_unit_json;

                if (isset($unit['value'])) {
                    $unit['value'] += 0;
                }

                $selectedUnits = [$unit];
            }

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            $response = [

                'fp_id' => $foodPlanner->id,
                'user_id' => Auth::user()->id,
                'title' => $foodPlanner->AI_food_name,
                'meal_type' => $mealTypeName,

                'image_url' => "",
                'small_image_url' => "",

                'quantity' => $quantity,

                'kcal' => number_format($kcal, 1),

                'description' => "",

                'is_favorite' => 0,

                'macros' => [

                    'protein' => number_format($protein, 1),
                    'fats' => number_format($fats, 1),
                    'carbs' => number_format($carbs, 1),

                    'percentages' => [
                        'carbs' => round($macrosCarbsPercentage, 2) . '%',
                        'protein' => round($macrosProteinsPercentage, 2) . '%',
                        'fats' => round($macrosFatsPercentage, 2) . '%',
                    ]
                ],

                'macrosPercentage' => [
                    'kcalPercentage' => $kcalPercentage . '%',
                    'proteinPercentage' => $proteinsPercentage . '%',
                    'fatsPercentage' => $fatsPercentage . '%',
                    'carbsPercentage' => $carbsPercentage . '%',
                ],

                'serving_units' => $selectedUnits,
                'barcodes' => [],
                'isUserCreated' => 0,
            ];

            return $this->successResponse("AI Food Found", $response, 200);

        } catch (Throwable $th) {

            return $this->throwableError(
                "Something went wrong",
                $th->getMessage(),
                500
            );
        }
    }

    private function calculateNutritionGoals() {
        // Fetch user's initial measurement and current measurement
        $bmrData = UsersInitialMeasurement::where('user_id', Auth::user()->id)
                        ->orderBy('id', 'desc')->first();
        $userMeasurementData = UsersCurrentMeasurement::with('initialMeasurement', 'targetMeasurement')
                        ->where('user_id', Auth::user()->id)
                        ->orderBy('id', 'desc')->first();
    
        // Use current measurement data if available
        if ($userMeasurementData) {
            $bmrData = $userMeasurementData;
        }
    
        if ($bmrData) {
            // Fetch user data and necessary details
            $userData = User::where('id', Auth::user()->id)->first();
            
            // Default values
            $defaultWeight = 70;  // Average weight in kg
            $defaultAge = 30;     // Average age in years
            $defaultGender = 'female';  // Default to female if gender is missing
            $defaultHeight = 170;  // Average height in cm (5'7")

            // Fetching values from user data or fallback to defaults if not available
            $weight = isset($bmrData['weight']) ? $bmrData['weight'] : $defaultWeight;
            $age = UsersInitialMeasurement::where('user_id', Auth::user()->id)->orderBy('id', 'desc')->value('age');
            $age = $age ?: $defaultAge;  // Fallback to default age if no value is found
            $gender = strtolower($userData['gender']) ?: $defaultGender;  // Fallback to default gender if missing
            $height = $userData['height'] ?: $defaultHeight;  // Fallback to default height if missing

    
            // Calculate BMR
            if ($gender == 'female') {
                $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
            } else {
                $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
            }
    
            // Fetch user's step goal for activity factor
            $stepsGoal = StepsGoal::where('user_id', Auth::user()->id)
                ->whereNotNull('activity_factor')
                ->where('activity_factor', '!=', '')
                ->first();
    
            // Calculate TDEE (Total Daily Energy Expenditure)
            $activityFactor = $stepsGoal['activity_factor'] ?? 1.2375;
            $tdee = round(($bmr * $activityFactor));
    
            // Calculate target calorie and macros
            $targetCalorie = $tdee;
    
            // Macronutrient distribution (percentages)
            $targetCarbsCalories = $targetCalorie * 0.5;
            $targetCarbsGrams = round($targetCarbsCalories / 4);
    
            $targetProteinsCalories = $targetCalorie * 0.3;
            $targetProteinsGrams = round($targetProteinsCalories / 4);
    
            $targetFatsCalories = $targetCalorie * 0.2;
            $targetFatsGrams = round($targetFatsCalories / 9);
    
            // Return all the calculated values
            return [
                'bmr' => $bmr,
                'tdee' => $tdee,
                'targetCalorie' => $targetCalorie,
                'targetCarbsGrams' => $targetCarbsGrams,
                'targetProteinsGrams' => $targetProteinsGrams,
                'targetFatsGrams' => $targetFatsGrams,
            ];
        } else {
            // If no valid data, return zero values
            return [
                'bmr' => 0,
                'tdee' => 0,
                'targetCalorie' => 0,
                'targetCarbsGrams' => 0,
                'targetProteinsGrams' => 0,
                'targetFatsGrams' => 0,
            ];
        }
    }
}
