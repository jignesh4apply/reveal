<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CalorieCalculator extends Controller
{
    public function calculateCalorie(Request $request)
    {
        if ($request->isMethod('post')) {
            // Validate input
            $validated = $request->validate([
                'weight' => 'required|numeric',
                'height' => 'required|numeric',
                'age' => 'required|numeric',
                'gender' => 'required|in:male,female',
                'activity_level' => 'required|in:sedentary,lightly_active,moderately_active,active,very_active'
            ]);

            // Extract validated data
            $weight = $validated['weight'];
            $height = $validated['height'];
            $age = $validated['age'];
            $gender = $validated['gender'];
            $activityLevel = $validated['activity_level'];

            // Calculate BMR
            if ($gender == 'female') {
                $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
            } else {
                $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
            }

            // Define activity factors
            $activityFactors = [
                'sedentary' => 1.2,
                'lightly_active' => 1.375,
                'moderately_active' => 1.55,
                'active' => 1.725,
                'very_active' => 1.9
            ];

            // Calculate TDEE
            $activityFactor = $activityFactors[$activityLevel];
            $tdee = round(($bmr * $activityFactor),2);

            $data = [
                'bmr' => $bmr,
                'tdee' => $tdee
            ];

            // echo "<pre>"; print_r($data); die;

            return response()->json([
                'bmr' => $bmr,
                'tdee' => $tdee
            ]);
        }
        return view('front.calculateCalorie');
    }
}
