<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MealType;
use App\Models\NutritionIngredient;
use App\Models\StepsGoal;
use App\Models\User;
use App\Models\UserRecipe;
use App\Models\UserRecipeIngredient;
use App\Models\UsersCurrentMeasurement;
use App\Models\UsersInitialMeasurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class UserRecipeController extends ApiController
{
    public function userRecipeCreate(Request $request)
    {
        try {
            $data = $request->only('title', 'quantity', 'description', 'image', 'meal_type_id');

            $validationRules = [
                'title' => ['required', 'string', 'max:50'],
                'quantity' => ['nullable', 'numeric', 'min:1'],
                'meal_type_id' => ['required', 'numeric'],
                'description' => ['required', 'string', 'max:300'],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,bmp,tiff,heif,heic'],
            ];

            $validationMessages = [
                'title.required' => 'Please enter title.',
                'description.required' => 'Please provide a description.',
                'image.mimes' => "Please select an image with one of the following formats: jpg, jpeg, png, gif, bmp, tiff, heif, heic.",
            ];

            $validator = Validator::make($data, $validationRules, $validationMessages);

            if ($validator->fails()) {
                return $this->validationError("Validation failed", $validator->errors()->first(), 200);
            }

            $mealType = MealType::find($data['meal_type_id']);
            if (!$mealType) {
                return $this->validationError("Validation failed", "Invalid meal type id", 200);
            }
            // Generate slug from title
            $slug = Str::slug($data['title']);

            // Create the recipe
            $userRecipe = UserRecipe::create([
                'user_id' => Auth::user()->id,
                'title' => $data['title'],
                'slug' => $slug,
                'meal_type_id' => $data['meal_type_id'],
                'quantity' => $data['quantity'] ?? 0,
                'kcal' => 0,
                'description' => $data['description'],
                'totalQuantity' => 0,
                'totalKcal' => 0,
            ]);

            // Handle image upload if exists
            if ($request->hasFile('image')) {
                $this->handleImageUpload($request, $userRecipe);
            }
            // Prepare and return response
            $userRecipedata = $this->prepareRecipeResponseData($userRecipe);

            return $this->successResponse("Recipe Created Successfully", $userRecipedata, 200);

        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function userRecipeUpdate(Request $request)
    {
        try {
            $data = $request->only('id','title', 'quantity', 'description', 'image', 'remove_image', 'meal_type_id');  // Only retrieve necessary data
            // Define validation rules
            $validationRules = [
                'id' => ['required', 'numeric'],
                'title' => ['required', 'string', 'max:50'],
                'quantity' => ['nullable', 'numeric', 'min:1'],
                'meal_type_id' => ['required', 'numeric'],
                'description' => ['required', 'string', 'max:300'],
                'remove_image' => ['nullable', 'boolean'],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,bmp,tiff,heif,heic'],
            ];

            $validationMessages = [
                'title.required' => 'Please enter title.',
                'description.required' => 'Please provide a description.',
                'image.mimes' => "Please select an image with one of the following formats: jpg, jpeg, png, gif, bmp, tiff, heif, heic.",
            ];

            $validator = Validator::make($data, $validationRules, $validationMessages);

            if ($validator->fails()) {
                return $this->validationError("Validation failed", $validator->errors()->first(), 200);
            }

            $id = $data['id'];
            // Find the existing recipe
            $userRecipe = UserRecipe::where('id', $id)
                ->where('user_id', Auth::user()->id)
                ->first();
            if(!$userRecipe){
                return $this->successResponse("No Recipe found", (object)[], 200);
            }

            // Update recipe fields
            $userRecipe->title = $data['title'];
            $userRecipe->quantity = $data['quantity'] ?? 0;
            $userRecipe->meal_type_id = $data['meal_type_id'];
            $userRecipe->description = $data['description'];

            // Check if the image should be removed
            if ($request->has('remove_image') && $request->remove_image == true) {
                // Remove the current image
                if ($userRecipe->image) {
                    Storage::disk('public')->delete($userRecipe->image);
                    $userRecipe->image = null;  // Remove the image reference from the database
                }
            } elseif ($request->hasFile('image')) {
                // Handle new image upload
                $this->handleImageUpload($request, $userRecipe);
            }

            $userRecipe->save();
            // Prepare and return response
            $userRecipedata = $this->prepareRecipeResponseData($userRecipe);

            return $this->successResponse("Recipe Updated Successfully", $userRecipedata, 200);

        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function addIngredientUserRecipe(Request $request)
    {
        try {
            $data = $request->only('user_recipe_id', 'nutritiondetails');  // Only retrieve necessary data

            // Define validation rules
            $validationRules = [
                'user_recipe_id' => ['required', 'numeric'],
                'nutritiondetails' => ['required', 'array'],
                'nutritiondetails.*.ingredient_id' => ['required', 'numeric'],
                'nutritiondetails.*.quantity' => ['required', 'numeric', 'min:1'],
            ];

            $validator = Validator::make($data, $validationRules);

            if ($validator->fails()) {
                return $this->validationError("Validation failed", $validator->errors()->first(), 200);
            }

            // Find the user recipe
            $userRecipe = UserRecipe::where('id', $data['user_recipe_id'])
                ->where('user_id', Auth::user()->id)
                ->first();
            if(!$userRecipe){
                return $this->successResponse("No Recipe found", (object)[], 200);
            }

            // Check if the ingredient already exists for this recipe then delete
            UserRecipeIngredient::where('user_recipes_id', $userRecipe->id)->delete();

            // Process each ingredient
            foreach ($data['nutritiondetails'] as $ingredient) {
                $ingredientRecord = NutritionIngredient::find($ingredient['ingredient_id']);
                if (!$ingredientRecord) continue;  // Skip if ingredient not found

                UserRecipeIngredient::create([
                    'user_recipes_id' => $userRecipe->id,
                    'ingredient_id' => $ingredient['ingredient_id'],
                    'quantity' => $ingredient['quantity'],
                    'kcal' => ($ingredientRecord->kcal * $ingredient['quantity']) / 100,
                    'protein' => ($ingredientRecord->protein * $ingredient['quantity']) / 100,
                    'fats' => ($ingredientRecord->fats * $ingredient['quantity']) / 100,
                    'carbs' => ($ingredientRecord->carbs * $ingredient['quantity']) / 100,
                ]);
            }

            // Recalculate the recipe's totals
            $this->recalculateRecipeTotals($userRecipe);
            // Prepare and return response
            $userRecipedata = $this->prepareRecipeResponseData($userRecipe);

            return $this->successResponse("Recipe Ingredients Updated Successfully", $userRecipedata, 200);

        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function userDeleteRecipe(Request $request)
    {
        try {
            $id = $request->only('id');
            $userRecipe = UserRecipe::where('id', $id)
                ->where('user_id', Auth::user()->id)
                ->first();
            if(!$userRecipe){
                return $this->successResponse("No Recipe found", [], 200);
            }
            // Delete associated ingredients
            $userRecipe->ingredients()->delete();

            // Delete the recipe
            $userRecipe->delete();

            return $this->successResponse("Recipe and Ingredients Deleted Successfully", [], 200);

        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
    private function handleImageUpload(Request $request, UserRecipe $userRecipe)
    {
        if ($userRecipe->image) {
            Storage::disk('public')->delete($userRecipe->image);
            $userRecipe->image = null;  // Remove the image reference from the database
        }
        $imageName = $userRecipe->title . '.' . time() . '.' . $request->image->extension();
        if (!Storage::disk('public')->exists("/userRecipe")) {
            Storage::disk('public')->makeDirectory("/userRecipe");
        }
        $request->image->storeAs('userRecipe', $imageName, 'public');
        $userRecipe->image = "userRecipe/{$imageName}";
        $userRecipe->save();
    }

    private function prepareRecipeResponseData(UserRecipe $userRecipe, $quantity = null, $meal_type_id = 0)
    {
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
        }else{
            $targetCalorie = 0;
        }
        $goal = $targetCalorie;

        $targetCarbsCalories = $goal * 0.5;
        $targetCarbsGrams = round($targetCarbsCalories / 4);

        $targetProteinsCalories = $goal * 0.3;
        $targetProteinsGrams = round($targetProteinsCalories / 4);

        $targetFatsCalories = $goal * 0.2;
        $targetFatsGrams = round($targetFatsCalories / 9);
        // Calculate total grams of carbs, protein, and fat
        $total = $userRecipe->carbs + $userRecipe->protein + $userRecipe->fats;
        if($quantity){
            $userRecipe->kcal = $userRecipe->kcal * ( $quantity / 100);
            $userRecipe->protein = $userRecipe->protein * ( $quantity / 100);
            $userRecipe->fats = $userRecipe->fats * ( $quantity / 100);
            $userRecipe->carbs = $userRecipe->carbs * ( $quantity / 100);
            $total = $userRecipe->carbs + $userRecipe->protein + $userRecipe->fats;
        }elseif($userRecipe->totalQuantity){
            $userRecipe->kcal = $userRecipe->kcal * ( $userRecipe->totalQuantity / 100);
            $userRecipe->protein = $userRecipe->protein * ( $userRecipe->totalQuantity / 100);
            $userRecipe->fats = $userRecipe->fats * ( $userRecipe->totalQuantity / 100);
            $userRecipe->carbs = $userRecipe->carbs * ( $userRecipe->totalQuantity / 100);
            $total = $userRecipe->carbs + $userRecipe->protein + $userRecipe->fats;
        }
        $carbsPercentage = round(($userRecipe->carbs / $targetCarbsGrams) * 100);
        $proteinsPercentage = round(($userRecipe->protein / $targetProteinsGrams) * 100);
        $fatsPercentage = round(($userRecipe->fats / $targetFatsGrams) * 100);
        $kcalPercentage = round(($userRecipe->kcal / $targetCalorie) * 100);

        $carbsPercentage = min($carbsPercentage, 100);
        $proteinsPercentage = min($proteinsPercentage, 100);
        $fatsPercentage = min($fatsPercentage, 100);
        $kcalPercentage = min($kcalPercentage, 100);

        // Calculate percentages
        $macrosCarbsPercentage = $total ? ($userRecipe->carbs / $total) * 100 : 0;
        $macrosProteinsPercentage = $total ? ($userRecipe->protein / $total) * 100 : 0;
        $macrosFatsPercentage = $total ? ($userRecipe->fats / $total) * 100 : 0;

        $ingredients = $userRecipe->ingredients()->get(); // Get ingredients for the recipe

        $ingredientsData = $ingredients->isEmpty() ? [] : $ingredients->map(function ($ingredient) {
            return [
                'id' => $ingredient->id,
                'name' => NutritionIngredient::find($ingredient->ingredient_id)->name ?? "",
                'ingredient_id' => $ingredient->ingredient_id,
                'quantity' => number_format($ingredient->quantity,1),
                'kcal' => number_format($ingredient->kcal,1),
                'protein' => number_format($ingredient->protein,1),
                'fats' => number_format($ingredient->fats,1),
                'carbs' => number_format($ingredient->carbs,1),
            ];
        });
        $mealTypeName = MealType::find($userRecipe->meal_type_id) ? MealType::find($userRecipe->meal_type_id)->slug : '';
        if($meal_type_id){
            $mealTypeName = MealType::find($meal_type_id) ? MealType::find($meal_type_id)->slug : '';
        }
        return [
            'id' => $userRecipe->id,
            'user_id' => $userRecipe->user_id,
            'title' => $userRecipe->title,
            'meal_type' => $mealTypeName,
            'image_url' => $userRecipe->image ? asset(Storage::url($userRecipe->image)) : '',
            'quantity' => $quantity ? $quantity : ($userRecipe->totalQuantity ? $userRecipe->totalQuantity : "0"),
            'kcal' => number_format($userRecipe->kcal,1),
            'description' => $userRecipe->description,
            'ingredients' => $ingredientsData,
            'macros' => [
                'protein' => number_format($userRecipe->protein, 1),
                'fats' => number_format($userRecipe->fats, 1),
                'carbs' => number_format($userRecipe->carbs, 1),
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
        ];
    }
    private function recalculateRecipeTotals(UserRecipe $userRecipe)
    {
        $totalKcal = 0;
        $totalProtein = 0;
        $totalFats = 0;
        $totalCarbs = 0;
        $totalQuantity = 0;

        // Recalculate total kcal and quantity
        $ingredients = UserRecipeIngredient::where('user_recipes_id', $userRecipe->id)->get();
        foreach ($ingredients as $ingredient) {
            $totalKcal += $ingredient->kcal;
            $totalProtein += $ingredient->protein;
            $totalFats += $ingredient->fats;
            $totalCarbs += $ingredient->carbs;
            $totalQuantity += $ingredient->quantity;
        }

        // Update the recipe's total values
        $userRecipe->totalKcal = round($totalKcal, 2);
        $userRecipe->totalQuantity = round($totalQuantity, 2);
        $userRecipe->kcal = round(($totalKcal * 100) / $totalQuantity, 2);  // Kcal per 100g
        $userRecipe->protein = round(($totalProtein * 100) / $totalQuantity, 2);  // protein per 100g
        $userRecipe->fats = round(($totalFats * 100) / $totalQuantity, 2);  // fats per 100g
        $userRecipe->carbs = round(($totalCarbs * 100) / $totalQuantity, 2);  // carbs per 100g

        // Convert to whole number if there are no decimal places
        $userRecipe->totalKcal = $userRecipe->totalKcal == floor($userRecipe->totalKcal) ? (int)$userRecipe->totalKcal : $userRecipe->totalKcal;
        $userRecipe->totalQuantity = $userRecipe->totalQuantity == floor($userRecipe->totalQuantity) ? (int)$userRecipe->totalQuantity : $userRecipe->totalQuantity;
        $userRecipe->kcal = $userRecipe->kcal == floor($userRecipe->kcal) ? (int)$userRecipe->kcal : $userRecipe->kcal;
        $userRecipe->protein = $userRecipe->protein == floor($userRecipe->protein) ? (int)$userRecipe->protein : $userRecipe->protein;
        $userRecipe->fats = $userRecipe->fats == floor($userRecipe->fats) ? (int)$userRecipe->fats : $userRecipe->fats;
        $userRecipe->carbs = $userRecipe->carbs == floor($userRecipe->carbs) ? (int)$userRecipe->carbs : $userRecipe->carbs;
        $userRecipe->save();
    }

    public function userRecipeDetails( Request $request){
        $data = $request->only('id', 'quantity', 'meal_type_id');
        $userRecipe = UserRecipe::where('id', $data['id'])
                ->where('user_id', Auth::user()->id)
                ->first();
        if(!$userRecipe){
            return $this->successResponse("No Recipe found", [], 200);
        }
        $userRecipedata = $this->prepareRecipeResponseData($userRecipe, $data['quantity'] ?? null, $data['meal_type_id'] ?? null);

        return $this->successResponse("Recipe found", $userRecipedata, 200);
    }

    public function userRecipeList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => ['nullable', 'string'],
            'page' => ['nullable', 'integer'],
            'perPage' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->validationError("Fail", $validator->errors()->first(), 200);
        }

        // Set default page and perPage values
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('perPage', 20);

        // Initialize the query for user recipes
        $allRecipe = UserRecipe::where('user_id', Auth::user()->id)->orderBy('id', 'desc');

        // If search term exists, filter recipes by title
        if ($request->has('search')) {
            $allRecipe = $allRecipe->where('title', 'like', '%' . $request->search . '%');
        }

        // Paginate results instead of getting all
        $paginatedRecipes = $allRecipe->paginate($perPage, ['*'], 'page', $page);

        // Prepare the response data
        $recipeList = $paginatedRecipes->map(function ($recipe) {
            return [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'kcal' => number_format($recipe->kcal, 1),
            ];
        });

        // Return success response with pagination data
        return $this->successResponse("Recipe list Found", [
            'recipeList' => $recipeList,
            'pagination' => [
                'currentPage' => $paginatedRecipes->currentPage(),
                'totalPages' => $paginatedRecipes->lastPage(),
                'totalItems' => $paginatedRecipes->total(),
                'perPage' => $perPage,
            ]
        ], 200);
    }
}
