<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\FoodPlanner;
use App\Models\User;
use App\Models\UserLevel;
use App\Models\UserLevelReport;
use App\Models\UserLevelTask;
use App\Models\UserLevelTaskLog;
use App\Models\UserMorphology;
use App\Models\UserMorphologyBehaviourTest;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ApiController extends Controller
{
    use ApiResponser;

    public static function getLoginKey($user_id)
    {
        $salt = "23df$#%%^66sd$^%fg%^sjgdk90fdklndg099ndfg09LKJDJ*@##lkhlkhlsa#$%";
        $login_key = hash('sha1', $salt . $user_id . time());
        return $login_key;
    }

    public function pagination($data, $page, $limit)
    {
        $totalRecords = count($data);
        $start = ($page - 1) * $limit;
        $end = $start + $limit;
        $paginatedData = array_slice($data, $start, $limit);

        $hasMore = $end < $totalRecords;

        return [
            'data' => $paginatedData,
            'hasMore' => $hasMore,
        ];
    }

    public function logLevelTaskEntries($userId, $task_type, $completed_count, $oneEntry)
    {
        // Get all levelTaskList for the user
        $user = User::find($userId);
        $UserLevel = UserLevel::where('level', $user->level)->with('userLevelTasks')->first();

        if (!$UserLevel) {
            return 0;
        }

        if ($oneEntry == 1) {
            // Update existing record's completed_count or create if not exists
            $log = UserLevelTaskLog::where([
                'user_id' => $userId,
                'level_task_type' => $task_type,
            ])->latest()->first();
            if ($log) {
                $log->completed_count = $completed_count;
                $log->save();
            } else {
                UserLevelTaskLog::create([
                    'user_id' => $userId,
                    'level_task_type' => $task_type,
                    'completed_count' => $completed_count,
                ]);
            }
        } else {
            // Log the record in userTaskListLogs table
            UserLevelTaskLog::create([
                'user_id' => $userId,
                'level_task_type' => $task_type,
                'completed_count' => $completed_count,
            ]);
        }

        $total_completed_count = (int) UserLevelTaskLog::where([
            'user_id' => $userId,
            'level_task_type' => $task_type,
        ])->sum('completed_count');


        $allCompleted = true;
        foreach ($UserLevel->userLevelTasks as $task) {
            $total_completed_count = (int) UserLevelTaskLog::where([
                'user_id' => $userId,
                'level_task_type' => $task->task_type,
            ])->sum('completed_count');
            if ($total_completed_count < $task->total_count) {
                $allCompleted = false;
                break;
            }
        }

        if ($allCompleted) {
            $currentLevel = $user->level;

            // Set user's level to currentLevel + 1 in the users table
            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'level' => $currentLevel + 1,
                    'previous_level_popup_shown' => '0',
                ]);
            return $currentLevel;
        } else {
            return 0;
        }
    }

    public function getLevelDetails($level)
    {
        $userCurrentLevel = Auth::user()->level;

        // Check if the level exists
        $SelectedLevel = UserLevel::where('level', $level)->first();
        if (!$SelectedLevel) {
            return null; // Return null if level doesn't exist
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
                default:
                    $taskName = '';
                    break;
            }
            $task->task_name = $taskName;
            return $task;
        });

        // Fetch completed reports for this level
        $completedReports = UserLevelReport::where('user_id', Auth::user()->id)
            ->where('level', $level)
            ->get();

        $morphologyReportData = [];
        foreach ($completedReports as $completedReport) {
            $morphologyReportData[] = $this->formatReportDetailsForLevel($completedReport);
        }

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
            'morphology_report' => $morphologyReportData,
        ];

        return $levelData;
    }
    public function deleteLogWaterEntries($userId){

        $WaterLog = UserLevelTaskLog::where([
            'user_id' => $userId,
            'level_task_type' => "drink-water",
        ])->latest()->first();

        if ($WaterLog) {
            $WaterLog->delete();
        }
    }

    public function deleteLogMealTypeEntries($userId, $mealType, $date)
    {
        $count = FoodPlanner::where([
            'meal_type' => $mealType,
            'date' => $date,
            'user_id' => $userId
        ])->count();

        if ($count === 1) {
            // If only one record exists, delete the latest 'log-meals' entry from UserLevelTaskLog
            $log = UserLevelTaskLog::where('user_id', $userId)
                ->where('level_task_type', 'log-meals')
                ->latest()
                ->first();

            if ($log) {
                $log->delete();
            }
        }
    }


    public function deleteLogProtienEntries($userId, $proteins)
    {
        $remainingToDelete = $proteins;

        // Get all protein logs for the user, ordered by latest first
        $logs = UserLevelTaskLog::where([
            'user_id' => $userId,
            'level_task_type' => 'protiens',
        ])->orderBy('created_at', 'desc')->get();

        foreach ($logs as $log) {
            if ($remainingToDelete <= 0) {
                break;
            }

            if ($log->completed_count > $remainingToDelete) {
                // Reduce the completed_count and save
                $log->completed_count -= $remainingToDelete;
                $log->save();
                $remainingToDelete = 0;
            } else {
                // Delete the log and reduce the remainingToDelete
                $remainingToDelete -= $log->completed_count;
                $log->delete();
            }
        }
    }

    /**
     * Format session exercise data with support for new exercise flow
     * Fetches exercise data based on is_new_exercise_flow flag
     *
     * @param object $exerciseRecord SessionExercise model instance
     * @param object $session Session model instance
     * @return array Formatted exercise data
     */
    public function formatSessionExerciseData($exerciseRecord, $session)
    {
        $exerciseDetails = Exercise::where(['id' => $exerciseRecord->session_exercise_id])->first();

        if (!$exerciseDetails) {
            return [];
        }

        // Determine if using new exercise flow
        $isNewExerciseFlow = $session->is_new_exercise_flow ?? false;

        // Initialize with fallback values from Exercise table
        $exerciseForm = $exerciseDetails->exercise_form ?? '';
        $duration = $exerciseDetails->duration ?? '00:00:00';
        $restPeriod = $exerciseDetails->rest_period ?? '00:00:00';
        $noOfRepetition = $exerciseDetails->no_of_repetition ?? 0;
        $rangeOfRepetition = $exerciseDetails->range_of_repetition ?? 0;

        // Override with SessionExercise pivot data if new flow is enabled
        if ($isNewExerciseFlow) {
            // Use pivot data if available, otherwise fallback to Exercise table
            $restPeriod = $exerciseRecord->rest_period ?? $restPeriod;
            $exerciseForm = $exerciseRecord->exercise_form ?? $exerciseForm;

            $duration = $exerciseForm === 'repetition'
                ? "00:00:00"
                : ($exerciseRecord->duration ?? $duration);

            $noOfRepetition = $exerciseForm === 'duration'
                ? 0
                : ($exerciseRecord->repetition ?? $noOfRepetition);

            $rangeOfRepetition = $exerciseForm === 'duration'
                ? 0
                : ($exerciseRecord->repetition_range ?? $rangeOfRepetition);
        }

        // Format time fields (convert to H:i:s if not null)
        $durationFormatted = $duration ? date('H:i:s', strtotime($duration)) : '00:00:00';
        $restPeriodFormatted = $restPeriod ? date('H:i:s', strtotime($restPeriod)) : '00:00:00';

        return [
            'id' => $exerciseRecord->id,
            'session_id' => $exerciseRecord->session_id,
            'session_exercise_id' => $exerciseRecord->session_exercise_id,
            'exercise_name' => $exerciseDetails->title_for_frontend,
            'exercise_form' => $exerciseForm,
            'duration' => $durationFormatted,
            'rest_period' => $restPeriodFormatted,
            'no_of_repetition' => (int) $noOfRepetition,
            'range_of_repetition' => (int) $rangeOfRepetition,
            'is_new_exercise_flow' => (bool) $isNewExerciseFlow,
            'video_link' => $exerciseDetails->video,
            'instructions' => $exerciseDetails->instructions,
            'exercise_gif' => $exerciseDetails->gif ? asset(Storage::url($exerciseDetails->gif)) : '',
            'exercise_type' => $exerciseDetails->exercise_type,
            'body_zone' => $exerciseDetails->body_zone,
            'audio' => $exerciseDetails->audio ? asset(Storage::url($exerciseDetails->audio)) : '',
        ];
    }

    public function linkUserReportLevel($reportId, $reportCategory)
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $level = $user->level;

        $reportType = '';
        if ($reportCategory === 'morphology') {
            $report = UserMorphology::find($reportId);
            if ($report) {
                if ($report->type === 'Baseline Report') {
                    $reportType = 'baseline';
                } elseif ($report->type === 'Progress Report') {
                    $progressCount = UserMorphology::where('user_id', $user->id)
                        ->where('type', 'Progress Report')
                        ->where('generation_status', 'completed')
                        ->where('id', '<=', $reportId)
                        ->count();
                    $reportType = 'progress report ' . $progressCount;
                }
            }
        } elseif ($reportCategory === 'behaviour_test') {
            $report = UserMorphologyBehaviourTest::find($reportId);
            if ($report) {
                $reportType = 'behaviour test';
            }
        }

        if (empty($reportType)) {
            return;
        }

        UserLevelReport::updateOrCreate([
            'user_id' => $user->id,
            'level' => $level,
            'report_type' => $reportType,
        ], [
            'report_id' => $reportId,
            'report_category' => $reportCategory,
        ]);
    }

    public function getReportCompletenessScore($report, $reportCategory)
    {
        if ($reportCategory === 'behaviour_test') {
            return ($report->status === 'completed') ? 100 : 0;
        }

        if ($reportCategory === 'morphology') {
            $basicFields = ['gender', 'age', 'height', 'weight'];
            $basicInfoCompleted = collect($basicFields)->every(function ($field) use ($report) {
                return !empty($report->$field);
            }) ? 1 : 0;

            $primaryMeasurementFields = ['waist', 'hips', 'neck'];
            $allMeasurementFields = ['waist', 'hips', 'neck', 'bicep', 'chest', 'thighs', 'calfs'];

            $primaryMeasurementCompleted = collect($primaryMeasurementFields)->every(function ($field) use ($report) {
                return !empty($report->$field);
            });

            $allMeasurementCompleted = collect($allMeasurementFields)->every(function ($field) use ($report) {
                return !empty($report->$field);
            });

            $measurementStatus = 0;
            if ($allMeasurementCompleted) {
                $measurementStatus = 1;
            } elseif ($primaryMeasurementCompleted) {
                $measurementStatus = 2;
            }

            $imageFields = ['image_fv', 'image_bv', 'image_ls', 'image_rs'];
            $uploadedImagesCount = collect($imageFields)->filter(function ($field) use ($report) {
                $val = method_exists($report, 'getRawOriginal') ? $report->getRawOriginal($field) : $report->$field;
                return !empty($val);
            })->count();

            $pictureStatus = 0;
            if ($uploadedImagesCount === 4) {
                $pictureStatus = 1;
            } elseif ($uploadedImagesCount >= 1) {
                $pictureStatus = 2;
            }

            $goalSummaryCompleted = (!empty($report->target_weight) && !empty($report->report_email)) ? 1 : 0;

            $canCalculateScore = false;
            if ($report->type === 'Baseline Report') {
                $canCalculateScore = ($basicInfoCompleted == 1 && $goalSummaryCompleted == 1);
            } elseif ($report->type === 'Progress Report') {
                $canCalculateScore = !empty($report->weight);
            }

            $completenessScore = 0;
            if ($canCalculateScore) {
                if ($measurementStatus == 0 && $pictureStatus == 0) {
                    $completenessScore = 45;
                } elseif ($measurementStatus == 0 && ($pictureStatus == 1 || $pictureStatus == 2)) {
                    $completenessScore = 60;
                } elseif ($measurementStatus == 1 || $measurementStatus == 2) {
                    $completenessScore = 100;
                }
            }

            if ($report->generation_status !== 'completed') {
                $completenessScore = 0;
            }

            return $completenessScore;
        }

        return 0;
    }

    public function formatReportDetailsForLevel($completedReport)
    {
        $reportData = [
            'id' => $completedReport->report_id,
            'report_type' => $completedReport->report_type,
            'report_category' => $completedReport->report_category,
            'title' => '',
            'report_date' => '',
            'completeness_score' => 0,
            'session_id' => '',
        ];

        if ($completedReport->report_category === 'morphology') {
            $sourceReport = UserMorphology::find($completedReport->report_id);
            if ($sourceReport) {
                $reportData['title'] = $sourceReport->type;
                $reportData['report_date'] = $sourceReport->report_generated_at
                    ? \Carbon\Carbon::parse($sourceReport->report_generated_at)->format('d M Y')
                    : '';
                $reportData['completeness_score'] = $this->getReportCompletenessScore($sourceReport, 'morphology');
                $reportData['session_id'] = $sourceReport->session_id ?? '';
            }
        } elseif ($completedReport->report_category === 'behaviour_test') {
            $sourceReport = UserMorphologyBehaviourTest::find($completedReport->report_id);
            if ($sourceReport) {
                $reportData['title'] = 'Behaviour Test';
                $reportData['report_date'] = $sourceReport->report_generated_at
                    ? \Carbon\Carbon::parse($sourceReport->report_generated_at)->format('d M Y')
                    : '';
                $reportData['completeness_score'] = $this->getReportCompletenessScore($sourceReport, 'behaviour_test');
                $reportData['session_id'] = $sourceReport->session_id ?? '';
            }
        }

        return $reportData;
    }
}
