<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\StepTrackerController as APIStepTrackerControllerV1;
use App\Models\StepsGoal;
use App\Models\TaskMilestone;
use App\Models\UserLevelTaskLog;
use App\Models\userMilestone;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StepTrackerController extends APIStepTrackerControllerV1
{
    public function stepsTracker(Request $request)
    {
        try {
            // Validation
            $validation = [
                'stepCount' => ['required', 'numeric'],
                'distance' => ['required'],
                'goal_date' => ['sometimes', 'date', 'date_format:Y-m-d'], // Allow manual date
            ];

            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $data = $request->all();

            $userTz = Auth::user()->timezone ?? "Europe/Paris";
            $currentDate = new DateTime('now', new \DateTimeZone($userTz));
            $currentDate = $currentDate->format('Y-m-d');

            // Use manual date if provided, otherwise use today
            $goalDate = isset($data['goal_date']) && !empty($data['goal_date'])
                ? $data['goal_date']
                : $currentDate;


            // Get Steps Goal
            $stepRecord = StepsGoal::where(['user_id' => Auth::user()->id, 'goal_date' => $goalDate])->first();
            $levelDetails = null;
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

                    $levelResponse = $this->logLevelTaskEntries(Auth::user()->id, 'walks',$totalSteps,1);
                    if ($levelResponse != 0) {
                        $levelDetails = $this->getLevelDetails($levelResponse);
                    }
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
                    
                    $levelResponse = $this->logLevelTaskEntries(Auth::user()->id, 'walks',$data['stepCount'],0);
                    if ($levelResponse != 0) {
                        $levelDetails = $this->getLevelDetails($levelResponse);
                    }
                }
            }

            $milestoneCompletedArray = [];

            $walksMilestones = TaskMilestone::where('milestone_type', 'walks')->get();

            $userCurrentMilestones = userMilestone::where([
                'user_id' => Auth::user()->id,
                'milestone_type' => 'walks'
            ])->pluck('milestone_id')->toArray();

            $userWalksCompleteCount = UserLevelTaskLog::where([
                'user_id' => Auth::user()->id,
                'level_task_type' => 'walks'
            ])->sum('completed_count');

            foreach ($walksMilestones as $milestone) {
                if (
                    $userWalksCompleteCount >= $milestone->milestone_count &&
                    !in_array($milestone->id, $userCurrentMilestones)
                ) {
                    $milestoneCompletedArray[] = [
                        'type' => 'walks',
                        'color' => 'light-blue',
                        'title' => $milestone->milestone_title,
                        'description' => $milestone->milestone_description,
                        'count' => $milestone->milestone_count,
                    ];

                    // Save to user_milestones table
                    UserMilestone::create([
                        'user_id' => Auth::user()->id,
                        'milestone_id' => $milestone->id,
                        'milestone_type' => 'walks',
                    ]);
                }
            }

            $stepRecordData = [
                'id' => $stepRecord->id,
                'steps_goal' => $stepRecord->steps_goal,
                'distance' => $stepRecord->distance,
                'steps_completed' => (string) $stepRecord->steps_completed,
                'steps_remaining' => $stepRecord->steps_remaining,
                'goal_date' => $stepRecord->goal_date,
                'isCompleted' => $stepRecord->isCompleted,
                'milestoneCompletedArray' => $milestoneCompletedArray,
                'levelCompletedDetails' => $levelDetails ?: (object)[],
            ];

            if ($stepRecord['steps_completed'] >= $stepRecord['steps_goal']) {
                return $this->successResponse("Your goal completed successfully.", $stepRecordData, 200);
            }

            return $this->successResponse("Your steps count updated successfully.", $stepRecordData, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}
