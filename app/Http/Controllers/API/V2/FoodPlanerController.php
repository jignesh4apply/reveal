<?php

namespace App\Http\Controllers\API\V2;

use stdClass;
use Throwable;
use App\Models\User;
use App\Models\Recipe;
use App\Models\MealType;
use App\Models\StepsGoal;
use App\Models\UserRecipe;
use App\Models\FoodPlanner;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TaskMilestone;
use App\Models\userMilestone;
use App\Models\FavouriteRecipe;
use App\Models\RecipeNutrition;
use PhpParser\Node\Stmt\Catch_;
use App\Models\UserLevelTaskLog;
use App\Models\NutritionFavourite;
use Illuminate\Support\Facades\DB;
use App\Models\NutritionIngredient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\NutritionIngredientUnit;
use App\Models\UsersCurrentMeasurement;
use App\Models\UsersInitialMeasurement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\NutritionIngredientBarcode;
use App\Http\Controllers\API\ApiController;
use App\Models\NutritionIngredientCategory;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB as FacadesDB;
use App\Http\Controllers\API\FoodPlanerController as APIFoodPlanerControllerV1;
use OpenAI;

class FoodPlanerController extends APIFoodPlanerControllerV1
{
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

    protected function handleWater(array $data)
    {
        $userId = Auth::user()->id;
        $waterQuantity = 250;
        $existingWaterEntry = FoodPlanner::where([
            'meal_type' => 'water',
            'date' => $data['date'],
            'user_id' => $userId
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
            $foodPlanner->user_id = $userId;
            $foodPlanner->recipe_or_ingredient_id = null;
            $foodPlanner->meal_type = 'water';
            $foodPlanner->date = $data['date'];
            $foodPlanner->portion = 0;
            $foodPlanner->kcal = null;
            $foodPlanner->water_consume = $waterQuantity;
            $foodPlanner->no_of_servings = 1;
            $foodPlanner->is_ingredient = 2;
            $foodPlanner->ai_Food_name = "";
            $foodPlanner->save();
        }
        $levelDetails = null;
        $levelResponse = $this->logLevelTaskEntries($userId, 'drink-water',1,0);


        if ($levelResponse != 0) {
            $levelDetails = $this->getLevelDetails($levelResponse);
        }

        $milestoneCompletedArray = [];

        $drinkWaterMilestones = TaskMilestone::where('milestone_type', 'drink-water')->get();

        $userCurrentMilestones = userMilestone::where([
            'user_id' => $userId,
            'milestone_type' => 'drink-water'
        ])->pluck('milestone_id')->toArray();

        $userStreakCompleteCount = UserLevelTaskLog::where([
            'user_id' => $userId,
            'level_task_type' => 'drink-water'
        ])->sum('completed_count');

        foreach ($drinkWaterMilestones as $milestone) {

            if (
                $userStreakCompleteCount >= $milestone->milestone_count &&

                !in_array($milestone->id, $userCurrentMilestones)
            ) {
                $milestoneCompletedArray[] = [
                    'type' => 'drink-water',
                    'color' => 'blue',
                    'title' => $milestone->milestone_title,
                    'description' => $milestone->milestone_description,
                    'count' => $milestone->milestone_count,
                ];

                // Save to user_milestones table
                UserMilestone::create([
                    'user_id' => $userId,
                    'milestone_id' => $milestone->id,
                    'milestone_type' => 'drink-water',
                ]);
            }
        }

        $savedData = $this->getFoodPlannerData($data['date'], $milestoneCompletedArray, $levelDetails);
        return $this->getSingleResponse("Water quantity updated", $savedData, 200);
    }


    protected function getCombinedData($payload)
    {
        try {
            $ids = array_column($payload['nutritiondetails'], 'recipe_or_ingredient_id');
            //AI data fetch
            $AiDataFetched=[];
            foreach ($payload['nutritiondetails'] as $AIdata) {
                if ($AIdata['is_ingredient'] && $AIdata['is_ingredient']== 4) {
                    $AiDataFetched[]=[
                        'recipe_or_ingredient_id'=>$AIdata['recipe_or_ingredient_id'],
                        'name'=>$AIdata['name'],
                        'is_ingredient'=>$AIdata['is_ingredient'],
                        'kcal'=>$AIdata['kcal'],
                        'protein'=>$AIdata['protein'],
                        'fats'=>$AIdata['fats'],
                        'carbs'=>$AIdata['carbs'],
                        'quantity'=>$AIdata['quantity'],
                        'no_of_servings'=>$AIdata['no_of_servings'],
                    ];
                }
            }
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

            return array_merge($recipeData, $ingredientData, $userRecipesData, $AiDataFetched);
        } catch (\Exception $e) {
            Log::error('Error in yourMethod: ' . $e->getMessage());

            return response()->json(['error' => 'An error occurred while fetching data.'], 500);
        }
    }

    protected function validateNutritionDetails(array $nutritionDetails, array $combinedData)
    {
        return array_filter($combinedData, function ($nutritionData) use ($nutritionDetails) {
            foreach ($nutritionDetails as $nutritionValue) {
                if ($nutritionValue['is_ingredient'] == 4 && $nutritionData['is_ingredient'] == $nutritionValue['is_ingredient']) {
                    if ($nutritionData['name']==$nutritionValue['name']) {
                        return true;
                    }
                }
                if ($nutritionData['recipe_or_ingredient_id'] == $nutritionValue['recipe_or_ingredient_id'] && $nutritionData['is_ingredient'] == $nutritionValue['is_ingredient']) {
                    return true;
                }
            }
            return false;
        });
    }

    protected function updateOrCreateFoodPlannerEntries(array $data, array $matches)
    {
        $levelDetails = null;
        $filtered = array_filter($data['nutritiondetails'], function ($item) use ($matches) {
            foreach ($matches as $match) {
                if ($match['is_ingredient'] == 4 && $item['is_ingredient'] == $match['is_ingredient']) {
                    if ($item['name']==$match['name']) {
                        return true;
                    }
                }
                if ($item['recipe_or_ingredient_id'] === $match['recipe_or_ingredient_id'] &&
                    $item['is_ingredient'] === $match['is_ingredient']
                ) {
                    return true;
                }
            }
            return false;
        });
        // Group the filtered data by is_ingredient
        $grouped = collect($filtered)->groupBy('is_ingredient');
        // Store each group in a separate variable
        $filteredIngredients = $grouped->get(1, []); // is_ingredient = 1
        $filteredRecipes = $grouped->get(0, []);     // is_ingredient = 0
        $filteredUserRecipes = $grouped->get(3, []); // is_ingredient = 3
        $filteredUserAI = $grouped->get(4, []); // is_ingredient = 4
        // Check if this is the first time logging for the meal_type today
        $firstLogged = FoodPlanner::where('user_id', Auth::user()->id)
        ->whereRaw('LOWER(meal_type) = ?', [strtolower($data['meal_type'])])
        ->whereDate('date', $data['date'])
        ->count() === 0;

        // // Output variables for testing
        // dd([
        //     'filteredIngredients' => $filteredIngredients,
        //     'filteredRecipes' => $filteredRecipes,
        //     'filteredUserRecipes' => $filteredUserRecipes,
        //     'filteredUserAI' => $filteredUserAI,
        // ]);

        foreach ($filteredUserAI as $key => $foodPlannerData_AI) {
            // nutrients pe 100gm

            $kcal=  $foodPlannerData_AI['kcal']?? 0;
            $carbs= $foodPlannerData_AI['carbs']?? 0;
            $fats= $foodPlannerData_AI['fats']?? 0;
            $proteins= $foodPlannerData_AI['protein']?? 0;
            $no_of_servings= $foodPlannerData_AI['no_of_servings'];
            $existingEntry = FoodPlanner::where([
                'is_ingredient' => $foodPlannerData_AI['is_ingredient'],
                'meal_type' => $data['meal_type'],
                "AI_food_name"=>$foodPlannerData_AI['name'],
                'date' => $data['date'],
                'user_id' => Auth::user()->id
            ])->first();
            if ($existingEntry) {
                $existingEntry->kcal += $kcal;
                $existingEntry->carbs += $carbs;
                $existingEntry->fats += $fats;
                $existingEntry->proteins += $proteins;
                $existingEntry->portion += $foodPlannerData_AI['quantity'];
                $existingEntry->no_of_servings += $no_of_servings;
                $existingEntry->save();
            } else {
                $foodPlanner = new FoodPlanner;
                $foodPlanner->user_id = Auth::user()->id;
                $foodPlanner->meal_type = $data['meal_type'];
                $foodPlanner->date = $data['date'];
                $foodPlanner->recipe_or_ingredient_id = $foodPlannerData_AI['recipe_or_ingredient_id']??0;
                $foodPlanner->AI_food_name =$foodPlannerData_AI['name'];
                $foodPlanner->portion = $foodPlannerData_AI['quantity'];
                $foodPlanner->kcal = $kcal;
                $foodPlanner->carbs = $carbs;
                $foodPlanner->fats = $fats;
                $foodPlanner->proteins = $proteins;
                $foodPlanner->no_of_servings = $no_of_servings;
                $foodPlanner->is_ingredient = $foodPlannerData_AI['is_ingredient'];
                $foodPlanner->save();
                if ($firstLogged) {
                    $levelResponse =  $this->logDailyMealTypeTaskIfFirstTime(Auth::user()->id, $data['meal_type'], $data['date']);
                    if ($levelResponse != 0) {
                        $levelDetails = $this->getLevelDetails($levelResponse);
                    }
                    $firstLogged = false; // Prevent calling again for subsequent entries
                }
            }
            if ($proteins > 0) {
                $levelResponse = $this->logLevelTaskEntries(Auth::user()->id, 'protiens', $proteins, 0);
                if ($levelResponse != 0) {
                    $levelDetails = $this->getLevelDetails($levelResponse);
                }
            }
        }
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
                $foodPlanner->ai_Food_name = "";
                $foodPlanner->save();

                // If it's the first log for this meal_type, trigger the task logging function
                if ($firstLogged) {

                    $levelResponse =  $this->logDailyMealTypeTaskIfFirstTime(Auth::user()->id, $data['meal_type'], $data['date']);
                    if ($levelResponse != 0) {
                        $levelDetails = $this->getLevelDetails($levelResponse);
                    }
                    $firstLogged = false; // Prevent calling again for subsequent entries
                }
            }

            if($proteins > 0){

                $levelResponse = $this->logLevelTaskEntries(Auth::user()->id, 'protiens', $proteins, 0);
                if ($levelResponse != 0) {
                    $levelDetails = $this->getLevelDetails($levelResponse);
                }
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
                $foodPlanner->ai_Food_name ="";
                $foodPlanner->save();

                // If it's the first log for this meal_type, trigger the task logging function
                if ($firstLogged) {
                    $levelResponse =  $this->logDailyMealTypeTaskIfFirstTime(Auth::user()->id, $data['meal_type'], $data['date']);
                    if ($levelResponse != 0) {
                        $levelDetails = $this->getLevelDetails($levelResponse);
                    }
                    $firstLogged = false; // Prevent calling again for subsequent entries
                }
            }

            if ($proteins > 0) {
                $levelResponse = $this->logLevelTaskEntries(Auth::user()->id, 'protiens', $proteins, 0);
                if ($levelResponse != 0) {
                    $levelDetails = $this->getLevelDetails($levelResponse);
                }
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
            } else {
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
                $foodPlanner->ai_Food_name = "";
                $foodPlanner->save();

                // If it's the first log for this meal_type, trigger the task logging function
                if ($firstLogged) {
                    $levelResponse =  $this->logDailyMealTypeTaskIfFirstTime(Auth::user()->id, $data['meal_type'], $data['date']);
                    if ($levelResponse != 0) {
                        $levelDetails = $this->getLevelDetails($levelResponse);
                    }
                    $firstLogged = false; // Prevent calling again for subsequent entries
                }
            }

            if ($proteins > 0) {
                $levelResponse = $this->logLevelTaskEntries(Auth::user()->id, 'protiens', $proteins, 0);
                if ($levelResponse != 0) {
                    $levelDetails = $this->getLevelDetails($levelResponse);
                }
            }
        }

        $milestoneCompletedArray = [];

        $taskTypes = [
            'log-meals' => 'purple',
            'proteins'  => 'light-yellow',
        ];

        foreach ($taskTypes as $type => $color) {
            // Fetch all available milestones for current task type
            $milestones = TaskMilestone::where('milestone_type', $type)->get();

            // Get already completed milestone IDs for user for this task type
            $userCompletedMilestones = UserMilestone::where([
                'user_id' => Auth::user()->id,
                'milestone_type' => $type
            ])->pluck('milestone_id')->toArray();

            // Sum total completed count from UserLevelTaskLog
            $completedCount = UserLevelTaskLog::where([
                'user_id' => Auth::user()->id,
                'level_task_type' => $type
            ])->sum('completed_count');

            // Check which milestones have been newly achieved
            foreach ($milestones as $milestone) {
                if ($completedCount >= $milestone->milestone_count &&
                    !in_array($milestone->id, $userCompletedMilestones)
                ) {
                    $milestoneCompletedArray[] = [
                        'type' => $type,
                        'color' => $color,
                        'title' => $milestone->milestone_title,
                        'description' => $milestone->milestone_description,
                        'count' => $milestone->milestone_count,
                    ];

                    // Save to user_milestones table
                    UserMilestone::create([
                        'user_id' => Auth::user()->id,
                        'milestone_id' => $milestone->id,
                        'milestone_type' => $type,
                    ]);
                }
            }
        }
        return $this->getFoodPlannerData($data['date'], $milestoneCompletedArray, $levelDetails);
    }

    protected function getFoodPlannerData($date, $milestoneCompletedArray = [], $levelDetails = null)
    {
        if ($levelDetails === null) {
            $levelDetails = (object)[];
        }
        $foodplanner = FoodPlanner::where([
            'user_id' => Auth::user()->id,
            'date' => $date
        ])->get()->toArray();

        $foodplanner[] = ['milestoneCompletedArray' => $milestoneCompletedArray];
        $foodplanner[] = ['levelCompletedDetails' => $levelDetails];

        return $foodplanner;
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
            $userMeasurementData = UsersCurrentMeasurement::with('initialMeasurement', 'targetMeasurement')
                ->where('user_id', Auth::user()->id)
                ->orderBy('id', 'desc')
                ->first();
            if ($userMeasurementData) {
                // If there's a record in UsersCurrentMeasurement, use it for weight
                $bmrData = $userMeasurementData;
            }
            if ($bmrData) {
                $userData = User::where('id', Auth::user()->id)->first();
                $weight = $bmrData['weight'];
                $initialMeasurement = UsersInitialMeasurement::where('user_id', Auth::user()->id)->latest('id')->first();
                $age = ($initialMeasurement && $initialMeasurement->age > 0) ? (int)$initialMeasurement->age : 24;
                if ($initialMeasurement && $initialMeasurement->created_at) {
                    $yearsElapsed = now()->diffInYears($initialMeasurement->created_at);
                    $age += $yearsElapsed;
                }
                $gender = strtolower($userData['gender']);
                $height = (!empty($userData['height']) && $userData['height'] != 0) ? $userData['height'] : 170;

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
                        $ingredientData = NutritionIngredient::where(['id' => $meal['recipe_or_ingredient_id']])->with(['units' => function ($query) { $query->select('nutrition_ingredient_id', 'size_key', 'value', 'units');}])->first();

                        $unitOptions = $ingredientData->units
                            ->map(function ($row) {
                                // Extract only the text part before the last underscore and number, if present
                                if (preg_match('/^(.+?)_\d+g$/', $row->size_key, $matches)) {
                                    $displayKey = $matches[1];
                                } else {
                                    $displayKey = $row->size_key;
                                }

                                // Assign the display value to the 'display' attribute
                                $row->display = $displayKey . ' - ' . $row->value;
                                return $row;
                            });

                        if (!empty($ingredientData)) {

                            $isUserCreated = 0;
                            if($ingredientData->user_id != null){
                                $isUserCreated = 1;
                            }
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
                                "image" => $ingredientData['image'] ? asset(Storage::url($ingredientData['image'])): '',
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => $unitOptions,
                                'isUserCreated' => $isUserCreated,
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
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => []
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
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => []
                            ];

                            $totalCaloriesByType[$mealType] += (int)round($meal['kcal']); // Accumulate total consumed calories for this meal type
                            $totalConsumedCalorie += (int)$meal['kcal']; // Accumulate total consumed calories overall
                            $totalConsumedCarbs += (int)round($meal['carbs']); // Accumulate total consumed carbs overall
                            $totalConsumedProteins += (int)round($meal['proteins']); // Accumulate total consumed proteins overall
                            $totalConsumedFats += (int)round($meal['fats']); // Accumulate total consumed fats overall
                        }
                    }
                    if ($meal['is_ingredient'] == 4) {


                            $mealData[] = [
                                "id" => $meal['id'],
                                "recipe_or_ingredient_id" => $meal['recipe_or_ingredient_id'],
                                "title" =>$meal['AI_food_name'],
                                "meal_type" => $meal['meal_type'],
                                "date" => $meal['date'],
                                "kcal" => $meal['kcal'],
                                "carbs" => $meal['carbs'],
                                "proteins" => $meal['proteins'],
                                "fats" => $meal['fats'],
                                "quantity" => $meal['portion'],
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" =>  '',
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => []
                            ];

                            $totalCaloriesByType[$mealType] += (int)round($meal['kcal']); // Accumulate total consumed calories for this meal type
                            $totalConsumedCalorie += (int)$meal['kcal']; // Accumulate total consumed calories overall
                            $totalConsumedCarbs += (int)round($meal['carbs']); // Accumulate total consumed carbs overall
                            $totalConsumedProteins += (int)round($meal['proteins']); // Accumulate total consumed proteins overall
                            $totalConsumedFats += (int)round($meal['fats']); // Accumulate total consumed fats overall
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
                    $this->deleteLogWaterEntries(Auth::User()->id);
                    return $this->successResponse("Water Consumption Updated", $mealRecipe, 200);
                }
                if ($getLoggedFood['is_ingredient'] == 2) {
                    $this->deleteLogWaterEntries(Auth::User()->id);
                } else {
                    $this->deleteLogProtienEntries(Auth::user()->id, $getLoggedFood['proteins']);

                    $this->deleteLogMealTypeEntries(Auth::user()->id, $getLoggedFood['meal_type'], $getLoggedFood['date']);
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
                                "fats" => $nutritionDetails['fats'],
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => asset(Storage::url($recipeData['picture'])),
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => []
                            ];
                        }
                    } else if ($meal['is_ingredient'] == 1) {
                        $ingredientData = NutritionIngredient::where(['id' => $meal['recipe_or_ingredient_id']])->with(['units' => function ($query) { $query->select('nutrition_ingredient_id', 'size_key', 'value', 'units');}])->first();

                        $unitOptions = $ingredientData->units
                            ->map(function ($row) {
                                // Extract only the text part before the last underscore and number, if present
                                if (preg_match('/^(.+?)_\d+g$/', $row->size_key, $matches)) {
                                    $displayKey = $matches[1];
                                } else {
                                    $displayKey = $row->size_key;
                                }

                                // Assign the display value to the 'display' attribute
                                $row->display = $displayKey . ' - ' . $row->value;
                                return $row;
                            });

                        if (!empty($ingredientData)) {

                            $isUserCreated = 0;
                            if($ingredientData->user_id != null){
                                $isUserCreated = 1;
                            }
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
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => asset(Storage::url($ingredientData['image'])),
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => $unitOptions,
                                'isUserCreated' => $isUserCreated,
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
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => []
                            ];
                        }
                    } else if ($meal['is_ingredient'] == 4) {
                            $mealData[] = [
                                "id" => $meal['id'],
                                "recipe_or_ingredient_id" => $meal['recipe_or_ingredient_id']??0,
                                "title" => $meal['AI_food_name'],
                                "meal_type" => $meal['meal_type'],
                                "date" => $meal['date'],
                                "kcal" => $meal['kcal'],
                                "carbs" => $meal['carbs'],
                                "proteins" => $meal['proteins'],
                                "fats" => $meal['fats'],
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => '',
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => []
                            ];
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


    public function fetchFoodJsonFromAI(Request $request)
    {
        $data = $request->get('food');

        if (empty($data)) {
            return $this->successResponse("No data provided", ['ingredients' => []], 200);
        }

        $client = OpenAI::client(env('OPENAI_API_KEY'));

        // Initial AI prompt
            $prompt = <<<EOT
You are a nutrition assistant. Return a pure JSON array based on the following input sentence in French or English.

[input sentence]: {$data}

TASK:
    1. Extract all food items from the sentence (ignore non-food words).

    2. For each food item:
        - Extract portion/quantity if provided (e.g., "2 bananas", "10g paneer", "a can of coke").
	- If the portion is provided put it as the top 1 in the list of the 5 most common portions sizes
        - If no portion is given, search and provide the 5 most common portion sizes for that specific food item.
	- For the portion make sure it's common french portion name (e.g : chicken : breast), it needs to be understandable by the user
	- Try to make sure the portions are different between each other, and provide the most classic portion in first (e.g : banana : medium size banana)

    3. Return the following JSON object for each food:
        - Nutrition data per 100g/ml: "kcal", "protein", "carbs", "fats"
        - Field "quantity" should always be "100g" or "100ml" based on food type
        - "value" and "quantity" in the units array must represent numeric strings with two decimal places
        - kcal, protein, carbs, fats — all must be strings with two decimals

FORMAT RULES:

    - Return only the JSON array: [ {...}, {...} ]
    - Do not include any text, markdown, code fences, or explanation
    - All numerical fields (kcal, protein, carbs, fats, value, quantity) must be strings with two decimal places, e.g. "42.00"
    - No variables or placeholders like xyz * 2 — use real calculated values
    - Ensure the response is valid JSON, parsable by JSON.parse()

Example input sentence:
    - J'ai mangé 2 bananes, une salade César, 10 grammes de paneer, une canette de coca.

Example response:
    [
        {
            "name": "banane",
            "kcal": "89.00",
            "protein": "1.10",
            "carbs": "22.80",
            "fats": "0.30",
            "quantity": "100g",
            "is_ingredient": 4,
            "recipe_or_ingredient_id": 0,
            "image_url": "",
            "small_image_url": "",
            "units": [
            {
                "size_key": "banane_moyenne",
                "value": "2.00",
                "quantity": "240.00",
                "units": "gram",
                "display": "2 bananes moyennes",
                "kcal": "213.60",
                "protein": "2.64",
                "carbs": "54.72",
                "fats": "0.72"
            },
            ...
            ],
            "barcodes": [],
            "is_favourite": 0
        },
        ....
    ]

ADDITIONAL RULES:
    - All food names ("name") and unit names ("units", "display") must be in French only.
    - Maintain correct singular/plural forms in French.
    - Units must be unique within the list for each food item (no duplicate unit names).
    - Each food item must always contain exactly 5 unique unit variations in "units".
EOT;
        try {
            $result = $client->chat()->create([
                'model' => 'gpt-5',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a French nutrition JSON extractor. Return only JSON. All numbers as strings with two decimals.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                // new parameters supported by GPT-5
                'verbosity' => 'low',             // ask it to produce concise outputs
                'reasoning_effort' => 'minimal', // reduce internal reasoning overhead
            ]);

            $responseText = $result->choices[0]->message->content;

            $aiData = [];
            $aiData = json_decode($responseText, true);

            if (empty($aiData)) {
                Log::info('AI raw response', ['responseText' => $responseText]);
                return $this->errorResponse("Something went wrong, please re-submit your input.", [], 200);
            }

            return $this->successResponse("Nutrition Found", ['ingredients' => $aiData], 200);
        } catch (\OpenAI\Exceptions\ErrorException $e) {
            // Handle quota exceeded or other OpenAI errors
            if (str_contains($e->getMessage(), 'You exceeded your current quota')) {
                Log::warning('OpenAI quota exceeded', ['error' => $e->getMessage()]);
                return $this->errorResponse(
                    "Quota exceeded. Please check your OpenAI billing or try again later.",
                    [],
                    429
                );
            }

            Log::error('OpenAI API error', ['error' => $e->getMessage()]);
            return $this->errorResponse(
                "An error occurred while fetching data from AI. Please try again later. : {$e->getMessage()} ",
                [],
                500
            );
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            Log::error('Unexpected error', ['error' => $e->getMessage()]);
            return $this->errorResponse(
                "An unexpected error occurred. Please try again later.: {$e->getMessage()}",
                [],
                500
            );
        }
    }

    public function fetchJSONfromImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'imageString' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->validationError("Fail", $validator->errors()->first(), 200);
        }

        $imageInput = $request->get('imageString');

        // Convert single image to array format for uniform processing
        $imageArray = is_array($imageInput) ? $imageInput : [$imageInput];

        // Validate all images are non-empty strings
        $imageArray = array_filter($imageArray, function($img) {
            return is_string($img) && !empty(trim($img));
        });

        if (empty($imageArray)) {
            return $this->successResponse("No data provided", ['foodText' => '', 'ingredients' => []], 200);
        }

        $client = OpenAI::client(env('OPENAI_API_KEY'));
        $allFoodSentences = [];

        try {
            // Process each image
            foreach ($imageArray as $imageString) {
                $imageUrl = 'data:image/jpeg;base64,' . trim($imageString);

                $result = $client->chat()->create([
                    'model' => 'gpt-5',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Analyze this food image and describe the food items with estimated portions in a single sentence.'
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => $imageUrl
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'verbosity' => 'low',
                    'reasoning_effort' => 'minimal'
                ]);

                $foodSentence = $result->choices[0]->message->content ?? '';
                $foodSentence = is_string($foodSentence) ? trim($foodSentence) : '';

                if (!empty($foodSentence)) {
                    $allFoodSentences[] = $foodSentence;
                } else {
                    Log::info('AI raw response for image', ['responseText' => $result->choices[0]->message->content ?? null]);
                }
            }

            if (empty($allFoodSentences)) {
                return $this->errorResponse("Something went wrong, please re-submit your input.", [], 200);
            }

            // Combine all food sentences into one
            $combinedFoodSentence = implode('. ', $allFoodSentences);

            return $this->generateNutritionJSON($combinedFoodSentence);

        } catch (\OpenAI\Exceptions\ErrorException $e) {
            // Handle quota exceeded or other OpenAI errors
            if (str_contains($e->getMessage(), 'You exceeded your current quota')) {
                Log::warning('OpenAI quota exceeded', ['error' => $e->getMessage()]);
                return $this->errorResponse(
                    "Quota exceeded. Please check your OpenAI billing or try again later.",
                    [],
                    429
                );
            }

            Log::error('OpenAI API error', ['error' => $e->getMessage()]);
            return $this->errorResponse(
                "An error occurred while fetching data from AI. Please try again later. : {$e->getMessage()} ",
                [],
                500
            );
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            Log::error('Unexpected error', ['error' => $e->getMessage()]);
            return $this->errorResponse(
                "An unexpected error occurred. Please try again later.: {$e->getMessage()}",
                [],
                500
            );
        }
    }

    public function fetchJSONfromImage_optimized(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'imageString' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError("Fail", $validator->errors()->first(), 200);
        }

        $imageString = trim($request->get('imageString'));

        if (empty($imageString)) {
            return $this->successResponse("No data provided", ['foodText' => '', 'ingredients' => []], 200);
        }

        try {
            // 🔹 Optimize Image
            $imageData = base64_decode($imageString);
            $image = imagecreatefromstring($imageData);

            if ($image === false) {
                return $this->errorResponse("Invalid image data.", [], 400);
            }

            $maxWidth = 512;
            $width = imagesx($image);
            $height = imagesy($image);

            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = intval(($height / $width) * $newWidth);

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                ob_start();
                imagejpeg($resizedImage, null, 75);
                $optimizedImageData = ob_get_clean();

                imagedestroy($resizedImage);
            } else {
                ob_start();
                imagejpeg($image, null, 75);
                $optimizedImageData = ob_get_clean();
            }

            imagedestroy($image);

            $imageUrl = 'data:image/jpeg;base64,' . base64_encode($optimizedImageData);

            $client = OpenAI::client(env('OPENAI_API_KEY'));

            $systemPrompt = <<<EOT
You are a highly accurate nutrition AI.

You MUST return STRICT JSON in this structure:

{
    "foodText": "string",
    "ingredients": [
        {
            "name": "string",
            "kcal": "00.00",
            "protein": "00.00",
            "carbs": "00.00",
            "fats": "00.00",
            "quantity": "100g",
            "is_ingredient": 4,
            "recipe_or_ingredient_id": 0,
            "image_url": "",
            "small_image_url": "",
            "units": [
                {
                    "size_key": "string",
                    "value": "00.00",
                    "quantity": "00.00",
                    "units": "gramme",
                    "display": "string",
                    "kcal": "00.00",
                    "protein": "00.00",
                    "carbs": "00.00",
                    "fats": "00.00"
                }
            ],
            "barcodes": [],
            "is_favourite": 0
        }
    ]
}

------------------------
CRITICAL RULES (VERY IMPORTANT)
------------------------

1. FOOD TEXT:
- Must be ONE detailed sentence
- Must include ALL foods + estimated quantity
- Example: "Une assiette avec environ 200g de steak grillé, 150g de frites épaisses et une petite quantité de persil haché"

2. BASE NUTRITION:
- ALWAYS per 100g or 100ml
- "quantity" MUST ALWAYS be "100g" or "100ml"
- kcal, protein, carbs, fats MUST correspond to 100g/ml ONLY

3. UNITS (VERY IMPORTANT):
- EXACTLY 3 units per ingredient
- FIRST unit MUST reflect detected portion from image (if visible)
- Remaining 2 = common French portion sizes

4. UNIT CALCULATION:
- quantity = weight in grams/ml
- value = number of units (ex: 1.00, 2.00)
- kcal/protein/carbs/fats MUST be calculated from base 100g

FORMULA:
unit_kcal = (base_kcal / 100) * quantity

Example:
If 100g = 250 kcal
and unit = 200g
→ kcal = 500.00

5. UNIT QUALITY:
- Use real French portions (entrecôte, bol, tasse, portion, tranche)
- NO duplicates
- All units must be DIFFERENT

6. LANGUAGE:
- ALL text must be in French
- Proper singular/plural

7. NUMBERS:
- EVERYTHING must be string with 2 decimals
- No integers

8. STRICT OUTPUT:
- ONLY JSON
- NO explanation
- NO markdown
- Must be valid JSON

------------------------
FAIL CONDITIONS (DO NOT DO)
------------------------
- Do NOT return per serving nutrition
- Do NOT skip quantity calculation
- Do NOT give random units
- Do NOT give same units multiple times
- Do NOT return partial JSON

EOT;


            $userPrompt = "Analyze this food image and return complete structured nutrition JSON with accurate portion estimation.";

            $result = $client->chat()->create([
                'model' => 'gpt-5',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $userPrompt],
                            [
                                'type' => 'image_url',
                                'image_url' => ['url' => $imageUrl]
                            ]
                        ]
                    ]
                ],
                'verbosity' => 'low',
                'reasoning_effort' => 'minimal'
            ]);

            $responseText = $result->choices[0]->message->content ?? '';

            $aiData = json_decode($responseText, true);

            if (empty($aiData) || !isset($aiData['ingredients'])) {
                Log::info('AI raw response', ['responseText' => $responseText]);
                return $this->errorResponse("Something went wrong, please re-submit your input.", [], 200);
            }

            // 🔹 FINAL RESPONSE (STRICT FORMAT)
            return response()->json([
                "message" => "Nutrition Found",
                "data" => [
                    "foodText" => $aiData['foodText'] ?? '',
                    "ingredients" => $aiData['ingredients']
                ],
                "status" => "success"
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error', ['error' => $e->getMessage()]);

            return response()->json([
                "message" => "An error occurred.",
                "data" => [],
                "status" => "error"
            ], 500);
        }
    }



    public function updateFoodFromAIText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foodText' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError("Fail", $validator->errors()->first(), 200);
        }

        $foodText = $request->get('foodText');

        if (empty($foodText)) {
            return $this->successResponse("No data provided", ['foodText' => '', 'ingredients' => []], 200);
        }

        return $this->generateNutritionJSON($foodText);
    }

    private function generateNutritionJSON($foodSentence)
    {
        if (empty($foodSentence)) {
            return $this->successResponse("No data provided", ['foodText' => '', 'ingredients' => []], 200);
        }

        $client = OpenAI::client(env('OPENAI_API_KEY'));

        // Initial AI prompt (must match fetchFoodJsonFromAI)
        $prompt = <<<EOT
You are a nutrition assistant. Return a pure JSON array based on the following input sentence in French or English.

[input sentence]: {$foodSentence}

TASK:
    1. Extract all food items from the sentence (ignore non-food words).

    2. For each food item:
        - Extract portion/quantity if provided (e.g., "2 bananas", "10g paneer", "a can of coke").
	- If the portion is provided put it as the top 1 in the list of the 5 most common portions sizes
        - If no portion is given, search and provide the 5 most common portion sizes for that specific food item.
	- For the portion make sure it's common french portion name (e.g : chicken : breast), it needs to be understandable by the user
	- Try to make sure the portions are different between each other, and provide the most classic portion in first (e.g : banana : medium size banana)

    3. Return the following JSON object for each food:
        - Nutrition data per 100g/ml: "kcal", "protein", "carbs", "fats"
        - Field "quantity" should always be "100g" or "100ml" based on food type
        - "value" and "quantity" in the units array must represent numeric strings with two decimal places
        - kcal, protein, carbs, fats — all must be strings with two decimals

FORMAT RULES:

    - Return only the JSON array: [ {...}, {...} ]
    - Do not include any text, markdown, code fences, or explanation
    - All numerical fields (kcal, protein, carbs, fats, value, quantity) must be strings with two decimal places, e.g. "42.00"
    - No variables or placeholders like xyz * 2 — use real calculated values
    - Ensure the response is valid JSON, parsable by JSON.parse()

Example input sentence:
    - J'ai mangé 2 bananes, une salade César, 10 grammes de paneer, une canette de coca.

Example response:
    [
        {
            "name": "banane",
            "kcal": "89.00",
            "protein": "1.10",
            "carbs": "22.80",
            "fats": "0.30",
            "quantity": "100g",
            "is_ingredient": 4,
            "recipe_or_ingredient_id": 0,
            "image_url": "",
            "small_image_url": "",
            "units": [
            {
                "size_key": "banane_moyenne",
                "value": "2.00",
                "quantity": "240.00",
                "units": "gram",
                "display": "2 bananes moyennes",
                "kcal": "213.60",
                "protein": "2.64",
                "carbs": "54.72",
                "fats": "0.72"
            },
            ...
            ],
            "barcodes": [],
            "is_favourite": 0
        },
        ....
    ]

ADDITIONAL RULES:
    - All food names ("name") and unit names ("units", "display") must be in French only.
    - Maintain correct singular/plural forms in French.
    - Units must be unique within the list for each food item (no duplicate unit names).
    - Each food item must always contain exactly 5 unique unit variations in "units".
EOT;

        try {
            $result = $client->chat()->create([
                'model' => 'gpt-5',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a French nutrition JSON extractor. Return only JSON. All numbers as strings with two decimals.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                // new parameters supported by GPT-5
                'verbosity' => 'low',             // ask it to produce concise outputs
                'reasoning_effort' => 'minimal', // reduce internal reasoning overhead
            ]);

            $responseText = $result->choices[0]->message->content;

            $aiData = [];
            $aiData = json_decode($responseText, true);

            if (empty($aiData)) {
                Log::info('AI raw response', ['responseText' => $responseText]);
                return $this->errorResponse("Something went wrong, please re-submit your input.", [], 200);
            }

            return $this->successResponse("Nutrition Found", ['foodText' => $foodSentence, 'ingredients' => $aiData], 200);
        } catch (\OpenAI\Exceptions\ErrorException $e) {
            // Handle quota exceeded or other OpenAI errors
            if (str_contains($e->getMessage(), 'You exceeded your current quota')) {
                Log::warning('OpenAI quota exceeded', ['error' => $e->getMessage()]);
                return $this->errorResponse(
                    "Quota exceeded. Please check your OpenAI billing or try again later.",
                    [],
                    429
                );
            }

            Log::error('OpenAI API error', ['error' => $e->getMessage()]);
            return $this->errorResponse(
                "An error occurred while fetching data from AI. Please try again later. : {$e->getMessage()} ",
                [],
                500
            );
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            Log::error('Unexpected error', ['error' => $e->getMessage()]);
            return $this->errorResponse(
                "An unexpected error occurred. Please try again later.: {$e->getMessage()}",
                [],
                500
            );
        }
    }

    public function fetchJSONfromImage_gemini(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'imageString' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->validationError("Fail", $validator->errors()->first(), 200);
        }

        $imageInput = $request->get('imageString');

        // Normalize to array
        $imageArray = is_array($imageInput) ? $imageInput : [$imageInput];

        // Filter valid base64 strings
        $imageArray = array_filter($imageArray, function ($img) {
            return is_string($img) && !empty(trim($img));
        });

        if (empty($imageArray)) {
            return $this->successResponse("No data provided", [
                'foodText' => '',
                'ingredients' => []
            ], 200);
        }

        $apiKey = env('GEMINI_API_KEY');
        $endpoint = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-pro:generateContent?key={$apiKey}";

        $allFoodSentences = [];

        try {

            foreach ($imageArray as $imageString) {

                $response = Http::timeout(30)
                    ->retry(2, 1000) // 2 retries, 1 sec delay
                    ->withHeaders([
                        'Content-Type' => 'application/json'
                    ])
                    ->post($endpoint, [
                        "contents" => [
                            [
                                "parts" => [
                                    [
                                        "text" => "Analyze this food image and describe the food items with estimated portions in a single sentence."
                                    ],
                                    [
                                        "inline_data" => [
                                            "mime_type" => "image/jpeg",
                                            "data" => trim($imageString) // raw base64 only
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]);

                if ($response->failed()) {
                    Log::error('Gemini API error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    continue;
                }

                $data = $response->json();

                $foodSentence = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (is_string($foodSentence) && !empty(trim($foodSentence))) {
                    $allFoodSentences[] = trim($foodSentence);
                } else {
                    Log::info('Gemini empty/invalid response', [
                        'response' => $data
                    ]);
                }
            }

            if (empty($allFoodSentences)) {
                return $this->errorResponse(
                    "Unable to analyze the image(s). Please try again.",
                    [],
                    200
                );
            }

            // Combine all sentences
            $combinedFoodSentence = implode('. ', $allFoodSentences);

            return $this->generateNutritionJSON($combinedFoodSentence);

        } catch (\Exception $e) {

            Log::error('Gemini API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                "An error occurred while processing the request. Please try again later.",
                [],
                500
            );
        }
    }

//     public function fetchFoodJsonFromAI(Request $request)
//     {
//         $data = $request->get('food');
//         $result_found = [];

//         if (empty($data)) {
//             return $this->successResponse("No data provided", ['ingredients' => []], 200);
//         }

//         $client = OpenAI::client(env('OPENAI_API_KEY'));

//         // Initial AI prompt
//             $prompt = <<<EOT
// You are a nutrition assistant. You must strictly return a valid JSON array by analyzing the following sentence (in French or English): {$data}

// ### Task:
// 1. Identify and extract **all food items** mentioned in the sentence (ignore non-food words).
// - Multi-word food names (e.g., butter chicken, vanilla ice cream) must always be extracted as a single food item, without splitting; terms like **glass, can, bottle, box, etc.** should always be treated strictly as portion indicators (not part of the food name), with their portion size identified and realistic nutrition data fetched only for that portion (e.g., if portion is "can", it must be standardized to 355ml).
// 2. For each extracted food:
//    - If a portion/quantity (before or after the food name, e.g. "200g chicken" or "chicken 200g") is explicitly provided → include **that portion as the first entry** inside the "units" array, followed by **4 additional realistic/most common default portions** for the same food (total 5).
//    - If no portion is provided → generate exactly **5 most commonly used realistic default portion sizes** based on whether the food is solid (gram/piece/plate) or liquid (ml/glass/bottle).
//    - Portions may represent **weight, count, slice, piece, part, plate, glass, bottle, can, etc.**
// ### Output:
// Return ONLY a pure JSON array → `[ {...}, {...}, {...} ]` (no markdown, no text, no explanations).

// IMPORTANT:
// ### JSON Integrity Rules (DO NOT BREAK):
// - DO NOT include any variables, formulas, or undefined tokens like `xyz * 2`, `salad_kcal`, etc.
// - DO NOT return any string that includes arithmetic (`*`, `+`, `/`) or undeclared variables.
// - DO NOT return `"kcal": something * 2.5` → must return `"kcal": 200.5` instead.
// - All output MUST be parsable by `JSON.parse()` or PHP `json_decode()` without errors.
// - If you're unsure of exact kcal/macros → use realistic estimates based on common food databases (USDA, etc.).
// - DOUBLE CHECK: After generating JSON, review for invalid formulas or tokens. Return clean numeric JSON only.
// - Every field must contain a **real number**, not a string or formula.
// Each element must strictly follow this structure:
// {
//     "name": string, (English food name),
//     "kcal": number(as per 100 g),
//     "protein": number(as per 100 g),
//     "carbs": number(as per 100 g),
//     "fats": number(as per 100 g),
//     "quantity": "100g or ml as per food",
//     "is_ingredient": 4,
//     "recipe_or_ingredient_id": 0,
//     "image_url": "",
//     "small_image_url": "",
//     "units": [
//         {
//             "size_key": "[portionType]_[amount]",
//             "value": number,
//             "quantity": number, (estimated serving weight in gram/ml, independent of outer quantity)
//             "units": "gram/ml/kg/L/etc.",
//             "display": "[amount]-[portionType]" (amount exactly as in user’s portion),
//             "kcal": number(as per estimated serving weight),
//             "protein": number(as per estimated serving weight),
//             "carbs": number(as per estimated serving weight),
//             "fats": number(as per estimated serving weight)
//         }
//     ],
//     "barcodes": [],
//     "is_favourite": 0
// }

// ### Rules:
// - Preserve the **same order of foods** as in the input.
// - All nutrition values must be returned as actual **numbers**, never formulas or placeholders. Never write things like `"kcal": some value * 3` or `"protein": salad protein`; instead, return calculated values directly.
// - Portions may represent **weight, count, slice, piece, part, plate, glass, bottle, can, etc.**
// - If a quantity is mentioned with portion terms (e.g., ‘6 bottles of coke’, ‘5 boxes of mangoes’), multiply the portion size accordingly and return the data for the total quantity, not just a single unit.
// - The outer quantity must always be fixed at 100, representing nutrition values per 100 g or per 100 ml. The unit (g or ml) should be chosen and adjusted automatically based on whether the food item is solid (grams) or liquid (ml).
// - Inner `"quantity"` = **estimated serving weight** from `size_key`. It must be independent of outer `"quantity"` and never calculated from it.
// - If explicit portion is mentioned → return **that portion first** and then add **4 more realistic/most common default portions** (total 5).
// - If no portion is mentioned → return 5 most commonly used realistic portions for that food, and ensure the 1st portion is the single most typical or standard serving size based on **real-world consumption patterns** (e.g., Coke is most commonly consumed in a can).
// - Portions must always be displayed exactly as mentioned by the user:
//     • If quantity + container/portion (e.g., ‘6 cans of coke’, ‘5 boxes of mangoes’), display as ‘6-can’, ‘5-box’, ‘5-piece’, etc., and calculate nutrition for that multiplied portion.
//     • If weight/volume is specified (e.g., ‘200g mango’, ‘200ml milk’), display as ‘200-g’ or ‘200-ml’ and calculate nutrition based on that portion.
//     • For items like ‘can’, ‘bottle’, or other containers → do not assume a fixed standard size. Always reflect the exact portion given by the user unless explicitly defined in the input.
//     "size_key": "[portionType]_[amount]",
//     "display": "[amount]-[portionType]",
//     "quantity": [estimated serving weight in gram/ml]
// - The inner nutrients (per portion) must always be calculated proportionally based on the outer nutrient data (per 100g/ml or provided reference), ensuring accuracy for each portion size.

// STRICT INSTRUCTION:
// - Ensure JSON is fully valid.
// - Run a `JSON.parse()` or validator check on your side before submitting the final output.
// - DO NOT return if any field is unresolved or contains variable-like placeholders.
// EOT;

//         $result = $client->chat()->create([
//             'model' => 'gpt-4o-mini',
//             'messages' => [
//                 ['role' => 'system', 'content' => 'You are a nutrition assistant. You must return strictly valid JSON arrays. Never use variables, placeholders, or formulas. Every field must be numeric.'],
//                 ['role' => 'user', 'content' => $prompt],
//             ],
//             'temperature' => 0.2,
//         ]);

//         $responseText = $result->choices[0]->message->content;

//         Log::info('AI raw response', ['responseText' => $responseText]);

//         $aiData = [];

//         // Step 1: Try to decode full array first
//         $aiData = json_decode($responseText, true);
//         $retryFoods = [];
//         $result_found = [];

//         if (!is_array($aiData)) {
//             Log::warning("Failed full decode, trying fallback parser", ['raw_response' => $responseText]);

//             // Try to extract individual JSON objects
//             preg_match_all('/\{(?:[^{}]|(?R))*\}/', $responseText, $matches);

//             if (!empty($matches[0])) {
//                 foreach ($matches[0] as $jsonObject) {
//                     $parsedItem = json_decode($jsonObject, true);
//                     if (
//                         is_array($parsedItem) &&
//                         isset($parsedItem['kcal'], $parsedItem['protein'], $parsedItem['carbs'], $parsedItem['fats']) &&
//                         is_numeric($parsedItem['kcal']) &&
//                         is_numeric($parsedItem['protein']) &&
//                         is_numeric($parsedItem['carbs']) &&
//                         is_numeric($parsedItem['fats'])
//                     ) {
//                         // Format outer nutrients
//                         $parsedItem['kcal'] = number_format($parsedItem['kcal'], 2, '.', '');
//                         $parsedItem['protein'] = number_format($parsedItem['protein'], 2, '.', '');
//                         $parsedItem['carbs'] = number_format($parsedItem['carbs'], 2, '.', '');
//                         $parsedItem['fats'] = number_format($parsedItem['fats'], 2, '.', '');

//                         foreach ($parsedItem['units'] as $key => $unit) {
//                             $parsedItem['units'][$key]['kcal'] = number_format($unit['kcal'], 2, '.', '');
//                             $parsedItem['units'][$key]['protein'] = number_format($unit['protein'], 2, '.', '');
//                             $parsedItem['units'][$key]['carbs'] = number_format($unit['carbs'], 2, '.', '');
//                             $parsedItem['units'][$key]['fats'] = number_format($unit['fats'], 2, '.', '');
//                             $parsedItem['units'][$key]['value'] = number_format($unit['value'], 2, '.', '');
//                             $parsedItem['units'][$key]['quantity'] = number_format($unit['quantity'], 2, '.', '');
//                         }

//                         $result_found[] = $parsedItem;
//                     } else {
//                         preg_match('/"name"\s*:\s*"([^"]+)"/', $jsonObject, $matches);
//                         $foodName = $matches[1] ?? 'unknown food 1';
//                         $retryFoods[] = $foodName;
//                     }
//                 }
//             }
//         } else {
//             // Regular decode succeeded
//             foreach ($aiData as $item) {
//                 if (
//                     isset($item['kcal'], $item['protein'], $item['carbs'], $item['fats']) &&
//                     is_numeric($item['kcal']) && is_numeric($item['protein']) &&
//                     is_numeric($item['carbs']) && is_numeric($item['fats'])
//                 ) {
//                     // Format outer nutrients
//                     $item['kcal'] = number_format($item['kcal'], 2, '.', '');
//                     $item['protein'] = number_format($item['protein'], 2, '.', '');
//                     $item['carbs'] = number_format($item['carbs'], 2, '.', '');
//                     $item['fats'] = number_format($item['fats'], 2, '.', '');

//                     foreach ($item['units'] as $key => $unit) {
//                         $item['units'][$key]['kcal'] = number_format($unit['kcal'], 2, '.', '');
//                         $item['units'][$key]['protein'] = number_format($unit['protein'], 2, '.', '');
//                         $item['units'][$key]['carbs'] = number_format($unit['carbs'], 2, '.', '');
//                         $item['units'][$key]['fats'] = number_format($unit['fats'], 2, '.', '');
//                         $item['units'][$key]['value'] = number_format($unit['value'], 2, '.', '');
//                         $item['units'][$key]['quantity'] = number_format($unit['quantity'], 2, '.', '');
//                     }

//                     $result_found[] = $item;
//                 } else {
//                     $retryFoods[] = $item['name'] ?? 'unknown food';
//                 }
//             }
//         }
//         // ✅ STEP: Validate retryFoods via GPT before retrying
//         if (!empty($retryFoods)) {
//             $retryFoodsList = implode("\n- ", $retryFoods);
//             // dd($retryFoodsList);
//             $message = "- " . $retryFoodsList;
//             $validationPrompt = <<<EOT
//     The following food names were returned with invalid or incomplete nutrition data in the previous response:
//     $message

//     Please double-check if these food items are real and if accurate nutrition information is generally available for them. Only include items that are valid foods and have realistic macronutrient values in common nutrition databases (e.g., USDA, Open Food Facts).

//     Return a clean JSON array with **only valid food names** from the list above.

//     Example:
//     ["chicken", "coke", "banana"]

//     DO NOT include non-foods or vague dishes that cannot have accurate nutrition data.
//     EOT;

//             $validationResult = $client->chat()->create([
//                 'model' => 'gpt-4o-mini',
//                 'messages' => [
//                     ['role' => 'system', 'content' => 'You are a strict nutrition data validator.'],
//                     ['role' => 'user', 'content' => $validationPrompt],
//                 ],
//                 'temperature' => 0,
//             ]);

//             $validatedText = $validationResult->choices[0]->message->content;
//             $validatedFoods = json_decode($validatedText, true);
//             Log::info('1');
//             if (is_array($validatedFoods) && !empty($validatedFoods)) {
//                 $retryFoods = array_filter(array_map('trim', $validatedFoods));
//                 Log::info('2');
//             } else {
//                 Log::info("Validation returned no valid foods. Skipping retry.");
//                 $retryFoods = [];
//             }
//         }

//         // ✅ STEP: Retry with cleaned and validated food names
//         if (!empty($retryFoods)) {
//             Log::info('3');
//             $retryFoodsList = implode("\n- ", $retryFoods);
//             $message = "- " . $retryFoodsList;

//             $retryPrompt = <<<EOT
//     You previously returned some food items with placeholder or undefined values.

//     Please now return correct and complete numeric nutrition data (per 100g/ml) for the following food items, strictly as a JSON array. Do not use variables, placeholders, or formulas. All values must be numeric.

//     Foods to fix:
//     $message

//     Return only the corrected JSON array.
//     EOT;

//             $retryResult = $client->chat()->create([
//                 'model' => 'gpt-4o-mini',
//                 'messages' => [
//                     ['role' => 'system', 'content' => 'You are a nutrition assistant. Return strictly valid JSON arrays. No variables or formulas.'],
//                     ['role' => 'user', 'content' => $retryPrompt],
//                 ],
//                 'temperature' => 0.2,
//             ]);
//             // dd($retryResult);
//             $retryText = $retryResult->choices[0]->message->content;
//             $retryData = json_decode($retryText, true);
//             Log::info('4');
//             if (is_array($retryData)) {
//                 Log::info('5');
//                 foreach ($retryData as $item) {
//                     Log::info('--6');
//                     if (
//                         isset($item['kcal'], $item['protein'], $item['carbs'], $item['fats']) &&
//                         is_numeric($item['kcal']) && is_numeric($item['protein']) &&
//                         is_numeric($item['carbs']) && is_numeric($item['fats'])
//                     ) {
//                         Log::info('6');
//                         $item['kcal'] = number_format($item['kcal'], 2, '.', '');
//                         $item['protein'] = number_format($item['protein'], 2, '.', '');
//                         $item['carbs'] = number_format($item['carbs'], 2, '.', '');
//                         $item['fats'] = number_format($item['fats'], 2, '.', '');
//                         foreach ($item['units'] as $key => $unit) {
//                             $item['units'][$key]['kcal'] = number_format($unit['kcal'], 2, '.', '');
//                             $item['units'][$key]['protein'] = number_format($unit['protein'], 2, '.', '');
//                             $item['units'][$key]['carbs'] = number_format($unit['carbs'], 2, '.', '');
//                             $item['units'][$key]['fats'] = number_format($unit['fats'], 2, '.', '');
//                             $item['units'][$key]['value'] = number_format($unit['value'], 2, '.', '');
//                             $item['units'][$key]['quantity'] = number_format($unit['quantity'], 2, '.', '');
//                         }
//                         $result_found[] = $item;
//                     } else {
//                         Log::info('7');
//                         Log::error("Retry still failed for item", ['item' => $item]);
//                     }
//                 }
//             }
//         }

//         return $this->successResponse("Nutrition Found", ['ingredients' => $result_found], 200);
//     }

    public function updateDailyFoodPlanner(Request $request){
        try {
            /* Validate Data */
            $validation = [
                'id' => ['required', 'integer'],
                'date' => ['required', 'date', 'date_format:Y-m-d'],
                'quantity' => ['required', 'numeric'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }
            $getLoggedFood = FoodPlanner::where(['id' => $request->id, 'user_id' => Auth::user()->id, 'date' => $request->date])->first();

            if ($getLoggedFood != null) {

                // Step 1: Store old protein value
                $oldQuantity = $getLoggedFood->portion > 0 ? $getLoggedFood->portion : 1; // avoid division by zero
                $perGramProteins = $getLoggedFood->proteins / $oldQuantity;
                $oldProteins = $getLoggedFood->proteins;

                // Step 2: Calculate new values
                $newQuantity = $request->quantity;
                $newProteins = round($perGramProteins * $newQuantity, 2);

                $getLoggedFood->portion = $newQuantity;
                $getLoggedFood->kcal = round(($getLoggedFood->kcal / $oldQuantity) * $newQuantity, 2);
                $getLoggedFood->carbs = round(($getLoggedFood->carbs / $oldQuantity) * $newQuantity, 2);
                $getLoggedFood->proteins = $newProteins;
                $getLoggedFood->fats = round(($getLoggedFood->fats / $oldQuantity) * $newQuantity, 2);

                $getLoggedFood->save();

                // Step 3: Adjust protein logs
                $proteinDiff = round($newProteins - $oldProteins, 2);

                if ($proteinDiff > 0) {
                    // Add protein log
                    UserLevelTaskLog::create([
                        'user_id' => Auth::user()->id,
                        'level_task_type' => 'protiens',
                        'completed_count' => $proteinDiff,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif ($proteinDiff < 0) {
                    // Remove protein logs
                    $this->deleteLogProtienEntries(Auth::user()->id, abs($proteinDiff));
                }

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
                                "fats" => $nutritionDetails['fats'],
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => asset(Storage::url($recipeData['picture'])),
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => []
                            ];
                        }
                    } else if ($meal['is_ingredient'] == 1) {
                        $ingredientData = NutritionIngredient::where(['id' => $meal['recipe_or_ingredient_id']])->with(['units' => function ($query) { $query->select('nutrition_ingredient_id', 'size_key', 'value', 'units');}])->first();

                        $unitOptions = $ingredientData->units
                            ->map(function ($row) {
                                // Extract only the text part before the last underscore and number, if present
                                if (preg_match('/^(.+?)_\d+g$/', $row->size_key, $matches)) {
                                    $displayKey = $matches[1];
                                } else {
                                    $displayKey = $row->size_key;
                                }

                                // Assign the display value to the 'display' attribute
                                $row->display = $displayKey . ' - ' . $row->value;
                                return $row;
                            });
                        if (!empty($ingredientData)) {
                            $isUserCreated = 0;
                            if($ingredientData->user_id != null){
                                $isUserCreated = 1;
                            }
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
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => asset(Storage::url($ingredientData['image'])),
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => $unitOptions,
                                'isUserCreated' => $isUserCreated,
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
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => []
                            ];
                        }
                    } else if ($meal['is_ingredient'] == 4) {
                            $mealData[] = [
                                "id" => $meal['id'],
                                "recipe_or_ingredient_id" => $meal['recipe_or_ingredient_id']??0,
                                "title" => $meal['AI_food_name'],
                                "meal_type" => $meal['meal_type'],
                                "date" => $meal['date'],
                                "kcal" => $meal['kcal'],
                                "carbs" => $meal['carbs'],
                                "proteins" => $meal['proteins'],
                                "fats" => $meal['fats'],
                                "no_of_servings" => $meal['no_of_servings'],
                                "image" => '',
                                "is_ingredient" => $meal['is_ingredient'],
                                "units" => []
                            ];
                    }
                }

                $mealRecipe = [
                    "name" => ucfirst($request->meal_type),
                    "recipe" => $mealData
                ];
                return $this->successResponse("Food Data updated successfully!", $mealRecipe, 200);
            }
            return $this->successResponse("Data Not Found.", [], 200);
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    /**
     * Analyze food image using advanced nutrition model with confidence scoring
     * Returns detailed nutritional breakdown with macros calculated from AI analysis
     */
    public function analyzeImageForNutrition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'imageString' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->validationError("Fail", $validator->errors()->first(), 200);
        }

        $imageInput = $request->get('imageString');

        // Convert single image to array format for uniform processing
        $imageArray = is_array($imageInput) ? $imageInput : [$imageInput];

        // Validate all images are non-empty strings
        $imageArray = array_filter($imageArray, function($img) {
            return is_string($img) && !empty(trim($img));
        });

        if (empty($imageArray)) {
            return $this->successResponse("No data provided", [
                'ingredients' => [],
                'total_quantity_g' => 0,
                'total_protein_g' => 0,
                'total_carbs_g' => 0,
                'total_fat_g' => 0,
                'total_kcal' => 0,
                'analysis_confidence' => 0
            ], 200);
        }

        $client = OpenAI::client(env('OPENAI_API_KEY'));

        try {
            // System prompt for advanced nutrition analysis
            $systemPrompt = <<<'SYSPROMPT'
You are a food image nutrition analysis model for a calorie tracking app.
Your task is to analyze a meal photo and return a realistic estimate of:
- visible food items
- edible quantity in grams for each item
- protein, carbs, fats, and calories for each item
- meal totals

Follow this process internally:
1. Identify all distinct visible food items.
2. Estimate the likely edible portion of each item using visual cues:
   - plate, bowl, tray, or container size
   - depth, stacking, and spread
   - relative size compared with cutlery, hands, cups, napkins, or common tableware
3. Estimate a realistic total meal weight, then distribute weight across items consistently.
4. Map each visible item to the closest common standard food entry (USDA-style cooked/as-served equivalent).
5. Compute macros from grams using per-100g values from the reference table below.
6. Compute calories strictly from macros: kcal = round((protein_g * 4) + (carbs_g * 4) + (fat_g * 9))

Rules:
- Be conservative but realistic. Avoid both overestimation and underestimation.
- Prefer home-style portions unless the image clearly shows a large restaurant-style serving.
- Do not invent foods that are not visible.
- If a food is present but cannot be identified reliably, use "unknown_food".
- If a mixed dish cannot be reliably decomposed visually, log it as one common mixed dish rather than hallucinating hidden ingredients.
- Only include oils, butter, sauces, or dressings if they are visible or strongly implied by the preparation style.
- Garnishes should remain minimal unless clearly eaten as a substantial part of the meal.
- Total meal weight should usually be realistic for the container shown:
  · light plate: 200–350g
  · typical plate: 350–700g
  · large plate: 700–900g if clearly justified
  · bowl meals may exceed plate norms if depth clearly supports it
- If the image suggests an unusually large or small serving, follow the image rather than forcing the default range.
- If confidence is low, reduce confidence_score rather than inventing precision.

── NUTRITION REFERENCE TABLE (per 100g, cooked/as-served) ──
PROTEINS
· Chicken breast (grilled/baked):   P=31  C=0   F=3.6  → 165 kcal
· Chicken thigh (grilled):           P=26  C=0   F=9    → 189 kcal
· Salmon (baked/pan-fried):          P=25  C=0   F=13   → 208 kcal
· Tuna (canned in water):            P=26  C=0   F=1    → 116 kcal
· Beef mince (lean, cooked):         P=26  C=0   F=10   → 196 kcal
· Steak (lean, grilled):             P=30  C=0   F=7    → 183 kcal
· Egg (whole, boiled):               P=13  C=1   F=11   → 155 kcal
· Shrimp (cooked):                   P=24  C=0.2 F=1.7  → 119 kcal
· Battered/fried fish:               P=15  C=12  F=10   → 199 kcal
· Tofu (firm):                       P=8   C=2   F=4.8  → 83 kcal
· Pork tenderloin (roasted):         P=26  C=0   F=4    → 143 kcal

CARBS
· White rice (cooked):               P=2.7 C=28  F=0.3  → 130 kcal
· Brown rice (cooked):               P=2.6 C=23  F=0.9  → 111 kcal
· Pasta (cooked):                    P=5   C=25  F=1.1  → 131 kcal
· Potato (boiled):                   P=2   C=17  F=0.1  → 77 kcal
· French fries:                      P=3.4 C=41  F=14   → 312 kcal
· White bread (1 slice ≈ 30g):       P=9   C=49  F=3.2  → 265 kcal
· Quinoa (cooked):                   P=4.4 C=21  F=1.9  → 120 kcal
· Sweet potato (baked):              P=2   C=20  F=0.1  → 90 kcal
· Oats (cooked with water):          P=2.4 C=12  F=1.4  → 71 kcal
· Tortilla wrap (flour, 1 ≈ 45g):    P=7   C=49  F=7    → 300 kcal

VEGETABLES
· Broccoli (steamed):                P=2.8 C=7   F=0.4  → 35 kcal
· Spinach (cooked):                  P=3   C=3.6 F=0.5  → 23 kcal
· Mixed salad leaves (raw):          P=1.4 C=2.2 F=0.2  → 17 kcal
· Tomato (raw):                      P=0.9 C=3.9 F=0.2  → 18 kcal
· Cucumber (raw):                    P=0.7 C=3.6 F=0.1  → 15 kcal
· Carrot (cooked):                   P=0.8 C=8   F=0.2  → 35 kcal
· Onion (cooked):                    P=1.1 C=9.3 F=0.1  → 40 kcal
· Peppers (roasted):                 P=1   C=6   F=0.3  → 28 kcal
· Zucchini (cooked):                 P=1.2 C=3.4 F=0.3  → 21 kcal
· Mushrooms (sautéed):               P=2.2 C=3.3 F=3.5  → 53 kcal

FATS & CONDIMENTS
· Olive oil:                         P=0   C=0   F=100  → 884 kcal
· Butter:                            P=0.6 C=0.1 F=81   → 717 kcal
· Cheddar cheese:                    P=25  C=1.3 F=33   → 403 kcal
· Avocado:                           P=2   C=9   F=15   → 160 kcal
· Mayonnaise:                        P=1.1 C=0.6 F=75   → 680 kcal
· Hummus:                            P=8   C=14  F=10   → 177 kcal
· Ketchup:                           P=1.3 C=27  F=0.1  → 112 kcal
· Vinaigrette dressing:              P=0.1 C=3   F=14   → 137 kcal
· Soy sauce:                         P=8   C=8   F=0    → 60 kcal
· Tomato sauce/passata:              P=1.5 C=7   F=0.3  → 36 kcal

Use this reference table when applicable. If the exact item is missing, map to the closest common equivalent.
Return valid JSON only. No markdown. No explanation.
SYSPROMPT;

            // User prompt for specific format
            $userPrompt = <<<'USERPROMPT'
Analyze this meal photo.
Return ONLY valid JSON in this format:
{
  "ingredients": [
    {
      "name": "French food name",
      "quantity_g": 0,
      "protein_g": 0,
      "carbs_g": 0,
      "fat_g": 0,
      "kcal": 0,
      "confidence": 0.0
    }
  ],
  "total_quantity_g": 0,
  "total_protein_g": 0,
  "total_carbs_g": 0,
  "total_fat_g": 0,
  "total_kcal": 0,
  "analysis_confidence": 0.0
}
Requirements:
- Use numbers, not strings.
- kcal for each ingredient must equal round((protein_g * 4) + (carbs_g * 4) + (fat_g * 9)).
- total_kcal must equal the sum of ingredient kcal values.
- Use French food names.
- If an item cannot be identified reliably, use "unknown_food".
USERPROMPT;

            $allAnalysisResults = [];

            // Process each image
            foreach ($imageArray as $imageString) {
                $imageUrl = 'data:image/jpeg;base64,' . trim($imageString);

                $result = $client->chat()->create([
                    'model' => 'gpt-4o',
                    'temperature' => 0,
                    'max_tokens' => 1500,
                    'response_format' => [
                        'type' => 'json_object'
                    ],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $userPrompt
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => $imageUrl,
                                        'detail' => 'auto'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]);

                $responseText = $result->choices[0]->message->content ?? '';

                if (empty($responseText)) {
                    Log::info('AI raw response for image (empty)', ['responseText' => $result->choices[0]->message->content ?? null]);
                    continue;
                }

                $analysisData = json_decode($responseText, true);

                if (is_array($analysisData) && !empty($analysisData)) {
                    $allAnalysisResults[] = $analysisData;
                } else {
                    Log::info('AI failed to parse JSON response', ['responseText' => $responseText]);
                }
            }

            if (empty($allAnalysisResults)) {
                return $this->errorResponse("Unable to analyze image. Please try another image.", [], 200);
            }

            // Merge results if multiple images
            $mergedResult = $this->mergeNutritionAnalysis($allAnalysisResults);

            return $this->successResponse("Image analyzed successfully", $mergedResult, 200);

        } catch (\OpenAI\Exceptions\ErrorException $e) {
            if (str_contains($e->getMessage(), 'You exceeded your current quota')) {
                Log::warning('OpenAI quota exceeded', ['error' => $e->getMessage()]);
                return $this->errorResponse(
                    "Quota exceeded. Please check your OpenAI billing or try again later.",
                    [],
                    429
                );
            }

            Log::error('OpenAI API error', ['error' => $e->getMessage()]);
            return $this->errorResponse(
                "An error occurred while analyzing the image. Please try again later.",
                [],
                500
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error in image analysis', ['error' => $e->getMessage()]);
            return $this->errorResponse(
                "An unexpected error occurred. Please try again later.",
                [],
                500
            );
        }
    }

    /**
     * Merge multiple nutrition analysis results into a single comprehensive result
     */
    private function mergeNutritionAnalysis(array $results): array
    {
        $mergedIngredients = [];
        $totalQuantity = 0;
        $totalProtein = 0;
        $totalCarbs = 0;
        $totalFat = 0;
        $totalKcal = 0;
        $confidenceScores = [];

        foreach ($results as $result) {
            // Merge ingredients
            if (isset($result['ingredients']) && is_array($result['ingredients'])) {
                foreach ($result['ingredients'] as $ingredient) {
                    $mergedIngredients[] = $ingredient;
                    $totalQuantity += (float)($ingredient['quantity_g'] ?? 0);
                    $totalProtein += (float)($ingredient['protein_g'] ?? 0);
                    $totalCarbs += (float)($ingredient['carbs_g'] ?? 0);
                    $totalFat += (float)($ingredient['fat_g'] ?? 0);
                    $totalKcal += (int)($ingredient['kcal'] ?? 0);

                    if (isset($ingredient['confidence'])) {
                        $confidenceScores[] = (float)$ingredient['confidence'];
                    }
                }
            }

            // Add overall analysis confidence
            if (isset($result['analysis_confidence'])) {
                $confidenceScores[] = (float)$result['analysis_confidence'];
            }
        }

        // Calculate average confidence
        $avgConfidence = !empty($confidenceScores) ? array_sum($confidenceScores) / count($confidenceScores) : 0;

        return [
            'ingredients' => $mergedIngredients,
            'total_quantity_g' => round($totalQuantity, 2),
            'total_protein_g' => round($totalProtein, 2),
            'total_carbs_g' => round($totalCarbs, 2),
            'total_fat_g' => round($totalFat, 2),
            'total_kcal' => round($totalKcal, 0),
            'analysis_confidence' => round($avgConfidence, 2)
        ];
    }

}

