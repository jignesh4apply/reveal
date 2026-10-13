<?php

namespace App\Http\Controllers\API;

use App\Models\AppPurchase;
use App\Models\Challenge;
use App\Models\ChallengeLevel;
use App\Models\ChallengeDay;
use App\Models\ChallengeUser;
use App\Models\ChallengeUserStatus;
use App\Models\CommunityPosts;
use App\Models\Exercise;
use App\Models\ExerciseOfChallenge;
use App\Models\FavouriteRecipe;
use App\Models\FoodPlanner;
use App\Models\Lesson;
use App\Models\LessonsPlanner;
use App\Models\MealType;
use App\Models\OnboardingVisitLog;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\Recipe;
use App\Models\Session;
use App\Models\Setting;
use App\Models\StepsGoal;
use App\Models\TaskMilestone;
use App\Models\User;
use App\Models\user_level;
use App\Models\UserAppFeedback;
use App\Models\UserLevel;
use App\Models\UserLevelReport;
use App\Models\UserLevelTask;
use App\Models\UserLevelTaskLog;
use App\Models\userMilestone;
use App\Models\UserMorphology;
use App\Models\UserMorphologyBehaviourTest;
use App\Models\UserProgram;
use App\Models\UsersAppearanceInfo;
use App\Models\UsersCurrentMeasurement;
use App\Models\UserSessionStatus;
use App\Models\UsersInitialMeasurement;
use App\Models\UsersSubscriptions;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Predis\Response\Status;
use stdClass;
use Throwable;

class DashboardController extends ApiController
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
                ]
            ];

            return $this->successResponse("Data Found", $response, 200);
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
    protected function getUserAssignedProgram()
    {
        try {
            $userAssignedProgram = UserProgram::with(['program.category', 'program.program_session.session_exercises', 'program.program_session.session_exercises.exercise', 'program.program_session.session', 'program.level'])
                ->where('user_id', Auth::user()->id)
                ->first();

            if (!$userAssignedProgram) {
                return new \stdClass();
            }

            $program = $userAssignedProgram->program;

            if (!$program || $program->status != 1) {
                return [
                    'categoryId' => 0,
                    'categoryName' => '',
                    'categorySlug' => '',
                    'categoryDescription' => '',
                    'categoryStatus' => 0,
                    'programs' => new \stdClass(),
                    'information' => new \stdClass(),
                    'user_streak' => 0
                ];
            }

            $programVideos = array_map(fn($video) => asset(Storage::url($video)), explode("|", $program->video));

            $pendingSession = [];

            foreach ($program->program_session as $session) {
                $userSessionStatus = UserSessionStatus::where([
                    'user_id' => Auth::user()->id,
                    'program_id' => $session->program_id,
                    'session_id' => $session->session_id,
                    'session_week' => $session->session_week,
                ])->first();

                if (!$userSessionStatus || $userSessionStatus->user_session_status == 0) {
                    $sessionDetails = Session::where(['id' => $session->session_id])->first();

                    $pendingSession[] = [
                        'week' => $session->session_week,
                        'session' => [
                            ['program_session_id' => $session->id,
                            'session_id' => $session->session_id,
                            'session_title' => $sessionDetails->title_for_frontend,
                            'difficulty_level' => $sessionDetails->difficulty_level,
                            'body_area' => $sessionDetails->body_area,
                            'time' => $sessionDetails->time,
                            'calories' => $sessionDetails->calories,
                            'session_type' => $sessionDetails->session_type,
                            'summary' => $sessionDetails->summary,
                            'user_session_status' => 'pending',
                            'session_material' => $sessionDetails->materials->map(fn($material) => [
                                'id' => $material->id,
                                'material_name' => $material->material_name,
                                'material_image' => asset(Storage::url($material->material_image))
                            ]),
                            'session_exercises' => $session->session_exercises->map(function ($exercise) use ($session) {
                                $sessionDetails = Session::where(['id' => $session->session_id])->first();
                                return $this->formatSessionExerciseData($exercise, $sessionDetails);
                            })->filter()->values()]
                        ]
                    ];
                    break;
                }
            }

            return [
                'categoryId' => $program->category->id,
                'categoryName' => $program->category->category_name,
                'categoryDescription' => $program->category->description,
                'categoryStatus' => $program->category->status,
                'programs' => [
                    'programId' => $program->id,
                    'programCategoryId' => $program->category_id,
                    'programTags' => explode("|", $program->program_tag),
                    'programTitle' => $program->title,
                    'programDescription' => $program->description,
                    'programImage' => asset(Storage::url($program->program_image)),
                    'programVideos' => $programVideos,
                    'programFreeAccess' => $program->free_access,
                    'programStatus' => $program->status,
                    'programSession' => $pendingSession
                ],
                'information' => [
                    'programObjective' => $program->objective,
                    'programLevel' => $program->level->level_title,
                    'programBodyArea' => $program->body_area,
                    'programDuration' => $program->duration . ' weeks',
                    'programFrequency' => $program->frequency . ' per week'
                ],
                'user_streak' => $userAssignedProgram->user_streak
            ];
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong with user assigned program", $th->getMessage(), 500);
        }
    }

    protected function stepsTracking()
    {
        try {
            $userTz = Auth::user()->timezone ?? "Europe/Paris";
            $goalDate = new DateTime('now', new \DateTimeZone($userTz));
            $goalDate = $goalDate->format('Y-m-d');

            // Try to get today's goal record
            $getStepsTracking = StepsGoal::where(['user_id' => Auth::user()->id, 'goal_date' => $goalDate])->first();

            // If no record is found for today's date, take the last available steps goal record
            if (empty($getStepsTracking)) {
                $getStepsgoal = StepsGoal::where('user_id', Auth::user()->id)
                                              ->orderBy('goal_date', 'desc')
                                              ->first();

                // If no records exist at all, return default response
                if (empty($getStepsgoal)) {
                    $response = [
                        "id"                =>  0,
                        "steps_goal"        =>  0,
                        "distance"          =>  "0",
                        "steps_completed"   =>  0,
                        "isCompleted"       =>  0,
                        "steps_remaining"   =>  0,
                    ];
                    return $response;
                }else{
                    $response = [
                        "id"                =>  0,
                        "steps_goal"        =>  $getStepsgoal['steps_goal'],
                        "distance"          =>  "0",
                        "steps_completed"   =>  0,
                        "isCompleted"       =>  0,
                        "steps_remaining"   =>  0,
                    ];
                    return $response;
                }

            }

            // Check if the steps goal is completed
            if ($getStepsTracking['steps_completed'] >= $getStepsTracking['steps_goal']) {
                $isCompleted = 1;
            } else {
                $isCompleted = 0;
            }

            // Prepare the response
            $stepsTracking = [
                "id"                =>  $getStepsTracking['id'],
                "steps_goal"        =>  $getStepsTracking['steps_goal'],
                "distance"          =>  $getStepsTracking['distance'],
                "steps_completed"   =>  $getStepsTracking['steps_completed'],
                "isCompleted"       =>  $isCompleted,
                "steps_remaining"   =>  $getStepsTracking['steps_remaining'],
            ];

            return $stepsTracking;

        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong with step tracking", $th->getMessage(), 500);
        }
    }

    protected function currentWeight()
    {
        try {
            if (Auth::check()) {
                $userCurrentMeasurements = UsersCurrentMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
                $userInitialMeasurements = UsersInitialMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
                if (!empty($userCurrentMeasurements) && !is_null($userCurrentMeasurements['weight'])) {
                    $currentWeight = $userCurrentMeasurements['weight'];
                    $saved_on_date = $userCurrentMeasurements['last_modified_date'];
                } elseif (!empty($userInitialMeasurements) && !is_null($userInitialMeasurements['weight'])) {
                    $currentWeight = $userInitialMeasurements['weight'];
                    $date = $userInitialMeasurements['updated_at'];
                    $saved_on_date = date('Y-m-d', strtotime($date));
                } else {
                    $currentWeight = 0;
                    $saved_on_date = "";
                }
                // Check if the saved date is within the last 7 days
                if (!empty($saved_on_date)) {
                    $currentDate = now(); // Carbon instance for the current date
                    $savedDate = Carbon::parse($saved_on_date); // Convert string to Carbon instance
                    $isTaskCompleted = $savedDate->diffInDays($currentDate) <= 7 ? 1 : 0;
                } else {
                    $isTaskCompleted = 0;
                }

                $response = [
                    'name' => 'CurrentWeight',
                    'weight' => (int) $currentWeight,
                    'on_date' => $saved_on_date,
                    'isTaskCompleted' => $isTaskCompleted,
                ];
            } else {
                $response = [
                    'name' => 'CurrentWeight',
                    'weight' => 0,
                    'on_date' => "",
                    'isTaskCompleted' => 0,
                ];
            }
            return $response;
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    protected function isLoggedMeal()
    {
        try {
            $allMealTypes = MealType::where('visible_in_tracker', 1)->get()->toArray();

            $mealTypes = array_map(function($mealType) {
                return strtolower($mealType['slug']);
            }, $allMealTypes);

            // Add 'water' to the end of the array
            $mealTypes[] = 'water';
            if (empty($mealList)) {
                $isLoggedToday = 0;
            }
            foreach ($mealTypes as $mealType) {
                $isLoggedToday = 0;
                $mealList = FoodPlanner::where(['user_id' => Auth::user()->id, 'meal_type' => $mealType, 'date' => date('Y-m-d')])->get()->toArray();
                foreach ($mealList as $meal) {
                    if ($meal['date'] == date('Y-m-d')) {
                        $isLoggedToday = 1;
                    }
                }
                $mealRecipe = [
                    "name" => ucfirst($mealType),
                    "isLoggedToday" => $isLoggedToday
                ];

                $response[] = $mealRecipe;
            }
            return $response;
        } catch (Throwable $th) {
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
            $lessonPlannerRecord = LessonsPlanner::where('user_id', $userId)
                ->where('date', $currentDate)
                ->first();

            $response = [];

            if ($lessonPlannerRecord) {
                // If record found, get the specific lesson
                $lesson = Lesson::with('lessonTips')->where('id', $lessonPlannerRecord->lesson_id)->first();

                if ($lesson) {
                    $lesson_data = [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'description' => $lesson->description,
                        'video_title' => $lesson->video_title ?? '',
                        'video_description' => $lesson->video_description ?? '',
                        'video_link' => $lesson->video_link ?? '',
                        'lesson_question' => $lesson->lesson_question,
                        'answers' => json_decode($lesson->answers, true),
                        'task_title' => $lesson->task_title,
                        'task_content' => $lesson->task_content,
                        'lesson_tips' => [],
                        'isTaskCompleted' => $lessonPlannerRecord->is_logged, // Mark as completed
                    ];

                    foreach ($lesson->lessonTips as $tip) {
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
                    $lesson_data['isFeature'] = $lesson->isFeature;
                    $lesson_data['feature_name'] = $lesson->feature_name ?? '';
                    $lesson_data['feature_title'] = $lesson->feature_title ?? '';
                    $lesson_data['feature_desc'] = $lesson->feature_desc ?? '';
                    $lesson_data['feature_image'] = $lesson->feature_image ? asset(Storage::url($lesson->feature_image)) : '';

                    $response = $lesson_data;
                }else{
                    $response = [
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
            } else {
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
            }

            return $response;
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    protected function userData($request)
    {
        try {
            $user = User::where(['id' => Auth::user()->id])->first();
            // Get the latest records for both AppPurchase (iOS) and UsersSubscriptions (Android)
            $appUser = AppPurchase::where(['user_id' => Auth::user()->id])->latest()->first();

            $userSubscribed = UsersSubscriptions::where('user_id', Auth::id())
                ->where(function ($q) {
                    $q->where('is_refunded', 0)
                    ->orWhere(function ($q2) {
                        $q2->where('is_refunded', 1)
                            ->where('status', '!=', 'canceled');
                    });
                })
                ->where(function ($q) {
                    $q->where('subscription_id', 'not like', 'klarna_%')
                    ->orWhere('is_refunded', 0);
                })
                ->latest()
                ->first();

            if (!empty($user)) {

                $userId = $user->id;
                $userName = $user->name;
                $userEmail = $user->email;
                $userToken = $request->bearerToken();
                // Default values
                $product_id = "";
                $price = "";
                $subscriptionEndDate = "";
                $joiningDate = "";
                $subscribedUser = 0;
                $currentDate = Carbon::now()->format('Y-m-d');

                // Initialize flags for subscription validity
                $iosSubscriptionValid = false;
                $androidSubscriptionValid = false;
                $iosSubscriptionEndDate = "";  // Store iOS subscription end date
                $androidSubscriptionEndDate = ""; // Store Android subscription end date

                // For iOS subscription (AppPurchase table)
                if (!empty($appUser)) {
                    // Extract product type and calculate subscription duration
                    $productType = substr($appUser['product_id'], strrpos($appUser['product_id'], '.') + 1);
                    $duration = 0;
                    if ($productType === 'OneYear' || $productType === 'oneYear' || $productType === 'oneYearRevised') {
                        $duration = 1;
                    } elseif ($productType === 'TwoYear' || $productType === 'twoYear') {
                        $duration = 2;
                    }

                    // Calculate subscription end date for iOS
                    $joiningDate = Carbon::createFromFormat('Y-m-d', $appUser['joining_date']);
                    $endDate = $joiningDate->addYears($duration);
                    $iosSubscriptionEndDate = $endDate->format('Y-m-d');  // iOS subscription end date

                    // Set iOS subscription details
                    $product_id = $appUser['product_id'];
                    $price = $appUser['price'];
                    $iosSubscriptionValid = ($iosSubscriptionEndDate >= $currentDate); // Check if iOS subscription is valid
                }

                // For Android subscription (UsersSubscriptions table)
                if (!empty($userSubscribed)) {
                    // Determine if the Android subscription is still valid
                    $androidSubscriptionValid = ($userSubscribed->end_date >= $currentDate);

                    // Set Android subscription details
                    $price = $userSubscribed->amount;
                    $joiningDate = $userSubscribed->start_date;
                    $androidSubscriptionEndDate = $userSubscribed->end_date;  // Android subscription end date
                }

                // Logic to determine which subscription is currently valid or the most recent one
                if ($iosSubscriptionValid && $androidSubscriptionValid) {
                    // If both iOS and Android are valid, compare their subscription end dates
                    $iosEndDate = Carbon::createFromFormat('Y-m-d', $iosSubscriptionEndDate);
                    $androidEndDate = Carbon::createFromFormat('Y-m-d', $androidSubscriptionEndDate);

                    if ($iosEndDate->greaterThan($androidEndDate)) {
                        // If iOS has a later end date, return iOS data
                        $subscribedUser = 1;
                        $product_id = $appUser['product_id'];
                        $price = $appUser['price'];
                        $joiningDate = $appUser['joining_date'];
                        $subscriptionEndDate = $iosEndDate->format('Y-m-d');
                    } else {
                        // If Android has a later end date, return Android data
                        $subscribedUser = 1;
                        $product_id = ""; // Android doesn't have a product_id
                        $price = $userSubscribed->amount;
                        $joiningDate = $userSubscribed->start_date;
                        $subscriptionEndDate = $androidEndDate->format('Y-m-d');
                    }
                } elseif ($iosSubscriptionValid) {
                    // If only iOS is valid
                    $subscribedUser = 1;
                    $product_id = $appUser['product_id'];
                    $price = $appUser['price'];
                    $joiningDate = $appUser['joining_date'];
                    $subscriptionEndDate = $iosSubscriptionEndDate;  // iOS end date
                } elseif ($androidSubscriptionValid) {
                    // If only Android is valid
                    $subscribedUser = 1;
                    $product_id = ""; // Android doesn't have a product_id
                    $price = $userSubscribed->amount;
                    $joiningDate = $userSubscribed->start_date;
                    $subscriptionEndDate = $androidSubscriptionEndDate;  // Android end date
                } else {
                    // If both are expired, return the most recent subscription based on end date
                    $subscribedUser = 0;

                    // Compare the end dates to determine which one is more recent
                    if (!empty($appUser) && !empty($userSubscribed)) {
                        $iosEndDate = Carbon::createFromFormat('Y-m-d', $iosSubscriptionEndDate);
                        $androidEndDate = Carbon::createFromFormat('Y-m-d', $androidSubscriptionEndDate);

                        if ($iosEndDate->greaterThan($androidEndDate)) {
                            // If iOS has a later end date, return iOS data
                            $product_id = $appUser['product_id'];
                            $price = $appUser['price'];
                            $joiningDate = $appUser['joining_date'];
                            $subscriptionEndDate = $iosEndDate->format('Y-m-d');
                        } else {
                            // If Android has a later end date, return Android data
                            $product_id = ""; // Android doesn't have a product_id
                            $price = $userSubscribed->amount;
                            $joiningDate = $userSubscribed->start_date;
                            $subscriptionEndDate = $androidEndDate->format('Y-m-d');
                        }
                    } elseif (!empty($appUser)) {
                        // Only iOS record exists
                        $product_id = $appUser['product_id'];
                        $price = $appUser['price'];
                        $joiningDate = $appUser['joining_date'];
                        $subscriptionEndDate = $iosSubscriptionEndDate;  // iOS end date
                    } elseif (!empty($userSubscribed)) {
                        // Only Android record exists
                        $product_id = ""; // Android doesn't have a product_id
                        $price = $userSubscribed->amount;
                        $joiningDate = $userSubscribed->start_date;
                        $subscriptionEndDate = $androidSubscriptionEndDate;  // Android end date
                    }
                }

                // Return the response with common keys for both iOS and Android
                $response = [
                    "subscribedUser" => $subscribedUser, // 1 if subscribed (either iOS or Android), 0 if both are expired
                    "product_id" => $product_id,         // Only iOS will have this field, Android will have empty string
                    "price" => strval($price),           // Common price for both
                    "subscriptionJoiningDate" => $joiningDate,
                    "subscriptionEndDate" => $subscriptionEndDate,
                    "avatar" => $user['avatar'] ? asset(Storage::url($user['avatar'])) : '', // Avatar URL
                    "id" => $userId,
                    "name" => $userName,
                    "email" => $userEmail,
                    "token" => $userToken
                ];

                return $response;
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function dailyLessonsPlanner(Request $request)
    {
        try {
            $data = $request->all();

            $validation = [
                'lesson_id' => ['required', 'numeric'],
                // 'is_logged' => ['required', 'integer', 'in:0,1'],
            ];
            $validator = Validator::make($data, $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            // Use the current date in YYYY-MM-DD format
            $currentDate = Carbon::now()->format('Y-m-d');

            // Find or create the LessonsPlanner entry
            $lessonPlanner = LessonsPlanner::updateOrCreate(
                [
                    'user_id' => Auth::user()->id,
                    'lesson_id' => $data['lesson_id'],
                    'date' => $currentDate, // Use the formatted current date
                ],
                [
                    // 'is_logged' => $data['is_logged'],
                    'is_logged' => 1,
                ]
            );

            return $this->getSingleResponse("Lesson planner entry saved successfully.", $lessonPlanner, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function latestImageData(){
        $imageRecordId = 0;
        $imagePath = "";
        $uploadDate = "";
        $isTaskCompleted = 0;

        $userAppearance = UsersAppearanceInfo::where(['user_id' => Auth::user()->id])->latest()->first();
        if($userAppearance){
            $imageRecordId = $userAppearance->id;
            $uploadDate = $userAppearance->imageDate;
            $imagePath = $userAppearance->image ? asset(Storage::url($userAppearance->image)) : '';
            if (!empty($uploadDate)) {
                $currentDate = now();
                $uploadedDate = Carbon::parse($uploadDate);
                $isTaskCompleted = $uploadedDate->diffInDays($currentDate) < 7 ? 1 : 0;
            }
        }
        $response = [
            "imageRecordId" => $imageRecordId,
            "imagePath" => $imagePath,
            "uploadDate" => $uploadDate,
            "isTaskCompleted" => $isTaskCompleted,
        ];
        return $response;
    }

    protected function maintainanceMode(){
        $setting = Setting::get()->toArray();
        $maintenanceMode = array_filter($setting, function ($item) {
            return $item['key'] === 'maintenance_mode_mobile';
        });
        $maintenanceMode = array_values($maintenanceMode);

        if (!empty($maintenanceMode) && $maintenanceMode[0]['value'] == 1 && $maintenanceMode[0]['key'] == 'maintenance_mode_mobile') {
            return 1;
        }
        return 0;
    }

    public function stepsTrackingforAndroid()
    {
        try {
            //all things are same , just an standalone API for android

            $userTz = Auth::user()->timezone ?? "Europe/Paris";
            $goalDate = new DateTime('now', new \DateTimeZone($userTz));
            $goalDate = $goalDate->format('Y-m-d');

            $getStepsTracking = StepsGoal::where(['user_id' => Auth::user()->id, 'goal_date' => $goalDate])->first();
            if (!empty($getStepsTracking)) {

                if ($getStepsTracking['steps_completed'] >= $getStepsTracking['steps_goal']) {
                    $isCompleted = 1;
                } else {
                    $isCompleted = 0;
                }

                $stepsTracking = [
                    "id"                =>  $getStepsTracking['id'],
                    "steps_goal"        =>  $getStepsTracking['steps_goal'],
                    "distance"          =>  $getStepsTracking['distance'],
                    "steps_completed"   =>  $getStepsTracking['steps_completed'],
                    "isCompleted"       =>  $isCompleted,
                    "steps_remaining"   =>  $getStepsTracking['steps_remaining'],
                ];
            } else {
                $stepsTracking = [
                    "id"                =>  0,
                    "steps_goal"        =>  0,
                    "distance"          =>  "0",
                    "steps_completed"   =>  0,
                    "isCompleted"       =>  0,
                    "steps_remaining"   =>  0,
                ];

            }
            $response = [
                [
                    'name' => "StepsTracking",
                    'stepsTracking' => $stepsTracking
                ]
            ];
            return $this->successResponse("Data Found", $response, 200);
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong with step tracking", $th->getMessage(), 500);
        }
    }

    protected function isWeek1Completed()
    {
        $isWeek1Completed = 0;

        $userAssignedProgram = UserProgram::with('program')->where('user_id', Auth::user()->id)->first();
        if (!empty($userAssignedProgram)) {
            // Get the program with its sessions
            $program = Program::with(['category', 'program_session'])
                ->where(['status' => 1, 'id' => $userAssignedProgram->program_id])
                ->first();

            if ($program && $program->program_session) {
                // Group sessions by weeks
                $sessionsByWeek = $program->program_session->groupBy('session_week');

                // Get all sessions for week 1
                $week1Sessions = $sessionsByWeek->get('Semaine 1');
                if (!empty($week1Sessions)) {
                    // Check each session's completion status
                    $allCompleted = $week1Sessions->every(function ($session) use ($program) {
                        $userSessionStatus = UserSessionStatus::where([
                            'user_id' => Auth::user()->id,
                            'program_id' => $program->id,
                            'program_session_id' => $session->id
                        ])->first();

                        return $userSessionStatus && $userSessionStatus->user_session_status == 1; // 1 = Completed
                    });

                    $isWeek1Completed = $allCompleted ? 1 : 0;
                }
            }
        }

        return $isWeek1Completed;
    }

    public function onBoardingChallengeStatus(Request $request) {
        $user = Auth::user();
        $isLogForFood = $isLogForSession = $isLogForLesson = $isLogForUserAppearenceInfo = $isLogForSession2 = $totalCompleted = $isChallengeTaken = $isLogForCommunityPost = 0;

        $source = $request->query('source');
        if ($source) {
            OnboardingVisitLog::create([
                'user_id' => $user->id,
                'source' => strtolower(str_replace(' ', '', $source)),
            ]);
        }


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
            'isLogForLesson' => $isLogForLesson,
            'isLogForSession' => $isLogForSession,
            'isLogForUserAppearenceInfo' => $isLogForUserAppearenceInfo,
            'isLogForCommunityPost' => $isLogForCommunityPost,
            'isLogForSession2' => $isLogForSession2,
            'isLogForFood' => $isLogForFood,
            'totalCompleted' => $totalCompleted,
            'isChallengeTaken' => $isChallengeTaken,
        ];

        return $this->successResponse("Onborading Challenge status", $data, 200);
    }
    public function deleteUserData($user_id)
    {
        // Ensure authenticated user matches the given user_id
        if (Auth::user()->id != $user_id) {
            return response()->json(['error' => 'Unauthorized action'], 403);
        }

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Delete records from related tables
            UserSessionStatus::where('user_id', $user_id)->delete();
            LessonsPlanner::where('user_id', $user_id)->delete();
            FoodPlanner::where('user_id', $user_id)->delete();
            UsersAppearanceInfo::where('user_id', $user_id)->delete();
            ChallengeUser::where('user_id', $user_id)->delete();
            ChallengeUserStatus::where('user_id', $user_id)->delete();
            CommunityPosts::where('user_id', $user_id)->delete();
            OnboardingVisitLog::where('user_id', $user_id)->delete();

            // Commit transaction
            DB::commit();

            return response()->json(['success' => 'User-related data deleted successfully'], 200);
        } catch (\Exception $e) {
            // Rollback in case of error
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete user data', 'message' => $e->getMessage()], 500);
        }
    }
    public function deleteUserLevelMilestoneData($user_id)
    {
        // Ensure authenticated user matches the given user_id
        if (Auth::user()->id != $user_id) {
            return response()->json(['error' => 'Unauthorized action'], 403);
        }

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Delete records from related tables
            userMilestone::where('user_id', $user_id)->delete();
            UserLevelTaskLog::where('user_id', $user_id)->delete();
            $user = User::findOrFail($user_id);
            $user->update(['level' => 1]);
            // Commit transaction
            DB::commit();

            return response()->json(['success' => 'User-related Milestone and level data deleted successfully'], 200);
        } catch (\Exception $e) {
            // Rollback in case of error
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete user Milestone and level data', 'message' => $e->getMessage()], 500);
        }
    }

    public function completeUptoLastChallengeDay(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'challenge_id' => 'required|integer',
            'challenge_level_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationError("Fail", $validator->errors()->first(), 200);
        }

        $challengeId = $request->input('challenge_id');
        $challengeLevelId = $request->input('challenge_level_id');

        // Check if the challenge user record exists
        $challengeUser = ChallengeUser::where([
            'user_id' => $user->id,
            'challenge_id' => $challengeId,
            'challenge_level_id' => $challengeLevelId,
        ])->first();

        if (!$challengeUser) {
            return $this->validationError("Fail", "Challenge user record not found.", 404);
        }

        // Get all challenge days for this challenge and level
        $challengeDays = ChallengeDay::where([
            'challenge_id' => $challengeId,
            'challenge_level_id' => $challengeLevelId,
        ])->orderBy('id')->get();

        if ($challengeDays->count() < 2) {
            return $this->validationError("Fail", "Not enough challenge days to complete up to the last day.", 400);
        }

        // Insert records up to the second last challenge day
        $inserted = [];
        foreach ($challengeDays->slice(0, $challengeDays->count() - 1) as $challengeDay) {
            $exists = ChallengeUserStatus::where([
                'user_id' => $user->id,
                'challenge_day_id' => $challengeDay->id,
            ])->exists();

            if (!$exists) {
                $inserted[] = ChallengeUserStatus::create([
                    'user_id' => $user->id,
                    'challenge_day_id' => $challengeDay->id,
                    'status' => 1,
                    'complete_date' => '2025-02-05',
                    'challenge_id' => $challengeId,
                    'challenge_level_id' => $challengeLevelId,
                    'completed_day' => $challengeDay->day
                ]);
            }
        }

        return $this->successResponse("Challenge days marked as completed up to the last day.", [
            'completed_count' => count($inserted),
        ], 200);
    }

    public function logUserStreak()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $milestoneCompletedArray = [];
        $levelDetails = (object)[];

        // If last_opened_at is today, do nothing
        if ($user->last_opened_at && Carbon::parse($user->last_opened_at)->isSameDay($today)) {
            return $this->successResponse("Streak already updated for today.", [
                'streak' => (int) $user->streak,
                'last_opened_at' => $user->last_opened_at,
                'milestoneCompletedArray' => $milestoneCompletedArray,
                'levelCompletedDetails' => $levelDetails
            ], 200);
        }

        // Always increment streak by 1 for a new day
        $user->streak = ($user->streak ?? 0) + 1;
        $user->last_opened_at = $today;
        $user->save();

        $levelDetails = null;
        $levelResponse = $this->logLevelTaskEntries($user->id, 'streaks',$user->streak,1);

        if ($levelResponse != 0) {
            $levelDetails = $this->getLevelDetails($levelResponse);
        }

        $streaksMilestones = TaskMilestone::where('milestone_type', 'streaks')->get();

        $userCurrentMilestones = userMilestone::where([
            'user_id' => $user->id,
            'milestone_type' => 'streaks'
        ])->pluck('milestone_id')->toArray();

        $userStreakCompleteCount = UserLevelTaskLog::where([
            'user_id' => $user->id,
            'level_task_type' => 'streaks'
        ])->sum('completed_count');

        foreach ($streaksMilestones as $milestone) {
            if (
                $userStreakCompleteCount >= $milestone->milestone_count &&
                !in_array($milestone->id, $userCurrentMilestones)
            ) {
                $milestoneCompletedArray[] = [
                    'type' => 'streaks',
                    'color' => 'red',
                    'title' => $milestone->milestone_title,
                    'description' => $milestone->milestone_description,
                    'count' => $milestone->milestone_count,
                ];

                // Save to user_milestones table
                UserMilestone::create([
                    'user_id' => $user->id,
                    'milestone_id' => $milestone->id,
                    'milestone_type' => 'streaks',
                ]);
            }
        }
        return $this->successResponse("Streak updated.", [
            'streak' => $user->streak,
            'last_opened_at' => $user->last_opened_at,
            'milestoneCompletedArray' => $milestoneCompletedArray,
            'levelCompletedDetails' => $levelDetails ?: (object)[]
        ], 200);
    }

    public function levelDetails(Request $request)
    {
        $level = $request->level;
        $userCurrentLevel = Auth::user()->level;

        // Check if the level exists
        $SelectedLevel = UserLevel::where('level', $level)->first();
        if (!$SelectedLevel) {
            return $this->validationError("Fail", "Level not found.", 404);
        }

        $isCompleted = ($userCurrentLevel > $level) ? 1 : 0;

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

        $completedReports = UserLevelReport::where('user_id', Auth::user()->id)
            ->where('level', $level)
            ->get();

        $morphologyReportData = [];
        foreach ($completedReports as $completedReport) {
            $morphologyReportData[] = $this->formatReportDetailsForLevel($completedReport);
        }

        $levelData = [
            'id' => $SelectedLevel->id,
            'level' => $SelectedLevel->level,
            'level_title' => $SelectedLevel->level_title ?? '',
            'description' => $SelectedLevel->description ?? '',
            'improvement_per' => $SelectedLevel->improvement_per ?? 0,
            'video_title' => $SelectedLevel->video_title ?? '',
            'video_description' => $SelectedLevel->video_description ?? '',
            // 'video_url' => $SelectedLevel->video_url ? asset(Storage::url($SelectedLevel->video_url)) : '',
            'video_url' => $SelectedLevel->video_url ?? '',
            'task_list' => $currentLevelTasks,
            'isCompleted' => $isCompleted,
            'morphology_report' => $morphologyReportData,
        ];

        return $this->successResponse("Level fetched successfully.", $levelData, 200);
    }

    public function userAppFeedback(Request $request){

        $validated = $request->validate([
            'rating' => 'required|integer|in:1,2,3,4,5',
            'description' => 'required|string',
        ]);

        // Get the authenticated user ID
        $userId = Auth::user()->id;

        // Create feedback
        $feedback = new UserAppFeedback();
        $feedback->user_id = $userId;
        $feedback->rating = $validated['rating'];
        $feedback->description = $validated['description'];
        $feedback->save();

        $levelDetails = null;
        $levelResponse =  $this->logLevelTaskEntries($userId, 'feedback',1,0);

        if ($levelResponse != 0) {
            $levelDetails = $this->getLevelDetails($levelResponse);
        }
        $feedback['levelCompletedDetails'] = $levelDetails ?: (object)[];

        // Return a response
        return $this->successResponse("Feedback submitted successfully.", $feedback, 201);
    }

    public function logLessonsForUsers()
    {
        // Step 1: Get all unique user_ids and their corresponding unique lesson_ids where is_logged = 1
        $lessonLogs = LessonsPlanner::select('user_id', 'lesson_id')
            ->where('is_logged', 1)
            ->distinct('user_id', 'lesson_id')
            ->get();

        // Step 2: Group by user_id to perform bulk inserts for each user
        $userLessonLogs = $lessonLogs->groupBy('user_id');

        $totalInserted = 0;

        // Step 3: Iterate through each user's lessons and log the tasks
        foreach ($userLessonLogs as $userId => $lessons) {
            $lessonCount = $lessons->count(); // Count how many lessons logged for this user

            // Insert the records into the user_level_task_logs table
            $taskLogs = [];
            foreach ($lessons as $lesson) {
                $taskLogs[] = [
                    'user_id' => $userId,
                    'level_task_type' => 'lessons',
                    'completed_count' => 1, // As each lesson counts as 1
                ];
            }

            // Bulk insert for this user
            $insertedCount = UserLevelTaskLog::insert($taskLogs) ? count($taskLogs) : 0;
            $totalInserted += $insertedCount;
        }

        // Optional: Return a success message or some result
        return response()->json([
            'message' => 'Lessons logged successfully.',
            'logged_count' => $totalInserted,
        ]);
    }

    public function logAppearanceForUsers()
    {
        // Step 1: Get the count of appearance records per user
        $appearanceLogs = UsersAppearanceInfo::select('user_id', DB::raw('COUNT(*) as appearance_count'))
            ->groupBy('user_id') // Group by user_id to get the count per user
            ->get();

        $totalInserted = 0;

        // Step 2: Iterate through each user's appearance record and log the tasks
        foreach ($appearanceLogs as $appearance) {
            $userId = $appearance->user_id;
            $appearanceCount = $appearance->appearance_count; // Get the count of appearance records for this user

            // Insert the records into the user_level_task_logs table (for appearance tasks)
            $taskLogs = [];
            $taskLogs[] = [
                'user_id' => $userId,
                'level_task_type' => 'add-photos', // Mark it as an appearance task
                'completed_count' => $appearanceCount, // Total appearance records for the user
            ];

            // Bulk insert for this user (only one record per user)
            $insertedCount = UserLevelTaskLog::insert($taskLogs) ? count($taskLogs) : 0;
            $totalInserted += $insertedCount;
        }

        // Optional: Return a success message or some result
        return response()->json([
            'message' => 'Appearance logs updated successfully.',
            'logged_count' => $totalInserted,
        ]);
    }


    public function popUpShown(){
        $user = User::where(['id' => Auth::user()->id])->first();
        $user->previous_level_popup_shown = '1';
        $user->save();

        return response()->json(['success' => true, 'message' => 'Popup shown status updated.'], 200);
    }
}
