<?php

namespace App\Http\Controllers\API\V3;

use App\Http\Controllers\API\V2\DashboardController as APIDashboardControllerV2;
use App\Models\Lesson;
use App\Models\LessonsPlanner;
use App\Models\UserMorphology;
use App\Models\UserMorphologyBehaviourTest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DashboardController extends APIDashboardControllerV2
{
    public function dashboard(Request $request)
    {
        try {
            $userAssignedProgram = $this->getUserAssignedProgram();
            $stepsTracking = $this->stepsTracking();
            $currentWeight = $this->currentWeight();
            $isMealLogged = $this->isLoggedMeal();
            $lessons = $this->lessons();
            $userData = $this->userData($request);
            $latestImageData = $this->latestImageData();
            $maintainanceMode = $this->maintainanceMode();
            $isWeek1Completed = $this->isWeek1Completed();
            $isChallengePicked = $this->onBoardingChallengeStatusInDashBoard();
            $UserChallenge = $this->UserChallengeDetails();
            $userLevelDetails = $this->userLevelDetails();
            $userPreviousLevelDetails = $this->userPreviousLevelDetails();
            $morphologyData = $this->morphologyData();

            $response = [
                [
                    'name' => "TopRecipes",
                    'topRecipes' => []
                ],
                    $currentWeight,
                [
                    'name' => "UserAssignedProgram",
                    'assignedProgram' => $userAssignedProgram
                ],
                [
                    'name' => "StepsTracking",
                    'stepsTracking' => $stepsTracking
                ],
                [
                    'name' => "MealLoggedInfo",
                    'mealWiseLogDeatails' => $isMealLogged
                ],
                [
                    'name' => "Lessons",
                    'dailyLesson' => $lessons
                ],
                [
                    'name' => "UserData",
                    'userData' => $userData
                ],
                [
                    'name' => "Evolution",
                    'latestImageData' => $latestImageData
                ],
                [
                    'isWeek1Completed' => $isWeek1Completed
                ],
                [
                    'maintainanceMode' => $maintainanceMode
                ],
                [   'name' => 'onboarding-challenge-status',
                    'ChallengeStatus' => $isChallengePicked
                ],
                [
                    'name' => 'UserChallenge',
                    'currentChallenge' => $UserChallenge
                ],
                [
                    'name' => 'UserLevels',
                    'userLevelDetails' => $userLevelDetails
                ],
                [
                    'name' => 'UserPreviousLevels',
                    'userPreviousLevelDetails' => $userPreviousLevelDetails
                ],
                [
                    'name' => 'UserMorphologyData',
                    'morphologyData' => $morphologyData
                ]
            ];

            return $this->successResponse("Data Found", $response, 200);
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    protected function morphologyData()
    {
        $userMorphologies = UserMorphology::where('user_id', Auth::user()->id)->get();
        $behaviourTests = UserMorphologyBehaviourTest::where('user_id', Auth::user()->id)->get();

        foreach ($behaviourTests as $bt) {
            $bt->type = 'Behaviour Test';
            $bt->generation_status = $bt->status;
        }

        $morphologies = $userMorphologies->concat($behaviourTests)->sortBy('created_at')->values();

        $reports = [];

        $baselineReport = $morphologies->first(function ($m) {
            return $m->type === 'Baseline Report' && $m->generation_status === 'completed';
        });

        $baselineDate = null;
        if ($baselineReport) {
            $baselineDate = Carbon::parse($baselineReport->report_generated_at ?? $baselineReport->updated_at)->startOfDay();
        }

        if ($morphologies->isEmpty()) {
            return [
                [
                    'is_locked' => 0,
                    'title' => 'Baseline Report',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => 0,
                    'show_finish_test_btn' => 0,
                    'current_day' => 0,
                ],
                [
                    'is_locked' => 1,
                    'title' => 'Behaviour Test',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => 15,
                    'show_finish_test_btn' => 0,
                    'current_day' => 0,
                ],
                [
                    'is_locked' => 1,
                    'title' => 'Progress Report',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => 30,
                    'show_finish_test_btn' => 0,
                    'current_day' => 0,
                ]
            ];
        }

        foreach ($morphologies as $index => $morphology) {

            // =========================
            // Completeness Score Logic
            // =========================

            $completenessScore = 0;
            $showFinishTestBtn = 0;

            if ($morphology->type === 'Baseline Report' || $morphology->type === 'Progress Report') {

                // Basic Info Completion
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
                $primaryMeasurementFields = [
                    'waist',
                    'hips',
                    'neck',
                ];

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

                // Determine whether scoring should be calculated
                $canCalculateScore = false;

                if ($morphology->type === 'Baseline Report') {
                    $canCalculateScore = ($basicInfoCompleted == 1 && $goalSummaryCompleted == 1);
                } elseif ($morphology->type === 'Progress Report') {
                    $canCalculateScore = !empty($morphology->weight);
                }

                // Final Completeness Score
                if ($canCalculateScore) {

                    if ($measurementStatus == 0 && $pictureStatus == 0) {
                        $completenessScore = 45;
                    } elseif ($measurementStatus == 0 && ($pictureStatus == 1 || $pictureStatus == 2)) {
                        $completenessScore = 60;
                    } elseif ($measurementStatus == 1 || $measurementStatus == 2) {
                        $completenessScore = 100;
                    }
                }

                // Score is 0 if report is not generated
                if ($morphology->generation_status !== 'completed') {
                    $completenessScore = 0;
                }
                $canShowFinishButton = false;

                if ($morphology->type === 'Baseline Report') {
                    $canShowFinishButton = ($basicInfoCompleted == 1);
                } elseif ($morphology->type === 'Progress Report') {
                    $canShowFinishButton = !empty($morphology->weight);
                }

                if ($canShowFinishButton) {
                    $showFinishTestBtn = 1;
                }

                if ($measurementStatus == 1 || $measurementStatus == 2) {
                    $showFinishTestBtn = 0;
                }

            } elseif ($morphology->type === 'Behaviour Test') {
                if ($morphology->generation_status === 'completed') {
                    $completenessScore = 100;
                } else {
                    $completenessScore = 0;
                }
            }

            $currentDay = 0;
            if ($morphology->type !== 'Baseline Report' && $baselineDate) {
                if ($morphology->generation_status === 'completed') {
                    $reportDate = Carbon::parse($morphology->report_generated_at ?? $morphology->updated_at)->startOfDay();
                    $currentDay = max(0, intval($baselineDate->diffInDays($reportDate, false)));
                } else {
                    $today = Carbon::now()->startOfDay();
                    $currentDay = max(0, intval($baselineDate->diffInDays($today, false)));
                }
            }

            $reports[] = [
                'is_locked' => 0,
                'title' => $morphology->type,
                'report_date' => $morphology->report_generated_at ? Carbon::parse($morphology->report_generated_at)->format('d F Y') : '',
                'completeness_score' => $completenessScore,
                'is_report_completed' => $morphology->generation_status === 'completed' ? 1 : 0,
                'session_id' => $morphology->session_id ?? '',
                'unlock_in_days' => 0,
                'show_finish_test_btn' => $showFinishTestBtn,
                'current_day' => $currentDay,
            ];
        }

        // =========================================
        // Append Next Upcoming Report Logic
        // =========================================

        $lastReportObj = $morphologies->last();
        $lastReportArray = end($reports);

        $now = Carbon::now()->startOfDay();
        $appendedCurrentDay = $baselineDate ? max(0, intval($baselineDate->diffInDays($now, false))) : 0;

        if ($lastReportObj->type === 'Baseline Report') {
            if ($lastReportArray['is_report_completed'] == 1) {
                $completedDate = Carbon::parse($lastReportObj->report_generated_at ?? $lastReportObj->updated_at)->startOfDay();
                $unlockDate = $completedDate->copy()->addDays(15);
                $diffInDays = $now->diffInDays($unlockDate, false);
                $behaviourLocked = $diffInDays > 0 ? 1 : 0;
                $behaviourUnlockInDays = $diffInDays > 0 ? intval(ceil($diffInDays)) : 0;

                $reports[] = [
                    'is_locked' => $behaviourLocked,
                    'title' => 'Behaviour Test',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => $behaviourUnlockInDays,
                    'show_finish_test_btn' => 0,
                    'current_day' => $appendedCurrentDay,
                ];

                $unlockDateProgress = $completedDate->copy()->addDays(30);
                $diffInDaysProgress = $now->diffInDays($unlockDateProgress, false);
                $progressUnlockInDays = $diffInDaysProgress > 0 ? intval(ceil($diffInDaysProgress)) : 0;

                $reports[] = [
                    'is_locked' => 1,
                    'title' => 'Progress Report',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => $progressUnlockInDays,
                    'show_finish_test_btn' => 0,
                    'current_day' => $appendedCurrentDay,
                ];
            } else {
                $reports[] = [
                    'is_locked' => 1,
                    'title' => 'Behaviour Test',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => 15,
                    'show_finish_test_btn' => 0,
                    'current_day' => $appendedCurrentDay,
                ];

                $reports[] = [
                    'is_locked' => 1,
                    'title' => 'Progress Report',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => 30,
                    'show_finish_test_btn' => 0,
                    'current_day' => $appendedCurrentDay,
                ];
            }
        } elseif ($lastReportObj->type === 'Behaviour Test' || $lastReportObj->type === 'Progress Report') {

            if ($lastReportObj->type === 'Behaviour Test') {
                $baselineReport = $morphologies->where('type', 'Baseline Report')->first();
                if ($baselineReport && $baselineReport->generation_status === 'completed') {
                    $completedDate = Carbon::parse($baselineReport->report_generated_at ?? $baselineReport->updated_at)->startOfDay();
                    $unlockDate = $completedDate->copy()->addDays(30);
                } else {
                    $unlockDate = $now->copy()->addDays(30);
                }
            } else {
                if ($lastReportArray['is_report_completed'] == 1) {
                    $completedDate = Carbon::parse($lastReportObj->report_generated_at ?? $lastReportObj->updated_at)->startOfDay();
                    $unlockDate = $completedDate->copy()->addDays(30);
                } else {
                    $unlockDate = $now->copy()->addDays(30);
                }
            }

            $diffInDays = $now->diffInDays($unlockDate, false);
            $unlockInDays = $diffInDays > 0 ? intval(ceil($diffInDays)) : 0;

            if ($lastReportArray['is_report_completed'] == 1) {
                $reports[] = [
                    'is_locked' => $diffInDays > 0 ? 1 : 0,
                    'title' => 'Progress Report',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => $unlockInDays,
                    'show_finish_test_btn' => 0,
                    'current_day' => $appendedCurrentDay,
                ];
            } else {
                $reports[] = [
                    'is_locked' => 1,
                    'title' => 'Progress Report',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => $unlockInDays,
                    'show_finish_test_btn' => 0,
                    'current_day' => $appendedCurrentDay,
                ];
            }
        }

        return $reports;
    }

    protected function lessons()
    {
        try {
            $userId = Auth::user()->id;

            $lessons = Lesson::where('status', 1)->with('lessonTips')->orderBy('id')->get();

            if ($lessons->isEmpty()) {
                return [
                    'id' => 0,
                    'title' => 'No lessons found.',
                    'description' => 'No lessons found.',
                    'video_title' => '',
                    'video_description' => '',
                    'video_link' => '',
                    'lesson_question' => '',
                    'answers' => [],
                    'task_title' => '',
                    'task_content' => '',
                    'lesson_tips' => [],
                    'isTaskCompleted' => 1,
                    'isFeature' => 0,
                    'feature_name' => '',
                    'feature_title' => '',
                    'feature_desc' => '',
                    'feature_image' => '',
                ];
            }

            $completedLessonIds = LessonsPlanner::where('user_id', $userId)
                ->where('is_logged', 1)
                ->pluck('lesson_id')->toArray();

            $nextLesson = $lessons->first(function ($lesson) use ($completedLessonIds) {
                return !in_array($lesson->id, $completedLessonIds);
            });

            if (!$nextLesson) {
                return [
                    'id' => 0,
                    'title' => 'All lessons completed.',
                    'description' => 'All lessons completed.',
                    'video_title' => '',
                    'video_description' => '',
                    'video_link' => '',
                    'lesson_question' => '',
                    'answers' => [],
                    'task_title' => '',
                    'task_content' => '',
                    'lesson_tips' => [],
                    'isTaskCompleted' => 1,
                    'isFeature' => 0,
                    'feature_name' => '',
                    'feature_title' => '',
                    'feature_desc' => '',
                    'feature_image' => '',
                ];
            }

            $lesson_data = [
                'id' => $nextLesson->id,
                'title' => $nextLesson->title,
                'description' => $nextLesson->description,
                'video_title' => $nextLesson->video_title ?? '',
                'video_description' => $nextLesson->video_description ?? '',
                'video_link' => $nextLesson->video_link ?? '',
                'lesson_question' => $nextLesson->lesson_question,
                'answers' => json_decode($nextLesson->answers, true),
                'task_title' => $nextLesson->task_title,
                'task_content' => $nextLesson->task_content,
                'lesson_tips' => [],
                'isTaskCompleted' => 0, // Lesson is not yet completed
            ];

            foreach ($nextLesson->lessonTips as $tip) {
                $tipImage = '';
                if ($tip->tip_image != '') {
                    $tipImage = asset(Storage::url($tip->tip_image));
                }
                $lesson_data['lesson_tips'][] = [
                    'id' => $tip->id,
                    'tip_title' => $tip->tip_title,
                    'tip_content' => $tip->tip_content,
                    'tip_image' => $tipImage,
                ];
            }

            // Add feature-related fields at the end of the response
            $lesson_data['isFeature'] = $nextLesson->isFeature;
            $lesson_data['feature_name'] = $nextLesson->feature_name ?? '';
            $lesson_data['feature_title'] = $nextLesson->feature_title ?? '';
            $lesson_data['feature_desc'] = $nextLesson->feature_desc ?? '';
            $lesson_data['feature_image'] = $nextLesson->feature_image ? asset(Storage::url($nextLesson->feature_image)) : '';

            return $lesson_data;
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}
