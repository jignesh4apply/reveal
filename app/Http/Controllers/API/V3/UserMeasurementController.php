<?php

namespace App\Http\Controllers\API\V3;

use App\Http\Controllers\API\V2\UserMeasurementController as APIUserMeasurementControllerV2;

use App\Models\UserMorphology;
use App\Models\UserMorphologyBehaviourTest;
use App\Models\UsersAppearanceInfo;
use App\Models\UserProgressImage;
use App\Models\UsersCurrentMeasurement;
use App\Models\UsersInitialMeasurement;
use App\Models\UsersTargetMeasurement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;


class UserMeasurementController extends APIUserMeasurementControllerV2
{
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

            $morphologyData = $this->morphologyData();

            $responseData['Evolution'] = $evolution;
            $responseData['Measurement'] = $userEvolutionData;
            $responseData['Morphology'] = $morphologyData;
            return $this->successResponse("Data Found", $responseData, 200);
        } catch (Throwable $th) {
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
                ],
                [
                    'is_locked' => 1,
                    'title' => 'Behaviour Test',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => 15,
                ],
                [
                    'is_locked' => 1,
                    'title' => 'Progress Report',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => 30,
                ]
            ];
        }

        foreach ($morphologies as $index => $morphology) {

            // =========================
            // Completeness Score Logic
            // =========================

            $completenessScore = 0;

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

                // Final Completeness Score
                if ($basicInfoCompleted == 1 && $goalSummaryCompleted == 1) {

                    if ($measurementStatus == 0 && $pictureStatus == 0) {
                        $completenessScore = 45;
                    } elseif ($measurementStatus == 0 && ($pictureStatus == 1 || $pictureStatus == 2)) {
                        $completenessScore = 60;
                    } elseif (($measurementStatus == 1 || $measurementStatus == 2) && $pictureStatus == 0) {
                        $completenessScore = 80;
                    } elseif (($measurementStatus == 1 || $measurementStatus == 2) && $pictureStatus == 2) {
                        $completenessScore = 90;
                    } elseif (($measurementStatus == 1 || $measurementStatus == 2) && $pictureStatus == 1) {
                        $completenessScore = 100;
                    }
                }
            } elseif ($morphology->type === 'Behaviour Test') {
                if ($morphology->generation_status === 'completed') {
                    $completenessScore = 100;
                } else {
                    $completenessScore = 0;
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
            ];
        }

        // =========================================
        // Append Next Upcoming Report Logic
        // =========================================

        $lastReportObj = $morphologies->last();
        $lastReportArray = end($reports);

        $now = Carbon::now()->startOfDay();

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
                ];

                $reports[] = [
                    'is_locked' => 1,
                    'title' => 'Progress Report',
                    'report_date' => '',
                    'completeness_score' => 0,
                    'is_report_completed' => 0,
                    'session_id' => '',
                    'unlock_in_days' => 30,
                ];
            }
        } elseif ($lastReportObj->type === 'Behaviour Test' || $lastReportObj->type === 'Progress report' || $lastReportObj->type === 'Progress Report') {

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
                ];
            }
        }

        return $reports;
    }
}
