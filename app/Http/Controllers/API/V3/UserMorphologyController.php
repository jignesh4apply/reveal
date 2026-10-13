<?php

namespace App\Http\Controllers\API\V3;

use App\Http\Controllers\API\ApiController;
use App\Models\BehaviourTestPointsResult;
use App\Models\BehaviourTestPointsResultRange;
use App\Models\BehaviourTestQuizGroup;
use App\Models\User;
use App\Models\UserMorphology;
use App\Models\UserMorphologyBehaviourTest;
use App\Models\UsersAppearanceInfo;
use App\Models\UsersCurrentMeasurement;
use App\Models\UsersInitialMeasurement;
use App\Models\UsersTargetMeasurement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\CustomerIoService;
use Illuminate\Support\Facades\Log;
use App\Models\StepsGoal;
use App\Models\FoodPlanner;
use App\Models\UserSessionStatus;
use App\Models\UserProgram;

class UserMorphologyController extends ApiController
{
    /**
     * Create morphology session
     */
    public function createMorphologySession()
    {
        $userId = Auth::user()->id;
        $type = 'Baseline Report';

        // Check if record already exists for same user & type
        $existingMorphology = UserMorphology::where('user_id', $userId)
            ->where('type', $type)
            ->latest('id')
            ->first();

        // Return existing record with default empty strings/0 for nulls
        if ($existingMorphology) {
            return $this->successResponse(
                "Morphology session already exists.",
                $this->formatMorphologyData($existingMorphology),
                200
            );
        }

        // Generate unique session id
        do {
            $sessionId = 'MRPH-' . strtoupper(Str::random(16));
        } while (UserMorphology::where('session_id', $sessionId)->exists());

        // Create new session
        $morphology = UserMorphology::create([
            'user_id'   => $userId,
            'session_id'=> $sessionId,
            'type'      => $type,
        ]);

        return $this->successResponse(
            "Morphology session created successfully.",
            $this->formatMorphologyData($morphology),
            200
        );
    }

    public function getBasicUserData()
    {
        $user = Auth::user();

        // Latest morphology record
        $morphology = UserMorphology::where('user_id', $user->id)
            ->latest('id')
            ->first();

        // Existing fallback data
        $initialMeasurement = UsersInitialMeasurement::select('gender', 'age', 'weight')
            ->where('user_id', $user->id)
            ->first();

        $currentMeasurement = UsersCurrentMeasurement::select('weight')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $targetMeasurement = UsersTargetMeasurement::select('gender', 'age', 'weight')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        // Default flow values
        $defaultGender = $targetMeasurement?->gender ?? $initialMeasurement?->gender ?? "";
        $defaultWeight = $currentMeasurement?->weight ?? $initialMeasurement?->weight ?? 0;
        $defaultAge = $targetMeasurement?->age ?? $initialMeasurement?->age ?? 0;
        $defaultTargetWeight = $targetMeasurement?->weight ?? 0;
        $defaultHeight = $user->height ?? 0;

        $userProfileData = [
            'user_id'       => $user->id,

            // Use morphology value only if not null/empty
            'gender'        => !empty($morphology?->gender) ? $morphology->gender : $defaultGender,
            'weight'        => number_format((float) (!empty($morphology?->weight) ? $morphology->weight : $defaultWeight), 2, '.', ''),
            'height'        => !empty($morphology?->height) ? $morphology->height : $defaultHeight,
            'age'           => !empty($morphology?->age) ? $morphology->age : $defaultAge,
            'target_weight' => number_format((float) (!empty($morphology?->target_weight) ? $morphology->target_weight : $defaultTargetWeight), 2, '.', ''),
            'report_email'  => !empty($morphology?->report_email) ? $morphology->report_email : $user->email,
        ];

        return $this->successResponse('Basic Profile Details Found!', $userProfileData, 200);
    }

    public function updateBasicUserData(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'session_id' => ['required','string','exists:user_morphologies,session_id',],
            'gender' => ['nullable','in:Male,Female','required_with:weight,height,age',],
            'weight' => ['nullable','numeric','required_with:gender,height,age',],
            'height' => ['nullable','numeric','required_with:gender,weight,age',],
            'age' => ['nullable','integer','required_with:gender,weight,height',],
            'target_weight' => ['nullable','numeric','required_with:report_email',],
            'report_email' => ['nullable','email','required_with:target_weight',],
        ]);

        $morphology = UserMorphology::where('session_id', $validated['session_id'])
            ->where('user_id', Auth::user()->id)
            ->first();

        if (!$morphology) {
            return $this->errorResponse(
                'Morphology session not found.',
                [],
                404
            );
        }

        $morphologyData = [];

        if (isset($validated['gender'])) {
            $morphologyData['gender'] = $validated['gender'];
        }
        if (isset($validated['weight'])) {
            $morphologyData['weight'] = $validated['weight'];
        }
        if (isset($validated['height'])) {
            $morphologyData['height'] = $validated['height'];
        }
        if (isset($validated['age'])) {
            $morphologyData['age'] = $validated['age'];
        }
        if (isset($validated['target_weight'])) {
            $morphologyData['target_weight'] = $validated['target_weight'];
        }
        if (isset($validated['report_email'])) {
            $morphologyData['report_email'] = $validated['report_email'];
        }
        $morphology->update($morphologyData);

        if (isset($validated['gender'])) {
            $user->gender = $validated['gender'];

            // Update gender across all related tables for this user
            UserMorphology::where('user_id', $user->id)->update(['gender' => $validated['gender']]);
            UsersInitialMeasurement::where('user_id', $user->id)->update(['gender' => $validated['gender']]);
            UsersTargetMeasurement::where('user_id', $user->id)->update(['gender' => $validated['gender']]);
            UsersCurrentMeasurement::where('user_id', $user->id)->update(['gender' => $validated['gender']]);
        }

        if (isset($validated['height'])) {
            $user->height = $validated['height'];
        }
        $user->save();

        if (isset($validated['weight']) || isset($validated['gender']) || isset($validated['age'])) {

            $currentMeasurement = UsersCurrentMeasurement::where('user_id', $user->id)
                ->latest('id')
                ->first();

            if ($currentMeasurement) {
                $currentData = [];

                if (isset($validated['weight'])) {
                    $currentData['weight'] = $validated['weight'];
                }
                if (isset($validated['gender'])) {
                    $currentData['gender'] = $validated['gender'];
                }
                if (isset($validated['age'])) {
                    $currentData['age'] = $validated['age'];
                }

                $currentData['last_modified_date'] = Carbon::today();

                $currentMeasurement->update($currentData);

            } else {
                UsersCurrentMeasurement::create([
                    'user_id' => $user->id,
                    'weight'  => $validated['weight'] ?? null,
                    'gender'  => $validated['gender'] ?? null,
                    'age'     => $validated['age'] ?? null,
                    'last_modified_date' => Carbon::today(),
                ]);
            }
        }

        if (isset($validated['target_weight'])) {
            $targetMeasurement = UsersTargetMeasurement::where('user_id', $user->id)
                ->latest('id')
                ->first();

            $targetGender = $validated['gender'] ?? $user->gender;

            if ($targetMeasurement) {
                $targetMeasurement->update([
                    'weight' => $validated['target_weight'],
                    'gender' => $targetGender,
                    'last_modified_date' => Carbon::today(),
                ]);
            } else {
                UsersTargetMeasurement::create([
                    'user_id' => $user->id,
                    'weight'  => $validated['target_weight'],
                    'gender'  => $targetGender,
                    'last_modified_date' => Carbon::today(),
                ]);
            }
        }

        return $this->successResponse('Basic profile updated successfully.', $this->formatMorphologyData($morphology->fresh()), 200);
    }

    public function addMorphologySessionMeasurements(Request $request) {
        $validated = $request->validate([
            'session_id' => ['required','string','exists:user_morphologies,session_id',],
            'waist'  => ['nullable', 'numeric'],
            'hips'   => ['nullable', 'numeric'],
            'neck'   => ['nullable', 'numeric'],
            'bicep'  => ['nullable', 'numeric'],
            'chest'  => ['nullable', 'numeric'],
            'thighs' => ['nullable', 'numeric'],
            'calfs'  => ['nullable', 'numeric'],
        ]);

        $user = Auth::user();

        $morphology = UserMorphology::where('session_id', $validated['session_id'])->where('user_id', $user->id)->first();

        if (!$morphology) {
            return $this->errorResponse('Morphology session not found.', [], 404);
        }

        $measurementFields = ['waist', 'hips', 'neck', 'bicep', 'chest', 'thighs', 'calfs'];

        $morphologyData = [];

        foreach ($measurementFields as $field) {
            if (isset($validated[$field])) {
                $morphologyData[$field] = $validated[$field];
            }
        }

        if (!empty($morphologyData)) {
            $morphology->update($morphologyData);
        }

        // Update Current Measurement Table

        $currentMeasurement = UsersCurrentMeasurement::where('user_id', $user->id)->latest('id')->first();

        $currentMeasurementData = [];

        foreach ($measurementFields as $field) {
            if (isset($validated[$field])) {
                $currentMeasurementData[$field] = $validated[$field];
            }
        }

        // Always update last modified date
        $currentMeasurementData['last_modified_date'] = now();

        if ($currentMeasurement) {

            $currentMeasurement->update($currentMeasurementData);

        } else {

            UsersCurrentMeasurement::create(array_merge(
                [
                    'user_id' => $user->id,
                ],
                $currentMeasurementData
            ));
        }

        return $this->successResponse('Morphology measurements updated successfully.', $this->formatMorphologyData($morphology->fresh()), 200);
    }

    public function addMorphologySessionImages(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphologies,session_id'],
            'image_fv' => ['nullable', 'image', 'mimes:jpeg,jpg,png'],
            'image_bv' => ['nullable', 'image', 'mimes:jpeg,jpg,png'],
            'image_ls' => ['nullable', 'image', 'mimes:jpeg,jpg,png'],
            'image_rs' => ['nullable', 'image', 'mimes:jpeg,jpg,png'],
        ]);

        $user = Auth::user();

        $morphology = UserMorphology::where('session_id', $validated['session_id'])->where('user_id', $user->id)->first();

        if (!$morphology) {
            return $this->errorResponse('Morphology session not found.', [], 404);
        }

        $imageFields = ['image_fv', 'image_bv', 'image_ls', 'image_rs'];

        $morphologyData = [];

        foreach ($imageFields as $field) {

            if ($request->hasFile($field)) {

                // Delete old image if already exists
                $oldImagePath = $morphology->getRawOriginal($field);

                if (!empty($oldImagePath)) {

                    // Delete old image from storage
                    if (Storage::disk('public')->exists($oldImagePath)) {
                        Storage::disk('public')->delete($oldImagePath);
                    }

                    // Delete old image record from appearance table
                    UsersAppearanceInfo::where('user_id', $user->id)
                        ->where('image', $oldImagePath)
                        ->delete();
                }

                // Generate unique image name
                $imageName = time() . '_' . $field . '.' . $request->$field->extension();

                // Create directory if not exists
                if (!Storage::disk('public')->exists('user/morphology/images')) {
                    Storage::disk('public')->makeDirectory('user/morphology/images');
                }

                $request->$field->storeAs('user/morphology/images', $imageName, 'public');

                $imagePath = "user/morphology/images/{$imageName}";

                $morphologyData[$field] = $imagePath;

                //Save same image path in user appearance table
                UsersAppearanceInfo::create([
                    'user_id'   => $user->id,
                    'imageDate' => now(),
                    'image'     => $imagePath
                ]);
            }
        }

        if (!empty($morphologyData)) {
            $morphology->update($morphologyData);
        }

        return $this->successResponse('Morphology images uploaded successfully.', $this->formatMorphologyData($morphology->fresh()), 200);
    }

    public function deleteMorphologySessionImage(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphologies,session_id'],
            'image_type' => ['required', 'in:image_fv,image_bv,image_ls,image_rs'],
        ]);

        $user = Auth::user();

        $morphology = UserMorphology::where('session_id', $validated['session_id'])->where('user_id', $user->id)->first();

        if (!$morphology) {
            return $this->errorResponse('Morphology session not found.', [], 404);
        }

        $imageColumn = $validated['image_type'];

        $imagePath = $morphology->getRawOriginal($imageColumn);

        if (empty($imagePath)) {
            return $this->errorResponse('Image not found.', [], 404);
        }

        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }


        $morphology->update([
            $imageColumn => null,
        ]);

        //Delete From UsersAppearanceInfo Table
        UsersAppearanceInfo::where('user_id', $user->id)->where('image', $imagePath)->delete();

        return $this->successResponse('Morphology image deleted successfully.', [], 200);
    }

    public function getMorphologySessionData(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphologies,session_id'],
        ]);

        $user = Auth::user();
        $type = 'Baseline Report';

        $morphology = UserMorphology::where('session_id', $validated['session_id'])
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->first();

        if (!$morphology) {
            return $this->errorResponse('Morphology session not found.', [], 404);
        }

        // Basic Information

        $basicFields = [
            'gender',
            'age',
            'height',
            'weight',
        ];

        $basicInfoCompleted = collect($basicFields)->every(function ($field) use ($morphology) {
            return !empty($morphology->$field);
        }) ? 1 : 0;

        // Measurement Completion

        // Primary required measurements
        $primaryMeasurementFields = [
            'waist',
            'hips',
            'neck',
        ];

        // All measurements
        $allMeasurementFields = [
            'waist',
            'hips',
            'neck',
            'bicep',
            'chest',
            'thighs',
            'calfs',
        ];

        $primaryMeasurementCompleted = collect($primaryMeasurementFields)->every(function ($field) use ($morphology) {
            return !empty($morphology->$field);
        });

        $allMeasurementCompleted = collect($allMeasurementFields)->every(function ($field) use ($morphology) {
            return !empty($morphology->$field);
        });

        /*
        Status:
        0 = Nothing completed
        2 = Partially completed
        1 = Fully completed
        */

        $measurementStatus = 0;

        if ($allMeasurementCompleted) {
            $measurementStatus = 1;
        } elseif ($primaryMeasurementCompleted) {
            $measurementStatus = 2;
        }

        // Pictures Completion

        $imageFields = [
            'image_fv',
            'image_bv',
            'image_ls',
            'image_rs',
        ];

        $uploadedImagesCount = collect($imageFields)->filter(function ($field) use ($morphology) {
            return !empty($morphology->getRawOriginal($field));
        })->count();

        $pictureStatus = 0;

        if ($uploadedImagesCount === 4) {
            $pictureStatus = 1;
        } elseif ($uploadedImagesCount >= 1) {
            $pictureStatus = 2;
        }

        // Goal Summary

        $goalSummaryCompleted = (
            !empty($morphology->target_weight) &&
            !empty($morphology->report_email)
        ) ? 1 : 0;

        // Completeness Score

        $completenessScore = 0;

        if ($basicInfoCompleted == 1 && $goalSummaryCompleted == 1) {

            // No measurements + no photos
            if ($measurementStatus == 0 && $pictureStatus == 0) {

                $completenessScore = 45;

            // No measurements + some/all photos
            } elseif ($measurementStatus == 0 && ($pictureStatus == 1 || $pictureStatus == 2)) {

                $completenessScore = 60;

            // Measurements completed + no photos
            } elseif (($measurementStatus == 1 || $measurementStatus == 2) && $pictureStatus == 0) {

                $completenessScore = 80;

            // Measurements completed + 1-3 photos
            } elseif (($measurementStatus == 1 || $measurementStatus == 2) && $pictureStatus == 2) {

                $completenessScore = 90;

            // Measurements completed + all photos
            } elseif (($measurementStatus == 1 || $measurementStatus == 2) && $pictureStatus == 1) {

                $completenessScore = 100;
            }
        }

        // Onboarding Summary

        $measurementSummary = '--';

        if (!empty($morphology->waist) && !empty($morphology->hips) && !empty($morphology->neck)) {

            $measurementSummary = collect([
                !empty($morphology->waist) ? $morphology->waist . ' cm' : null,
                !empty($morphology->hips) ? $morphology->hips . ' cm' : null,
                !empty($morphology->neck) ? $morphology->neck . ' cm' : null,
            ])->filter()->implode(' | ');
        }

        $photosSummary = '--';

        if ($uploadedImagesCount > 0) {
            $photosSummary = $uploadedImagesCount . '/4';
        }


        // Response

        $response = [
            'session_id' => $morphology->session_id,
            'type' => $morphology->type,

            'basic_information' => [
                'is_completed' => $basicInfoCompleted ? 1 : 0,
                'gender' => $morphology->gender ?? "",
                'age' => $morphology->age ?? 0,
                'height' => $morphology->height ?? 0,
                'weight' => $morphology->weight ?? 0,
            ],

            'measurements' => [
                'is_completed' => $measurementStatus,
                'waist' => number_format((float) ($morphology->waist ?? 0), 2, '.', ''),
                'hips' => number_format((float) ($morphology->hips ?? 0), 2, '.', ''),
                'neck' => number_format((float) ($morphology->neck ?? 0), 2, '.', ''),
                'bicep' => number_format((float) ($morphology->bicep ?? 0), 2, '.', ''),
                'chest' => number_format((float) ($morphology->chest ?? 0), 2, '.', ''),
                'thighs' => number_format((float) ($morphology->thighs ?? 0), 2, '.', ''),
                'calfs' => number_format((float) ($morphology->calfs ?? 0), 2, '.', ''),
            ],

            'pictures' => [
                'is_completed' => $pictureStatus,
                'image_fv' => $morphology->image_fv ?? "",
                'image_bv' => $morphology->image_bv ?? "",
                'image_ls' => $morphology->image_ls ?? "",
                'image_rs' => $morphology->image_rs ?? "",
            ],

            'goal_summary' => [
                'is_completed' => $goalSummaryCompleted,
                'target_weight' => $morphology->target_weight ?? 0,
                'report_email' => $morphology->report_email ?? "",
            ],

            'completeness_score' => $completenessScore,

            'onboarding_summary' => [
                [
                    'key' => 'Gender',
                    'value' => $morphology->gender ?? '--',
                ],
                [
                    'key' => 'Weight',
                    'value' => !empty($morphology->weight)
                        ? $morphology->weight . ' kg'
                        : '--',
                ],
                [
                    'key' => 'Height',
                    'value' => !empty($morphology->height)
                        ? $morphology->height . ' cm'
                        : '--',
                ],
                [
                    'key' => 'Age',
                    'value' => $morphology->age ?? '--',
                ],
                [
                    'key' => 'Waist | Hips | Neck',
                    'value' => $measurementSummary,
                ],
                [
                    'key' => 'Photos',
                    'value' => $photosSummary,
                ],
                [
                    'key' => 'Target Weight',
                    'value' => !empty($morphology->target_weight)
                        ? $morphology->target_weight . ' kg'
                        : '--',
                ],
            ],
        ];

        return $this->successResponse('Morphology session data fetched successfully.', $response, 200);
    }

    public function generateBaselineMorphologyReport(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphologies,session_id'],
        ]);

        $user = Auth::user();

        $morphology = UserMorphology::where('session_id', $validated['session_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$morphology) {
            return $this->errorResponse('Morphology session not found.', [], 404);
        }

        // Age Restriction
        if (!empty($morphology->age) && $morphology->age < 18) {
            return $this->errorResponse('Morphology report is only available for users above 18 years.', [], 422);
        }

        // Required Basic Info
        $basicFields = [
            'gender',
            'age',
            'height',
            'weight',
        ];

        foreach ($basicFields as $field) {
            if (empty($morphology->$field)) {
                return $this->errorResponse(ucfirst(str_replace('_', ' ', $field)) . ' is required.', [], 422);
            }
        }

        // Target Weight Validation
        if (empty($morphology->target_weight)) {
            return $this->errorResponse('Target weight is required.', [], 422);
        }

        // Goal BMI Validation
        $heightCm = (float) $morphology->height;
        $heightMeter = $heightCm / 100;
        $weightKg = (float) $morphology->weight;
        $targetWeight = (float) $morphology->target_weight;

        $goalBMI = $targetWeight / ($heightMeter * $heightMeter);

        if ($goalBMI < 18.5) {
            return $this->errorResponse('This goal is below the healthy weight range for your height. Try a higher target.', [], 422);
        }

        try {
            $morphology->update([
                'generation_status' => 'processing',
            ]);

            $gender = strtolower($morphology->gender);
            $age = (int) $morphology->age;

            $waist = !empty($morphology->waist) ? (float) $morphology->waist : null;
            $hips = !empty($morphology->hips) ? (float) $morphology->hips : null;
            $neck = !empty($morphology->neck) ? (float) $morphology->neck : null;

            $bmi = null;
            if ($weightKg > 0 && $heightMeter > 0) {
                $bmi = $weightKg / ($heightMeter * $heightMeter);
            }

            $bmiCategory = null;
            if (!is_null($bmi)) {
                if ($bmi < 18.5) {
                    $bmiCategory = 'Underweight';
                } elseif ($bmi <= 24.9) {
                    $bmiCategory = 'Normal';
                } else {
                    $bmiCategory = 'Overweight';
                }
            }

            $bodyFat = null;
            $bodyFatMethod = null;
            $heightIn = $heightCm / 2.54;
            $waistIn = !is_null($waist) ? $waist / 2.54 : null;
            $neckIn = !is_null($neck) ? $neck / 2.54 : null;
            $hipsIn = !is_null($hips) ? $hips / 2.54 : null;

            if (!empty($waist) && !empty($neck)) {
                if ($gender == 'male') {
                    $bodyFat = (86.010 * log10($waistIn - $neckIn)) - (70.041 * log10($heightIn)) + 36.76;
                    $bodyFatMethod = 'navy';
                } elseif ($gender == 'female' && !empty($hips)) {
                    $bodyFat = (163.205 * log10($waistIn + $hipsIn - $neckIn)) - (97.684 * log10($heightIn)) - 78.387;
                    $bodyFatMethod = 'navy';
                }
            } elseif (!empty($waist)) {
                $ratio = $heightCm / $waist;
                if ($gender == 'male') {
                    $bodyFat = 64 - (20 * $ratio);
                } else {
                    $bodyFat = 76 - (20 * $ratio);
                }
                $bodyFatMethod = 'rfm';
            }

            if (!is_null($bodyFat)) {
                $bodyFat = max(3, min(50, $bodyFat));
            }

            $fatMassKg = null;
            $leanMassKg = null;
            if (!is_null($bodyFat)) {
                $fatMassKg = $weightKg * ($bodyFat / 100);
                $leanMassKg = $weightKg - $fatMassKg;
            }

            $lmi = null;
            if (!is_null($leanMassKg)) {
                $lmi = $leanMassKg / ($heightMeter * $heightMeter);
            }

            $whr = null;
            if (!empty($waist) && !empty($hips)) {
                $whr = $waist / $hips;
            }

            $whtr = null;
            if (!empty($waist) && !empty($heightCm)) {
                $whtr = $waist / $heightCm;
            }

            $bodyType = null;
            if (!is_null($whr)) {
                $threshold = $gender == 'male' ? 0.90 : 0.85;
                $bodyType = $whr >= $threshold ? 'android' : 'gynoid';
            }

            $healthyLower = null;
            $healthyUpper = null;
            if ($gender == 'male') {
                if ($age < 30) {
                    $healthyLower = 8;
                    $healthyUpper = 19;
                } elseif ($age <= 50) {
                    $healthyLower = 11;
                    $healthyUpper = 21;
                } else {
                    $healthyLower = 13;
                    $healthyUpper = 24;
                }
            } else {
                if ($age < 30) {
                    $healthyLower = 16;
                    $healthyUpper = 24;
                } elseif ($age <= 50) {
                    $healthyLower = 17;
                    $healthyUpper = 27;
                } else {
                    $healthyLower = 19;
                    $healthyUpper = 30;
                }
            }

            $bfSubScore = null;
            $bfHealthy = false;
            if (!is_null($bodyFat)) {
                if ($bodyFat >= $healthyLower && $bodyFat <= $healthyUpper) {
                    $bfSubScore = 100;
                    $bfHealthy = true;
                } elseif ($bodyFat < $healthyLower) {
                    $bfSubScore = max(40, 100 - (($healthyLower - $bodyFat) * 6));
                } else {
                    $bfSubScore = max(40, 100 - (($bodyFat - $healthyUpper) * 5));
                }
            }

            $whtrSubScore = null;
            if (!is_null($whtr)) {
                if ($whtr < 0.40) {
                    $whtrSubScore = 80;
                } elseif ($whtr <= 0.50) {
                    $whtrSubScore = 100;
                } elseif ($whtr <= 0.55) {
                    $whtrSubScore = 100 - (($whtr - 0.50) * 500);
                } elseif ($whtr <= 0.60) {
                    $whtrSubScore = 75 - (($whtr - 0.55) * 500);
                } else {
                    $whtrSubScore = max(40, 50 - (($whtr - 0.60) * 100));
                }
            }

            $bmiSubScore = null;
            if (!is_null($bmi)) {
                if ($bmi < 18.5) {
                    $bmiSubScore = 60;
                } elseif ($bmi < 20) {
                    $bmiSubScore = 70 + (($bmi - 18.5) * 20);
                } elseif ($bmi <= 24.9) {
                    $bmiSubScore = 100;
                } elseif ($bmi <= 29.9) {
                    $bmiSubScore = 100 - (($bmi - 24.9) * 8);
                } else {
                    $bmiSubScore = max(40, 60 - (($bmi - 29.9) * 4));
                }
                if ($bfHealthy) {
                    $bmiSubScore = min(100, $bmiSubScore + 15);
                }
            }

            $lmiSubScore = null;
            if (!is_null($lmi)) {
                if ($gender == 'male') {
                    if ($lmi < 17) {
                        $lmiSubScore = 60;
                    } elseif ($lmi < 19) {
                        $lmiSubScore = 75;
                    } elseif ($lmi <= 22) {
                        $lmiSubScore = 100;
                    } else {
                        $lmiSubScore = 90;
                    }
                } else {
                    if ($lmi < 14) {
                        $lmiSubScore = 60;
                    } elseif ($lmi < 16) {
                        $lmiSubScore = 75;
                    } elseif ($lmi <= 19) {
                        $lmiSubScore = 100;
                    } else {
                        $lmiSubScore = 90;
                    }
                }
            }

            $compositionScore = null;
            if (!is_null($bfSubScore) && !is_null($whtrSubScore) && !is_null($bmiSubScore) && !is_null($lmiSubScore)) {
                $compositionScore = (0.40 * $bfSubScore) + (0.30 * $whtrSubScore) + (0.20 * $bmiSubScore) + (0.10 * $lmiSubScore);
            } elseif (!is_null($bfSubScore) && !is_null($whtrSubScore) && !is_null($bmiSubScore)) {
                $compositionScore = ((0.40 / 0.90) * $bfSubScore) + ((0.30 / 0.90) * $whtrSubScore) + ((0.20 / 0.90) * $bmiSubScore);
            } elseif (!is_null($bmiSubScore)) {
                $compositionScore = $bmiSubScore;
            }
            if (!is_null($compositionScore)) {
                $compositionScore = round(max(40, min(100, $compositionScore)));
            }

            $scoreLabel = null;
            if (!is_null($compositionScore)) {
                if ($compositionScore >= 85) {
                    $scoreLabel = 'Optimal range';
                } elseif ($compositionScore >= 70) {
                    $scoreLabel = 'Healthy range';
                } elseif ($compositionScore >= 55) {
                    $scoreLabel = 'Room to improve';
                } else {
                    $scoreLabel = 'Supervision needed';
                }
            }

            $completenessScore = 0;
            $aboutYouCompleted = !empty($morphology->gender) && !empty($morphology->weight) && !empty($morphology->height) && !empty($morphology->age);
            if ($aboutYouCompleted) {
                $completenessScore += 25;
            }
            $coreMeasurementCompleted = !empty($morphology->waist) && !empty($morphology->hips) && !empty($morphology->neck);
            if ($coreMeasurementCompleted) {
                $completenessScore += 35;
            }
            $optionalMeasurements = [$morphology->bicep, $morphology->chest, $morphology->thighs, $morphology->calfs];
            $optionalCount = collect($optionalMeasurements)->filter(fn ($value) => !empty($value))->count();
            $completenessScore += ($optionalCount * 2.5);
            $photos = [$morphology->image_fv, $morphology->image_bv, $morphology->image_ls, $morphology->image_rs];
            $photoCount = collect($photos)->filter(fn ($value) => !empty($value))->count();
            $completenessScore += ($photoCount * 5);
            if (!empty($morphology->target_weight)) {
                $completenessScore += 10;
            }

            $projection = null;
            if (!is_null($targetWeight)) {
                $delta = abs($weightKg - $targetWeight);

                // Fixed weekly loss rates
                $minWeeks = $delta / 0.5;   // 0.5 kg per week (fastest)
                $maxWeeks = $delta / 0.25;  // 0.25 kg per week (slowest)

                $projection = [
                    'min_months' => round($minWeeks / 4.345),
                    'max_months' => round($maxWeeks / 4.345),
                ];
            }

            $chartData = [];
            if (!is_null($targetWeight) && isset($delta) && $delta > 0) {
                // Build chart that follows the MIN trajectory exactly (0.5 kg/week)
                $minWeeksFloat = $minWeeks;
                $fullWeeks = (int) floor($minWeeksFloat);

                for ($week = 0; $week <= $fullWeeks; $week++) {
                    $projectedWeight = $weightKg - (0.5 * $week);
                    if ($projectedWeight < $targetWeight) {
                        $projectedWeight = $targetWeight;
                    }
                    $chartData[] = [
                        'week' => $week,
                        'projected_weight' => round($projectedWeight, 1),
                    ];
                }

                // If minWeeks is fractional, append final point at exact goal week
                if ($fullWeeks < $minWeeksFloat) {
                    $chartData[] = [
                        'week' => $minWeeksFloat,
                        'projected_weight' => round($targetWeight, 1),
                    ];
                } else {
                    // Ensure last point aligns exactly with the goal
                    $lastIndex = count($chartData) - 1;
                    if ($lastIndex >= 0) {
                        $chartData[$lastIndex]['projected_weight'] = round($targetWeight, 1);
                        $chartData[$lastIndex]['week'] = $minWeeksFloat;
                    }
                }
            }

            $reportJson = $this->sanitizeReportJson([
                'summary' => [
                    'completeness_score' => round($completenessScore),
                    'report_generated_at' => now()->toDateTimeString(),
                ],
                'bmi_score' => [
                    'bmi' => $bmi ? round($bmi, 1) : null,
                    'bmi_category' => $bmiCategory,
                    'composition_score' => $compositionScore,
                    'score_label' => $scoreLabel,
                ],
                'measurements' => [
                    'whr' => $whr ? round($whr, 3) : null,
                    'whtr' => $whtr ? round($whtr, 3) : null,
                    'body_type' => $bodyType,
                ],
                'composition' => [
                    'body_fat_pct' => $bodyFat ? round($bodyFat, 1) : null,
                    'fat_mass_kg' => $fatMassKg ? round($fatMassKg, 1) : null,
                    'lean_mass_kg' => $leanMassKg ? round($leanMassKg, 1) : null,
                    'lmi' => $lmi ? round($lmi, 1) : null,
                ],
                'projection' => [
                    'projection' => $projection,
                    'chart_data' => $chartData,
                ],
            ]);

            $generatedAt = now();
            $morphology->update([
                'generation_status' => 'completed',
                'generation_error' => '',
                'report_generated_at' => $generatedAt,
                'bmi' => !is_null($bmi) ? round($bmi, 1) : null,
                'whr' => !is_null($whr) ? round($whr, 3) : null,
                'body_fat' => !is_null($bodyFat) ? round($bodyFat, 1) : null,
                'whtr' => !is_null($whtr) ? round($whtr, 3) : null,
                'lmi' => !is_null($lmi) ? round($lmi, 1) : null,
                'composition_score' => $compositionScore,
                'report_json' => $reportJson,
            ]);

            $this->linkUserReportLevel($morphology->id, 'morphology');

            return $this->successResponse(
                'Baseline morphology report is generated.',
                [
                    'generation_status' => 'completed',
                    'generation_error' => '',
                    'report_generated_at' => $generatedAt,
                    'report_json' => $reportJson,
                ],
                200
            );
        } catch (\Throwable $exception) {
            $morphology->update([
                'generation_status' => 'failed',
                'generation_error' => $exception->getMessage(),
            ]);

            return $this->errorResponse('Baseline morphology report generation failed.', ['error' => $exception->getMessage()], 422);
        }
    }

    public function getBaselineMorphologyReportStatus(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphologies,session_id'],
        ]);

        $user = Auth::user();

        $morphology = UserMorphology::where('session_id', $validated['session_id'])->where('user_id', $user->id)->first();

        if (!$morphology) {
            return $this->errorResponse('Morphology session not found.', [], 404);
        }

        $response = [
            'session_id' => $morphology->session_id,
            'generation_status' => $morphology->generation_status,
            'generation_error' => $morphology->generation_error,
            'report_available' => $morphology->generation_status == 'completed',
        ];

        return $this->successResponse('Morphology report status fetched successfully.', $response, 200);
    }

    public function getBaselineMorphologyReport(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphologies,session_id'],
        ]);

        $user = Auth::user();

        $morphology = UserMorphology::where('session_id', $validated['session_id'])->where('user_id', $user->id)->first();

        if (!$morphology) {
            return $this->errorResponse('Morphology session not found.', [], 404);
        }

        if ($morphology->generation_status == 'pending') {

            return $this->successResponse(
                'Baseline morphology report is pending.',
                [
                    'generation_status' => 'pending',
                    'generation_error' => $morphology->generation_error ?? '',
                    'report_generated_at' => $morphology->report_generated_at,
                    'report_json' => (object) [],
                ],
                200
            );
        }

        if ($morphology->generation_status == 'processing') {

            return $this->successResponse(
                'Baseline morphology report is currently processing.',
                [
                    'generation_status' => 'processing',
                    'generation_error' => $morphology->generation_error ?? '',
                    'report_generated_at' => $morphology->report_generated_at,
                    'report_json' => (object) [],
                ],
                200
            );
        }

        if ($morphology->generation_status == 'failed') {

            return $this->successResponse(
                'Baseline morphology report generation failed.',
                [
                    'generation_status' => 'failed',
                    'generation_error' => $morphology->generation_error ?? '',
                    'report_generated_at' => $morphology->report_generated_at,
                    'report_json' => (object) [],
                ],
                422
            );
        }

        $reportJson = $this->sanitizeReportJson($morphology->report_json);

        return $this->successResponse(
            'Baseline morphology report is generated.',
            [
                'generation_status' => 'completed',
                'generation_error' => '',
                'report_generated_at' => $morphology->report_generated_at,
                'report_json' => $reportJson,
            ],
            200
        );
    }

    public function createBehaviouralTestSession()
    {
        $userId = Auth::user()->id;

        // Check if record already exists for same user & type
        $existingBehaviourSession = UserMorphologyBehaviourTest::where('user_id', $userId)
            ->latest('id')
            ->first();

        // Return existing record with only filled values
        if ($existingBehaviourSession) {

            $filteredData = collect($existingBehaviourSession->toArray())
                ->filter(function ($value) {
                    return !is_null($value) && $value !== '';
                })
                ->toArray();

            return $this->successResponse(
                "Behavioural Test session already exists.",
                $filteredData,
                200
            );
        }

        // Validate that Behaviour Test is unlocked before creating
        $baseline = UserMorphology::where('user_id', $userId)
            ->where('type', 'Baseline Report')
            ->where('generation_status', 'completed')
            ->first();

        if (!$baseline) {
            return $this->errorResponse("Behavioural Test is locked. Please complete your Baseline Report first.", [], 400);
        }

        $completedDate = Carbon::parse($baseline->report_generated_at ?? $baseline->updated_at)->startOfDay();
        $unlockDate = $completedDate->copy()->addDays(15);
        $now = Carbon::now()->startOfDay();
        if ($now->lt($unlockDate)) {
            $diffInDays = $now->diffInDays($unlockDate, false);
            $days = intval(ceil($diffInDays));
            return $this->errorResponse("Behavioural Test is locked. It will unlock in {$days} days.", [], 400);
        }

        // Generate unique session id
        do {
            $sessionId = 'MRPH-BT-' . strtoupper(Str::random(16));
        } while (UserMorphologyBehaviourTest::where('session_id', $sessionId)->exists());

        // Create new session
        $behaviourTest = UserMorphologyBehaviourTest::create([
            'user_id'   => $userId,
            'session_id'=> $sessionId
        ]);

        return $this->successResponse(
            "Behavioural Test session created successfully.",
            $behaviourTest,
            200
        );
    }
    public function fetchBehaviouralTestQuestions()
    {
        $quizGroups = BehaviourTestQuizGroup::orderBy('quiz_group_order', 'asc')
            ->with([
                'quizzes' => function ($query) {
                    $query->where('is_active', 1)->orderBy('quiz_position', 'asc');
                },
                'quizzes.answers' => function ($query) {
                    $query->orderBy('id', 'asc');
                },
                'quizzes.answers.actions' => function ($query) {
                    $query->where('quiz_actions.is_active', 1)->orderBy('quiz_actions.id', 'asc');
                },
            ])
            ->get();

        $data = $quizGroups->map(function ($group) {
            return [
                'id' => $group->id,
                'title' => $group->title,
                'slug' => $group->slug,
                'quiz_group_order' => $group->quiz_group_order,
                'quizzes' => $group->quizzes->map(function ($quiz) {
                    return [
                        'id' => $quiz->id,
                        'question' => $quiz->question,
                        'slug' => $quiz->slug,
                        'amplitude_word' => $quiz->amplitude_word ?? "",
                        'quiz_position' => $quiz->quiz_position,
                        'is_multiple_choice' => (int) $quiz->is_multiple_choice,
                        'answers' => $quiz->answers->map(function ($answer) {
                            return [
                                'id' => $answer->id,
                                'answer' => $answer->answer,
                                'points' => number_format((float) $answer->points, 2, '.', ''),
                                'actions' => $answer->actions->map(function ($action) {
                                    return [
                                        'id' => $action->id,
                                        'title' => $action->title,
                                        'slug' => $action->slug,
                                        'description' => $action->description ?? "",
                                        'tag' => $action->tag ?? "",
                                        'video_link' => $action->video_link ?? "",
                                        'extra_link' => $action->extra_link ?? "",
                                        'action_button_text' => $action->action_button_text ?? "",
                                        'trigger_email' => $action->trigger_email,
                                    ];
                                })->values(),
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        })->values();

        return $this->successResponse(
            'Behavioural test questions fetched successfully.',
            ['quiz_groups' => $data],
            200
        );
    }

    public function saveBehaviouralTestQuestionsAnswers(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphology_behaviour_tests,session_id'],
            'ques_ans_json' => ['required', 'array'],
            'ques_ans_json.*.group' => ['required', 'string'],
            'ques_ans_json.*.questions' => ['required', 'array'],
            'ques_ans_json.*.questions.*.question' => ['required', 'string'],
            'ques_ans_json.*.questions.*.answer' => ['required','string'],
            'ques_ans_json.*.questions.*.points' => ['required','numeric','min:0','max:1'],
        ]);

        $user = Auth::user();

        $behaviourTest = UserMorphologyBehaviourTest::where('session_id', $validated['session_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$behaviourTest) {
            return $this->errorResponse('Behavioural Test session not found.', [], 404);
        }

        $resultJson = [];

        foreach ($validated['ques_ans_json'] as $groupData) {
            $groupSlug = $groupData['group'];
            $questions = $groupData['questions'];

            $quizGroup = BehaviourTestQuizGroup::where('slug', $groupSlug)->first();

            $totalQuestions = 0;
            if ($quizGroup) {
                $totalQuestions = $quizGroup->quizzes()->where('is_active', 1)->count();
            }

            if ($totalQuestions === 0) {
                $totalQuestions = count($questions);
            }

            if ($totalQuestions === 0) continue;

            $pointsSum = 0;
            foreach ($questions as $q) {
                $pointsSum += floatval($q['points'] ?? 0);
            }

            $percentage = ($pointsSum / $totalQuestions) * 100;
            $resultText = '';

            if ($quizGroup) {
                $pointsResult = BehaviourTestPointsResult::where('behaviour_test_quiz_group_id', $quizGroup->id)
                    ->where('is_active', 1)
                    ->first();

                if ($pointsResult) {
                    $range = BehaviourTestPointsResultRange::where('behaviour_test_points_result_id', $pointsResult->id)
                        ->where('range_start', '<=', $percentage)
                        ->where('range_end', '>=', $percentage)
                        ->first();

                    if ($range) {
                        $resultText = $range->result_text;
                    }
                }
            }

            $resultJson[] = [
                'group' => $quizGroup->title,
                'group_slug' => $groupSlug,
                'result' => $resultText,
                'percentage' => number_format($percentage, 2, '.', ''),
            ];
        }

        $behaviourTest->update([
            'ques_ans_json' => $validated['ques_ans_json'],
            'report_json' => $resultJson,
            'report_generated_at' => now(),
            'status' =>'completed',
        ]);

        $this->linkUserReportLevel($behaviourTest->id, 'behaviour_test');

        return $this->successResponse('Behavioural Test questions answers saved successfully.', $behaviourTest, 200);
    }

    public function getBehaviouralTestMorphologyReport(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphology_behaviour_tests,session_id'],
        ]);

        $user = Auth::user();

        $behaviourTest = UserMorphologyBehaviourTest::where('session_id', $validated['session_id'])->where('user_id', $user->id)->first();

        if (!$behaviourTest) {
            return $this->errorResponse('behaviour Test session not found.', [], 404);
        }

        if ($behaviourTest->status == 'pending') {

            return $this->successResponse(
                'Behaviour Test morphology report is pending.',
                [
                    'report_json' => (object) [],
                    'report_generated_at' => $behaviourTest->report_generated_at,
                    'status' => 'pending',
                ],
                200
            );
        }

        if ($behaviourTest->status == 'failed') {

            return $this->successResponse(
                'Behaviour Test morphology report generation failed.',
                [
                    'report_json' => (object) [],
                    'report_generated_at' => $behaviourTest->report_generated_at,
                    'status' => 'failed',
                ],
                422
            );
        }

        $reportJson = $behaviourTest->report_json;

        return $this->successResponse(
            'Baseline morphology report is generated.',
            [
                'report_json' => $reportJson,
                'report_generated_at' => $behaviourTest->report_generated_at,
                'status' => 'completed',
            ],
            200
        );
    }

    public function triggerMailWithGuide(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphology_behaviour_tests,session_id'],
            'guide_link' => ['nullable', 'url'],
        ]);

        $user = Auth::user();

        if (!$user || !$user->email) {
            return $this->errorResponse('Authenticated user with email is required.', [], 400);
        }

        $behaviourTest = UserMorphologyBehaviourTest::where('session_id', $validated['session_id'])->where('user_id', $user->id)->first();

        if (!$behaviourTest) {
            return $this->errorResponse('behaviour Test session not found.', [], 404);
        }

        try {
            $customerIo = new CustomerIoService();

            $dynamicData = [
                'name' => $user->name ?? 'User'
            ];

            if (!empty($validated['guide_link'])) {
                $dynamicData['guide_link'] = $validated['guide_link'];
            }

            $transactionalId = 'trigger_guide_email';

            $customerIo->sendTransactionalEmail($user->email, $transactionalId, $dynamicData);

            return $this->successResponse('Guide sent successfully to your email.', [], 200);

        } catch (\Exception $e) {
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/morphology_guide_mail.log'),
            ])->error('Error triggering mail with guide', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Failed to send guide mail. Please try again later.', [], 500);
        }
    }

    private function formatMorphologyData($morphology)
    {
        if (!$morphology) return null;

        $data = $morphology->toArray();

        $numericFields = [
            'age', 'height'
        ];

        $twoDecimalFields = [
            'waist', 'hips', 'neck', 'bicep', 'chest', 'thighs', 'calfs', 'weight', 'target_weight'
        ];

        foreach ($data as $key => $value) {
            if (($key === 'report_json' || $key === 'raw_report_json') && !empty($value)) {
                $data[$key] = $this->sanitizeReportJson($value);
            } elseif ($key === 'report_json' && empty($value)) {
                $data[$key] = (object) [];
            } elseif ($key === 'raw_report_json' && empty($value)) {
                $data[$key] = (object) [];
            } elseif (in_array($key, $twoDecimalFields)) {
                $data[$key] = number_format((float) ($value ?? 0), 2, '.', '');
            } elseif (is_null($value)) {
                if (in_array($key, $numericFields)) {
                    $data[$key] = 0;
                } else {
                    $data[$key] = "";
                }
            }
        }

        return $data;
    }

    private function translateMorphologyValue($key, $value)
    {
        if (empty($value)) {
            return $value;
        }

        $translations = [
            'bmi_category' => [
                'Overweight' => 'Surpoids',
                'Normal' => 'Normal',
                'Underweight' => 'Insuffisance pondérale',
                'Obesity class I' => 'Obésité de classe I',
                'Obesity class II' => 'Obésité de classe II',
                'Obesity class III' => 'Obésité de classe III',
            ],
            'score_label' => [
                'Optimal range' => 'Zone optimale',
                'Healthy range' => 'Zone saine',
                'Room to improve' => 'Marge de progression',
                'Supervision needed' => 'Suivi nécessaire',
                'Worth discussing with a professional' => 'Suivi nécessaire',
            ]
        ];

        if (isset($translations[$key][$value])) {
            return $translations[$key][$value];
        }

        return $value;
    }

    /**
     * Format a decimal value as string. Null becomes "0.0" or similar depending on decimals.
     */
    private function fmtDec($value, $decimals = 1)
    {
        if (is_null($value)) {
            return number_format(0.0, $decimals, '.', '');
        }
        return number_format((float) $value, $decimals, '.', '');
    }

    /**
     * Recursively sanitize report JSON:
     * - Decimal fields → string ("22.5"), null → "0.0"
     * - String fields → null → ""
     * - Integer fields → null → 0
     */
    private function sanitizeReportJson($data)
    {
        if (!is_array($data)) return $data;

        $decimalFields1 = [
            'bmi', 'body_fat_pct', 'fat_mass_kg', 'lean_mass_kg', 'lmi',
            'fat_mass_pct', 'lean_mass_pct', 'projected_weight', 'weight',
            'starting_weight', 'current_weight', 'goal_weight',
            'waist', 'hips', 'neck', 'daily_average_steps', 'daily_target_steps',
        ];
        $decimalFields2 = ['week'];
        $decimalFields3 = ['whr', 'whtr', 'delta'];
        $stringFields = ['bmi_category', 'score_label', 'body_type', 'label', 'date', 'report_generated_at'];
        $integerFields = [
            'composition_score', 'completeness_score', 'min_months', 'max_months', 'change_status', 'day',
            'current_day', 'previous_day', 'baseline_measurement_data_present',
            'days_in_period', 'sessions_expected', 'sessions_completed', 'has_steps_record', 'days_logged'
        ];

        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->sanitizeReportJson($value);
            } elseif (is_object($value)) {
                $result[$key] = $value;
            } elseif (in_array($key, $decimalFields1)) {
                $result[$key] = $this->fmtDec($value, 1);
            } elseif (in_array($key, $decimalFields2)) {
                $result[$key] = $this->fmtDec($value, 2);
            } elseif (in_array($key, $decimalFields3)) {
                $result[$key] = $this->fmtDec($value, 3);
            } elseif (in_array($key, $stringFields)) {
                $val = $value ?? "";
                if ($key === 'bmi_category' || $key === 'score_label') {
                    $val = $this->translateMorphologyValue($key, $val);
                }
                $result[$key] = $val;
            } elseif (in_array($key, $integerFields)) {
                $result[$key] = $value ?? 0;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function createProgressMorphologySession()
    {
        $userId = Auth::user()->id;
        $type = 'Progress Report';

        // Check if record already exists for same user & type
        $existingMorphology = UserMorphology::where('user_id', $userId)
            ->where('type', $type)
            ->where('generation_status', '<>', 'completed')
            ->latest('id')
            ->first();

        // Return existing record with default empty strings/0 for nulls
        if ($existingMorphology) {
            return $this->successResponse(
                "Morphology session already exists.",
                $this->formatMorphologyData($existingMorphology),
                200
            );
        }

        // Validate that Progress Report is unlocked before creating
        $baseline = UserMorphology::where('user_id', $userId)
            ->where('type', 'Baseline Report')
            ->where('generation_status', 'completed')
            ->first();

        if (!$baseline) {
            return $this->errorResponse("Progress Report is locked. Please complete your Baseline Report first.", [], 400);
        }

        $behaviour = UserMorphologyBehaviourTest::where('user_id', $userId)
            ->where('status', 'completed')
            ->first();

        if (!$behaviour) {
            return $this->errorResponse("Progress Report is locked. Please complete your Behaviour Test first.", [], 400);
        }

        $latestCompletedProgress = UserMorphology::where('user_id', $userId)
            ->where('type', 'Progress Report')
            ->where('generation_status', 'completed')
            ->latest('id')
            ->first();

        if ($latestCompletedProgress) {
            $completedDate = Carbon::parse($latestCompletedProgress->report_generated_at ?? $latestCompletedProgress->updated_at)->startOfDay();
        } else {
            $completedDate = Carbon::parse($baseline->report_generated_at ?? $baseline->updated_at)->startOfDay();
        }

        $unlockDate = $completedDate->copy()->addDays(30);
        $now = Carbon::now()->startOfDay();
        if ($now->lt($unlockDate)) {
            $diffInDays = $now->diffInDays($unlockDate, false);
            $days = intval(ceil($diffInDays));
            return $this->errorResponse("Progress Report is locked. It will unlock in {$days} days.", [], 400);
        }

        // Generate unique session id
        do {
            $sessionId = 'MRPH-' . strtoupper(Str::random(16));
        } while (UserMorphology::where('session_id', $sessionId)->exists());

        // Create new session
        $morphology = UserMorphology::create([
            'user_id'   => $userId,
            'session_id'=> $sessionId,
            'type'      => $type,
        ]);

        return $this->successResponse(
            "Morphology session created successfully.",
            $this->formatMorphologyData($morphology),
            200
        );
    }

    public function updateProgressBasicUserData(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'session_id' => ['required','string','exists:user_morphologies,session_id',],
            'weight' => ['nullable','numeric','required',],
        ]);

        $morphology = UserMorphology::where('session_id', $validated['session_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$morphology) {
            return $this->errorResponse(
                'Morphology session not found.',
                [],
                404
            );
        }

        $morphologyData = [];

        if (isset($validated['weight'])) {
            $morphologyData['weight'] = $validated['weight'];

            $morphology->update($morphologyData);

            $currentMeasurement = UsersCurrentMeasurement::where('user_id', $user->id)
                ->latest('id')
                ->first();

            if ($currentMeasurement) {
                $currentData = [];

                $currentData['weight'] = $validated['weight'];
                $currentData['last_modified_date'] = Carbon::today();

                $currentMeasurement->update($currentData);

            } else {
                UsersCurrentMeasurement::create([
                    'user_id' => $user->id,
                    'weight'  => $validated['weight'] ?? null,
                    'last_modified_date' => Carbon::today(),
                ]);
            }
        }

        return $this->successResponse('Basic profile updated successfully.', $this->formatMorphologyData($morphology->fresh()), 200);
    }

    /**
     * Get progress morphology session data with comparison against previous record.
     */
    public function getProgressMorphologySessionData(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphologies,session_id'],
        ]);

        $user = Auth::user();

        $morphology = UserMorphology::where('session_id', $validated['session_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$morphology) {
            return $this->errorResponse('Morphology session not found.', [], 404);
        }

        // Fetch baseline report for starting weight & target weight
        $baselineReport = UserMorphology::where('user_id', $user->id)
            ->where('type', 'Baseline Report')
            ->latest('id')
            ->first();

        // Fetch previous report (baseline or earlier progress) for comparison
        $previousReport = UserMorphology::where('user_id', $user->id)
            ->where('id', '<', $morphology->id)
            ->latest('id')
            ->first();

        // Basic Information (only weight)

        $basicFields = ['weight'];

        $basicInfoCompleted = collect($basicFields)->every(function ($field) use ($morphology) {
            return !empty($morphology->$field);
        }) ? 1 : 0;

        // Measurement Completion

        // Primary required measurements
        $primaryMeasurementFields = [
            'waist',
            'hips',
            'neck',
        ];

        // All measurements
        $allMeasurementFields = [
            'waist',
            'hips',
            'neck',
            'bicep',
            'chest',
            'thighs',
            'calfs',
        ];

        $primaryMeasurementCompleted = collect($primaryMeasurementFields)->every(function ($field) use ($morphology) {
            return !empty($morphology->$field);
        });

        $allMeasurementCompleted = collect($allMeasurementFields)->every(function ($field) use ($morphology) {
            return !empty($morphology->$field);
        });

        /*
        Status:
        0 = Nothing completed
        2 = Partially completed
        1 = Fully completed
        */

        $measurementStatus = 0;

        if ($allMeasurementCompleted) {
            $measurementStatus = 1;
        } elseif ($primaryMeasurementCompleted) {
            $measurementStatus = 2;
        }

        // Pictures Completion

        $imageFields = [
            'image_fv',
            'image_bv',
            'image_ls',
            'image_rs',
        ];

        $uploadedImagesCount = collect($imageFields)->filter(function ($field) use ($morphology) {
            return !empty($morphology->getRawOriginal($field));
        })->count();

        $pictureStatus = 0;

        if ($uploadedImagesCount === 4) {
            $pictureStatus = 1;
        } elseif ($uploadedImagesCount >= 1) {
            $pictureStatus = 2;
        }

        // Completeness Score (no goalSummaryCompleted for progress)

        $completenessScore = 0;

        if ($basicInfoCompleted == 1) {

            // No measurements + no photos
            if ($measurementStatus == 0 && $pictureStatus == 0) {

                $completenessScore = 45;

            // No measurements + some/all photos
            } elseif ($measurementStatus == 0 && ($pictureStatus == 1 || $pictureStatus == 2)) {

                $completenessScore = 60;

            // Measurements completed + no photos
            } elseif (($measurementStatus == 1 || $measurementStatus == 2) && $pictureStatus == 0) {

                $completenessScore = 80;

            // Measurements completed + 1-3 photos
            } elseif (($measurementStatus == 1 || $measurementStatus == 2) && $pictureStatus == 2) {

                $completenessScore = 90;

            // Measurements completed + all photos
            } elseif (($measurementStatus == 1 || $measurementStatus == 2) && $pictureStatus == 1) {

                $completenessScore = 100;
            }
        }

        // --- Comparison helpers ---

        /**
         * Compare a numeric value against the previous report.
         * Returns: 1 = decreased (loss), 0 = same, 2 = increased (gain)
         */
        $getChangeStatus = function ($currentValue, $previousValue) {
            $current  = (float) ($currentValue ?? 0);
            $previous = (float) ($previousValue ?? 0);

            if ($previous == 0) {
                return 0;
            }
            if ($current < $previous) {
                return 1; // decreased / loss
            } elseif ($current > $previous) {
                return 2; // increased / gain
            }
            return 0; // same
        };

        // Weight change status
        $weightChangeStatus = $previousReport
            ? $getChangeStatus($morphology->weight, $previousReport->weight)
            : 0;

        // Measurement change statuses
        $waistChangeStatus = $previousReport
            ? $getChangeStatus($morphology->waist, $previousReport->waist)
            : 0;

        $hipsChangeStatus = $previousReport
            ? $getChangeStatus($morphology->hips, $previousReport->hips)
            : 0;

        $neckChangeStatus = $previousReport
            ? $getChangeStatus($morphology->neck, $previousReport->neck)
            : 0;

        // Photos change status (count-based)
        $previousImagesCount = 0;
        if ($previousReport) {
            $previousImagesCount = collect($imageFields)->filter(function ($field) use ($previousReport) {
                return !empty($previousReport->getRawOriginal($field));
            })->count();
        }

        $photosChangeStatus = $getChangeStatus($uploadedImagesCount, $previousImagesCount);

        // Measurements combined change status (any of waist/hips/neck changed)
        $measurementChangeStatus = 0;
        if ($waistChangeStatus !== 0 || $hipsChangeStatus !== 0 || $neckChangeStatus !== 0) {
            // Use the most significant change across all three
            $changes = [$waistChangeStatus, $hipsChangeStatus, $neckChangeStatus];
            // If any decreased, mark as 1; if any increased and none decreased, mark as 2
            if (in_array(1, $changes)) {
                $measurementChangeStatus = 1;
            } elseif (in_array(2, $changes)) {
                $measurementChangeStatus = 2;
            }
        }

        // Onboarding Summary

        $startingWeight = ($baselineReport && !empty($baselineReport->weight))
            ? $baselineReport->weight . ' kg'
            : '--';

        $currentWeight = !empty($morphology->weight)
            ? $morphology->weight . ' kg'
            : '--';

        $targetWeight = ($baselineReport && !empty($baselineReport->target_weight))
            ? $baselineReport->target_weight . ' kg'
            : '--';

        $measurementSummary = '--';

        if (!empty($morphology->waist) && !empty($morphology->hips) && !empty($morphology->neck)) {

            $measurementSummary = collect([
                !empty($morphology->waist) ? $morphology->waist . ' cm' : null,
                !empty($morphology->hips) ? $morphology->hips . ' cm' : null,
                !empty($morphology->neck) ? $morphology->neck . ' cm' : null,
            ])->filter()->implode(' | ');
        }

        $photosSummary = '--';

        if ($uploadedImagesCount > 0) {
            $photosSummary = $uploadedImagesCount . '/4';
        }

        // Response

        $response = [
            'session_id' => $morphology->session_id,
            'type' => $morphology->type,

            'basic_information' => [
                'is_completed' => $basicInfoCompleted ? 1 : 0,
                'weight' => $morphology->weight ?? 0,
            ],

            'measurements' => [
                'is_completed' => $measurementStatus,
                'waist' => number_format((float) ($morphology->waist ?? 0), 2, '.', ''),
                'hips' => number_format((float) ($morphology->hips ?? 0), 2, '.', ''),
                'neck' => number_format((float) ($morphology->neck ?? 0), 2, '.', ''),
                'bicep' => number_format((float) ($morphology->bicep ?? 0), 2, '.', ''),
                'chest' => number_format((float) ($morphology->chest ?? 0), 2, '.', ''),
                'thighs' => number_format((float) ($morphology->thighs ?? 0), 2, '.', ''),
                'calfs' => number_format((float) ($morphology->calfs ?? 0), 2, '.', ''),
            ],

            'pictures' => [
                'is_completed' => $pictureStatus,
                'image_fv' => $morphology->image_fv ?? "",
                'image_bv' => $morphology->image_bv ?? "",
                'image_ls' => $morphology->image_ls ?? "",
                'image_rs' => $morphology->image_rs ?? "",
            ],

            'completeness_score' => $completenessScore,

            'onboarding_summary' => [
                [
                    'key' => 'Starting Weight',
                    'value' => $startingWeight,
                ],
                [
                    'key' => 'Current Weight',
                    'value' => $currentWeight,
                    'change_status' => $weightChangeStatus,
                ],
                [
                    'key' => 'Target Weight',
                    'value' => $targetWeight,
                ],
                [
                    'key' => 'Waist | Hips | Neck',
                    'value' => $measurementSummary,
                    'change_status' => $measurementChangeStatus,
                ],
                [
                    'key' => 'Photos',
                    'value' => $photosSummary,
                    'change_status' => $photosChangeStatus,
                ],
            ],
        ];

        return $this->successResponse('Progress morphology session data fetched successfully.', $response, 200);
    }

    /**
     * Generate progress morphology report with comparison against previous record.
     */
    public function generateProgressMorphologyReport(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphologies,session_id'],
        ]);

        $user = Auth::user();

        $morphology = UserMorphology::where('session_id', $validated['session_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$morphology) {
            return $this->errorResponse('Morphology session not found.', [], 404);
        }

        // Fetch baseline report for gender, age, height, target_weight
        $baselineReport = UserMorphology::where('user_id', $user->id)
            ->where('type', 'Baseline Report')
            ->latest('id')
            ->first();

        if (!$baselineReport) {
            return $this->errorResponse('Baseline report not found. Please complete the baseline report first.', [], 422);
        }

        // Required: weight must be present in the progress record
        if (empty($morphology->weight)) {
            return $this->errorResponse('Weight is required.', [], 422);
        }

        // Required: mandatory measurements must be present
        if (empty($morphology->waist) || empty($morphology->hips) || empty($morphology->neck)) {
            return $this->errorResponse('Mandatory measurements (waist, hips, neck) are required.', [], 422);
        }

        // Use baseline data for gender, age, height, target_weight
        if (empty($baselineReport->gender) || empty($baselineReport->age) || empty($baselineReport->height)) {
            return $this->errorResponse('Baseline report is missing required data (gender, age, height).', [], 422);
        }

        // Fetch previous completed report for comparison
        $previousReport = UserMorphology::where('user_id', $user->id)
            ->where('id', '<', $morphology->id)
            ->where('generation_status', 'completed')
            ->whereNotNull('report_json')
            ->latest('id')
            ->first();

        // Check if previous report is baseline and lacks mandatory measurements
        $baselineMeasurementDataPresent = 1;
        if ($previousReport && $previousReport->type === 'Baseline Report') {
            if (empty($previousReport->waist) || empty($previousReport->hips) || empty($previousReport->neck)) {
                $baselineMeasurementDataPresent = 0;
            }
        }

        try {
            $morphology->update([
                'generation_status' => 'processing',
            ]);

            $gender = strtolower($baselineReport->gender);
            $age = (int) $baselineReport->age;
            $heightCm = (float) $baselineReport->height;
            $heightMeter = $heightCm / 100;
            $weightKg = (float) $morphology->weight;
            $targetWeight = !empty($baselineReport->target_weight) ? (float) $baselineReport->target_weight : null;

            $waist = !empty($morphology->waist) ? (float) $morphology->waist : null;
            $hips = !empty($morphology->hips) ? (float) $morphology->hips : null;
            $neck = !empty($morphology->neck) ? (float) $morphology->neck : null;

            // --- BMI ---
            $bmi = null;
            if ($weightKg > 0 && $heightMeter > 0) {
                $bmi = $weightKg / ($heightMeter * $heightMeter);
            }

            $bmiCategory = null;
            if (!is_null($bmi)) {
                if ($bmi < 18.5) {
                    $bmiCategory = 'Underweight';
                } elseif ($bmi <= 24.9) {
                    $bmiCategory = 'Normal';
                } else {
                    $bmiCategory = 'Overweight';
                }
            }

            // --- Body Fat ---
            $bodyFat = null;
            $bodyFatMethod = null;
            $heightIn = $heightCm / 2.54;
            $waistIn = !is_null($waist) ? $waist / 2.54 : null;
            $neckIn = !is_null($neck) ? $neck / 2.54 : null;
            $hipsIn = !is_null($hips) ? $hips / 2.54 : null;

            if (!empty($waist) && !empty($neck)) {
                if ($gender == 'male') {
                    $bodyFat = (86.010 * log10($waistIn - $neckIn)) - (70.041 * log10($heightIn)) + 36.76;
                    $bodyFatMethod = 'navy';
                } elseif ($gender == 'female' && !empty($hips)) {
                    $bodyFat = (163.205 * log10($waistIn + $hipsIn - $neckIn)) - (97.684 * log10($heightIn)) - 78.387;
                    $bodyFatMethod = 'navy';
                }
            } elseif (!empty($waist)) {
                $ratio = $heightCm / $waist;
                if ($gender == 'male') {
                    $bodyFat = 64 - (20 * $ratio);
                } else {
                    $bodyFat = 76 - (20 * $ratio);
                }
                $bodyFatMethod = 'rfm';
            }

            if (!is_null($bodyFat)) {
                $bodyFat = max(3, min(50, $bodyFat));
            }

            // --- Fat Mass / Lean Mass ---
            $fatMassKg = null;
            $leanMassKg = null;
            if (!is_null($bodyFat)) {
                $fatMassKg = $weightKg * ($bodyFat / 100);
                $leanMassKg = $weightKg - $fatMassKg;
            }

            // --- LMI ---
            $lmi = null;
            if (!is_null($leanMassKg)) {
                $lmi = $leanMassKg / ($heightMeter * $heightMeter);
            }

            // --- WHR ---
            $whr = null;
            if (!empty($waist) && !empty($hips)) {
                $whr = $waist / $hips;
            }

            // --- WHtR ---
            $whtr = null;
            if (!empty($waist) && !empty($heightCm)) {
                $whtr = $waist / $heightCm;
            }

            // --- Body Type ---
            $bodyType = null;
            if (!is_null($whr)) {
                $threshold = $gender == 'male' ? 0.90 : 0.85;
                $bodyType = $whr >= $threshold ? 'android' : 'gynoid';
            }

            // --- Healthy Body Fat Range ---
            $healthyLower = null;
            $healthyUpper = null;
            if ($gender == 'male') {
                if ($age < 30) {
                    $healthyLower = 8;
                    $healthyUpper = 19;
                } elseif ($age <= 50) {
                    $healthyLower = 11;
                    $healthyUpper = 21;
                } else {
                    $healthyLower = 13;
                    $healthyUpper = 24;
                }
            } else {
                if ($age < 30) {
                    $healthyLower = 16;
                    $healthyUpper = 24;
                } elseif ($age <= 50) {
                    $healthyLower = 17;
                    $healthyUpper = 27;
                } else {
                    $healthyLower = 19;
                    $healthyUpper = 30;
                }
            }

            // --- Sub-Scores ---
            $bfSubScore = null;
            $bfHealthy = false;
            if (!is_null($bodyFat)) {
                if ($bodyFat >= $healthyLower && $bodyFat <= $healthyUpper) {
                    $bfSubScore = 100;
                    $bfHealthy = true;
                } elseif ($bodyFat < $healthyLower) {
                    $bfSubScore = max(40, 100 - (($healthyLower - $bodyFat) * 6));
                } else {
                    $bfSubScore = max(40, 100 - (($bodyFat - $healthyUpper) * 5));
                }
            }

            $whtrSubScore = null;
            if (!is_null($whtr)) {
                if ($whtr < 0.40) {
                    $whtrSubScore = 80;
                } elseif ($whtr <= 0.50) {
                    $whtrSubScore = 100;
                } elseif ($whtr <= 0.55) {
                    $whtrSubScore = 100 - (($whtr - 0.50) * 500);
                } elseif ($whtr <= 0.60) {
                    $whtrSubScore = 75 - (($whtr - 0.55) * 500);
                } else {
                    $whtrSubScore = max(40, 50 - (($whtr - 0.60) * 100));
                }
            }

            $bmiSubScore = null;
            if (!is_null($bmi)) {
                if ($bmi < 18.5) {
                    $bmiSubScore = 60;
                } elseif ($bmi < 20) {
                    $bmiSubScore = 70 + (($bmi - 18.5) * 20);
                } elseif ($bmi <= 24.9) {
                    $bmiSubScore = 100;
                } elseif ($bmi <= 29.9) {
                    $bmiSubScore = 100 - (($bmi - 24.9) * 8);
                } else {
                    $bmiSubScore = max(40, 60 - (($bmi - 29.9) * 4));
                }
                if ($bfHealthy) {
                    $bmiSubScore = min(100, $bmiSubScore + 15);
                }
            }

            $lmiSubScore = null;
            if (!is_null($lmi)) {
                if ($gender == 'male') {
                    if ($lmi < 17) {
                        $lmiSubScore = 60;
                    } elseif ($lmi < 19) {
                        $lmiSubScore = 75;
                    } elseif ($lmi <= 22) {
                        $lmiSubScore = 100;
                    } else {
                        $lmiSubScore = 90;
                    }
                } else {
                    if ($lmi < 14) {
                        $lmiSubScore = 60;
                    } elseif ($lmi < 16) {
                        $lmiSubScore = 75;
                    } elseif ($lmi <= 19) {
                        $lmiSubScore = 100;
                    } else {
                        $lmiSubScore = 90;
                    }
                }
            }

            // --- Composition Score ---
            $compositionScore = null;
            if (!is_null($bfSubScore) && !is_null($whtrSubScore) && !is_null($bmiSubScore) && !is_null($lmiSubScore)) {
                $compositionScore = (0.40 * $bfSubScore) + (0.30 * $whtrSubScore) + (0.20 * $bmiSubScore) + (0.10 * $lmiSubScore);
            } elseif (!is_null($bfSubScore) && !is_null($whtrSubScore) && !is_null($bmiSubScore)) {
                $compositionScore = ((0.40 / 0.90) * $bfSubScore) + ((0.30 / 0.90) * $whtrSubScore) + ((0.20 / 0.90) * $bmiSubScore);
            } elseif (!is_null($bmiSubScore)) {
                $compositionScore = $bmiSubScore;
            }
            if (!is_null($compositionScore)) {
                $compositionScore = round(max(40, min(100, $compositionScore)));
            }

            $scoreLabel = null;
            if (!is_null($compositionScore)) {
                if ($compositionScore >= 85) {
                    $scoreLabel = 'Optimal range';
                } elseif ($compositionScore >= 70) {
                    $scoreLabel = 'Healthy range';
                } elseif ($compositionScore >= 55) {
                    $scoreLabel = 'Room to improve';
                } else {
                    $scoreLabel = 'Supervision needed';
                }
            }

            // --- Completeness Score (weight=35, core=35, optional=10, photos=20) ---
            $completenessScore = 0;

            if (!empty($morphology->weight)) {
                $completenessScore += 35;
            }

            $coreMeasurementCompleted = !empty($morphology->waist) && !empty($morphology->hips) && !empty($morphology->neck);
            if ($coreMeasurementCompleted) {
                $completenessScore += 35;
            }

            $optionalMeasurements = [$morphology->bicep, $morphology->chest, $morphology->thighs, $morphology->calfs];
            $optionalCount = collect($optionalMeasurements)->filter(fn ($value) => !empty($value))->count();
            $completenessScore += ($optionalCount * 2.5);

            $photos = [$morphology->image_fv, $morphology->image_bv, $morphology->image_ls, $morphology->image_rs];
            $photoCount = collect($photos)->filter(fn ($value) => !empty($value))->count();
            $completenessScore += ($photoCount * 5);

            // --- Projection ---
            $projection = null;
            if (!is_null($targetWeight)) {
                $delta = abs($weightKg - $targetWeight);

                $minWeeks = $delta / 0.5;
                $maxWeeks = $delta / 0.25;

                $projection = [
                    'min_months' => round($minWeeks / 4.345),
                    'max_months' => round($maxWeeks / 4.345),
                ];
            }

            $chartData = [];
            if (!is_null($targetWeight) && isset($delta) && $delta > 0) {
                $minWeeksFloat = $minWeeks;
                $fullWeeks = (int) floor($minWeeksFloat);

                for ($week = 0; $week <= $fullWeeks; $week++) {
                    $projectedWeight = $weightKg - (0.5 * $week);
                    if ($projectedWeight < $targetWeight) {
                        $projectedWeight = $targetWeight;
                    }
                    $chartData[] = [
                        'week' => $week,
                        'projected_weight' => round($projectedWeight, 1),
                    ];
                }

                if ($fullWeeks < $minWeeksFloat) {
                    $chartData[] = [
                        'week' => $minWeeksFloat,
                        'projected_weight' => round($targetWeight, 1),
                    ];
                } else {
                    $lastIndex = count($chartData) - 1;
                    if ($lastIndex >= 0) {
                        $chartData[$lastIndex]['projected_weight'] = round($targetWeight, 1);
                        $chartData[$lastIndex]['week'] = $minWeeksFloat;
                    }
                }
            }

            // --- Build raw_report_json (same flat structure as baseline report_json) ---
            $rawReportJson = $this->sanitizeReportJson([
                'summary' => [
                    'completeness_score' => round($completenessScore),
                    'report_generated_at' => now()->toDateTimeString(),
                ],
                'bmi_score' => [
                    'bmi' => $bmi ? round($bmi, 1) : null,
                    'bmi_category' => $bmiCategory,
                    'composition_score' => $compositionScore,
                    'score_label' => $scoreLabel,
                ],
                'measurements' => [
                    'whr' => $whr ? round($whr, 3) : null,
                    'whtr' => $whtr ? round($whtr, 3) : null,
                    'body_type' => $bodyType,
                ],
                'composition' => [
                    'body_fat_pct' => $bodyFat ? round($bodyFat, 1) : null,
                    'fat_mass_kg' => $fatMassKg ? round($fatMassKg, 1) : null,
                    'lean_mass_kg' => $leanMassKg ? round($leanMassKg, 1) : null,
                    'lmi' => $lmi ? round($lmi, 1) : null,
                ],
                'projection' => [
                    'projection' => $projection,
                    'chart_data' => $chartData,
                ],
            ]);

            // --- Build comparison report_json (5 tab structure) ---

            // Helper: compare two numeric values and return change_status + delta
            $compareValues = function ($current, $previous) {
                $curr = (float) ($current ?? 0);
                $prev = (float) ($previous ?? 0);

                $delta = round($curr - $prev, 3);

                if ($prev == 0 && $curr == 0) {
                    $changeStatus = 0;
                } elseif ($curr < $prev) {
                    $changeStatus = 1; // decreased
                } elseif ($curr > $prev) {
                    $changeStatus = 2; // increased
                } else {
                    $changeStatus = 0; // same
                }

                return [
                    'delta' => $delta,
                    'change_status' => $changeStatus,
                ];
            };

            // Extract previous report data (could be baseline's report_json or earlier progress's report_json)
            $prevJson = $previousReport ? $previousReport->report_json : null;

            // If previous is a progress report, its report_json has tab structure with current_report inside
            // If previous is a baseline report, its report_json is the flat structure
            // We need to extract the flat data from the previous report for comparison
            $prevBmiScore = null;
            $prevMeasurements = null;
            $prevComposition = null;
            $prevProjection = null;
            $prevWeight = null;
            $prevWaist = null;
            $prevHips = null;
            $prevNeck = null;
            $prevFatMassKg = null;
            $prevLeanMassKg = null;

            if ($prevJson) {
                // Check if previous report has tab structure (progress) or flat structure (baseline)
                if (isset($prevJson['tab_bmi_score'])) {
                    // Previous is a progress report — use current_report from its raw_report_json
                    $prevRawJson = $previousReport->raw_report_json;
                    $prevBmiScore = $prevRawJson['bmi_score'] ?? null;
                    $prevMeasurements = $prevRawJson['measurements'] ?? null;
                    $prevComposition = $prevRawJson['composition'] ?? null;
                    $prevProjection = $prevRawJson['projection'] ?? null;
                } else {
                    // Previous is a baseline report — flat structure
                    $prevBmiScore = $prevJson['bmi_score'] ?? null;
                    $prevMeasurements = $prevJson['measurements'] ?? null;
                    $prevComposition = $prevJson['composition'] ?? null;
                    $prevProjection = $prevJson['projection'] ?? null;
                }

                // Sanitize previous data (older records may have nulls)
                if ($prevBmiScore) $prevBmiScore = $this->sanitizeReportJson($prevBmiScore);
                if ($prevMeasurements) $prevMeasurements = $this->sanitizeReportJson($prevMeasurements);
                if ($prevComposition) $prevComposition = $this->sanitizeReportJson($prevComposition);
                if ($prevProjection) $prevProjection = $this->sanitizeReportJson($prevProjection);

                $prevWeight = $previousReport->weight;
                $prevWaist = $previousReport->waist;
                $prevHips = $previousReport->hips;
                $prevNeck = $previousReport->neck;
                $prevFatMassKg = $prevComposition['fat_mass_kg'] ?? null;
                $prevLeanMassKg = $prevComposition['lean_mass_kg'] ?? null;
            }

            // --- Day labels relative to baseline ---
            $baselineGeneratedAt = $baselineReport->report_generated_at
                ? Carbon::parse($baselineReport->report_generated_at)
                : null;

            $currentDay = 0;
            $previousDay = 0;

            if ($baselineGeneratedAt) {
                // Current day = difference between now and baseline
                $currentDay = $baselineGeneratedAt->diffInDays(now());

                if ($previousReport) {
                    if ($previousReport->type === 'Baseline Report') {
                        // Previous is baseline → Day 0
                        $previousDay = 0;
                    } else {
                        // Previous is progress → day diff from baseline
                        $prevGeneratedAt = $previousReport->report_generated_at
                            ? Carbon::parse($previousReport->report_generated_at)
                            : null;
                        $previousDay = $prevGeneratedAt
                            ? $baselineGeneratedAt->diffInDays($prevGeneratedAt)
                            : 0;
                    }
                }
            }

            // Tab 1: BMI Score
            $tabBmiScore = [
                'current_day' => $currentDay,
                'previous_day' => $previousDay,
                'current' => $rawReportJson['bmi_score'],
                'previous' => $prevBmiScore ?? (object) [],
                'comparison' => [
                    'bmi' => $compareValues(
                        $rawReportJson['bmi_score']['bmi'],
                        $prevBmiScore['bmi'] ?? null
                    ),
                    'composition_score' => $compareValues(
                        $rawReportJson['bmi_score']['composition_score'],
                        $prevBmiScore['composition_score'] ?? null
                    ),
                ],
            ];

            // Tab 2: Measurements
            $tabMeasurements = [
                'current_day' => $currentDay,
                'previous_day' => $previousDay,
                'current' => array_merge($rawReportJson['measurements'], [
                    'waist' => $waist,
                    'hips' => $hips,
                    'neck' => $neck,
                ]),
                'previous' => $prevMeasurements
                    ? array_merge($prevMeasurements, [
                        'waist' => $prevWaist ? (float) $prevWaist : null,
                        'hips' => $prevHips ? (float) $prevHips : null,
                        'neck' => $prevNeck ? (float) $prevNeck : null,
                    ])
                    : (object) [],
                'comparison' => [
                    'whr' => $compareValues(
                        $rawReportJson['measurements']['whr'],
                        $prevMeasurements['whr'] ?? null
                    ),
                    'whtr' => $compareValues(
                        $rawReportJson['measurements']['whtr'],
                        $prevMeasurements['whtr'] ?? null
                    ),
                    'waist' => $compareValues($waist, $prevWaist),
                    'hips' => $compareValues($hips, $prevHips),
                    'neck' => $compareValues($neck, $prevNeck),
                ],
            ];

            // Tab 3: Composition — with fat/lean percentage ratio
            $currentFat = $rawReportJson['composition']['fat_mass_kg'];
            $currentLean = $rawReportJson['composition']['lean_mass_kg'];
            $currentTotal = ($currentFat ?? 0) + ($currentLean ?? 0);
            $currentFatPct = $currentTotal > 0 ? round(($currentFat / $currentTotal) * 100, 1) : null;
            $currentLeanPct = $currentTotal > 0 ? round(($currentLean / $currentTotal) * 100, 1) : null;

            $prevTotal = ((float) ($prevFatMassKg ?? 0)) + ((float) ($prevLeanMassKg ?? 0));
            $prevFatPct = $prevTotal > 0 ? round(($prevFatMassKg / $prevTotal) * 100, 1) : null;
            $prevLeanPct = $prevTotal > 0 ? round(($prevLeanMassKg / $prevTotal) * 100, 1) : null;

            $tabComposition = [
                'current_day' => $currentDay,
                'previous_day' => $previousDay,
                'current' => array_merge($rawReportJson['composition'], [
                    'fat_mass_pct' => $currentFatPct,
                    'lean_mass_pct' => $currentLeanPct,
                ]),
                'previous' => $prevComposition
                    ? array_merge($prevComposition, [
                        'fat_mass_pct' => $prevFatPct,
                        'lean_mass_pct' => $prevLeanPct,
                    ])
                    : (object) [],
                'comparison' => [
                    'body_fat_pct' => $compareValues(
                        $rawReportJson['composition']['body_fat_pct'],
                        $prevComposition['body_fat_pct'] ?? null
                    ),
                    'fat_mass_kg' => $compareValues(
                        $rawReportJson['composition']['fat_mass_kg'],
                        $prevComposition['fat_mass_kg'] ?? null
                    ),
                    'lean_mass_kg' => $compareValues(
                        $rawReportJson['composition']['lean_mass_kg'],
                        $prevComposition['lean_mass_kg'] ?? null
                    ),
                    'lmi' => $compareValues(
                        $rawReportJson['composition']['lmi'],
                        $prevComposition['lmi'] ?? null
                    ),
                    'fat_mass_pct' => $compareValues($currentFatPct, $prevFatPct),
                    'lean_mass_pct' => $compareValues($currentLeanPct, $prevLeanPct),
                ],
            ];

            // Tab 4: Projection — with weight markers and date-based slope chart
            $startingWeight = $baselineReport->weight ? (float) $baselineReport->weight : null;
            $goalWeight = $targetWeight;

            $baselineDate = $baselineGeneratedAt ? $baselineGeneratedAt->toDateString() : null;
            $previousDate = ($previousReport && $previousReport->report_generated_at)
                ? Carbon::parse($previousReport->report_generated_at)->toDateString()
                : null;
            $currentDate = now()->toDateString();

            // Build slope chart data points with dates for the graph
            $slopeChartData = [];

            // Fetch all completed past reports chronologically (oldest to newest)
            $allPastReports = UserMorphology::where('user_id', $user->id)
                ->where('id', '<', $morphology->id)
                ->where('generation_status', 'completed')
                ->orderBy('id', 'asc')
                ->get();

            $baselineDateObj = $baselineGeneratedAt ? Carbon::parse($baselineGeneratedAt)->startOfDay() : null;

            foreach ($allPastReports as $pastReport) {
                $pastWeight = $pastReport->weight ? (float) $pastReport->weight : null;
                if (is_null($pastWeight)) {
                    continue;
                }

                $pastDateStr = $pastReport->report_generated_at 
                    ? Carbon::parse($pastReport->report_generated_at)->toDateString() 
                    : Carbon::parse($pastReport->updated_at)->toDateString();

                $pastDateObj = Carbon::parse($pastDateStr)->startOfDay();

                if ($pastReport->type === 'Baseline Report') {
                    $slopeChartData[] = [
                        'label' => 'Starting Weight',
                        'date' => $pastDateStr,
                        'day' => 0,
                        'weight' => round($pastWeight, 1),
                    ];
                } else {
                    $pastDay = $baselineDateObj ? $baselineDateObj->diffInDays($pastDateObj) : 0;
                    $slopeChartData[] = [
                        'label' => 'Previous Weight',
                        'date' => $pastDateStr,
                        'day' => $pastDay,
                        'weight' => round($pastWeight, 1),
                    ];
                }
            }

            // Fallback safety check: ensure Starting Weight is present if startingWeight and baselineDate are available
            $hasStartingWeight = collect($slopeChartData)->contains('label', 'Starting Weight');
            if (!$hasStartingWeight && $startingWeight && $baselineDate) {
                array_unshift($slopeChartData, [
                    'label' => 'Starting Weight',
                    'date' => $baselineDate,
                    'day' => 0,
                    'weight' => round($startingWeight, 1),
                ]);
            }

            // Current weight
            $slopeChartData[] = [
                'label' => 'Current Weight',
                'date' => $currentDate,
                'day' => $currentDay,
                'weight' => round($weightKg, 1),
            ];

            // Goal weight (projected)
            if ($goalWeight) {
                $goalDay = $currentDay;
                if (!is_null($projection) && isset($projection['min_months'])) {
                    $goalDay = $currentDay + (int) round($projection['min_months'] * 30.44);
                }
                $goalDate = now()->addDays($goalDay - $currentDay)->toDateString();

                $slopeChartData[] = [
                    'label' => 'Goal Weight',
                    'date' => $goalDate,
                    'day' => $goalDay,
                    'weight' => round($goalWeight, 1),
                ];
            }

            $tabProjection = [
                'current_day' => $currentDay,
                'previous_day' => $previousDay,
                'starting_weight' => $startingWeight ? round($startingWeight, 1) : null,
                'current_weight' => round($weightKg, 1),
                'goal_weight' => $goalWeight ? round($goalWeight, 1) : null,
                'current' => $rawReportJson['projection'],
                'previous' => $prevProjection ?? (object) [],
                'slope_chart_data' => $slopeChartData,
            ];

            // Tab 5: Body Recap
            $startReport = $previousReport ?: $baselineReport;
            $startDate = Carbon::parse($startReport->report_generated_at);
            $endDate = Carbon::now();
            $daysDiff = $startDate->diffInDays($endDate);
            if ($daysDiff < 1) {
                $daysDiff = 1;
            }

            // Workout Sessions
            $expectedWeeks = max(1, (int) ceil($daysDiff / 7));

            $userAssignedProgram = UserProgram::with(['program.program_session'])
                ->where('user_id', $user->id)
                ->first();

            $programId = $userAssignedProgram ? $userAssignedProgram->program_id : null;

            $expectedSessions = 0;
            if ($userAssignedProgram && $userAssignedProgram->program) {
                $expectedWeekStrings = [];
                for ($i = 1; $i <= $expectedWeeks; $i++) {
                    $expectedWeekStrings[] = 'Semaine ' . $i;
                    $expectedWeekStrings[] = 'week ' . $i;
                    $expectedWeekStrings[] = 'Week ' . $i;
                    $expectedWeekStrings[] = (string) $i;
                }

                $expectedSessions = $userAssignedProgram->program->program_session
                    ->filter(function ($ps) use ($expectedWeekStrings) {
                        return in_array($ps->session_week, $expectedWeekStrings);
                    })
                    ->count();
            } else {
                $expectedSessions = $expectedWeeks * 4;
            }

            if ($programId) {
                $completedWorkoutSessions = UserSessionStatus::where('user_id', $user->id)
                    ->where('program_id', $programId)
                    ->where('user_session_status', 1)
                    ->whereBetween('updated_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
                    ->count();
            } else {
                $completedWorkoutSessions = UserSessionStatus::where('user_id', $user->id)
                    ->where('user_session_status', 1)
                    ->whereBetween('updated_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
                    ->count();
            }

            // Nutrition
            $dailyKcal = FoodPlanner::where('user_id', $user->id)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->selectRaw('date, sum(kcal) as total_kcal')
                ->groupBy('date')
                ->get();

            $validDays = $dailyKcal->filter(function ($day) {
                return (float) $day->total_kcal > 1000;
            });

            $validDaysCount = $validDays->count();
            $averageKcal = round($validDaysCount > 0 ? $validDays->average('total_kcal') : 0);
            $targetKcal = $this->calculateDailyTargetKcal($user->id);

            // Step Tracker
            $stepsRecords = StepsGoal::where('user_id', $user->id)
                ->whereBetween('goal_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            $daysWithStepsCount = $stepsRecords->count();
            $hasStepsRecord = $daysWithStepsCount > 0 ? 1 : 0;

            $averageSteps = $hasStepsRecord ? (float) $stepsRecords->average('steps_completed') : 0.0;
            $averageDailyTarget = $hasStepsRecord ? (float) $stepsRecords->average('steps_goal') : 0.0;

            $tabBodyRecap = [
                'has_steps_record' => $hasStepsRecord,
                'workout_recap' => [
                    'days_in_period' => $daysDiff,
                    'sessions_expected' => $expectedSessions,
                    'sessions_completed' => $completedWorkoutSessions,
                ],
                'nutrition_recap' => [
                    'days_logged' => $validDaysCount,
                    'average_kcal' => $averageKcal,
                    'daily_target_kcal' => $targetKcal,
                ],
                'step_recap' => [
                    'days_logged' => $daysWithStepsCount,
                    'daily_average_steps' => $averageSteps,
                    'daily_target_steps' => $averageDailyTarget,
                ]
            ];

            // Build the comparison report_json
            $reportJson = $this->sanitizeReportJson([
                'summary' => [
                    'completeness_score' => round($completenessScore),
                    'report_generated_at' => now()->toDateTimeString(),
                    'weight' => $compareValues($weightKg, $prevWeight),
                    'baseline_measurement_data_present' => $baselineMeasurementDataPresent,
                ],
                'tab_bmi_score' => $tabBmiScore,
                'tab_measurements' => $tabMeasurements,
                'tab_composition' => $tabComposition,
                'tab_projection' => $tabProjection,
                'tab_body_recap' => $tabBodyRecap,
            ]);

            // --- Save ---
            $generatedAt = now();
            $morphology->update([
                'generation_status' => 'completed',
                'generation_error' => '',
                'report_generated_at' => $generatedAt,
                'bmi' => !is_null($bmi) ? round($bmi, 1) : null,
                'whr' => !is_null($whr) ? round($whr, 3) : null,
                'body_fat' => !is_null($bodyFat) ? round($bodyFat, 1) : null,
                'whtr' => !is_null($whtr) ? round($whtr, 3) : null,
                'lmi' => !is_null($lmi) ? round($lmi, 1) : null,
                'composition_score' => $compositionScore,
                'raw_report_json' => $rawReportJson,
                'report_json' => $reportJson,
            ]);

            $this->linkUserReportLevel($morphology->id, 'morphology');

            return $this->successResponse(
                'Progress morphology report is generated.',
                [
                    'generation_status' => 'completed',
                    'generation_error' => '',
                    'report_generated_at' => $generatedAt,
                    'report_json' => $reportJson,
                    'raw_report_json' => $rawReportJson,
                ],
                200
            );
        } catch (\Throwable $exception) {
            $morphology->update([
                'generation_status' => 'failed',
                'generation_error' => $exception->getMessage(),
            ]);

            return $this->errorResponse('Progress morphology report generation failed.', ['error' => $exception->getMessage()], 422);
        }
    }

    /**
     * Get progress morphology report.
     */
    public function getProgressMorphologyReport(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'exists:user_morphologies,session_id'],
        ]);

        $user = Auth::user();

        $morphology = UserMorphology::where('session_id', $validated['session_id'])->where('user_id', $user->id)->first();

        if (!$morphology) {
            return $this->errorResponse('Morphology session not found.', [], 404);
        }

        if ($morphology->generation_status == 'pending') {

            return $this->successResponse(
                'Progress morphology report is pending.',
                [
                    'generation_status' => 'pending',
                    'generation_error' => $morphology->generation_error ?? '',
                    'report_generated_at' => $morphology->report_generated_at,
                    'report_json' => (object) [],
                    'raw_report_json' => (object) [],
                ],
                200
            );
        }

        if ($morphology->generation_status == 'processing') {

            return $this->successResponse(
                'Progress morphology report is currently processing.',
                [
                    'generation_status' => 'processing',
                    'generation_error' => $morphology->generation_error ?? '',
                    'report_generated_at' => $morphology->report_generated_at,
                    'report_json' => (object) [],
                    'raw_report_json' => (object) [],
                ],
                200
            );
        }

        if ($morphology->generation_status == 'failed') {

            return $this->successResponse(
                'Progress morphology report generation failed.',
                [
                    'generation_status' => 'failed',
                    'generation_error' => $morphology->generation_error ?? '',
                    'report_generated_at' => $morphology->report_generated_at,
                    'report_json' => (object) [],
                    'raw_report_json' => (object) [],
                ],
                422
            );
        }

        return $this->successResponse(
            'Progress morphology report is generated.',
            [
                'generation_status' => 'completed',
                'generation_error' => '',
                'report_generated_at' => $morphology->report_generated_at,
                'report_json' => $this->sanitizeReportJson($morphology->report_json),
                'raw_report_json' => $this->sanitizeReportJson($morphology->raw_report_json),
            ],
            200
        );
    }

    private function calculateDailyTargetKcal($userId)
    {
        $targetKcal = 10000;
        $bmrData = UsersInitialMeasurement::where('user_id', $userId)->orderBy('id', 'desc')->first();
        $userMeasurementData = UsersCurrentMeasurement::with('initialMeasurement', 'targetMeasurement')
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();
        if ($userMeasurementData) {
            $bmrData = $userMeasurementData;
        }
        if ($bmrData) {
            $userData = User::where('id', $userId)->first();
            if ($userData) {
                $weight = $bmrData->weight;
                $initialMeasurement = UsersInitialMeasurement::where('user_id', $userId)->latest('id')->first();
                $age = ($initialMeasurement && $initialMeasurement->age > 0) ? (int)$initialMeasurement->age : 24;
                if ($initialMeasurement && $initialMeasurement->created_at) {
                    $yearsElapsed = now()->diffInYears($initialMeasurement->created_at);
                    $age += $yearsElapsed;
                }
                $gender = strtolower($userData->gender ?? 'male');
                $height = (!empty($userData->height) && $userData->height != 0) ? $userData->height : 170;

                // Calculate BMR
                if ($gender == 'female') {
                    $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
                } else {
                    $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
                }

                $stepsGoal = StepsGoal::where(['user_id' => $userId])->first();

                // Calculate TDEE
                $activityFactor = $stepsGoal ? ($stepsGoal->activity_factor ?? 1.237) : 1.237;
                $tdee = round(($bmr * $activityFactor));

                $targetKcal = $tdee;
            }
        }
        return $targetKcal;
    }

    public function resetUserReports(Request $request)
    {
        $user = Auth::user();

        // 1. Delete Behaviour Test Reports
        $behaviourTests = UserMorphologyBehaviourTest::where('user_id', $user->id)->get();
        $behaviourTestsDeletedCount = 0;
        foreach ($behaviourTests as $behaviourTest) {
            $sessionId = $behaviourTest->session_id;
            if (!empty($sessionId)) {
                $storagePath = "user/morphology/report-screenshots/{$sessionId}";
                if (Storage::disk('public')->exists($storagePath)) {
                    Storage::disk('public')->deleteDirectory($storagePath);
                }
            }
            $behaviourTest->delete();
            $behaviourTestsDeletedCount++;
        }

        // 2. Delete Progress Reports
        $progressReports = UserMorphology::where('user_id', $user->id)->where('type', 'Progress Report')->get();
        $progressReportsDeletedCount = 0;
        foreach ($progressReports as $progressReport) {
            $sessionId = $progressReport->session_id;
            if (!empty($sessionId)) {
                $storagePath = "user/morphology/report-screenshots/{$sessionId}";
                if (Storage::disk('public')->exists($storagePath)) {
                    Storage::disk('public')->deleteDirectory($storagePath);
                }
            }

            $imageColumns = ['image_fv', 'image_bv', 'image_ls', 'image_rs'];
            foreach ($imageColumns as $imageColumn) {
                $imagePath = $progressReport->getRawOriginal($imageColumn);
                if (!empty($imagePath)) {
                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                    }
                    // Delete from UsersAppearanceInfo Table
                    UsersAppearanceInfo::where('user_id', $user->id)->where('image', $imagePath)->delete();
                }
            }

            $progressReport->delete();
            $progressReportsDeletedCount++;
        }

        // 3. Modify Baseline Report Date to strictly before 30 days from today (31 days ago)
        $newGeneratedAtDate = Carbon::now()->subDays(31)->toDateString();
        $baselineReports = UserMorphology::where('user_id', $user->id)->where('type', 'Baseline Report')->get();
        $baselineReportsModifiedCount = 0;
        foreach ($baselineReports as $baselineReport) {
            $baselineReport->update([
                'report_generated_at' => $newGeneratedAtDate,
            ]);
            $baselineReportsModifiedCount++;
        }

        return $this->successResponse(
            'User reports reset successfully.',
            [
                'behaviour_test_reports_deleted' => $behaviourTestsDeletedCount,
                'progress_reports_deleted' => $progressReportsDeletedCount,
                'baseline_reports_modified' => $baselineReportsModifiedCount,
                'baseline_report_new_date' => $newGeneratedAtDate
            ],
            200
        );
    }

    public function resetUserReports2(Request $request)
    {
        $user = Auth::user();

        // Retrieve the latest morphology report (Progress Report or Baseline Report)
        $latestReport = UserMorphology::where('user_id', $user->id)
            ->whereIn('type', ['Baseline Report', 'Progress Report'])
            ->latest('id')
            ->first();

        if (!$latestReport) {
            return $this->errorResponse('No morphology report found for this user.', [], 404);
        }

        $previousGeneratedAt = $latestReport->report_generated_at;
        $newGeneratedAtDate = Carbon::now()->subDays(31)->toDateString();

        $latestReport->update([
            'report_generated_at' => $newGeneratedAtDate,
        ]);

        return $this->successResponse(
            'Latest report generated date updated successfully.',
            [
                'report_id' => $latestReport->id,
                'session_id' => $latestReport->session_id,
                'type' => $latestReport->type,
                'previous_generated_at' => $previousGeneratedAt,
                'new_report_generated_at' => $newGeneratedAtDate,
            ],
            200
        );
    }
}

