<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\ApiController;
use App\Models\Activity;
use App\Models\TrackedActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ActivityController extends ApiController
{
    // List all activities with search and tracking info
    public function listActivities(Request $request)
    {
        try {
            $validation = [
                'search'  => ['nullable', 'string'],
                'date'    => ['required', 'date'],
                'page'    => ['nullable', 'integer'],
                'perPage' => ['nullable', 'integer'],
            ];

            $validator = Validator::make($request->all(), $validation);
            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $userId  = Auth::id();
            $date    = $request->date;
            $page    = (int) $request->input('page', 1);
            $perPage = (int) $request->input('perPage', 10);

            // Get total count of tracked activities on this day by this user (independent query)
            $loggedTodayCount = TrackedActivity::where('user_id', $userId)
                ->whereDate('activity_date', $date)
                ->count();

            // Check if any activity is logged today
            $isLoggedToday = $loggedTodayCount > 0;


            /** -----------------------------
             * Base Query
             * ----------------------------- */
            $query = Activity::query()
                ->when($request->search, function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%');
                })
                ->with([
                    'trackedActivities' => function ($q) use ($userId, $date) {
                        $q->where('user_id', $userId)
                        ->whereDate('activity_date', $date);
                    }
                ])
                ->orderBy('name', 'asc');

            /** -----------------------------
             * Total Count & Pages
             * ----------------------------- */
            $totalRecords = $query->count();
            $totalPages   = ceil($totalRecords / $perPage);

            if ($totalRecords === 0 || $page > $totalPages) {
                return $this->notFoundResponse("No activities found.", [
                    'activities'   => [],
                    'currentPage'  => $page,
                    'totalPage'    => $totalPages,
                    'perPage'      => $perPage,
                    'totalRecords' => $totalRecords,
                    'isLoggedToday' => $isLoggedToday,
                    'loggedTodayCount' => $loggedTodayCount,
                ], 200);
            }

            /** -----------------------------
             * Paginated Data
             * ----------------------------- */
            $activities = $query
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $activityList = $activities->map(function ($activity) {
                $tracked = $activity->trackedActivities->first();

                return [
                    'id'         => $activity->id,
                    'name'       => $activity->name,
                    'icon'       => $activity->image,
                    'is_tracked' => $tracked ? true : false,

                    'tracked_activity' => $tracked ? [
                        'id'               => $tracked->id,
                        'duration_minutes' => $tracked->duration_minutes,
                        'calories_burned'  => $tracked->calories_burned,
                    ] : (object)[],
                ];
            });

            /** -----------------------------
             * Final Response
             * ----------------------------- */
            $responseData = [
                'activities'   => $activityList,
                'currentPage'  => $page,
                'totalPage'    => $totalPages,
                'perPage'      => $perPage,
                'totalRecords' => $totalRecords,
                'isLoggedToday' => $isLoggedToday, // Whether any task is logged today
                'loggedTodayCount' => $loggedTodayCount, // Number of tasks logged today
            ];

            return $this->successResponse("Activity found successfully!", $responseData, 200);

        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }


    // List all tracked activities for the authenticated user
    public function listTrackedActivities(Request $request)
    {
        try {
            $validation = [
                'date'    => ['required', 'date'],
                'page'    => ['nullable', 'integer'],
                'perPage' => ['nullable', 'integer'],
            ];

            $validator = Validator::make($request->all(), $validation);
            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $userId  = Auth::id();
            $date    = $request->date;
            $page    = (int) $request->input('page', 1);
            $perPage = (int) $request->input('perPage', 10);

            /** --------------------------------
             * Base Query (ONLY logged activities)
             * -------------------------------- */
            $query = TrackedActivity::where('user_id', $userId)
                ->whereDate('activity_date', $date)
                ->with('activity')
                ->orderBy('activity_date', 'desc');

            /** --------------------------------
             * Count & Pages
             * -------------------------------- */
            $totalRecords = $query->count();
            $totalPages   = ceil($totalRecords / $perPage);

            if ($totalRecords === 0 || $page > $totalPages) {
                return $this->notFoundResponse("No tracked activities found.", [
                    'activities'   => [],
                    'currentPage'  => $page,
                    'totalPage'    => $totalPages,
                    'perPage'      => $perPage,
                    'totalRecords' => $totalRecords,
                ], 200);
            }

            /** --------------------------------
             * Paginated Data
             * -------------------------------- */
            $trackedActivities = $query
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            /** --------------------------------
             * Response Mapping (Same Structure)
             * -------------------------------- */
            $activityList = $trackedActivities->map(function ($tracked) {
                return [
                    'id'         => $tracked->activity->id,
                    'name'       => $tracked->activity->name,
                    'icon'       => $tracked->activity->image,
                    'is_tracked' => true,

                    'tracked_activity' => [
                        'id'               => $tracked->id,
                        'duration_minutes' => $tracked->duration_minutes,
                        'calories_burned'  => $tracked->calories_burned,
                    ],
                ];
            });

            /** --------------------------------
             * Final Response
             * -------------------------------- */
            $responseData = [
                'activities'   => $activityList,
                'currentPage'  => $page,
                'totalPage'    => $totalPages,
                'perPage'      => $perPage,
                'totalRecords' => $totalRecords,
            ];

            return $this->successResponse("Tracked activities found successfully!", $responseData, 200);

        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    // Track a new activity
    public function trackActivity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'activity_id'       => 'required|integer|exists:activities,id',
            'duration_minutes'  => 'required|numeric|min:1|max:1440',
            'activity_date'     => 'required|date',
            'notes'             => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError("Fail", $validator->errors()->first(), 200);
        }

        // Check if activity exists
        $activity = Activity::where('id', $request->activity_id)->first();
        if (!$activity) {
            return $this->errorResponse("Activity not found", [], 200);
        }

        // Prevent duplicate activity tracking for same day
        $alreadyTracked = TrackedActivity::where('user_id', Auth::id())
            ->where('activity_id', $request->activity_id)
            ->where('activity_date', $request->activity_date)
            ->first();

        if ($alreadyTracked) {
            return $this->errorResponse("This activity has already been tracked for the selected date", [], 200);
        }

        $trackedActivity = TrackedActivity::create([
            'user_id'           => Auth::id(),
            'activity_id'       => $activity->id,
            'duration_minutes'  => $request->duration_minutes,
            'activity_date'     => $request->activity_date,
            'notes'             => $request->notes,
            'calories_burned'   => $this->calculateCaloriesForActivity(
                $activity->default_burnout_per_hour,
                $request->duration_minutes
            ),
        ]);

        return $this->successResponse( "Activity tracked successfully", $trackedActivity, 200 );
    }

    // Update a tracked activity
    public function updateTrackedActivity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'                => 'required|integer',
            'activity_date'     => 'required|date',
            'duration_minutes'  => 'required|numeric|min:1|max:1440',
            'notes'             => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError(
                "Fail",
                $validator->errors()->first(),
                200
            );
        }

        // Fetch record with id + date + user in one query
        $trackedActivity = TrackedActivity::where('id', $request->id)
            ->where('activity_date', $request->activity_date)
            ->where('user_id', Auth::id())
            ->first();

        if (!$trackedActivity) {
            return $this->errorResponse(
                "Tracked activity not found or unauthorized",
                [],
                200
            );
        }

        $trackedActivity->update([
            'duration_minutes' => $request->duration_minutes,
            'notes'            => $request->notes,
            'calories_burned'  => $this->calculateCaloriesForActivity(
                $trackedActivity->activity->default_burnout_per_hour,
                $request->duration_minutes
            ),
        ]);

        return $this->successResponse(
            "Tracked activity updated successfully",
            $trackedActivity,
            200
        );
    }

    // Delete a tracked activity
    public function deleteTrackedActivity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'   => 'required|integer',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError(
                "Fail",
                $validator->errors()->first(),
                200
            );
        }

        $trackedActivity = TrackedActivity::where('id', $request->id)
            ->where('activity_date', $request->date)
            ->where('user_id', Auth::id())
            ->first();

        if (!$trackedActivity) {
            return $this->errorResponse(
                "Fail",
                "Tracked activity not found or you are not authorized to delete it",
                200
            );
        }

        $trackedActivity->delete();

        return $this->successResponse(
            "Tracked activity deleted successfully",
            [],
            200
        );
    }

    // Calculate calories for an activity
    public function calculateCalories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'activity_id'      => 'required|integer',
            'duration_minutes'=> 'required|numeric|min:1|max:1440',
        ]);

        if ($validator->fails()) {
            return $this->validationError(
                "Fail",
                $validator->errors()->first(),
                200
            );
        }

        // Fetch only what is required and store in cache(FAST)
        $activity = Cache::remember(
            "activity_burn_rate_{$request->activity_id}",
            3600,
            fn () => Activity::select('id','name','default_burnout_per_hour')
                ->find($request->activity_id)
        );


        if (!$activity) {
            return $this->errorResponse(
                "Activity not found",
                [],
                200
            );
        }

        $calories = $this->calculateCaloriesForActivity(
            $activity->default_burnout_per_hour,
            $request->duration_minutes
        );

        return $this->successResponse(
            "Calories calculated successfully",
            [
                'calories_burned'  => round($calories, 2),
                'duration_minutes'=> (int) $request->duration_minutes,
                'activity'        => $activity->name,
            ],
            200
        );
    }

    // Helper method to calculate calories
    private function calculateCaloriesForActivity($caloriesPerHour, $minutes)
    {
        return round(($caloriesPerHour / 60) * $minutes, 2);
    }
    
    public function calendarWiseActivityTrackedRecord(Request $request)
    {
        try {
            $validation = [
                'date' => ['required', 'date', 'date_format:Y-m'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $activityDate = TrackedActivity::where('user_id', Auth::user()->id)
                ->where('activity_date', 'like', $request->date . '%')
                ->get()->toArray();
            if ($activityDate) {
                return $this->successResponse("data found", $activityDate, 200);
            }
            return $this->successResponse("data not found", $activityDate, 200);
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}