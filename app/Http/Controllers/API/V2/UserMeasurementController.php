<?php

namespace App\Http\Controllers\API\V2;


use App\Http\Controllers\API\UserMeasurementController as APIUserMeasurementControllerV1;

use App\Http\Controllers\Controller;
use App\Models\TaskMilestone;
use App\Models\UserLevelTaskLog;
use App\Models\userMilestone;
use App\Models\UsersAppearanceInfo;
use App\Models\UserProgressImage;
use App\Models\UsersCurrentMeasurement;
use App\Models\UsersInitialMeasurement;
use App\Models\UsersTargetMeasurement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UserMeasurementController extends APIUserMeasurementControllerV1

{
    public function userMeasures(Request $request)
    {
        try {
            $data = $request->all();
            $fields = ['weight', 'waist', 'chest', 'bicep', 'neck', 'thighs', 'hips', 'calfs'];
            $userId = Auth::user()->id;
            $today = Carbon::today();
            $updateData = [];

            // Format the provided data
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = number_format((float)$data[$field], 1, '.', '');
                }
            }

            // INITIAL MEASUREMENT: Only update, don't insert
            if ($request->measurementType == 0) {
                $initialMeasurement = UsersInitialMeasurement::where('user_id', $userId)->first();

                if ($initialMeasurement) {
                    foreach ($fields as $field) {
                        if (isset($updateData[$field])) {
                            $initialMeasurement->$field = $updateData[$field];
                        }
                    }
                    $initialMeasurement->last_modified_date = $today;
                    $initialMeasurement->save();

                    // Format output
                    foreach ($fields as $field) {
                        $initialMeasurement->$field = number_format((float)$initialMeasurement->$field, 1, '.', '');
                    }

                    return $this->successResponse("Initial measurement updated successfully.", $initialMeasurement, 200);
                } else {
                    // Create new initial measurement if not found
                    $initialMeasurement = new UsersInitialMeasurement();
                    $initialMeasurement->user_id = $userId;
                    foreach ($fields as $field) {
                        $initialMeasurement->$field = isset($updateData[$field]) ? $updateData[$field] : 0;
                    }
                    $initialMeasurement->added_date = $today;
                    $initialMeasurement->last_modified_date = $today;
                    $initialMeasurement->save();

                    // Format output
                    foreach ($fields as $field) {
                        $initialMeasurement->$field = number_format((float)$initialMeasurement->$field, 1, '.', '');
                    }

                    return $this->successResponse("Initial measurement created successfully.", $initialMeasurement, 200);
                }
            }

            // For measurementType 1 or 2
            if ($request->measurementType == 1) {
                $model = UsersTargetMeasurement::class;
            } elseif ($request->measurementType == 2) {
                $model = UsersCurrentMeasurement::class;

                // Step 1: Ensure Initial Measurement exists
                $initialMeasurement = UsersInitialMeasurement::where('user_id', $userId)->first();

                if (!$initialMeasurement) {
                    // Create initial measurement with provided or 0 values
                    $initialMeasurement = new UsersInitialMeasurement();
                    $initialMeasurement->user_id = $userId;
                    foreach ($fields as $field) {
                        $initialMeasurement->$field = isset($updateData[$field]) ? $updateData[$field] : 0;
                    }
                    $initialMeasurement->added_date = $today;
                    $initialMeasurement->last_modified_date = $today;
                    $initialMeasurement->save();
                } else {
                    // Update initial measurement fields that are 0
                    $needsUpdate = false;
                    foreach ($fields as $field) {
                        if ((is_null($initialMeasurement->$field) || floatval($initialMeasurement->$field) == 0) && isset($updateData[$field])) {
                            $initialMeasurement->$field = $updateData[$field];
                            $needsUpdate = true;
                        }
                    }
                    if ($needsUpdate) {
                        $initialMeasurement->last_modified_date = $today;
                        $initialMeasurement->save();
                    }
                }
            } else {
                return $this->errorResponse("Invalid measurement type.", 400);
            }

            // Get previous measurement (for fallback)
            $previousRecord = $model::where('user_id', $userId)
                ->whereDate('last_modified_date', '<=', $today)
                ->orderBy('last_modified_date', 'desc')
                ->first();

            // Current measurement needs special isWeight check
            if ($request->measurementType == 2) {
                $previousRecord = UsersCurrentMeasurement::where('user_id', $userId)
                    ->where('isWeight', 0)
                    ->whereDate('last_modified_date', '<=', $today)
                    ->orderBy('last_modified_date', 'desc')
                    ->first();
            }

            // Check if there's a record for today
            $existingTodayRecord = $model::where('user_id', $userId)
                ->whereDate('last_modified_date', $today)
                ->orderBy('id', 'desc')
                ->first();

            if ($existingTodayRecord) {
                foreach ($fields as $field) {
                    if (isset($updateData[$field])) {
                        $existingTodayRecord->$field = $updateData[$field];
                    } else {
                        $existingTodayRecord->$field = $previousRecord ? $previousRecord->$field : 0;

                        if ($request->measurementType == 2 && (
                            !$previousRecord || floatval($previousRecord->$field) == 0
                        )) {
                            $existingTodayRecord->$field = $initialMeasurement ? $initialMeasurement->$field : 0;
                        }
                    }
                }

                $existingTodayRecord->last_modified_date = $today;
                $existingTodayRecord->save();

                foreach ($fields as $field) {
                    $existingTodayRecord->$field = number_format((float)$existingTodayRecord->$field, 1, '.', '');
                }

                return $this->successResponse("Measurement updated successfully.", $existingTodayRecord, 200);
            } else {
                $newRecord = new $model;
                $newRecord->user_id = $userId;
                $newRecord->last_modified_date = $today;

                foreach ($fields as $field) {
                    if (isset($updateData[$field])) {
                        $newRecord->$field = $updateData[$field];
                    } else {
                        $value = $previousRecord ? $previousRecord->$field : 0;

                        if ($request->measurementType == 2 && (
                            !$previousRecord || floatval($previousRecord->$field) == 0
                        )) {
                            $value = $initialMeasurement ? $initialMeasurement->$field : 0;
                        }

                        $newRecord->$field = $value;
                    }
                }

                $newRecord->save();

                foreach ($fields as $field) {
                    $newRecord->$field = number_format((float)$newRecord->$field, 1, '.', '');
                }

                return $this->successResponse("Measurement added successfully.", $newRecord, 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function addEvolution(Request $request)
    {
        try {
            $rules = [
                'imageDate' => ['required', 'date', 'date_format:Y-m-d'],
                'image' => ['required', 'image', 'mimes:jpeg,jpg,png']
            ];

            $message = [
                'imageDate.required' => "Please select date.",
                'imageDate.date-format' => "Please provide image in Y-m-d format only.",
                'image.required' => "Please select an image.",
                'image.mimes' => "Choose an image with jpeg,jpg and in png format only"
            ];
            $validator = Validator::make($request->all(), $rules, $message);
            $userId = Auth::user()->id;

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }
            if ($request->has('image')) {
                $appearanceImage = time() . '.' . $request->image->extension();
                if (!Storage::disk('public')->exists("/user/appearance/image")) {
                    Storage::disk('public')->makeDirectory("/user/appearance/image"); //creates directory
                }

                $request->image->storeAs("user/appearance/image", $appearanceImage, 'public');
                $data['image'] = "user/appearance/image/$appearanceImage";
            }
            $userAppearance = new UsersAppearanceInfo;
            $userAppearance->user_id = Auth::user()->id;
            $userAppearance->imageDate = $request->imageDate;
            $userAppearance->image = $data['image'];
            $userAppearance->save();

            // Handle set_as parameter to update UserProgressImage
            if ($request->has('set_as')) {
                $setAs = $request->set_as;
                if ($setAs == 1 || $setAs == 2) {
                    $progressImage = UserProgressImage::where('user_id', $userId)->where('is_active', true)->first();

                    if (!$progressImage) {
                        $progressImage = new UserProgressImage();
                        $progressImage->user_id = $userId;
                    }

                    if ($setAs == 1) {
                        $progressImage->before_image_id = $userAppearance->id;
                    } elseif ($setAs == 2) {
                        $progressImage->after_image_id = $userAppearance->id;
                    }

                    $progressImage->save();
                }
            }

            $milestoneCompletedArray = [];

            $photoMilestones = TaskMilestone::where('milestone_type', 'add-photos')->get();

            $userCurrentMilestones = userMilestone::where([
                'user_id' => $userId,
                'milestone_type' => 'add-photos'
            ])->pluck('milestone_id')->toArray();

            $userPhotoCompleteCount = UserLevelTaskLog::where([
                'user_id' => $userId,
                'level_task_type' => 'add-photos'
            ])->sum('completed_count');

            foreach ($photoMilestones as $milestone) {
                if (
                    $userPhotoCompleteCount >= $milestone->milestone_count &&
                    !in_array($milestone->id, $userCurrentMilestones)
                ) {
                    $milestoneCompletedArray[] = [
                        'type' => 'add-photos',
                        'color' => 'green',
                        'title' => $milestone->milestone_title,
                        'description' => $milestone->milestone_description,
                        'count' => $milestone->milestone_count,
                    ];

                    // Save to user_milestones table
                    UserMilestone::create([
                        'user_id' => $userId,
                        'milestone_id' => $milestone->id,
                        'milestone_type' => 'add-photos',
                    ]);
                }
            }

            $levelDetails = null;
            $levelResponse = $this->logLevelTaskEntries(Auth::user()->id, 'add-photos',1,0);
            if ($levelResponse != 0) {
                $levelDetails = $this->getLevelDetails($levelResponse);
            }

            $userAppearancedata = [
                'user_id' => $userAppearance->user_id,
                'imageDate' => $userAppearance->imageDate,
                'image' => asset(Storage::url($userAppearance->image)),
                'updated_at' => $userAppearance->updated_at,
                'created_at' => $userAppearance->created_at,
                'id' => $userAppearance->id,
                'milestoneCompletedArray' => $milestoneCompletedArray,
                'levelCompletedDetails' => $levelDetails ?: (object)[]

            ];

            return $this->successResponse("User evolution added successfully.", $userAppearancedata, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function getUserEvolutionMeasurement()
    {
        try {
            $userId = Auth::id();
            $evolution = [];

            // Helper function to format values, replacing "0.0" with "--"
            $formatValue = function($val) {
                $formatted = number_format($val, 1, '.', '');
                return $formatted === '0.0' ? '--' : $formatted;
            };

            // Get the active progress images for the user
            $progressImage = UserProgressImage::where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            // Get all user appearance images
            $userAppearance = UsersAppearanceInfo::where('user_id', $userId)
                ->orderBy('id', 'desc')
                ->get();

            if ($userAppearance->count() > 0) {
                foreach ($userAppearance as $userEvolution) {
                    $setAs = 0; // Default to 0 (not set as before/after)

                    // Check if this image is set as before or after image
                    if ($progressImage) {
                        if ($progressImage->before_image_id == $userEvolution->id) {
                            $setAs = 1; // 1 for before image
                        } elseif ($progressImage->after_image_id == $userEvolution->id) {
                            $setAs = 2; // 2 for after image
                        }
                    }

                    $evolution[] = [
                        'id' => $userEvolution->id,
                        'imageDate' => $userEvolution->imageDate,
                        'image' => asset(Storage::url($userEvolution->image)),
                        'set_as' => $setAs // 0 = none, 1 = before, 2 = after
                    ];
                }
            }

            $userMeasurementData = UsersCurrentMeasurement::with('initialMeasurement', 'targetMeasurement')->where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
            $userPreviousMeasurementData = UsersCurrentMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->skip(1)->first();
            $userInitialMeasurement = UsersInitialMeasurement::where(['user_id' => Auth::user()->id])->first();
            $userTargetMeasurement = UsersTargetMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
            if (!empty($userMeasurementData)) {
                $userEvolutionData = [
                    [
                        "Name" => "Poids",
                        "slug" => "weight",
                        "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['weight'] : 0),
                        "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['weight'] : 0),
                        "Current" => $formatValue($userMeasurementData['weight'] ? $userMeasurementData['weight'] : 0),
                        "Previous" => $formatValue(
                            isset($userPreviousMeasurementData['weight']) && $userPreviousMeasurementData['weight'] !== null
                                ? $userPreviousMeasurementData['weight']
                                : (isset($userInitialMeasurement['weight']) && $userInitialMeasurement['weight'] !== null
                                    ? $userInitialMeasurement['weight']
                                    : 0)
                        ),
                        "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['weight'] : 0) - ($userMeasurementData['weight'] ? $userMeasurementData['weight'] : 0))),
                    ],
                    [
                        "Name" => "Taille",
                        "slug" => "waist",
                        "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['waist'] : 0),
                        "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['waist'] : 0),
                        "Current" => $formatValue($userMeasurementData['waist'] ? $userMeasurementData['waist'] : 0),
                        "Previous" => $formatValue(
                            isset($userPreviousMeasurementData['waist']) && $userPreviousMeasurementData['waist'] !== null
                                ? $userPreviousMeasurementData['waist']
                                : (isset($userInitialMeasurement['waist']) && $userInitialMeasurement['waist'] !== null
                                    ? $userInitialMeasurement['waist']
                                    : 0)
                        ),
                        "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['waist'] : 0) - ($userMeasurementData['waist'] ? $userMeasurementData['waist'] : 0))),
                    ],
                    [
                        "Name" => "Poitrine",
                        "slug" => "chest",
                        "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['chest'] : 0),
                        "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['chest'] : 0),
                        "Current" => $formatValue($userMeasurementData['chest'] ? $userMeasurementData['chest'] : 0),
                        "Previous" => $formatValue(
                            isset($userPreviousMeasurementData['chest']) && $userPreviousMeasurementData['chest'] !== null
                                ? $userPreviousMeasurementData['chest']
                                : (isset($userInitialMeasurement['chest']) && $userInitialMeasurement['chest'] !== null
                                    ? $userInitialMeasurement['chest']
                                    : 0)
                        ),
                        "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['chest'] : 0) - ($userMeasurementData['chest'] ? $userMeasurementData['chest'] : 0))),
                    ],
                    [
                        "Name" => "Biceps",
                        "slug" => "bicep",
                        "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['bicep'] : 0),
                        "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['bicep'] : 0),
                        "Current" => $formatValue($userMeasurementData['bicep'] ? $userMeasurementData['bicep'] : 0),
                        "Previous" => $formatValue(
                            isset($userPreviousMeasurementData['bicep']) && $userPreviousMeasurementData['bicep'] !== null
                                ? $userPreviousMeasurementData['bicep']
                                : (isset($userInitialMeasurement['bicep']) && $userInitialMeasurement['bicep'] !== null
                                    ? $userInitialMeasurement['bicep']
                                    : 0)
                        ),
                        "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['bicep'] : 0) - ($userMeasurementData['bicep'] ? $userMeasurementData['bicep'] : 0))),
                    ],
                    [
                        "Name" => "Cou",
                        "slug" => "neck",
                        "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['neck'] : 0),
                        "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['neck'] : 0),
                        "Current" => $formatValue($userMeasurementData['neck'] ? $userMeasurementData['neck'] : 0),
                        "Previous" => $formatValue(
                            isset($userPreviousMeasurementData['neck']) && $userPreviousMeasurementData['neck'] !== null
                                ? $userPreviousMeasurementData['neck']
                                : (isset($userInitialMeasurement['neck']) && $userInitialMeasurement['neck'] !== null
                                    ? $userInitialMeasurement['neck']
                                    : 0)
                        ),
                        "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['neck'] : 0) - ($userMeasurementData['neck'] ? $userMeasurementData['neck'] : 0))),
                    ],
                    [
                        "Name" => "Cuisses",
                        "slug" => "thighs",
                        "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['thighs'] : 0),
                        "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['thighs'] : 0),
                        "Current" => $formatValue($userMeasurementData['thighs'] ? $userMeasurementData['thighs'] : 0),
                        "Previous" => $formatValue(
                            isset($userPreviousMeasurementData['thighs']) && $userPreviousMeasurementData['thighs'] !== null
                                ? $userPreviousMeasurementData['thighs']
                                : (isset($userInitialMeasurement['thighs']) && $userInitialMeasurement['thighs'] !== null
                                    ? $userInitialMeasurement['thighs']
                                    : 0)
                        ),
                        "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['thighs'] : 0) - ($userMeasurementData['thighs'] ? $userMeasurementData['thighs'] : 0))),
                    ],
                    [
                        "Name" => "Mollets",
                        "slug" => "calfs",
                        "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['calfs'] : 0),
                        "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['calfs'] : 0),
                        "Current" => $formatValue($userMeasurementData['calfs'] ? $userMeasurementData['calfs'] : 0),
                        "Previous" => $formatValue(
                            isset($userPreviousMeasurementData['calfs']) && $userPreviousMeasurementData['calfs'] !== null
                                ? $userPreviousMeasurementData['calfs']
                                : (isset($userInitialMeasurement['calfs']) && $userInitialMeasurement['calfs'] !== null
                                    ? $userInitialMeasurement['calfs']
                                    : 0)
                        ),
                        "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['calfs'] : 0) - ($userMeasurementData['calfs'] ? $userMeasurementData['calfs'] : 0))),
                    ],
                    [
                        "Name" => "Hanches",
                        "slug" => "hips",
                        "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['hips'] : 0),
                        "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['hips'] : 0),
                        "Current" => $formatValue($userMeasurementData['hips'] ? $userMeasurementData['hips'] : 0),
                        "Previous" => $formatValue(
                            isset($userPreviousMeasurementData['hips']) && $userPreviousMeasurementData['hips'] !== null
                                ? $userPreviousMeasurementData['hips']
                                : (isset($userInitialMeasurement['hips']) && $userInitialMeasurement['hips'] !== null
                                    ? $userInitialMeasurement['hips']
                                    : 0)
                        ),
                        "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['hips'] : 0) - ($userMeasurementData['hips'] ? $userMeasurementData['hips'] : 0))),
                    ],
                ];
            } else {
                $userEvolutionData = [
                    [
                        "Name" => "Poids",
                        "slug" => "weight",
                        "Initial" => $formatValue($userInitialMeasurement->weight ?? 0),
                        "Target" => $formatValue($userTargetMeasurement->weight ?? 0),
                        "Current" => $formatValue($userInitialMeasurement->weight ?? 0),
                        "Previous" => $formatValue($userInitialMeasurement->weight ?? 0),
                        "Evolution" => $formatValue(0),
                    ],
                    [
                        "Name" => "Taille",
                        "slug" => "waist",
                        "Initial" => $formatValue($userInitialMeasurement->waist ?? 0),
                        "Target" => $formatValue($userTargetMeasurement->waist ?? 0),
                        "Current" => $formatValue($userInitialMeasurement->waist ?? 0),
                        "Previous" => $formatValue($userInitialMeasurement->waist ?? 0),
                        "Evolution" => $formatValue(0),
                    ],
                    [
                        "Name" => "Poitrine",
                        "slug" => "chest",
                        "Initial" => $formatValue($userInitialMeasurement->chest ?? 0),
                        "Target" => $formatValue($userTargetMeasurement->chest ?? 0),
                        "Current" => $formatValue($userInitialMeasurement->chest ?? 0),
                        "Previous" => $formatValue($userInitialMeasurement->chest ?? 0),
                        "Evolution" => $formatValue(0),
                    ],
                    [
                        "Name" => "Biceps",
                        "slug" => "bicep",
                        "Initial" => $formatValue($userInitialMeasurement->bicep ?? 0),
                        "Target" => $formatValue($userTargetMeasurement->bicep ?? 0),
                        "Current" => $formatValue($userInitialMeasurement->bicep ?? 0),
                        "Previous" => $formatValue($userInitialMeasurement->bicep ?? 0),
                        "Evolution" => $formatValue(0),
                    ],
                    [
                        "Name" => "Cou",
                        "slug" => "neck",
                        "Initial" => $formatValue($userInitialMeasurement->neck ?? 0),
                        "Target" => $formatValue($userTargetMeasurement->neck ?? 0),
                        "Current" => $formatValue($userInitialMeasurement->neck ?? 0),
                        "Previous" => $formatValue($userInitialMeasurement->neck ?? 0),
                        "Evolution" => $formatValue(0),
                    ],
                    [
                        "Name" => "Cuisses",
                        "slug" => "thighs",
                        "Initial" => $formatValue($userInitialMeasurement->thighs ?? 0),
                        "Target" => $formatValue($userTargetMeasurement->thighs ?? 0),
                        "Current" => $formatValue($userInitialMeasurement->thighs ?? 0),
                        "Previous" => $formatValue($userInitialMeasurement->thighs ?? 0),
                        "Evolution" => $formatValue(0),
                    ],
                    [
                        "Name" => "Mollets",
                        "slug" => "calfs",
                        "Initial" => $formatValue($userInitialMeasurement->calfs ?? 0),
                        "Target" => $formatValue($userTargetMeasurement->calfs ?? 0),
                        "Current" => $formatValue($userInitialMeasurement->calfs ?? 0),
                        "Previous" => $formatValue($userInitialMeasurement->calfs ?? 0),
                        "Evolution" => $formatValue(0),
                    ],
                    [
                        "Name" => "Hanches",
                        "slug" => "hips",
                        "Initial" => $formatValue($userInitialMeasurement->hips ?? 0),
                        "Target" => $formatValue($userTargetMeasurement->hips ?? 0),
                        "Current" => $formatValue($userInitialMeasurement->hips ?? 0),
                        "Previous" => $formatValue($userInitialMeasurement->hips ?? 0),
                        "Evolution" => $formatValue(0),
                    ],
                ];
            }

            $responseData['Evolution'] = $evolution;
            $responseData['Measurement'] = $userEvolutionData;
            return $this->successResponse("Data Found", $responseData, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function getUserMeasurements(Request $request)
    {
        // echo "<pre>";print_r(Auth::user()->id);die;
        try {
            $userMeasurement = UsersInitialMeasurement::where(['user_id' => Auth::user()->id])->first();
            $userCurrentMeasurement = UsersCurrentMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
            $userPreviousMeasurement = UsersCurrentMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->skip(1)->first();
            $userTargetMeasurement = UsersTargetMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
            $starting = (object)[];
            $targetGoals = (object)[];
            $current = (object)[];
            $previous = (object)[];


            // Helper function to format values, replacing "0.0" with "--"
            $formatValue = function($val) {
                $formatted = number_format($val, 1, '.', '');
                return $formatted === '0.0' ? '--' : $formatted;
            };

        if (!empty($userMeasurement)) {
            $starting = [
                'id' => $userMeasurement->id ?? 0,
                'user_id' => $userMeasurement->user_id ?? 0,
                'waist' => $formatValue($userMeasurement->waist ?? 0),
                'chest' => $formatValue($userMeasurement->chest ?? 0),
                'bicep' => $formatValue($userMeasurement->bicep ?? 0),
                'neck' => $formatValue($userMeasurement->neck ?? 0),
                'thighs' => $formatValue($userMeasurement->thighs ?? 0),
                'calfs' => $formatValue($userMeasurement->calfs ?? 0),
                'hips' => $formatValue($userMeasurement->hips ?? 0),
                'weight' => $formatValue($userMeasurement->weight ?? 0),
            ];
        }

        if (!empty($userCurrentMeasurement)) {
            $current = [
                'id' => $userCurrentMeasurement->id ?? 0,
                'user_id' => $userCurrentMeasurement->user_id ?? 0,
                'waist' => $formatValue($userCurrentMeasurement->waist ?? 0),
                'chest' => $formatValue($userCurrentMeasurement->chest ?? 0),
                'bicep' => $formatValue($userCurrentMeasurement->bicep ?? 0),
                'neck' => $formatValue($userCurrentMeasurement->neck ?? 0),
                'thighs' => $formatValue($userCurrentMeasurement->thighs ?? 0),
                'calfs' => $formatValue($userCurrentMeasurement->calfs ?? 0),
                'hips' => $formatValue($userCurrentMeasurement->hips ?? 0),
                'weight' => $formatValue($userCurrentMeasurement->weight ?? 0),
            ];
        }

        if (!empty($userPreviousMeasurement)) {
            $previous = [
                'id' => $userPreviousMeasurement->id ?? 0,
                'user_id' => $userPreviousMeasurement->user_id ?? 0,
                'waist' => $formatValue($userPreviousMeasurement->waist ?? 0),
                'chest' => $formatValue($userPreviousMeasurement->chest ?? 0),
                'bicep' => $formatValue($userPreviousMeasurement->bicep ?? 0),
                'neck' => $formatValue($userPreviousMeasurement->neck ?? 0),
                'thighs' => $formatValue($userPreviousMeasurement->thighs ?? 0),
                'calfs' => $formatValue($userPreviousMeasurement->calfs ?? 0),
                'hips' => $formatValue($userPreviousMeasurement->hips ?? 0),
                'weight' => $formatValue($userPreviousMeasurement->weight ?? 0),
            ];
        }

        if (!empty($userTargetMeasurement)) {
            $targetGoals = [
                'id' => $userTargetMeasurement->id ?? 0,
                'user_id' => $userTargetMeasurement->user_id ?? 0,
                'waist' => $formatValue($userTargetMeasurement->waist ?? 0),
                'chest' => $formatValue($userTargetMeasurement->chest ?? 0),
                'bicep' => $formatValue($userTargetMeasurement->bicep ?? 0),
                'neck' => $formatValue($userTargetMeasurement->neck ?? 0),
                'thighs' => $formatValue($userTargetMeasurement->thighs ?? 0),
                'calfs' => $formatValue($userTargetMeasurement->calfs ?? 0),
                'hips' => $formatValue($userTargetMeasurement->hips ?? 0),
                'weight' => $formatValue($userTargetMeasurement->weight ?? 0),
            ];
        }


            $responseData['starting'] = $starting;
            $responseData['currentMeasurement'] = $current;
            $responseData['previous'] = $previous;
            $responseData['targetGoals'] = $targetGoals;

            if (!empty($responseData['starting']) || !empty($responseData['targetGoals'])) {
                return $this->successResponse("Data Found", $responseData, 200);
            } else {
                return $this->successResponse("Data Not Found", (object)[], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function deleteEvolution(Request $request)
    {
        try {
            $rules = [
                'id' => ['required', 'integer']
            ];

            $message = [
                'id.required' => "Plese enter evolution id.",
                'id.integer' => "Id should be a numeric value."
            ];

            $validator = Validator::make($request->all(), $rules, $message);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            if (UsersAppearanceInfo::where(['id' => $request->id, 'user_id' => Auth::user()->id])->count() > 0) {
                UsersAppearanceInfo::where(['id' => $request->id, 'user_id' => Auth::user()->id])->delete();

                if (UsersAppearanceInfo::where(['user_id' => Auth::user()->id])->count() > 0) {
                   $userAppearance = UsersAppearanceInfo::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->get();
                    // print_r($userAppearance); die;
                    $evolution = [];
                    foreach ($userAppearance as $key => $userEvolution) {
                        $evolution[] = [
                            'id' => $userEvolution['id'],
                            'imageDate' => $userEvolution['imageDate'],
                            'image' => asset(Storage::url($userEvolution['image']))
                        ];
                    }
                } else {
                    $evolution = [];
                }

                // Helper function to format values, replacing "0.0" with "--"
                $formatValue = function($val) {
                    $formatted = number_format($val, 1, '.', '');
                    return $formatted === '0.0' ? '--' : $formatted;
                };

                $userMeasurementData = UsersCurrentMeasurement::with('initialMeasurement', 'targetMeasurement')->where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
                $userPreviousMeasurementData = UsersCurrentMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->skip(1)->first();
                $userInitialMeasurement = UsersInitialMeasurement::where(['user_id' => Auth::user()->id])->first();
                $userTargetMeasurement = UsersTargetMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
                if (!empty($userMeasurementData)) {
                    $userEvolutionData = [
                        [
                            "Name" => "Poids",
                            "slug" => "weight",
                            "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['weight'] : 0),
                            "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['weight'] : 0),
                            "Current" => $formatValue($userMeasurementData['weight'] ? $userMeasurementData['weight'] : 0),
                            "Previous" => $formatValue(
                                isset($userPreviousMeasurementData['weight']) && $userPreviousMeasurementData['weight'] !== null
                                    ? $userPreviousMeasurementData['weight']
                                    : (isset($userMeasurementData['weight']) && $userMeasurementData['weight'] !== null
                                        ? $userMeasurementData['weight']
                                        : 0)
                            ),
                            "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['weight'] : 0) - ($userMeasurementData['weight'] ? $userMeasurementData['weight'] : 0))),
                        ],
                        [
                            "Name" => "Taille",
                            "slug" => "waist",
                            "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['waist'] : 0),
                            "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['waist'] : 0),
                            "Current" => $formatValue($userMeasurementData['waist'] ? $userMeasurementData['waist'] : 0),
                            "Previous" => $formatValue(
                                isset($userPreviousMeasurementData['waist']) && $userPreviousMeasurementData['waist'] !== null
                                    ? $userPreviousMeasurementData['waist']
                                    : (isset($userMeasurementData['waist']) && $userMeasurementData['waist'] !== null
                                        ? $userMeasurementData['waist']
                                        : 0)
                            ),
                            "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['waist'] : 0) - ($userMeasurementData['waist'] ? $userMeasurementData['waist'] : 0))),
                        ],
                        [
                            "Name" => "Poitrine",
                            "slug" => "chest",
                            "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['chest'] : 0),
                            "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['chest'] : 0),
                            "Current" => $formatValue($userMeasurementData['chest'] ? $userMeasurementData['chest'] : 0),
                            "Previous" => $formatValue(
                                isset($userPreviousMeasurementData['chest']) && $userPreviousMeasurementData['chest'] !== null
                                    ? $userPreviousMeasurementData['chest']
                                    : (isset($userMeasurementData['chest']) && $userMeasurementData['chest'] !== null
                                        ? $userMeasurementData['chest']
                                        : 0)
                            ),
                            "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['chest'] : 0) - ($userMeasurementData['chest'] ? $userMeasurementData['chest'] : 0))),
                        ],
                        [
                            "Name" => "Biceps",
                            "slug" => "bicep",
                            "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['bicep'] : 0),
                            "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['bicep'] : 0),
                            "Current" => $formatValue($userMeasurementData['bicep'] ? $userMeasurementData['bicep'] : 0),
                            "Previous" => $formatValue(
                                isset($userPreviousMeasurementData['bicep']) && $userPreviousMeasurementData['bicep'] !== null
                                    ? $userPreviousMeasurementData['bicep']
                                    : (isset($userMeasurementData['bicep']) && $userMeasurementData['bicep'] !== null
                                        ? $userMeasurementData['bicep']
                                        : 0)
                            ),
                            "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['bicep'] : 0) - ($userMeasurementData['bicep'] ? $userMeasurementData['bicep'] : 0))),
                        ],
                        [
                            "Name" => "Cou",
                            "slug" => "neck",
                            "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['neck'] : 0),
                            "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['neck'] : 0),
                            "Current" => $formatValue($userMeasurementData['neck'] ? $userMeasurementData['neck'] : 0),
                            "Previous" => $formatValue(
                                isset($userPreviousMeasurementData['neck']) && $userPreviousMeasurementData['neck'] !== null
                                    ? $userPreviousMeasurementData['neck']
                                    : (isset($userMeasurementData['neck']) && $userMeasurementData['neck'] !== null
                                        ? $userMeasurementData['neck']
                                        : 0)
                            ),
                            "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['neck'] : 0) - ($userMeasurementData['neck'] ? $userMeasurementData['neck'] : 0))),
                        ],
                        [
                            "Name" => "Cuisses",
                            "slug" => "thighs",
                            "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['thighs'] : 0),
                            "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['thighs'] : 0),
                            "Current" => $formatValue($userMeasurementData['thighs'] ? $userMeasurementData['thighs'] : 0),
                            "Previous" => $formatValue(
                                isset($userPreviousMeasurementData['thighs']) && $userPreviousMeasurementData['thighs'] !== null
                                    ? $userPreviousMeasurementData['thighs']
                                    : (isset($userMeasurementData['thighs']) && $userMeasurementData['thighs'] !== null
                                        ? $userMeasurementData['thighs']
                                        : 0)
                            ),
                            "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['thighs'] : 0) - ($userMeasurementData['thighs'] ? $userMeasurementData['thighs'] : 0))),
                        ],
                        [
                            "Name" => "Mollets",
                            "slug" => "calfs",
                            "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['calfs'] : 0),
                            "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['calfs'] : 0),
                            "Current" => $formatValue($userMeasurementData['calfs'] ? $userMeasurementData['calfs'] : 0),
                            "Previous" => $formatValue(
                                isset($userPreviousMeasurementData['calfs']) && $userPreviousMeasurementData['calfs'] !== null
                                    ? $userPreviousMeasurementData['calfs']
                                    : (isset($userMeasurementData['calfs']) && $userMeasurementData['calfs'] !== null
                                        ? $userMeasurementData['calfs']
                                        : 0)
                            ),
                            "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['calfs'] : 0) - ($userMeasurementData['calfs'] ? $userMeasurementData['calfs'] : 0))),
                        ],
                        [
                            "Name" => "Hanches",
                            "slug" => "hips",
                            "Initial" => $formatValue($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['hips'] : 0),
                            "Target" => $formatValue($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['hips'] : 0),
                            "Current" => $formatValue($userMeasurementData['hips'] ? $userMeasurementData['hips'] : 0),
                            "Previous" => $formatValue(
                                isset($userPreviousMeasurementData['hips']) && $userPreviousMeasurementData['hips'] !== null
                                    ? $userPreviousMeasurementData['hips']
                                    : (isset($userMeasurementData['hips']) && $userMeasurementData['hips'] !== null
                                        ? $userMeasurementData['hips']
                                        : 0)
                            ),
                            "Evolution" => $formatValue(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['hips'] : 0) - ($userMeasurementData['hips'] ? $userMeasurementData['hips'] : 0))),
                        ],
                    ];
                } else {
                    $userEvolutionData = [
                        [
                            "Name" => "Poids",
                            "slug" => "weight",
                            "Initial" => $formatValue($userInitialMeasurement->weight ?? 0),
                            "Target" => $formatValue($userTargetMeasurement->weight ?? 0),
                            "Current" => $formatValue($userInitialMeasurement->weight ?? 0),
                            "Previous" => $formatValue($userInitialMeasurement->weight ?? 0),
                            "Evolution" => $formatValue(0),
                        ],
                        [
                            "Name" => "Taille",
                            "slug" => "waist",
                            "Initial" => $formatValue($userInitialMeasurement->waist ?? 0),
                            "Target" => $formatValue($userTargetMeasurement->waist ?? 0),
                            "Current" => $formatValue($userInitialMeasurement->waist ?? 0),
                            "Previous" => $formatValue($userInitialMeasurement->waist ?? 0),
                            "Evolution" => $formatValue(0),
                        ],
                        [
                            "Name" => "Poitrine",
                            "slug" => "chest",
                            "Initial" => $formatValue($userInitialMeasurement->chest ?? 0),
                            "Target" => $formatValue($userTargetMeasurement->chest ?? 0),
                            "Current" => $formatValue($userInitialMeasurement->chest ?? 0),
                            "Previous" => $formatValue($userInitialMeasurement->chest ?? 0),
                            "Evolution" => $formatValue(0),
                        ],
                        [
                            "Name" => "Biceps",
                            "slug" => "bicep",
                            "Initial" => $formatValue($userInitialMeasurement->bicep ?? 0),
                            "Target" => $formatValue($userTargetMeasurement->bicep ?? 0),
                            "Current" => $formatValue($userInitialMeasurement->bicep ?? 0),
                            "Previous" => $formatValue($userInitialMeasurement->bicep ?? 0),
                            "Evolution" => $formatValue(0),
                        ],
                        [
                            "Name" => "Cou",
                            "slug" => "neck",
                            "Initial" => $formatValue($userInitialMeasurement->neck ?? 0),
                            "Target" => $formatValue($userTargetMeasurement->neck ?? 0),
                            "Current" => $formatValue($userInitialMeasurement->neck ?? 0),
                            "Previous" => $formatValue($userInitialMeasurement->neck ?? 0),
                            "Evolution" => $formatValue(0),
                        ],
                        [
                            "Name" => "Cuisses",
                            "slug" => "thighs",
                            "Initial" => $formatValue($userInitialMeasurement->thighs ?? 0),
                            "Target" => $formatValue($userTargetMeasurement->thighs ?? 0),
                            "Current" => $formatValue($userInitialMeasurement->thighs ?? 0),
                            "Previous" => $formatValue($userInitialMeasurement->thighs ?? 0),
                            "Evolution" => $formatValue(0),
                        ],
                        [
                            "Name" => "Mollets",
                            "slug" => "calfs",
                            "Initial" => $formatValue($userInitialMeasurement->calfs ?? 0),
                            "Target" => $formatValue($userTargetMeasurement->calfs ?? 0),
                            "Current" => $formatValue($userInitialMeasurement->calfs ?? 0),
                            "Previous" => $formatValue($userInitialMeasurement->calfs ?? 0),
                            "Evolution" => $formatValue(0),
                        ],
                        [
                            "Name" => "Hanches",
                            "slug" => "hips",
                            "Initial" => $formatValue($userInitialMeasurement->hips ?? 0),
                            "Target" => $formatValue($userTargetMeasurement->hips ?? 0),
                            "Current" => $formatValue($userInitialMeasurement->hips ?? 0),
                            "Previous" => $formatValue($userInitialMeasurement->hips ?? 0),
                            "Evolution" => $formatValue(0),
                        ],
                    ];
                }

                $responseData['Evolution'] = $evolution;
                $responseData['Measurement'] = $userEvolutionData;
                return $this->successResponse("User transformation image deleted successfully", $responseData, 200);
            } else {
                return $this->successResponse("Data Not Found.", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}
