<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;

use App\Http\Controllers\API\ChallengeController as APIChallengeControllerV1;
use App\Models\ChallengeDay;
use App\Models\ChallengeUser;
use App\Models\ChallengeUserStatus;
use App\Models\TaskMilestone;
use App\Models\UserLevelTaskLog;
use App\Models\userMilestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChallengeController extends APIChallengeControllerV1
{
    public function logChallengeDay(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'challenge_day_id' => 'required'
            ], [
                'challenge_day_id.required' => 'Please add challenge day id.'
            ]);
            
            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $userId = Auth::user()->id;
            $challenge_day_id = $request->challenge_day_id;
            $todayDate = date('Y-m-d');

            $challengeDay = ChallengeDay::find($challenge_day_id);
            if (!$challengeDay) {
                return $this->notFoundResponse("Challenge day is not found!", (object)[], 200);
            }
    
            $completed_day = $challengeDay->day; // Get the completed day based on the ChallengeDay record
    
            $alreadyCompletedToday = ChallengeUserStatus::where('user_id', $userId)
                ->where('complete_date', $todayDate)
                ->where('completed_day', $completed_day) // Check by completed_day instead of challenge_day_id
                ->whereHas('challengeDay', function ($query) use ($challengeDay) {
                    $query->where('challenge_id', $challengeDay->challenge_id)
                          ->where('challenge_level_id', $challengeDay->challenge_level_id);
                })
                ->exists();

            if ($alreadyCompletedToday) {
                return $this->successResponse("You have already completed a challenge today.", (object)[], 200);
            }
    
            $userChallengeData = ChallengeUser::where(['user_id' => $userId, 'challenge_id' => $challengeDay->challenge_id, 'challenge_level_id' => $challengeDay->challenge_level_id, 'status' => 1])->first();
            if (!$userChallengeData) {
                return $this->notFoundResponse("Challenge is not found!", (object)[], 200);
            }

            // Check if the user has already completed this day, regardless of challenge_day_id
            $completedDay = ChallengeUserStatus::where(['user_id' => $userId, 'challenge_id' => $challengeDay->challenge_id, 'challenge_level_id' => $challengeDay->challenge_level_id, 'completed_day' => $completed_day,])->first();

            if ($completedDay) {
                return $this->successResponse("Challenge day is already completed.", $completedDay, 200);
            }
    
            // Ensure previous challenge days are completed
            $previousChallengeDays = ChallengeDay::where('challenge_id', $challengeDay->challenge_id)
                ->where('challenge_level_id', $challengeDay->challenge_level_id)
                ->whereRaw('CAST(day AS UNSIGNED) < ?', [$challengeDay->day])
                ->pluck('day') // Now fetching day instead of ID
                ->toArray();
    
            if (!empty($previousChallengeDays)) {
                $previousCompletedDays = ChallengeUserStatus::where('user_id', $userId)
                    ->where('challenge_id', $challengeDay->challenge_id)
                    ->where('challenge_level_id', $challengeDay->challenge_level_id)
                    ->pluck('completed_day') // Now checking by completed_day
                    ->toArray();

                if (array_diff($previousChallengeDays, $previousCompletedDays)) {
                    return $this->successResponse("Please complete previous days before logging this challenge day.", [], 200);
                }
            }

            // Store challenge day completion
            $challengeUserStatus = new ChallengeUserStatus;
            $challengeUserStatus->user_id = $userId;
            $challengeUserStatus->challenge_day_id = $challenge_day_id;
            $challengeUserStatus->challenge_id = $challengeDay->challenge_id;
            $challengeUserStatus->challenge_level_id = $challengeDay->challenge_level_id;
            $challengeUserStatus->completed_day = $completed_day; // Store by day instead of challenge_day_id
            $challengeUserStatus->status = 1;
            $challengeUserStatus->complete_date = $todayDate;
            $challengeUserStatus->save();


            $levelDetails = null;
            $levelResponse = $this->logLevelTaskEntries($userId, 'challenge',1,0);
            if ($levelResponse != 0) {
                $levelDetails = $this->getLevelDetails($levelResponse);
            }

            $milestoneCompletedArray = [];

            $workoutMilestones = TaskMilestone::where('milestone_type', 'challenge')->get();

            $userCurrentMilestones = userMilestone::where([
                'user_id' => $userId,
                'milestone_type' => 'challenge'
            ])->pluck('milestone_id')->toArray();

            $userChallengeCompleteCount = UserLevelTaskLog::where([
                'user_id' => $userId,
                'level_task_type' => 'challenge'
            ])->sum('completed_count');

            foreach ($workoutMilestones as $milestone) {
                if (
                    $userChallengeCompleteCount >= $milestone->milestone_count &&
                    !in_array($milestone->id, $userCurrentMilestones)
                ) {
                    $milestoneCompletedArray[] = [
                        'type' => 'challenge',
                        'color' => 'yellow',
                        'title' => $milestone->milestone_title,
                        'description' => $milestone->milestone_description,
                        'count' => $milestone->milestone_count,
                    ];

                    // Save to user_milestones table
                    UserMilestone::create([
                        'user_id' => $userId,
                        'milestone_id' => $milestone->id,
                        'milestone_type' => 'challenge',
                    ]);
                }
            }

            $challengeUserStatus['milestoneCompletedArray'] = $milestoneCompletedArray;
            $challengeUserStatus['levelCompletedDetails'] = $levelDetails ?: (object)[];

            return $this->successResponse("Challenge day completed successfully.", $challengeUserStatus, 200);
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}
