<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\DashboardController as APIDashboardControllerV1;
use App\Models\Challenge;
use App\Models\ChallengeLevel;
use App\Models\ChallengeUser;
use App\Models\ChallengeUserStatus;
use App\Models\CommunityPosts;
use App\Models\FoodPlanner;
use App\Models\Lesson;
use App\Models\LessonsPlanner;
use App\Models\OnboardingVisitLog;
use App\Models\TaskMilestone;
use App\Models\UserLevel;
use App\Models\UserLevelTask;
use App\Models\UserLevelTaskLog;
use App\Models\userMilestone;
use App\Models\UsersAppearanceInfo;
use App\Models\UserSessionStatus;
use App\Models\UserProgressImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class DashboardController extends APIDashboardControllerV1
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
                ]
            ];

            return $this->successResponse("Data Found", $response, 200);
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
    protected function lessons()
    {
        try {
            $currentDate = Carbon::now()->format('Y-m-d');
            $userId = Auth::user()->id;
            
            $lessons = Lesson::where('status', 1)->orderBy('id')->get();
            $completedLessonIds = LessonsPlanner::where('user_id', $userId)
                ->where('is_logged', 1)
                ->pluck('lesson_id')->toArray();

            // Check if there's any record for the current date
            // $lessonPlannerRecord = LessonsPlanner::where('user_id', $userId)
            //     ->where('date', $currentDate)
            //     ->first();

            $response = [];

            // if ($lessonPlannerRecord) {
            //     // If record found, get the specific lesson
            //     $lesson = Lesson::with('lessonTips')->where('id', $lessonPlannerRecord->lesson_id)->first();

            //     if ($lesson) {
            //         $lesson_data = [
            //             'id' => $lesson->id,
            //             'title' => $lesson->title,
            //             'description' => $lesson->description,
            //             'video_title' => $lesson->video_title ?? '',
            //             'video_description' => $lesson->video_description ?? '',
            //             'video_link' => $lesson->video_link ?? '',
            //             'lesson_question' => $lesson->lesson_question,
            //             'answers' => json_decode($lesson->answers, true),
            //             'task_title' => $lesson->task_title,
            //             'task_content' => $lesson->task_content,
            //             'lesson_tips' => [],
            //             'isTaskCompleted' => $lessonPlannerRecord->is_logged, // Mark as completed
            //         ];

            //         foreach ($lesson->lessonTips as $tip) {
            //             $tipImage = '';
            //             if ($tip->tip_image != '') {
            //                 $tipImage = asset(Storage::url($tip->tip_image));
            //             }
            //             $lesson_data['lesson_tips'][] = [
            //                 'id' => $tip->id,
            //                 'tip_title' => $tip->tip_title,
            //                 'tip_content' => $tip->tip_content,
            //                 'tip_image' => $tipImage,
            //             ];
            //         }

            //         // Add feature-related fields at the end of the response
            //         $lesson_data['isFeature'] = $lesson->isFeature;
            //         $lesson_data['feature_name'] = $lesson->feature_name ?? '';
            //         $lesson_data['feature_title'] = $lesson->feature_title ?? '';
            //         $lesson_data['feature_desc'] = $lesson->feature_desc ?? '';
            //         $lesson_data['feature_image'] = $lesson->feature_image ? asset(Storage::url($lesson->feature_image)) : '';

            //         $response = $lesson_data;
            //     }else{
            //         $response = [
            //             'id' => 0,
            //             'title' => 'No lessons found.',
            //             'description' => 'No lessons found.',
            //             'video_title' => '',
            //             'video_description' => '',
            //             'video_link' => '',
            //             'lesson_question' => '',
            //             'answers' => [],
            //             'task_title' => '',
            //             'task_content' => '',
            //             'lesson_tips' => [],
            //             'isTaskCompleted' => 1,
            //             'isFeature' => 0,
            //             'feature_name' => '',
            //             'feature_title' => '',
            //             'feature_desc' => '',
            //             'feature_image' => '',
            //         ];
            //     }
            // } else {
                // Get the first incomplete lesson for the user
                $lessonPlannerRecord = LessonsPlanner::where('user_id', $userId)
                    ->orderBy('date')
                    ->first();
    
                // Check if the user has completed the previous lesson
                $nextLesson = null;

                if ($lessonPlannerRecord) {
                    $nextLesson = $lessons->first(function($lesson) use ($completedLessonIds) {
                        return !in_array($lesson->id, $completedLessonIds);
                    });
                    if (!$nextLesson) {
                        $response = [
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

                } else {
                    // If it's the user's first day, show the first lesson
                    $nextLesson = $lessons->first();
                }

                if ($nextLesson) {
                    // Create a lesson planner record for the next lesson
                    LessonsPlanner::create([
                        'user_id' => $userId,
                        'lesson_id' => $nextLesson->id,
                        'date' => $currentDate,
                        'is_logged' => 0, // Mark as not yet completed
                    ]);

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

                    $response = $lesson_data;
                }
            // }

            return $response;
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function dailyLessonsPlanner(Request $request)
    {
        try {
            $data = $request->all();
            $userId = Auth::user()->id;
            $validation = [
                'lesson_id' => ['required', 'numeric'],
            ];
            $validator = Validator::make($data, $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            // Use the current date in YYYY-MM-DD format
            $currentDate = Carbon::now()->format('Y-m-d');

            // Find or create the LessonsPlanner entry
            // Try to find existing record
            $lessonPlanner = LessonsPlanner::where([
                'user_id' => $userId,
                'lesson_id' => $data['lesson_id'],
                'date' => $currentDate,
            ])->first();

            $shouldLogLevelTask = false;

            if (!$lessonPlanner) {
                // No record exists, create new
                $lessonPlanner = LessonsPlanner::create([
                    'user_id' => $userId,
                    'lesson_id' => $data['lesson_id'],
                    'date' => $currentDate,
                    'is_logged' => 1,
                ]);
                $shouldLogLevelTask = true;
            } elseif ($lessonPlanner->is_logged == 0) {
                // Existing record, but is_logged was 0, update to 1
                $lessonPlanner->is_logged = 1;
                $lessonPlanner->save();
                $shouldLogLevelTask = true;
            }
            $levelDetails = null;
            if ($shouldLogLevelTask) {
                $levelResponse = $this->logLevelTaskEntries($userId, 'lessons', 1, 0);

                if ($levelResponse != 0) {
                    $levelDetails = $this->getLevelDetails($levelResponse);
                }
            }

            
            $milestoneCompletedArray = [];

            $lessonMilestones = TaskMilestone::where('milestone_type', 'lessons')->get();

            $userCurrentMilestones = userMilestone::where([
                'user_id' => $userId,
                'milestone_type' => 'lessons'
            ])->pluck('milestone_id')->toArray();

            $userlessonsCompleteCount = UserLevelTaskLog::where([
                'user_id' => $userId,
                'level_task_type' => 'lessons'
            ])->sum('completed_count');

            foreach ($lessonMilestones as $milestone) {
                if (
                    $userlessonsCompleteCount >= $milestone->milestone_count &&
                    !in_array($milestone->id, $userCurrentMilestones)
                ) {
                    $milestoneCompletedArray[] = [
                        'type' => 'lessons',
                        'color' => 'pink',
                        'title' => $milestone->milestone_title,
                        'description' => $milestone->milestone_description,
                        'count' => $milestone->milestone_count,
                    ];

                    // Save to user_milestones table
                    UserMilestone::create([
                        'user_id' => $userId,
                        'milestone_id' => $milestone->id,
                        'milestone_type' => 'lessons',
                    ]);
                }
            }

            $lessonPlanner['milestoneCompletedArray'] = $milestoneCompletedArray;

            $lessonPlanner['levelCompletedDetails'] = $levelDetails ?: (object)[];

            return $this->getSingleResponse("Lesson planner entry saved successfully.", $lessonPlanner, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function latestImageData() {
        $response = [
            "latestimageRecordId" => 0,
            "latestimagePath" => "",
            "latestuploadDate" => "",
            "previousimageRecordId" => 0,
            "previousimagePath" => "",
            "previousuploadDate" => "",
            "isTaskCompleted" => 0,
        ];
        $userId = Auth::id();

        $progressImage = UserProgressImage::with(['beforeImage', 'afterImage'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if ($progressImage) {
            // Previous image
            if (!empty($progressImage->before_image) && is_array($progressImage->before_image)) {
                $before = $progressImage->before_image;
                $response["previousimageRecordId"] = $progressImage->before_image_id;
                $response["previousimagePath"] = $before['image'] ?? '';
                $response["previousuploadDate"] = $before['imageDate'] ?? '';
            }

            // Latest image
            if (!empty($progressImage->after_image) && is_array($progressImage->after_image)) {
                $after = $progressImage->after_image;
                $response["latestimageRecordId"] = $progressImage->after_image_id;
                $response["latestimagePath"] = $after['image'] ?? '';
                $response["latestuploadDate"] = $after['imageDate'] ?? '';
            }

            // Task completion based on UsersAppearanceInfo
            $latestUpload = UsersAppearanceInfo::where('user_id', $userId)
                ->latest('imageDate')
                ->first();

            if ($latestUpload && !empty($latestUpload->imageDate)) {
                $response["isTaskCompleted"] = Carbon::parse($latestUpload->imageDate)
                    ->diffInDays(now()) < 7 ? 1 : 0;
            }
        } else {
            // Fallback if no progress images
            $userAppearance = UsersAppearanceInfo::where('user_id', $userId)
                ->latest()
                ->get();

            if ($userAppearance->count() > 0) {
                $latestRecord = $userAppearance->first();
                $response["latestimageRecordId"] = $latestRecord->id;
                $response["latestimagePath"] = $latestRecord->image ? url('storage/' . $latestRecord->image) : '';
                $response["latestuploadDate"] = $latestRecord->imageDate ?? "";

                if (!empty($response["latestuploadDate"])) {
                    $response["isTaskCompleted"] = Carbon::parse($response["latestuploadDate"])
                        ->diffInDays(now()) < 7 ? 1 : 0;
                }

                if ($userAppearance->count() > 1) {
                    $previousRecord = $userAppearance->skip(1)->first();
                    $response["previousimageRecordId"] = $previousRecord->id;
                    $response["previousimagePath"] = $previousRecord->image ? url('storage/' . $previousRecord->image) : '';
                    $response["previousuploadDate"] = $previousRecord->imageDate ?? "";
                }
            }
        }

        return $response;
    }


    protected function onBoardingChallengeStatusInDashBoard() {
        $user = Auth::user();
        $isLogForFood = $isLogForSession = $isLogForLesson = $isLogForUserAppearenceInfo = $isLogForSession2 = $totalCompleted = $isChallengeTaken = $isLogForCommunityPost = 0;

        // Check Food Planner
        $foodPlanner = FoodPlanner::where(['user_id' => $user->id])->latest()->first();
        if ($foodPlanner) {
            $isLogForFood = 1;
            $totalCompleted++;
        }
        
        // Check User Session Status
        $userSessionStatus = UserSessionStatus::where(['user_id' => $user->id, 'user_session_status' => 1])->latest()->first();
        if ($userSessionStatus) {
            $isLogForSession = 1;
            $totalCompleted++;
        }
        
        // Check Lesson Planner
        $lessonPlanner = LessonsPlanner::where(['user_id' => $user->id, 'is_logged' => 1])->latest()->first();
        if ($lessonPlanner) {
            $isLogForLesson = 1;
            $totalCompleted++;
        }
        
        // Check User Appearence Info
        $userAppearanceInfo = UsersAppearanceInfo::where(['user_id' => $user->id])->latest()->first();
        if ($userAppearanceInfo) {
            $isLogForUserAppearenceInfo = 1;
            $totalCompleted++;
        }

        // Check if user is older than 2 days (today's date is 09-07-2025)
        $oldUser = 0;
        $userCreatedAt = Carbon::parse($user->created_at);
        $today = Carbon::createFromFormat('d-m-Y', '09-07-2025');
        if ($userCreatedAt->diffInDays($today) > 2) {
            $oldUser = 1;
        }

        $userVisitedCommunityScreen = OnboardingVisitLog::where('user_id', $user->id)
            ->where('source', 'communityscreen')
            ->exists() ? 1 : 0;

        // Check User posted 
        $userCommunityPost = CommunityPosts::where(['user_id' => $user->id])->latest()->first();

        if ($userCommunityPost) {
            $isLogForCommunityPost = 1;
            $totalCompleted++;
        }elseif($userVisitedCommunityScreen == 1){
            $isLogForCommunityPost = 1;
            $totalCompleted++;
        }elseif($oldUser == 1){
            $isLogForCommunityPost = 1;
            $totalCompleted++;
        }
        
        // Check User Sessions
        $userSessions = UserSessionStatus::where(['user_id' => $user->id, 'user_session_status' => 1])->count();
        if ($userSessions > 1) {
            $isLogForSession2 = 1;
            $totalCompleted++;
        }
        // Check User Challenge
        $UserChallenge = ChallengeUser::where(['user_id' => $user->id, 'status' => 1])->count();
        if ($UserChallenge > 0) {
            $isLogForFood = 1;
            $isLogForSession = 1;
            $isLogForLesson = 1;
            $isLogForUserAppearenceInfo = 1;
            $isLogForCommunityPost = 1;
            $isLogForSession2 = 1;
            $totalCompleted = 6;
            $isChallengeTaken = 1;
        }

        $data = [
            // 'isLogForLesson' => $isLogForLesson,
            // 'isLogForSession' => $isLogForSession,
            // 'isLogForUserAppearenceInfo' => $isLogForUserAppearenceInfo,
            // 'isLogForCommunityPost' => $isLogForCommunityPost,
            // 'isLogForSession2' => $isLogForSession2,
            // 'isLogForFood' => $isLogForFood,
            'totalCompleted' => $totalCompleted,
            'isChallengeTaken' => $isChallengeTaken,
        ];

        return $data;
    }

    protected function UserChallengeDetails()
    {
        $userId = Auth::user()->id;
        $challengeUser = ChallengeUser::where(['user_id' => $userId, 'status' => 1])->first();
        
        if (!$challengeUser) {
            return (object)[];
        }

        $challenge = Challenge::select('id as challenge_id', 'title_for_frontend as challenge_title_for_frontend', 'description as challenge_description', 'gif_url as challenge_gif')
            ->where(['id' => $challengeUser->challenge_id])
            ->first();

        if (!$challenge) {
            return (object)[];
        }

        // Fetch challenge level details
        $challengeLevel = ChallengeLevel::select('id', 'title', 'description as sub_title')
            ->where('id', $challengeUser->challenge_level_id)
            ->first();

        $todayExercisesCompleted = 0;
        $todayDate = date('Y-m-d');
        if (ChallengeUserStatus::where('user_id', $userId)
            ->where('complete_date', $todayDate)
            ->where('challenge_id', $challenge->challenge_id)
            ->where('challenge_level_id', $challengeUser->challenge_level_id)
            ->exists()) {
            $todayExercisesCompleted = 1;
        }

        $responseData = [
            "challenge_id" => $challenge->challenge_id,
            "challenge_title_for_frontend" => $challenge->challenge_title_for_frontend,
            "challenge_description" => $challenge->challenge_description,
            "challenge_gif" => $challenge->challenge_gif ? asset(Storage::url($challenge->challenge_gif)) : '',
            "challenge_level" => $challengeLevel,
            "todayExercisesCompleted" => $todayExercisesCompleted
        ];

        return $responseData;
    }

    protected function userLevelDetails(){
        $user = Auth::user();
        $currentLevel = $user->level;
        $currentLevelRecord = UserLevel::where('level', $currentLevel)->first();
        $levelsCount = UserLevel::count();
        $currentLevelTasksList = [];
        
        if (!$currentLevelRecord) {
            return  $response = [
                        'currentLevelTasksList' => $currentLevelTasksList,
                        'currentLevelProgress' => 0,
                        'currentLevel' => $currentLevel, 
                        'totalLevels' => $levelsCount,
                    ];
        }

        // Get all tasks for the current level
        $currentLevelTasks = UserLevelTask::where('user_level_id', $currentLevelRecord->id)
            ->select('id', 'task_type', 'total_count', 'duration')
            ->get();

        // Calculate progress
        $totalTasks = count($currentLevelTasks);
        $progress = 0;

        foreach ($currentLevelTasks as $task) {
            $completed_count = (int) UserLevelTaskLog::where([
                'user_id' => $user->id,
                'level_task_type' => $task->task_type,
            ])->sum('completed_count');
            $status = ($completed_count >= $task->total_count) ? 1 : 0;

            // Each task's share of 100%
            $taskShare = $totalTasks > 0 ? (100 / $totalTasks) : 0;
            // Progress for this task
            $taskProgress = $task->total_count > 0 ? min($completed_count / $task->total_count, 1) * $taskShare : 0;
            $progress += $taskProgress;
            
            // Determine task name based on task type
            switch ($task->task_type) {
                case 'lessons':
                    $taskName = 'Tutos des Mo’s';
                    break;
                case 'workouts':
                    $taskName = 'Séances';
                    break;
                case 'log-meals':
                    $taskName = 'Track tes repas';
                    break;
                case 'add-photos':
                    $taskName = 'Ajoute une photo';
                    break;
                case 'drink-water':
                    $taskName = 'Bois de l’eau';
                    break;
                case 'community-posts':
                    $taskName = 'Interagis avec la communauté';
                    break;
                case 'streaks':
                    $taskName = 'Streak';
                    break;
                case 'walks':
                    $taskName = 'Objectif de pas';
                    break;
                case 'feedback':
                    $taskName = 'Donne ton avis';
                    break;
                case 'protiens':
                    $taskName = 'Mange des protéines';
                    break;
                case 'challenge':
                    $taskName = 'Challenge';
                    break;
                default:
                    $taskName = ''; 
                    break;
            }

            $taskData = [
                'task_name' => $taskName,
                'task_type' => $task->task_type,
                'duration' => $task->duration,
                'completed_count' => $completed_count,
                'total_count' => $task->total_count,
                'status' => $status,
            ];

            // Add remaining_count only for 'walks'
            if ($task->task_type === 'walks') {
                $taskData['remaining_count'] = max($task->total_count - $completed_count, 0);
            }

            $currentLevelTasksList[] = $taskData;
        }

        // Round progress to 2 decimal places
        $currentLevelProgress = round($progress, 2);

        $response = [
            'currentLevelTasksList' => $currentLevelTasksList,
            'currentLevelProgress' => $currentLevelProgress,
            'currentLevel' => $currentLevel, 
            'totalLevels' => $levelsCount,
        ];

        return $response;
    }
    protected function userPreviousLevelDetails(){
        $user = Auth::user();
        $previousLevel = $user->level -1 ;
        $previousLevelPopupShown = '1';
        if($previousLevel > 0){
            $previousLevelPopupShown = $user->previous_level_popup_shown;
        }

        // Check if the level exists
        $SelectedLevel = UserLevel::where('level', $previousLevel)->first();
        if (!$SelectedLevel) {
            return (object)[]; // Return null if level doesn't exist
        }

        $currentLevelTasks = UserLevelTask::where('user_level_id', $SelectedLevel->id)
            ->select('id', 'task_type', 'total_count')
            ->get();

        // Add task_name for each task
        $currentLevelTasks->transform(function ($task) {
            switch ($task->task_type) {
                case 'lessons':
                    $taskName = 'Tutos des Mo’s';
                    break;
                case 'workouts':
                    $taskName = 'Séances';
                    break;
                case 'log-meals':
                    $taskName = 'Track tes repas';
                    break;
                case 'add-photos':
                    $taskName = 'Ajoute une photo';
                    break;
                case 'drink-water':
                    $taskName = 'Bois de l’eau';
                    break;
                case 'community-posts':
                    $taskName = 'Interagis avec la communauté';
                    break;
                case 'streaks':
                    $taskName = 'Streak';
                    break;
                case 'walks':
                    $taskName = 'Objectif de pas';
                    break;
                case 'feedback':
                    $taskName = 'Donne ton avis';
                    break;
                case 'protiens':
                    $taskName = 'Mange des protéines';
                    break;
                case 'challenge':
                    $taskName = 'Challenge';
                    break;
                default:
                    $taskName = '';
                    break;
            }
            $task->task_name = $taskName;
            return $task;
        });

        // Prepare the level data
        $levelData = [
            'id' => $SelectedLevel->id,
            'level' => $SelectedLevel->level,
            'level_title' => $SelectedLevel->level_title ?? '',
            'description' => $SelectedLevel->description ?? '',
            'improvement_per' => $SelectedLevel->improvement_per ?? 0,
            'video_title' => $SelectedLevel->video_title ?? '',
            'video_description' => $SelectedLevel->video_description ?? '',
            'video_url' => $SelectedLevel->video_url ?? '',
            'task_list' => $currentLevelTasks,
            'previousLevelPopupShown' => $previousLevelPopupShown,
        ];

        return $levelData;
    }
}
