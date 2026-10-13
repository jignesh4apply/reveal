<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StepsGoal;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StepTrackerController extends ApiController
{
    public function setStepsGoal(Request $request)
    {
        try {
            // Validation
            $validation = [
                'steps_goal' => ['required', 'integer', 'numeric'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $data = $request->all();

            $goalDate = new DateTime('now');
            $goalDate = $goalDate->format('Y-m-d');

            $stepsGoalCount = StepsGoal::where(['user_id' => Auth::user()->id, 'goal_date' => $goalDate])->count();

            if ($stepsGoalCount > 0) {
                return $this->successResponse("You have already added your todays goal", (object)[], 200);
            }

            $setStepsGoal = new StepsGoal;
            $setStepsGoal->steps_goal = $data['steps_goal'];
            $setStepsGoal->user_id = Auth::user()->id;
            $setStepsGoal->goal_date = $goalDate;
            if ($setStepsGoal->save()) {
                return $this->getSingleResponse("Your steps goal saved successfully.", $setStepsGoal, 200);
            } else {
                return $this->errorResponse("Something went wrong.", (object)[], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function updateStepsGoal(Request $request)
    {
        try {
            // Validation
            $validation = [
                'step_goal_id' => ['required', 'integer', 'numeric'],
                'steps_goal' => ['required', 'integer', 'numeric']
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $data = $request->all();

            $goalDate = new DateTime('now');
            $goalDate = $goalDate->format('Y-m-d');

            // Check if step goal exists or not
            $getStepsGoalCount = StepsGoal::where(['id' => $data['step_goal_id'], 'user_id' => Auth::user()->id])->count();

            if ($getStepsGoalCount > 0) {
                StepsGoal::where(['id' => $data['step_goal_id'], 'user_id' => Auth::user()->id])->update(['steps_goal' => $data['steps_goal']]);
                $updatedRecord = StepsGoal::where(['id' => $data['step_goal_id'], 'user_id' => Auth::user()->id])->first()->toArray();
                return $this->getSingleResponse("Steps Goal updated successfully", $updatedRecord, 200);
            } else {
                return $this->successResponse("Data Not Found", (object)[], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    // public function stepsTracker(Request $request)
    // {
    //     try {
    //         // Validation
    //         $validation = [
    //             'data' => ['required', 'array'],
    //             'data.*.goal_date' => ['required', 'date', 'date_format:Y-m-d'],
    //             'data.*.goal_time' => ['required', 'date_format:H:i'],
    //             'data.*.stepCount' => ['required', 'numeric'],
    //         ];

    //         $validator = Validator::make($request->all(), $validation);

    //         if ($validator->fails()) {
    //             return $this->validationError("Fail", $validator->errors()->first(), 200);
    //         }

    //         $goalDate = new DateTime('now');
    //         $goalDate = $goalDate->format('Y-m-d');
    //         // Get Steps Goal
    //         $goal = StepsGoal::where(['user_id' => Auth::user()->id, 'goal_date' => $goalDate])->first();

    //         if (!empty($goal)) {
    //             foreach ($request->data as $stepData) {
    //                 $getStepsGoal = StepsGoal::where(['user_id' => Auth::user()->id, 'goal_date' => $goalDate])->get();
    //                 $totalSteps = $getStepsGoal->sum('steps_count') ? $getStepsGoal->sum('steps_count') : 0;
    //                 $stepsCompleted = $totalSteps + $stepData['stepCount'];
    //                 $stepsRemaining = $goal->steps_goal - $stepsCompleted;

    //                 $stepTracker = new StepsGoal;
    //                 $stepTracker->user_id = Auth::user()->id;
    //                 $stepTracker->steps_goal = $goal['steps_goal'];
    //                 $stepTracker->steps_count = $stepData['stepCount'];
    //                 $stepTracker->steps_completed = $stepsCompleted;
    //                 $stepTracker->steps_remaining = $stepsRemaining;
    //                 $stepTracker->goal_date = $stepData['goal_date'];
    //                 $stepTracker->goal_time = $stepData['goal_time'];
    //                 $stepTracker->save();
    //             }
    //             $getSteps = StepsGoal::where(['user_id' => Auth::user()->id, 'goal_date' => $goalDate])->orderBy('id', 'desc')->first()->toArray();

    //             if ($getSteps['steps_completed'] == 0 || $getSteps['steps_remaining'] < 0) {
    //                 $isCompleted = 1;
    //             } else {
    //                 $isCompleted = 0;
    //             }

    //             if ($isCompleted == 1) {
    //                 return $this->getSingleResponse("Your goal completed successfully.", $getSteps, 200);
    //             }
    //             return $this->getSingleResponse("Your steps count updated successfully.", $getSteps, 200);
    //         } else {
    //             return $this->errorResponse("Goal Not Found", (object)[], 200);
    //         }
    //     } catch (Throwable $th) {
    //         return $this->throwableError("Something went wrong", $th->getMessage(), 500);
    //     }
    // }

    public function stepsTracker(Request $request)
    {
        try {
            // Validation
            $validation = [
                'stepCount' => ['required', 'numeric'],
                'distance' => ['required'],
            ];

            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $data = $request->all();

            $goalDate = new DateTime('now');
            $goalDate = $goalDate->format('Y-m-d');
            // Get Steps Goal
            $stepRecord = StepsGoal::where(['user_id' => Auth::user()->id, 'goal_date' => $goalDate])->first();

            if ($stepRecord) {
                // Check if existing steps_completed is greater than or equal to new stepCount
                if ($stepRecord['steps_completed'] < $data['stepCount']) {
                    $totalSteps = $data['stepCount'];
                    $stepsRemaining = $stepRecord->steps_goal - $totalSteps;

                    $stepRecord->update([
                        'distance' => $data['distance'],
                        'steps_completed' => $totalSteps,
                        'steps_remaining' => $stepsRemaining,
                        'isCompleted' => $stepsRemaining <= 0 ? 1 : 0,
                    ]);
                }
            } else {
                $yesterdaysStepRecord = StepsGoal::where(['user_id' => Auth::user()->id])->latest()->first();
                if(!$yesterdaysStepRecord) {
                    return $this->errorResponse("Goal Not Found", (object)[], 200);
                } else {
                    $stepsRemaining = $yesterdaysStepRecord['steps_goal'] - $data['stepCount'];
                    $stepRecord = StepsGoal::create([
                        'user_id' => Auth::user()->id,
                        'steps_goal' => $yesterdaysStepRecord['steps_goal'],
                        'distance' => $data['distance'],
                        'steps_completed' => $data['stepCount'],
                        'steps_remaining' => $stepsRemaining,
                        'goal_date' => $goalDate,
                        'isCompleted' => $stepsRemaining <= 0 ? 1 : 0,
                    ]);
                }
            }

            $stepRecordData = [
                'id' => $stepRecord->id,
                'steps_goal' => $stepRecord->steps_goal,
                'distance' => $stepRecord->distance,
                'steps_completed' => $stepRecord->steps_completed,
                'steps_remaining' => $stepRecord->steps_remaining,
                'goal_date' => $stepRecord->goal_date,
                'isCompleted' => $stepRecord->isCompleted,
            ];

            if ($stepRecord['steps_completed'] >= $stepRecord['steps_goal']) {
                return $this->successResponse("Your goal completed successfully.", $stepRecordData, 200);
            }

            return $this->successResponse("Your steps count updated successfully.", $stepRecordData, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function getSteps(Request $request)
    {
        try {
            $getSteps = StepsGoal::where(['user_id' => Auth::user()->id])->first();
            if (!empty($getSteps)) {
                return $this->successResponse("Your steps count updated successfully.", $getSteps, 200);
            } else {
                return $this->successResponse("No Data Found.", (object)[], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}
