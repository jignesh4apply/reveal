<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FavouriteRecipe;
use App\Models\FoodPlanner;
use App\Models\Ingredient;
use App\Models\MealType;
use App\Models\NutritionFavourite;
use App\Models\NutritionIngredient;
use App\Models\NutritionIngredientBarcode;
use App\Models\NutritionIngredientCategory;
use App\Models\NutritionIngredientUnit;
use App\Models\Recipe;
use App\Models\RecipeNutrition;
use App\Models\StepsGoal;
use App\Models\TaskMilestone;
use App\Models\User;
use App\Models\UserLevelTaskLog;
use App\Models\userMilestone;
use App\Models\UserRecipe;
use App\Models\UsersCurrentMeasurement;
use App\Models\UsersInitialMeasurement;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use stdClass;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class FoodPlanerController extends ApiController
{
    public function searchNutrition(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'search' => ['nullable', 'string'],
                'page' => ['nullable', 'integer', 'min:1'],
                'limit' => ['nullable', 'integer', 'min:1']
            ]);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            // $recipes = Recipe::with('nutrition')->get()->toArray();

            // $recipeData = [];
            // foreach ($recipes as $recipe) {
            //     $minKcalNutrition = collect($recipe['nutrition'])
            //         ->sortBy('kcal')
            //         ->first();

            //     if ($minKcalNutrition) {
            //         $recipeData[] = [
            //             "id" => $recipe['id'],
            //             "title" => $recipe['title'],
            //             "kcal" => $minKcalNutrition['kcal'],
            //             "image" => asset(Storage::url($recipe['picture'])),
            //             "is_ingredients" => 0
            //         ];
            //     }
            // }

            // Fetch ingredients with pagination and search
            $query = NutritionIngredient::query()->select(['id', 'name', 'kcal']);

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orderByRaw("CASE
                                    WHEN name = '{$search}' THEN 1
                                    WHEN name LIKE '{$search}%' THEN 2
                                    ELSE 3
                                END");
            }

            $limit = $request->input('limit', 15);
            $page = $request->input('page', 1);

            // Cache results for improved performance
            $cacheKey = "nutrition_search_{$search}_page_{$page}_limit_{$limit}";
            $ingredients = Cache::remember($cacheKey, 300, function () use ($query, $limit) {
                return $query->paginate($limit);
            });

            // Add additional data to each ingredient
            $ingredientData = $ingredients->getCollection()->map(function ($ingredient) {
                $isFavourite = NutritionFavourite::where('user_id', Auth::id())
                    ->where('nutrition_ingredient_id', $ingredient->id)
                    ->exists();

                // Fetch unit options
                $unitOptions = NutritionIngredientUnit::where('nutrition_ingredient_id', $ingredient->id)
                    ->select('size_key', 'value', 'units')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->map(function ($row) {
                        // Extract only the text part before the last underscore and number, if present
                        if (preg_match('/^(.+?)_\d+g$/', $row->size_key, $matches)) {
                            $displayKey = $matches[1];
                        } else {
                            $displayKey = $row->size_key;
                        }
                        $row->display = $displayKey . ' - ' . $row->value;
                        return $row;
                    });

                $isUserCreated = 0;
                if($ingredient->user_id != null){
                    $isUserCreated = 1;
                }
                // Fetch barcodes
                $barcodes = NutritionIngredientBarcode::where('nutrition_ingredient_id', $ingredient->id)
                    ->pluck('barcode');

                return [
                    "id" => $ingredient->id,
                    "title" => $ingredient->name,
                    "kcal" => $ingredient->kcal,
                    "is_ingredient" => 1,
                    "is_favorite" => $isFavourite ? 1 : 0,
                    "serving_units" => $unitOptions, // Include unit options
                    "barcodes" => $barcodes, // Include barcodes
                    'isUserCreated' => $isUserCreated,
                ];
            })->toArray();

            $response = [
                'ingredients' => $ingredientData,
                'current_page' => $ingredients->currentPage(),
                'total_pages' => $ingredients->lastPage(),
                'total_items' => $ingredients->total(),
            ];

            return $this->successResponse("Nutrition Found", $response, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function fetchByBarcode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError("Fail", $validator->errors()->first(), 200);
        }

        $barcode = $request->get('barcode');

        // Fetch product with barcode and eager load related data
        $barcodeRecord = NutritionIngredientBarcode::where('barcode', $barcode)->first();

        if (!$barcodeRecord) {
            return $this->successResponse("Nutrition not Found", [], 404);
        }

        $record = NutritionIngredient::with(['barcodes:barcode,nutrition_ingredient_id', 'units:size_key,value'])
            ->where('id', $barcodeRecord->nutrition_ingredient_id)
            ->where('status', 1)
            ->first();

        if (!$record) {
            return $this->successResponse("Nutrition not Found", [], 404);
        }

        $userId = Auth::id();
        $isFavourite = NutritionFavourite::where('user_id', $userId)
            ->where('nutrition_ingredient_id', $record->id)
            ->exists();

        // Fetch related products efficiently
        $relatedRecords = NutritionIngredient::where('status', 1)
            ->where('name', 'LIKE', "{$record->name}%") // Faster query by avoiding '%keyword%'
            ->where('id', '!=', $record->id)
            ->limit(11)
            ->get(['id', 'name', 'kcal', 'protein', 'fats', 'carbs']);

        // Format actual product
        $actual = $this->formatNutritionData($record, $isFavourite);

        // Format related products
        $relatedRecords = $relatedRecords->map(function ($item) use ($userId) {
            return $this->formatNutritionData($item, NutritionFavourite::where('user_id', $userId)
                ->where('nutrition_ingredient_id', $item->id)
                ->exists());
        });

        $response = [
            [
                "title" => "Résultat du scan",
                "count" => 1,
                "nutritions" => [$actual]
            ]
        ];

        if ($relatedRecords->isNotEmpty()) {
            $response[] = [
                "title" => "Résultat(s) associés",
                "count" => $relatedRecords->count(),
                "nutritions" => $relatedRecords->toArray()
            ];
        }

        return $this->successResponse("Data Found", $response, 200);
    }

    /**
     * Optimized helper function to format nutrition ingredient data
     */
    private function formatNutritionData($ingredient, $isFavourite)
    {

        // Fetch unit options
        $unitOptions = NutritionIngredientUnit::where('nutrition_ingredient_id', $ingredient->id)
            ->select('size_key', 'value', 'units')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($row) use ($ingredient) {

                // Extract display key
                if (preg_match('/^(.+?)_\d+g$/', $row->size_key, $matches)) {
                    $displayKey = $matches[1];
                } else {
                    $displayKey = $row->size_key;
                }

                $quantity = (float) $row->value;

                // Calculate nutrition based on 100g
                $row->quantity = number_format($quantity, 2, '.', '');
                $row->kcal = number_format(($ingredient->kcal * $quantity) / 100, 2, '.', '');
                $row->protein = number_format(($ingredient->protein * $quantity) / 100, 2, '.', '');
                $row->fats = number_format(($ingredient->fats * $quantity) / 100, 2, '.', '');
                $row->carbs = number_format(($ingredient->carbs * $quantity) / 100, 2, '.', '');

                $row->display = $displayKey . ' - ' . $row->value;

                return $row;
            });

        // Fetch barcodes
        $barcodes = NutritionIngredientBarcode::where('nutrition_ingredient_id', $ingredient->id)
            ->pluck('barcode');

        return [
            "id" => $ingredient->id,
            "title" => $ingredient->name,
            "kcal" => $ingredient->kcal,
            "protein" => $ingredient->protein,
            "fats" => $ingredient->fats,
            "carbs" => $ingredient->carbs,
            "barcodes" => $barcodes ?? [],
            "serving_units" => $unitOptions ?? [],
            "is_ingredient" => 1,
            "is_favorite" => $isFavourite ? 1 : 0
        ];
    }
    public function dailyFoodPlanner(Request $request)
    {
        try {
            $data = $request->all();

            // Check if meal type is water and handle it separately
            if ($data['meal_type'] === 'water') {
                return $this->handleWater($data);
            }

            // Validate the input data
            $validator = Validator::make($data, $this->validationRules());
            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            // Fetch recipes and ingredients data
            $combinedData = $this->getCombinedData($data);

            // Validate if the provided nutrition details match the available data
            $matches = $this->validateNutritionDetails($data['nutritiondetails'], $combinedData);
            if (empty($matches)) {
                return $this->errorResponse("Recipe or Ingredient does not match with nutrition list.", [], 200);
            }

            // Update or create food planner entries
            $savedData = $this->updateOrCreateFoodPlannerEntries($data, $matches);

            return $this->getSingleResponse("Food added to your plan", $savedData, 200);
        } catch (Throwable $th) {
            // dd($th->getMessage());
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    private function handleWater(array $data)
    {
        $waterQuantity = 250;
        $existingWaterEntry = FoodPlanner::where([
            'meal_type' => 'water',
            'date' => $data['date'],
            'user_id' => Auth::user()->id
        ])->first();

        if ($existingWaterEntry) {
            if ($existingWaterEntry->water_consume + $waterQuantity > 2500) {
                $savedData = $this->getFoodPlannerData($data['date']);
                return $this->getSingleResponse("Water consumption limit reached", $savedData, 400);
            }
            $existingWaterEntry->water_consume += $waterQuantity;
            $existingWaterEntry->save();
        } else {
            $foodPlanner = new FoodPlanner;
            $foodPlanner->user_id = Auth::user()->id;
            $foodPlanner->recipe_or_ingredient_id = null;
            $foodPlanner->meal_type = 'water';
            $foodPlanner->date = $data['date'];
            $foodPlanner->portion = 0;
            $foodPlanner->kcal = null;
            $foodPlanner->water_consume = $waterQuantity;
            $foodPlanner->no_of_servings = 1;
            $foodPlanner->is_ingredient = 2;
            $foodPlanner->save();
        }

        $savedData = $this->getFoodPlannerData($data['date']);
        return $this->getSingleResponse("Water quantity updated", $savedData, 200);
    }

    protected function validationRules()
    {
        return [
            'meal_type' => ['required', 'string'],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'nutritiondetails' => ['required', 'array'],
            'nutritiondetails.*.recipe_or_ingredient_id' => ['required', 'numeric'],
            'nutritiondetails.*.is_ingredient' => ['required', 'integer', 'in:0,1,3,4'],
            'nutritiondetails.*.name' => ['required_if:is_ingredient,4','string'],
            'nutritiondetails.*.fats' => ['required_if:is_ingredient,4','numeric','min:0'],
            'nutritiondetails.*.carbs' => ['required_if:is_ingredient,4','numeric','min:0'],
            'nutritiondetails.*.protein' => ['required_if:is_ingredient,4','numeric','min:0'],
            'nutritiondetails.*.kcal' => ['required_if:is_ingredient,4','numeric','min:0'],
            'nutritiondetails.*.no_of_servings' => ['required', 'integer'],
            'nutritiondetails.*.quantity' => ['required', 'numeric'],
            // 'nutritiondetails.*.kcal' => ['required', 'numeric'], // Uncomment if kcal is required
            'nutritiondetails.*.selected_unit_json' => ['nullable','array'],
        ];
    }

    protected function getCombinedData($payload)
    {
        try {
            $ids = array_column($payload['nutritiondetails'], 'recipe_or_ingredient_id');

            $recipes = Recipe::with('nutrition')->whereIn('id', $ids)->get()->toArray();
            $recipeData = array_map(fn($recipe) => [
                "recipe_or_ingredient_id" => $recipe['id'],
                "title" => $recipe['title'],
                "image" => asset(Storage::url($recipe['picture'])),
                "is_ingredient" => 0
            ], $recipes);

            $ingredients = NutritionIngredient::select('id', 'name', 'small_image_url')->whereIn('id', $ids)->get()->toArray();
            $ingredientData = array_map(fn($ingredient) => [
                "recipe_or_ingredient_id" => $ingredient['id'],
                "title" => $ingredient['name'],
                "image" => $ingredient['small_image_url'] ?? '',
                "is_ingredient" => 1
            ], $ingredients);

            $userRecipes = UserRecipe::select('id', 'title', 'image')->whereIn('id', $ids)->get()->toArray();
            $userRecipesData = array_map(fn($userRecipe) => [
                "recipe_or_ingredient_id" => $userRecipe['id'],
                "title" => $userRecipe['title'],
                "image" => $userRecipe['image'] ? asset(Storage::url($userRecipe['image'])) : '',
                "is_ingredient" => 3
            ], $userRecipes);

            return array_merge($recipeData, $ingredientData, $userRecipesData);
        } catch (\Exception $e) {
            Log::error('Error in yourMethod: ' . $e->getMessage());

            return response()->json(['error' => 'An error occurred while fetching data.'], 500);
        }
    }

    protected function validateNutritionDetails(array $nutritionDetails, array $combinedData)
    {
        return array_filter($combinedData, function ($nutritionData) use ($nutritionDetails) {
            foreach ($nutritionDetails as $nutritionValue) {
                if ($nutritionData['recipe_or_ingredient_id'] == $nutritionValue['recipe_or_ingredient_id'] && $nutritionData['is_ingredient'] == $nutritionValue['is_ingredient']) {
                    return true;
                }
            }
            return false;
        });
    }

    private function updateOrCreateFoodPlannerEntries(array $data, array $matches)
    {
        $filtered = array_filter($data['nutritiondetails'], function ($item) use ($matches) {
            foreach ($matches as $match) {
                if (
                    $item['recipe_or_ingredient_id'] === $match['recipe_or_ingredient_id'] &&
                    $item['is_ingredient'] === $match['is_ingredient']
                ) {
                    return true;
                }
            }
            return false;
        });

       // Group the filtered data by is_ingredient
        $grouped = collect($filtered)->groupBy('is_ingredient');
        // dd($grouped);
        // Store each group in a separate variable
        $filteredIngredients = $grouped->get(1, []); // is_ingredient = 1
        $filteredRecipes = $grouped->get(0, []);     // is_ingredient = 0
        $filteredUserRecipes = $grouped->get(3, []); // is_ingredient = 3

        // // Output variables for testing
        // dd([
        //     'filteredIngredients' => $filteredIngredients,
        //     'filteredRecipes' => $filteredRecipes,
        //     'filteredUserRecipes' => $filteredUserRecipes,
        // ]);

        foreach ($filteredIngredients as $foodPlannerData_I) {
            // Calorie Calculation from request

            $ingredientData = NutritionIngredient::where(['id' => $foodPlannerData_I['recipe_or_ingredient_id']])->first();

            if (isset($foodPlannerData_I['quantity']) && !empty($foodPlannerData_I['quantity'])) {
                if (!is_null($ingredientData) && !empty($ingredientData)) {
                    $kcal = ($foodPlannerData_I['quantity'] * $ingredientData['kcal']) / 100;
                    $carbs = ($foodPlannerData_I['quantity'] * $ingredientData['carbs']) / 100;
                    $fats = ($foodPlannerData_I['quantity'] * $ingredientData['fats']) / 100;
                    $proteins = ($foodPlannerData_I['quantity'] * $ingredientData['protein']) / 100;
                } else {
                    $kcal = 0;
                    $carbs = 0;
                    $fats = 0;
                    $proteins = 0;
                }
            }else{
                $kcal = 0;
                $carbs = 0;
                $fats = 0;
                $proteins = 0;
            }

            $existingEntry = FoodPlanner::where([
                'recipe_or_ingredient_id' => $foodPlannerData_I['recipe_or_ingredient_id'],
                'is_ingredient' => $foodPlannerData_I['is_ingredient'],
                'meal_type' => $data['meal_type'],
                'date' => $data['date'],
                'user_id' => Auth::user()->id
            ])->first();

            if ($existingEntry) {
                $existingEntry->kcal += $kcal;
                $existingEntry->carbs += $carbs;
                $existingEntry->fats += $fats;
                $existingEntry->proteins += $proteins;
                $existingEntry->portion += $foodPlannerData_I['quantity'];
                $existingEntry->no_of_servings += $foodPlannerData_I['no_of_servings'];
                $existingEntry->save();
            } else {
                $foodPlanner = new FoodPlanner;
                $foodPlanner->user_id = Auth::user()->id;
                $foodPlanner->recipe_or_ingredient_id = $foodPlannerData_I['recipe_or_ingredient_id'];
                $foodPlanner->meal_type = $data['meal_type'];
                $foodPlanner->date = $data['date'];
                $foodPlanner->portion = $foodPlannerData_I['quantity'];
                $foodPlanner->kcal = $kcal;
                $foodPlanner->carbs = $carbs;
                $foodPlanner->fats = $fats;
                $foodPlanner->proteins = $proteins;
                $foodPlanner->no_of_servings = $foodPlannerData_I['no_of_servings'];
                $foodPlanner->is_ingredient = $foodPlannerData_I['is_ingredient'];
                $foodPlanner->save();
            }
        }

        foreach ($filteredUserRecipes as $foodPlannerData_UR) {
            // Calorie Calculation from request

            $userRecipeData = UserRecipe::where(['id' => $foodPlannerData_UR['recipe_or_ingredient_id']])->first();

            if (isset($foodPlannerData_UR['quantity']) && !empty($foodPlannerData_UR['quantity'])) {
                if (!is_null($userRecipeData) && !empty($userRecipeData)) {
                    $kcal = ($foodPlannerData_UR['quantity'] * $userRecipeData['kcal']) / 100;
                    $carbs = ($foodPlannerData_UR['quantity'] * $userRecipeData['carbs']) / 100;
                    $fats = ($foodPlannerData_UR['quantity'] * $userRecipeData['fats']) / 100;
                    $proteins = ($foodPlannerData_UR['quantity'] * $userRecipeData['protein']) / 100;
                } else {
                    $kcal = 0;
                    $carbs = 0;
                    $fats = 0;
                    $proteins = 0;
                }
            }else{
                $kcal = 0;
                $carbs = 0;
                $fats = 0;
                $proteins = 0;
            }

            $existingEntry = FoodPlanner::where([
                'recipe_or_ingredient_id' => $foodPlannerData_UR['recipe_or_ingredient_id'],
                'is_ingredient' => $foodPlannerData_UR['is_ingredient'],
                'meal_type' => $data['meal_type'],
                'date' => $data['date'],
                'user_id' => Auth::user()->id
            ])->first();

            if ($existingEntry) {
                $existingEntry->kcal += $kcal;
                $existingEntry->carbs += $carbs;
                $existingEntry->fats += $fats;
                $existingEntry->proteins += $proteins;
                $existingEntry->portion += $foodPlannerData_UR['quantity'];
                $existingEntry->no_of_servings += $foodPlannerData_UR['no_of_servings'];
                $existingEntry->save();
            } else {
                $foodPlanner = new FoodPlanner;
                $foodPlanner->user_id = Auth::user()->id;
                $foodPlanner->recipe_or_ingredient_id = $foodPlannerData_UR['recipe_or_ingredient_id'];
                $foodPlanner->meal_type = $data['meal_type'];
                $foodPlanner->date = $data['date'];
                $foodPlanner->portion = $foodPlannerData_UR['quantity'];
                $foodPlanner->kcal = $kcal;
                $foodPlanner->carbs = $carbs;
                $foodPlanner->fats = $fats;
                $foodPlanner->proteins = $proteins;
                $foodPlanner->no_of_servings = $foodPlannerData_UR['no_of_servings'];
                $foodPlanner->is_ingredient = $foodPlannerData_UR['is_ingredient'];
                $foodPlanner->save();
            }
        }

        foreach ($filteredRecipes as $foodPlannerData_R) {

            $recipe = Recipe::where(['id' => $foodPlannerData_R['recipe_or_ingredient_id']])->first();

            if (isset($foodPlannerData_R['quantity']) && !empty($foodPlannerData_R['quantity'])) {

                $nutrition = RecipeNutrition::where(['recipe_id' => $foodPlannerData_R['recipe_or_ingredient_id'], 'id' => $foodPlannerData_R['nutrition_id']])->first();

                if (!is_null($nutrition) && !empty($nutrition)) {
                    $actualQuantity = RecipeNutrition::with(relations: 'nutritioningredients')
                        ->where('recipe_id', $foodPlannerData_R['recipe_or_ingredient_id'])
                        ->where('id', $foodPlannerData_R['nutrition_id'])
                        ->get()
                        ->flatMap(function ($nutrition) {
                            return $nutrition->nutritioningredients->map(fn($ingredient) => $ingredient->quantity);
                        })->sum();

                    $multiplyBy = round($foodPlannerData_R['quantity'] / $actualQuantity, 2);

                    $kcal = round($nutrition->kcal * $multiplyBy, 2);
                    $proteins = round($nutrition->protien * $multiplyBy, 2);
                    $fats = round($nutrition->fat * $multiplyBy, 2);
                    $carbs = round($nutrition->carbs * $multiplyBy, 2);

                } else {
                    $kcal = 0;
                    $carbs = 0;
                    $fats = 0;
                    $proteins = 0;
                }
            }else{
                $kcal = 0;
                $carbs = 0;
                $fats = 0;
                $proteins = 0;
            }

            $existingEntry = FoodPlanner::where([
                'recipe_or_ingredient_id' => $foodPlannerData_R['recipe_or_ingredient_id'],
                'is_ingredient' => $foodPlannerData_R['is_ingredient'],
                'meal_type' => $data['meal_type'],
                'date' => $data['date'],
                'user_id' => Auth::user()->id
            ])->first();

            if ($existingEntry) {
                $existingEntry->kcal += $kcal;
                $existingEntry->carbs += $carbs;
                $existingEntry->fats += $fats;
                $existingEntry->proteins += $proteins;
                $existingEntry->portion += $foodPlannerData_R['quantity'];
                $existingEntry->no_of_servings += $foodPlannerData_R['no_of_servings'];
                $existingEntry->save();
            } else {
                $foodPlanner = new FoodPlanner;
                $foodPlanner->user_id = Auth::user()->id;
                $foodPlanner->recipe_or_ingredient_id = $foodPlannerData_R['recipe_or_ingredient_id'];
                $foodPlanner->meal_type = $data['meal_type'];
                $foodPlanner->date = $data['date'];
                $foodPlanner->portion = $foodPlannerData_R['quantity'];
                $foodPlanner->kcal = $kcal;
                $foodPlanner->carbs = $carbs;
                $foodPlanner->fats = $fats;
                $foodPlanner->proteins = $proteins;
                $foodPlanner->no_of_servings = $foodPlannerData_R['no_of_servings'];
                $foodPlanner->is_ingredient = $foodPlannerData_R['is_ingredient'];
                $foodPlanner->save();
            }
        }

        return $this->getFoodPlannerData($data['date']);
    }

    protected function getFoodPlannerData($date)
    {
        return FoodPlanner::where([
            'user_id' => Auth::user()->id,
            'date' => $date
        ])->get()->toArray();
    }

    public function getFoodPlanner(Request $request)
    {
        try {
            $validation = [
                'date' => ['required', 'date', 'date_format:Y-m-d'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $data = $request->all();

            $allMealTypes = MealType::where('visible_in_tracker', 1)->get()->toArray();

            // Extract the 'slug' from each meal type and store them in a new array
            $mealTypes = array_map(function ($mealType) {
                return strtolower($mealType['slug']); // Convert to lowercase
            }, $allMealTypes);

            // Add 'water' to the end of the array
            $mealTypes[] = 'water';
            $response = [];

            // Initialize variables for calorie calculation
            $targetCalorie = 10000; // Assuming a target of 10000 calories
            $totalConsumedCalorie = 0;
            $totalConsumedCarbs = 0;
            $totalConsumedProteins = 0;
            $totalConsumedFats = 0;

            $totalCaloriesByType = []; // Array to store total calories by meal type
            $totalCarbsByType = []; // Array to store total carbs by meal type
            $totalProteinsByType = []; // Array to store total proteins by meal type
            $totalFatsByType = []; // Array to store total fats by meal type

            $bmrData = UsersInitialMeasurement::where('user_id', Auth::user()->id)->orderBy('id', 'desc')->first();
            // $userMeasurementData = UsersCurrentMeasurement::with('initialMeasurement', 'targetMeasurement')
            //     ->where('user_id', Auth::user()->id)
            //     ->orderBy('id', 'desc')
            //     ->first();
            // if ($userMeasurementData) {
            //     // If there's a record in UsersCurrentMeasurement, use it for weight
            //     $bmrData = $userMeasurementData;
            // }
            if ($bmrData) {
                $userData = User::where('id', Auth::user()->id)->first();
                $weight = $bmrData['weight'];
                $age = UsersInitialMeasurement::where('user_id', Auth::user()->id)->orderBy('id', 'desc')->value('age');
                $gender = strtolower($userData['gender']);
                $height = $userData['height'];

                // Calculate BMR
                if ($gender == 'female') {
                    $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
                } else {
                    $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
                }

                $stepsGoal = StepsGoal::where(['user_id' => Auth::user()->id])->first();

                // Calculate TDEE
                $activityFactor = $stepsGoal['activity_factor'] ?? 1.237;
                $tdee = round(($bmr * $activityFactor));

                $targetCalorie = $tdee;
            }

            foreach ($mealTypes as $mealType) {
                // Initialize total calories for the current meal type
                $totalCaloriesByType[$mealType] = 0;
                $totalCarbsByType[$mealType] = 0;
                $totalProteinsByType[$mealType] = 0;
                $totalFatsByType[$mealType] = 0;

                $waterId = 0;
                $waterconsume = 0;
                $waterRemainingPortion = 10;
                $waterCompletedPortion = 0;
                // Fetch recipes or ingredients for each meal type
                $mealList = FoodPlanner::where(['user_id' => Auth::user()->id, 'meal_type' => $mealType, 'date' => $data['date']])->get()->toArray();
                $mealData = [];
                $mealTypeImage = MealType::where('slug', $mealType)->pluck('image')->first();
                $mealTypeID = MealType::where('slug', $mealType)->pluck('id')->first();
                if (empty($mealList)) {
                    $isLoggedToday = 0;
                }
                foreach ($mealList as $meal) {
                    if ($meal['date'] == date('Y-m-d')) {
                        $isLoggedToday = 1;
                    } else {
                        $isLoggedToday = 0;
                    }
                    if ($meal['is_ingredient'] == 1) {
                        $ingredientData = NutritionIngredient::where(['id' => $meal['recipe_or_ingredient_id']])->first();

                        if (!empty($ingredientData)) {
                            $mealData[] = [
                                "id" => $meal['id'],
                                "recipe_or_ingredient_id" => $meal['recipe_or_ingredient_id'],
                                "title" => $ingredientData['name'],
                                "meal_type" => $meal['meal_type'],
                                "date" => $meal['date'],
                                "kcal" => $meal['kcal'],
                                "carbs" => $meal['carbs'],
                                "proteins" => $meal['proteins'],
                                "fats" => $meal['fats'],
                                "quantity" => $meal['portion'],
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => asset(Storage::url($ingredientData['image'])),
                                "is_ingredient" => $meal['is_ingredient']
                            ];

                            $totalCaloriesByType[$mealType] += (int)round($meal['kcal']); // Accumulate total consumed calories for this meal type
                            $totalConsumedCalorie += (int)$meal['kcal']; // Accumulate total consumed calories overall
                            $totalConsumedCarbs += (int)round($meal['carbs']); // Accumulate total consumed carbs overall
                            $totalConsumedProteins += (int)round($meal['proteins']); // Accumulate total consumed proteins overall
                            $totalConsumedFats += (int)round($meal['fats']); // Accumulate total consumed fats overall
                        }
                    }
                    if ($meal['is_ingredient'] == 3) {
                        $userRecipeData = UserRecipe::where(['id' => $meal['recipe_or_ingredient_id']])->first();

                        if (!empty($userRecipeData)) {
                            $mealData[] = [
                                "id" => $meal['id'],
                                "recipe_or_ingredient_id" => $meal['recipe_or_ingredient_id'],
                                "title" => $userRecipeData['title'],
                                "meal_type" => $meal['meal_type'],
                                "date" => $meal['date'],
                                "kcal" => $meal['kcal'],
                                "carbs" => $meal['carbs'],
                                "proteins" => $meal['proteins'],
                                "fats" => $meal['fats'],
                                "quantity" => $meal['portion'],
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => $userRecipeData['image'] ? asset(Storage::url($userRecipeData['image'])) : '',
                                "is_ingredient" => $meal['is_ingredient']
                            ];

                            $totalCaloriesByType[$mealType] += (int)round($meal['kcal']); // Accumulate total consumed calories for this meal type
                            $totalConsumedCalorie += (int)$meal['kcal']; // Accumulate total consumed calories overall
                            $totalConsumedCarbs += (int)round($meal['carbs']); // Accumulate total consumed carbs overall
                            $totalConsumedProteins += (int)round($meal['proteins']); // Accumulate total consumed proteins overall
                            $totalConsumedFats += (int)round($meal['fats']); // Accumulate total consumed fats overall
                        }
                    }
                    if ($meal['is_ingredient'] == 0) {

                        $recipeData = Recipe::where(['id' => $meal['recipe_or_ingredient_id']])->first();

                        if (!empty($recipeData)) {
                            $mealData[] = [
                                "id" => $meal['id'],
                                "recipe_or_ingredient_id" => $meal['recipe_or_ingredient_id'],
                                "title" => $recipeData['title'],
                                "meal_type" => $meal['meal_type'],
                                "date" => $meal['date'],
                                "kcal" => $meal['kcal'],
                                "carbs" => $meal['carbs'],
                                "proteins" => $meal['proteins'],
                                "fats" => $meal['fats'],
                                "quantity" => $meal['portion'],
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => $recipeData['picture'] ? asset(Storage::url($recipeData['picture'])) : '',
                                "is_ingredient" => $meal['is_ingredient']
                            ];

                            $totalCaloriesByType[$mealType] += (int)round($meal['kcal']); // Accumulate total consumed calories for this meal type
                            $totalConsumedCalorie += (int)$meal['kcal']; // Accumulate total consumed calories overall
                            $totalConsumedCarbs += (int)round($meal['carbs']); // Accumulate total consumed carbs overall
                            $totalConsumedProteins += (int)round($meal['proteins']); // Accumulate total consumed proteins overall
                            $totalConsumedFats += (int)round($meal['fats']); // Accumulate total consumed fats overall
                        }
                    }
                }

                if ($mealType == 'water') {
                    if ($mealList != []) {
                        $waterconsume = $mealList[0]['water_consume'];
                        $waterId = $mealList[0]['id'];
                        $waterCompletedPortion = $waterconsume / 250;
                        $waterRemainingPortion = 10 - $waterCompletedPortion;
                    }
                    $mealRecipe = [
                        "id" => $waterId,
                        "name" => ucfirst($mealType),
                        "water_consume" => $waterconsume,
                        "water_completed_portion" => $waterCompletedPortion,
                        "water_remaining_portion" => $waterRemainingPortion,
                    ];
                } else {
                    // Store meal type and its recipes
                    $mealRecipe = [
                        "id" => $mealTypeID,
                        "name" => ucfirst($mealType),
                        "image" => $mealTypeImage ? asset(Storage::url($mealTypeImage)) : '',
                        "totalCalories" => $totalCaloriesByType[$mealType],
                        "isLoggedToday" => $isLoggedToday,
                        "recipe" => $mealData
                    ];
                }
                $response[] = $mealRecipe;
            }

            // Calculate remaining calories
            $remainingCalorie = $targetCalorie - $totalConsumedCalorie;
            // $remainingCalorie = max($remainingCalorie, 0);

            // Calorie And Macro
            // Calorie
            foreach ($mealTypes as $mealType) {
                if ($mealType == "water") {
                    continue; // Skip this iteration if mealType is "water"
                }
                $mealWiseCalorie[$mealType] = $totalCaloriesByType[$mealType];
            }
            $goal = $targetCalorie;

            foreach ($mealWiseCalorie as $key => $value) {
                $consumedCalorie = $value;
                $percent = round((100 * $consumedCalorie) / $goal);
                if ($percent > 100) {
                    $percent = 100;
                }
                $calorie[] = [
                    "name" => $key,
                    "percent" => $percent . "%",
                    "consumedCalorie" => $consumedCalorie
                ];
            }

            $calorieDetails = new stdClass();
            foreach ($calorie as $index => $meal) {
                $calorieDetails->{$index} = $meal;
            }

            $calorie = [
                "name" => "calorie",
                "details" => $calorieDetails,
                "netCalorie" => $totalConsumedCalorie,
                "goal" => $targetCalorie
            ];
            $response[] = $calorie;

            // Macro
            // Calculate target calories for each macro
            // $targetCarbsCalories = ($goal * 40) / 100;
            // $targetProteinsCalories = ($goal * 40) / 100;
            // $targetFatsCalories = ($goal * 40) / 100;

            // // Convert target calories to grams
            // $targetCarbsGrams = round($targetCarbsCalories / 8);
            // $targetProteinsGrams = round($targetProteinsCalories / 10);
            // $targetFatsGrams = round($targetFatsCalories / 5);

            // // Calculate the percentage of each macro consumed
            // $carbsPercentage = round(($totalConsumedCarbs / $targetCarbsGrams) * 100, 2);
            // $proteinsPercentage = round(($totalConsumedProteins / $targetProteinsGrams) * 100, 2);
            // $fatsPercentage = round(($totalConsumedFats / $targetFatsGrams) * 100, 2);

            // // Cap the percentage at 100%
            // $carbsPercentage = min($carbsPercentage, 100);
            // $proteinsPercentage = min($proteinsPercentage, 100);
            // $fatsPercentage = min($fatsPercentage, 100);

            // New Macro
            $targetCarbsCalories = $goal * 0.4;
            $targetCarbsGrams = round($targetCarbsCalories / 4);

            $targetProteinsCalories = $goal * 0.3;
            $targetProteinsGrams = round($targetProteinsCalories / 4);

            $targetFatsCalories = $goal * 0.3;
            $targetFatsGrams = round($targetFatsCalories / 9);

            $carbsPercentage = round(($totalConsumedCarbs / $targetCarbsGrams) * 100);
            $proteinsPercentage = round(($totalConsumedProteins / $targetProteinsGrams) * 100);
            $fatsPercentage = round(($totalConsumedFats / $targetFatsGrams) * 100);

            $carbsPercentage = min($carbsPercentage, 100);
            $proteinsPercentage = min($proteinsPercentage, 100);
            $fatsPercentage = min($fatsPercentage, 100);
            // End

            // Prepare the macro array with the target and percentage consumed
            $macro = [
                'name' => "macro",
                'carbs' => [
                    'target' => $targetCarbsGrams,
                    'consumed' => $totalConsumedCarbs,
                    'percentage' => $carbsPercentage . '%'
                ],

                'proteins' => [
                    'target' => $targetProteinsGrams,
                    'consumed' => $totalConsumedProteins,
                    'percentage' => $proteinsPercentage . '%'
                ],

                'fats' => [
                    'target' => $targetFatsGrams,
                    'consumed' => $totalConsumedFats,
                    'percentage' => $fatsPercentage . '%'
                ]
            ];

            $response[] = $macro;

            // Add calorie details to the response
            $calorieDetails = [
                "name" => "calorieDetails",
                "targetCalorie" => $targetCalorie,
                "mealWiseCalorie" => $mealWiseCalorie,
                "totalConsumedCalorie" => $totalConsumedCalorie,
                "remainingCalorie" => $remainingCalorie
            ];

            $response[] = $calorieDetails;

            $foodPlanner = FoodPlanner::where(['user_id' => Auth::user()->id])->latest()->first();
            $isLogForFood = 0;

            if ($foodPlanner) {
                $isLogForFood = 1;
            }
            $isFoodLoggedEver = [
                "name" => "foodLogged",
                'isFoodLoggedEver' => $isLogForFood
            ];
            $response[] = $isFoodLoggedEver;

            return $this->successResponse("Data Found", $response, 200);
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }


    public function deleteFoodPlanner(Request $request)
    {
        try {
            /* Validate Data */
            $validation = [
                'id' => ['required', 'integer'],
                'date' => ['required', 'date', 'date_format:Y-m-d'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $getLoggedFood = FoodPlanner::where(['id' => $request->id, 'user_id' => Auth::user()->id, 'date' => $request->date])->first();
            if ($getLoggedFood != null) {
                if ($getLoggedFood['is_ingredient'] == 2 && $getLoggedFood['water_consume'] > 250) {
                    $waterConsumed = $getLoggedFood['water_consume'] - 250;
                    $getLoggedFood->update(['water_consume' => $waterConsumed]);
                    $waterCompletedPortion = $getLoggedFood['water_consume'] / 250;
                    $waterRemainingPortion = 10 - $waterCompletedPortion;
                    $mealRecipe = [
                        "id" => $getLoggedFood['id'],
                        "name" => ucfirst($request->meal_type),
                        "water_consume" => $getLoggedFood['water_consume'],
                        "water_completed_portion" => $waterCompletedPortion,
                        "water_remaining_portion" => $waterRemainingPortion,
                    ];
                    return $this->successResponse("Water Consumption Updated", $mealRecipe, 200);
                }
                $getLoggedFood->delete();
                $mealList = FoodPlanner::where(['user_id' => Auth::user()->id, 'meal_type' => $request->meal_type, 'date' => $request->date])->get()->toArray();
                $mealData = [];

                foreach ($mealList as $meal) {
                    if ($meal['is_ingredient'] == 0) {
                        $recipeData = Recipe::with('nutrition')->where(['id' => $meal['recipe_or_ingredient_id']])->first();
                        if (!empty($recipeData)) {
                            $nutritionDetails = $recipeData->nutrition()->orderBy('kcal', 'asc')->first();

                            $mealData[] = [
                                "id" => $meal['id'],
                                "recipe_or_ingredient_id" => $meal['recipe_or_ingredient_id'],
                                "title" => $recipeData['title'],
                                "meal_type" => $meal['meal_type'],
                                "date" => $meal['date'],
                                "kcal" => $meal['kcal'],
                                "carbs" => $nutritionDetails['carbs'],
                                "proteins" => $nutritionDetails['protien'],
                                "fats" => $nutritionDetails['fat'],
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => asset(Storage::url($recipeData['picture'])),
                                "is_ingredient" => $meal['is_ingredient'],
                            ];
                        }
                    } else if ($meal['is_ingredient'] == 1) {
                        $ingredientData = NutritionIngredient::where(['id' => $meal['recipe_or_ingredient_id']])->first();
                        if (!empty($ingredientData)) {
                            $mealData[] = [
                                "id" => $meal['id'],
                                "recipe_or_ingredient_id" => $meal['recipe_or_ingredient_id'],
                                "title" => $ingredientData['name'],
                                "meal_type" => $meal['meal_type'],
                                "date" => $meal['date'],
                                "kcal" => $meal['kcal'],
                                "carbs" => $ingredientData['carbs'],
                                "proteins" => $ingredientData['protein'],
                                "fats" => $ingredientData['fat'],
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => asset(Storage::url($ingredientData['image'])),
                                "is_ingredient" => $meal['is_ingredient']
                            ];
                        }
                    } else if ($meal['is_ingredient'] == 0) {
                        $recipe = Recipe::where(['id' => $meal['recipe_or_ingredient_id']])->first();
                        if (!empty($recipe)) {
                            $mealData[] = [
                                "id" => $meal['id'],
                                "recipe_or_ingredient_id" => $meal['recipe_or_ingredient_id'],
                                "title" => $recipe['name'],
                                "meal_type" => $meal['meal_type'],
                                "date" => $meal['date'],
                                "kcal" => $meal['kcal'],
                                "carbs" => $meal['carbs'],
                                "proteins" => $meal['protein'],
                                "fats" => $meal['fat'],
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => $recipe['picture'] ? asset(Storage::url($recipe['picture'])) : '',
                                "is_ingredient" => $meal['is_ingredient']
                            ];
                        }
                    }
                }

                $mealRecipe = [
                    "name" => ucfirst($request->meal_type),
                    "recipe" => $mealData
                ];
                return $this->successResponse("Item deleted", $mealRecipe, 200);
            }
            return $this->successResponse("Data Not Found.", [], 200);
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function calendarWiseFoodLogRecord(Request $request)
    {
        try {
            $validation = [
                'date' => ['required', 'date', 'date_format:Y-m'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $foodPlanners = FoodPlanner::where('user_id', Auth::user()->id)
                ->where('date', 'like', $request->date . '%')
                ->get()->toArray();
            if ($foodPlanners) {
                return $this->successResponse("data found", $foodPlanners, 200);
            }
            return $this->successResponse("data not found", $foodPlanners, 200);
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function nutritionHistory(Request $request)
    {
        try {
            $history = collect(FacadesDB::select("
                SELECT *
                FROM (
                    -- Normal ingredients / recipes
                    SELECT
                        MAX(id) as latest_id,
                        recipe_or_ingredient_id,
                        NULL as AI_food_name,
                        is_ingredient
                    FROM food_planners
                    WHERE user_id = ?
                    AND is_ingredient IN (0,1,3)
                    GROUP BY recipe_or_ingredient_id, is_ingredient

                    UNION ALL

                    -- AI foods
                    SELECT
                        MAX(id) as latest_id,
                        0 as recipe_or_ingredient_id,
                        AI_food_name,
                        is_ingredient
                    FROM food_planners
                    WHERE user_id = ?
                    AND is_ingredient = 4
                    GROUP BY AI_food_name, is_ingredient
                ) as combined
                ORDER BY latest_id DESC
                LIMIT 10
            ", [Auth::user()->id, Auth::user()->id]));

            $ingredientIds = $history->where('is_ingredient', 1)->pluck('recipe_or_ingredient_id')->filter()->toArray();

            $userRecipeIds = $history->where('is_ingredient', 3)->pluck('recipe_or_ingredient_id')->filter()->toArray();

            $recipeIds = $history->where('is_ingredient', 0)->pluck('recipe_or_ingredient_id')->filter()->toArray();

            $aIFoodNames = $history->where('is_ingredient', 4)->pluck('AI_food_name')->filter()->toArray();

            $ingredients = collect(NutritionIngredient::whereIn('id', $ingredientIds)->get());
            $userRecipes = collect(UserRecipe::whereIn('id', $userRecipeIds)->get());
            $recipes = collect(Recipe::whereIn('id', $recipeIds)->get());

            // Fetch aiFooddata from food_planners - get distinct by name (latest one for each name)
            $aiFoods = collect(FoodPlanner::where('user_id', Auth::user()->id)
                ->where('is_ingredient', 4)
                ->whereIn('AI_food_name', $aIFoodNames)
                ->get()
                ->keyBy('AI_food_name') // Group by AI_food_name to get one per food name
                ->values()); // Reset keys

            $ingredients = $ingredients->map(function ($item) {
                $isfavourite = NutritionFavourite::where('user_id', Auth::user()->id)->where('nutrition_ingredient_id', $item->id)->first() ? 1 : 0;
                $ingredientData = $item->only(['id', 'kcal', 'protein', 'fats', 'carbs']);
                $ingredientData = array_map(function ($value) {
                    return $value === null ? '' : $value;
                }, $ingredientData);
                // Fetch barcodes for the ingredient
                $barcodes = NutritionIngredientBarcode::where('nutrition_ingredient_id', $item->id)->pluck('barcode');
                // Fetch unit options
                $servingUnits = NutritionIngredientUnit::where('nutrition_ingredient_id', $item->id)
                    ->select('size_key', 'value', 'units')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->map(function ($row) {
                        // Extract only the text part before the last underscore and number, if present
                        if (preg_match('/^(.+?)_\d+g$/', $row->size_key, $matches)) {
                            $displayKey = $matches[1];
                        } else {
                            $displayKey = $row->size_key;
                        }
                        $row->display = $displayKey . ' - ' . $row->value;
                        return $row;
                    });

                $isUserCreated = 0;
                if($item->user_id != null){
                    $isUserCreated = 1;
                }

                $ingredientData['title'] = $item->name;
                $ingredientData['is_ingredient'] = 1;
                $ingredientData['is_favorite'] = $isfavourite;
                $ingredientData["serving_units"] = $servingUnits; // Include serving units
                $ingredientData["barcodes"] = $barcodes; // Include barcodes
                $ingredientData["isUserCreated"] = $isUserCreated;
                return $ingredientData;
            });

            $userRecipes = $userRecipes->map(function ($item) {
                $userRecipeData = $item->only(['id', 'kcal','protein', 'fats', 'carbs', 'title']);
                $userRecipeData['is_ingredient'] = 3;
                $userRecipeData['is_favorite'] = 0; // Assuming user recipes are not marked as favorite
                $userRecipeData = array_map(function ($value) {
                    return $value === null ? '' : $value;
                }, $userRecipeData);
                return $userRecipeData;
            });

            $recipes = $recipes->map(function ($item) {
                $isfavourite = FavouriteRecipe::where('user_id', Auth::user()->id)->where('recipe_id', $item->id)->first() ? 1 : 0;

                $nutrition = RecipeNutrition::where('recipe_id', $item->id)
                    ->orderBy('kcal', 'asc') // Ensures consistent selection (e.g., least kcal)
                    ->first();

                if (!is_null($nutrition)) {
                    // Use $nutrition's ID to fetch related 'nutritioningredients'
                    $actualQuantity = $nutrition->nutritioningredients
                        ->map(fn($ingredient) => $ingredient->quantity)
                        ->sum();

                    $multiplyBy = round(100 / $actualQuantity, 2);
                    $nutritionId = $nutrition->id;
                    $kcal = round($nutrition->kcal * $multiplyBy, 2);
                    $proteins = round($nutrition->protien * $multiplyBy, 2);
                    $fats = round($nutrition->fat * $multiplyBy, 2);
                    $carbs = round($nutrition->carbs * $multiplyBy, 2);
                } else {
                    $nutritionId = 0;
                    $kcal = 0;
                    $carbs = 0;
                    $fats = 0;
                    $proteins = 0;
                }

                $recipeData = $item->only(['id', 'title']);
                $recipeData['kcal'] = "$kcal";
                $recipeData['protein'] = "$proteins";
                $recipeData['fats'] = "$fats";
                $recipeData['carbs'] = "$carbs";
                $recipeData['is_ingredient'] = 0;
                $recipeData['is_favorite'] = $isfavourite;
                $recipeData['nutritionId'] = $nutritionId;
                return $recipeData;
            });

            // Handle aiFooddata (is_ingredient = 4) - convert macros to 100g
            $aiFoodsProcessed = $aiFoods->map(function ($item) {
                $portion = (float)$item->portion;
                $kcal100g = $portion > 0 ? ($item->kcal / $portion) * 100 : 0;
                $carbs100g = $portion > 0 ? ($item->carbs / $portion) * 100 : 0;
                $proteins100g = $portion > 0 ? ($item->proteins / $portion) * 100 : 0;
                $fats100g = $portion > 0 ? ($item->fats / $portion) * 100 : 0;

                return [
                    'fp_id' => $item->id, // Fp_Id (food_planner ID)
                    'title' => $item->AI_food_name,
                    'kcal' => (string)round($kcal100g, 2),
                    'protein' => (string)round($proteins100g, 2),
                    'fats' => (string)round($fats100g, 2),
                    'carbs' => (string)round($carbs100g, 2),
                    'is_ingredient' => 4,
                    'is_favorite' => 0
                ];
            });

            $combinedData = $ingredients->merge($userRecipes)->merge($recipes)->merge($aiFoodsProcessed)->sortBy(function ($item) use ($history) {
                if ($item['is_ingredient'] == 4) {
                    return $history->search(function ($row) use ($item) {
                        return $row->AI_food_name == $item['title'] && $row->is_ingredient == 4;
                    });
                }
                return $history->search(function ($row) use ($item) {
                    return $row->recipe_or_ingredient_id == $item['id'] && $row->is_ingredient == $item['is_ingredient'];
                });
            })->values();

            if ($combinedData->isNotEmpty()) {
                return $this->successResponse("data found", $combinedData, 200);
            }
            return $this->successResponse("data not found", $combinedData, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function nutritionFavourite(Request $request)
    {
        try {
            $validation = [
                'nutrition_ingredient_id' => ['required'],
                'favourite' => ['required']
            ];
            $validator = Validator::make($request->all(), $validation);
            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }
            $data = $request->all();
            $ingredient = NutritionIngredient::where('id', $data['nutrition_ingredient_id'])->first();
            if (!$ingredient) {
                return $this->successResponse("Ingredient not found", (object)[], 200);
            }

            if ($data['favourite'] == 1) {
                $existingFavourite = NutritionFavourite::where('user_id', Auth::user()->id)->where('nutrition_ingredient_id', $data['nutrition_ingredient_id'])->first();
                if ($existingFavourite) {
                    return $this->successResponse("Nutrition ingredient already in your favourites", (object)[], 200);
                }

                $isUserCreated = ($ingredient && $ingredient->user_id !== null) ? 1 : 0;
                $nutritionFavourite = new NutritionFavourite;
                $nutritionFavourite->user_id = Auth::user()->id;
                $nutritionFavourite->nutrition_ingredient_id = $data['nutrition_ingredient_id'];
                $nutritionFavourite->save();
                // Convert to array and append isUserCreated at the end
                $responseData = $nutritionFavourite->toArray();
                $responseData['isUserCreated'] = $isUserCreated;

                return $this->successResponse("Favourite nutrition ingredient successfully", $responseData, 200);            } else {
                $nutritionFavourite = NutritionFavourite::where('user_id', Auth::user()->id)->where('nutrition_ingredient_id', $data['nutrition_ingredient_id'])->first();
                if ($nutritionFavourite) {
                    $nutritionFavourite->delete();
                    return $this->successResponse("Remove Favourite nutrition ingredient successfully", (object)[], 200);
                } else {
                    return $this->successResponse("Nutrition ingredient not found in your favourites", (object)[], 200);
                }
            }
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function nutritionFavouriteList(Request $request)
    {
        try {
            // Validate the request parameters
            $validated = $request->validate([
                'limit' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
            ]);

            // Set default values if not provided
            $limit = (int) ($validated['limit'] ?? 5);
            $page = (int) ($validated['page'] ?? 1);
            $search = $validated['search'] ?? null;

            // Fetch user's favorite nutrition ingredient IDs
            $nutritionFavouriteList = NutritionFavourite::where('user_id', Auth::user()->id)
                ->orderBy('updated_at', 'desc')
                ->pluck('nutrition_ingredient_id')
                ->toArray();

            if (empty($nutritionFavouriteList)) {
                return $this->successResponse("Data Not Found", (object) [], 200);
            }

            // Query ingredients based on favorites and optional search term
            $ingredientsQuery = NutritionIngredient::whereIn('id', $nutritionFavouriteList)
                ->orderByRaw('FIELD(id, ' . implode(',', $nutritionFavouriteList) . ')');

            if ($search) {
                $ingredientsQuery->where('name', 'LIKE', "%$search%");
            }

            // Paginate the results
            $ingredients = $ingredientsQuery->paginate($limit, ['*'], 'page', $page);

            // Transform the collection for the response
            $ingredients->getCollection()->transform(function ($item) {
                // Fetch barcodes for the ingredient
                $barcodes = NutritionIngredientBarcode::where('nutrition_ingredient_id', $item->id)->pluck('barcode');
                // Fetch unit options
                $servingUnits = NutritionIngredientUnit::where('nutrition_ingredient_id', $item->id)
                    ->select('size_key', 'value', 'units')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->map(function ($row) {
                        // Extract only the text part before the last underscore and number, if present
                        if (preg_match('/^(.+?)_\d+g$/', $row->size_key, $matches)) {
                            $displayKey = $matches[1];
                        } else {
                            $displayKey = $row->size_key;
                        }
                        $row->display = $displayKey . ' - ' . $row->value;
                        return $row;
                    });

                $isUserCreated = 0;
                if($item->user_id != null){
                    $isUserCreated = 1;
                }
                return [
                    'id' => $item->id,
                    'title' => $item->name,
                    'kcal' => $item->kcal,
                    'protein' => $item->protein,
                    'fats' => $item->fats,
                    'carbs' => $item->carbs,
                    'is_ingredient' => 1,
                    'serving_units' => $servingUnits, // Include serving units
                    'barcodes' => $barcodes, // Include barcodes
                    'isUserCreated' => $isUserCreated,
                ];
            });

            $response = [
                'ingredients' => $ingredients->items(),
                'current_page' => $ingredients->currentPage(),
                'total_pages' => $ingredients->lastPage(),
                'total_items' => $ingredients->total(),
            ];

            return $this->successResponse("Records Found", $response, 200);
        } catch (ValidationException $e) {
            return $this->errorResponse("Validation Error", $e->errors(), 422);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function getNutritionByName(Request $request)
    {
        try {
            $validation = [
                'name' => ['required', 'string']
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $data = $request->all();
            $name = trim(strtolower($data['name'])); // Normalize input name

            // Cache to improve performance for non-existent results
            $cacheKey = 'nutrition_ingredient_' . $name;
            $ingredient = Cache::remember($cacheKey, 60, function () use ($name) {
                return NutritionIngredient::whereRaw('LOWER(TRIM(name)) = ?', [$name])->first();
            });

            if ($ingredient) {
                $ingredientData = $ingredient->only(['id', 'kcal', 'protein', 'fats', 'carbs']);
                $ingredientData['title'] = $ingredient->name;
                $ingredientData['is_ingredient'] = 1;

                return $this->successResponse("Record Found", $ingredientData, 200);
            } else {
                return $this->successResponse("Data Not Found", (object)[], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function getVisibleMealTypes()
    {

        $allMealTypes = MealType::where('visible_in_tracker', 1)->get()->toArray();

        $mealTypes = array_map(function ($mealType) {
            return [
                'id' => $mealType['id'],
                'title' => $mealType['title'],
                'slug' => strtolower($mealType['slug']),
            ];
        }, $allMealTypes);

        return $this->successResponse("Record Found", $mealTypes, 200);
    }

    public function getNutritionIngredientCategory()
    {
        $categories = NutritionIngredientCategory::with('portionSizes')->where('status', 1)->get();

        $categoryList = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'image_url' => $category->image_url ? asset(Storage::url($category->image_url)) : '',
                'portion_sizes' => $category->portionSizes->map(function ($portion) {
                    return [
                        'id' => $portion->id,
                        'name' => $portion->name,
                        'quantity' => $portion->quantity,
                        'units' => $portion->units,
                    ];
                }),
            ];
        })->toArray();

        if (empty($categoryList)) {
            return $this->successResponse("Data Not Found", [], 200);
        }
        return $this->successResponse("Record Found", $categoryList, 200);
    }

    public function createNutritionIngredientByUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:nutrition_ingredients,name',
            'weight' => 'required|numeric|min:1',
            'kcal' => 'required|numeric|min:0',
            'fats' => 'required|numeric|min:0',
            'carbs' => 'required|numeric|min:0',
            'protein' => 'required|numeric|min:0',
            'sugar' => 'nullable|numeric|min:0',
            'salt' => 'nullable|numeric|min:0',
            'image' => 'nullable|string', // Changed to accept base64 string
            'portion' => 'required|array',
            'portion.*.name' => 'required|string',
            'portion.*.quantity' => 'required|numeric|min:0.01',
            'portion.*.units' => 'required|string',
            'barcode' => 'nullable|string|unique:nutrition_ingredient_barcodes,barcode',
        ]);

        DB::beginTransaction();

        try {

            // Normalize factor to 100g
            $factor = 100 / $validated['weight'];

            // Handle base64 image
            $imagePath = '';
            if (!empty($validated['image'])) {
                // Decode base64 string
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $validated['image']));

                // Generate unique filename
                $filename = uniqid() . '.png';
                $path = 'public/ingredients/' . $filename;

                // Store the decoded image
                Storage::put($path, $imageData);
                $imagePath = asset(Storage::url($path));
            }

            // Create ingredient
            $ingredient = NutritionIngredient::create([
                'name' => $validated['name'],
                'kcal' => round($validated['kcal'] * $factor, 2),
                'fats' => round($validated['fats'] * $factor, 2),
                'carbs' => round($validated['carbs'] * $factor, 2),
                'protein' => round($validated['protein'] * $factor, 2),
                'sugar' => isset($validated['sugar']) ? round($validated['sugar'] * $factor, 2) : null,
                'salt' => isset($validated['salt']) ? round($validated['salt'] * $factor, 2) : null,
                'small_image_url' => $imagePath,
                'slug' => Str::slug($validated['name']),
                'mark_as_reviewed' => 0,
                'status' => 1,
                'user_id' => Auth::user()->id,
            ]);

            if (isset($validated['barcode']) && $validated['barcode'] != null) {
                // Store barcode
                NutritionIngredientBarcode::create([
                    'nutrition_ingredient_id' => $ingredient->id,
                    'barcode' => $validated['barcode'],
                ]);
            }

            // Store portion sizes
            foreach ($validated['portion'] as $portion) {
                NutritionIngredientUnit::create([
                    'nutrition_ingredient_id' => $ingredient->id,
                    'size_key' => $portion['name'],
                    'value' => $portion['quantity'],
                    'units' => strtolower($portion['units']) ?? 'grams',
                ]);
            }

            // Save to favourites
            $nutritionFavourite = new NutritionFavourite();
            $nutritionFavourite->user_id = Auth::id();
            $nutritionFavourite->nutrition_ingredient_id = $ingredient->id;
            $nutritionFavourite->save();

            DB::commit();

            return $this->successResponse("Ingredient created successfully.", $ingredient->load('barcodes', 'units'), 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->throwableError("Error creating ingredient.", $e->getMessage(), 500);
        }
    }

    public function addPortionSizeToNutrition(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'nutrition_ingredient_id' => 'required|integer|exists:nutrition_ingredients,id',
                'name' => 'required|string|max:255',
                'quantity' => 'required|numeric|min:0.01',
                'units' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $nutritionIngredientId = $request->input('nutrition_ingredient_id');
            $ingredient = NutritionIngredient::find($nutritionIngredientId);

            if (!$ingredient) {
                return $this->errorResponse("Nutrition ingredient not found.", [], 404);
            }

            $ingredient->mark_as_reviewed = 0;
            $ingredient->save();

            // Store portion size
            $portion = NutritionIngredientUnit::create([
                'nutrition_ingredient_id' => $nutritionIngredientId,
                'size_key' => $request->input('name'),
                'value' => $request->input('quantity'),
                'units' => strtolower($request->input('units')),
            ]);

            return $this->successResponse("Portion size added successfully.", $portion, 200);
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function checkIngredientExist(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'barcode' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $name = trim($request->input('name'));
            $barcode = $request->input('barcode');

            // Check for existing ingredient by name (case-insensitive, trimmed)
            $ingredientByName = NutritionIngredient::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])->first();

            // Check for existing ingredient by barcode if barcode is provided
            $ingredientByBarcode = null;
            if ($barcode) {
                $ingredientByBarcode = NutritionIngredientBarcode::where('barcode', $barcode)->first();
            }

            if ($ingredientByName) {
                return $this->successResponse("Un aliment porte déjà ce nom, change le nom pour qu'il soit accepté !", [
                    'exists' => 1,
                    'type' => 'name',
                    'ingredient_id' => $ingredientByName->id
                ], 200);
            }

            if ($ingredientByBarcode) {
                return $this->successResponse("Un aliment possède déjà ce code-barres, changez le code-barres pour qu'il soit accepté !", [
                    'exists' => 1,
                    'type' => 'barcode',
                    'ingredient_id' => $ingredientByBarcode->nutrition_ingredient_id
                ], 200);
            }

            return $this->successResponse("Ingredient does not exist.", [
                'exists' => 0
            ], 200);

        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function logDailyMealTypeTaskIfFirstTime($userId, $mealType, $date)
    {
        // Only log if meal type is part of visible meal types
        $visibleMealTypes = MealType::where('visible_in_tracker', 1)->pluck('slug')->toArray();

        if (!in_array(strtolower($mealType), array_map('strtolower', $visibleMealTypes))) {
            return 0;
        }

        // Check if this meal_type was already logged today in FoodPlanner by this user
        $alreadyLogged = FoodPlanner::where('user_id', $userId)
            ->whereRaw('LOWER(meal_type) = ?', [strtolower($mealType)])
            ->whereDate('date', $date)
            ->count();

        // If count > 1, it means they already logged this meal_type earlier today
        if ($alreadyLogged > 1) {
            return 0;
        }

        // Log to UserLevelTaskLog once per meal type per day
        $taskType = 'log-meals'; // use your actual task_type
        $completedCount = 1;
        $oneEntry = 0;

        $levelResponse = $this->logLevelTaskEntries($userId, $taskType, $completedCount, $oneEntry);
        return $levelResponse;
    }
}
