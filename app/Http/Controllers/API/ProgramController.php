<?php

namespace App\Http\Controllers\API;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\ProgramSession;
use App\Models\Session;
use App\Models\SessionMaterial;
use App\Models\User;
use App\Models\UserProgram;
use App\Models\UserSessionStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ProgramController extends ApiController
{
    public function programList(Request $request)
    {
        try {
            // Validate the incoming request
            $validation = [
                'search' => ['nullable', 'string'],
                'page' => ['nullable', 'integer'],
                'perPage' => ['nullable', 'integer'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            // Get pagination parameters
            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('perPage', 3);

            // Query active categories with explicit ordering
            $categoriesQuery = Category::where('status', 1)->orderBy('id', 'asc');
            $categories = $categoriesQuery->get();

            if ($categories->isEmpty()) {
                return $this->notFoundResponse("No categories found.", [], 200);
            }

            // Filter categories to include only those with programs
            $filteredCategories = $categories->filter(function ($category) {
                return $category->programs()->where('status', 1)->exists();
            });

            // Recalculate total categories and total pages
            $totalCategories = $filteredCategories->count();
            $totalPages = ceil($totalCategories / $perPage);

            // Check if the requested page exceeds the available pages
            if ($page > $totalPages) {
                return $this->notFoundResponse("No data available for this page.", [], 200);
            }

            // Paginate the filtered categories
            $paginatedCategories = $filteredCategories->slice(($page - 1) * $perPage, $perPage);

            $categoryWiseProgramList = [];
            foreach ($paginatedCategories as $category) {
                // Query programs for this category
                $programsQuery = $category->programs()->where('status', 1)->orderBy('id', 'asc');
                $programs = $programsQuery->get();

                $programList = [];
                foreach ($programs as $program) {
                    $getLevel = ProgramLevel::where('id', $program->level_id)->first();
                    $programAlreadyJoined = UserProgram::where([
                        'program_id' => $program->id,
                        'user_id' => Auth::user()->id,
                        'status' => 1,
                    ])->exists() ? 1 : 0;

                    $programList[] = [
                        'programId' => $program->id,
                        'programTitle' => $program->title,
                        'programImage' => $program->program_image ? asset(Storage::url($program->program_image)) : '',
                        'programLevel' => $getLevel->level_title ?? null,
                        "program_session_count" => $program['program_session'] ? collect($program['program_session'])->groupBy('session_week')->keys()->count() : 0,
                        'programAlreadyJoined' => $programAlreadyJoined,
                    ];
                }

                // Add the category with its programs to the final list
                $categoryWiseProgramList[] = [
                    'categoryId' => $category->id,
                    'categoryName' => $this->sanitizeText($category->category_name),
                    'categorySlug' => $category->slug,
                    'categoryDescription' => $category->description,
                    'categoryStatus' => $category->status,
                    'programs' => $programList,
                ];
            }

            // Prepare the response data
            $responseData = [
                'CategoriesList' => $categoryWiseProgramList,
                'currentPage' => $page,
                'totalPage' => $totalPages,
                'perPage' => $perPage,
                'totalCategory' => $totalCategories,
            ];

            if (count($categoryWiseProgramList) > 0) {
                return $this->successResponse("Categories and Programs Found", $responseData, 200);
            } else {
                return $this->notFoundResponse("No Programs Found", $responseData, 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
    public function programDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'program_id' => ['required', 'numeric'],
            ]);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            // Eager load only necessary relationships and fields
            $program = Program::with([
                'category:id,category_name,description,status',
                'level:id,level_title',
                'program_session',
                'program_session.session:id,title_for_frontend,difficulty_level,body_area,time,calories,session_type,summary'
            ])->where([
                'status' => 1,
                'id' => $request->program_id
            ])->first();

            if (!$program) {
                return $this->notFoundResponse("Program Not Found", (object)[], 200);
            }

            // Get user session statuses including session_id and session_week
            $userSessionStatuses = UserSessionStatus::where([
                'user_id' => Auth::user()->id,
                'program_id' => $program->id
            ])->get(['session_id', 'session_week', 'user_session_status'])
              ->keyBy(function ($item) {
                  return $item->session_id . '_' . $item->session_week;
              });

            // Find the first pending week
            $firstPendingWeek = null;
            foreach ($program->program_session->groupBy('session_week') as $week => $sessions) {
                $allCompleted = $sessions->every(function ($session) use ($userSessionStatuses) {
                    $key = $session->session_id . '_' . $session->session_week;
                    return ($userSessionStatuses[$key]->user_session_status ?? 0) == 1;
                });

                if (!$allCompleted) {
                    $firstPendingWeek = $week;
                    break;
                }
            }

            $programData = [
                'categoryId' => $program->category->id,
                'categoryName' => $this->sanitizeText($program->category->category_name),
                'categoryDescription' => $program->category->description,
                'categoryStatus' => $program->category->status,
                'programs' => [
                    'programId' => $program->id,
                    'programCategoryId' => $program->category_id,
                    'programTags' => explode("|", $program->program_tag),
                    'programTitle' => $program->title,
                    'programDescription' => $program->description,
                    'programImage' => $program->program_image ? asset(Storage::url($program->program_image)) : '',
                    'programVideos' => $program->video,
                    'programFreeAccess' => $program->free_access,
                    'programStatus' => $program->status,
                    'programSession' => $this->formatProgramSessions($program->program_session, $userSessionStatuses, $firstPendingWeek)
                ],
                'information' => [
                    'programObjective' => $program->objective,
                    'programLevel' => $program->level->level_title,
                    'programBodyArea' => $program->body_area,
                    'programDuration' => $program->duration . ' weeks',
                    'programFrequency' => $program->frequency . ' per week'
                ]
            ];

            return $this->successResponse("Program Found", $programData, 200);

        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    private function formatProgramSessions($programSessions, $userSessionStatuses, $firstPendingWeek)
    {
        return $programSessions->groupBy('session_week')
            ->map(function ($sessions, $week) use ($userSessionStatuses, $firstPendingWeek) {
                // Only process sessions for the first pending week or completed weeks
                if ($week != $firstPendingWeek && $firstPendingWeek !== null) {
                    return [
                        'week' => $week,
                        'session' => $sessions->map(function ($session) use ($userSessionStatuses) {
                            $key = $session->session_id . '_' . $session->session_week;
                            $sessionStatus = isset($userSessionStatuses[$key]) ? $userSessionStatuses[$key]->user_session_status : 0;
                            $status = $sessionStatus == 0 ? "pending" : "completed";
                            $sessionDetails = Session::where(['id' => $session->session_id])->first();
                            return [
                                'program_session_id' => $session->id,
                                'session_id' => $session->session_id,
                                'session_week' => $session->session_week,
                                'session_title' => $sessionDetails->title_for_frontend,
                                'difficulty_level' => $sessionDetails->difficulty_level,
                                'body_area' => $sessionDetails->body_area,
                                'time' => $sessionDetails->time,
                                'calories' => $sessionDetails->calories,
                                'session_type' => $sessionDetails->session_type,
                                'summary' => $sessionDetails->summary,
                                'user_session_status' => $status,
                                'session_material' => [],
                                'session_exercises' => []
                            ];
                        })
                    ];
                }

                return [
                    'week' => $week,
                    'session' => $sessions->map(function ($session) use ($userSessionStatuses) {
                        $key = $session->session_id . '_' . $session->session_week;
                        $sessionStatus = isset($userSessionStatuses[$key]) ? $userSessionStatuses[$key]->user_session_status : 0;
                        $status = $sessionStatus == 0 ? "pending" : "completed";
                        $sessionDetails = Session::where(['id' => $session->session_id])->first();
                        $sessionData = [
                            'program_session_id' => $session->id,
                            'session_id' => $session->session_id,
                            'session_week' => $session->session_week,
                            'session_title' => $sessionDetails->title_for_frontend,
                            'difficulty_level' => $sessionDetails->difficulty_level,
                            'body_area' => $sessionDetails->body_area,
                            'time' => $sessionDetails->time,
                            'calories' => $sessionDetails->calories,
                            'session_type' => $sessionDetails->session_type,
                            'summary' => $sessionDetails->summary,
                            'user_session_status' => $status,
                            'session_material' => [],
                            'session_exercises' => []
                        ];

                        $sessionWithDetails = Session::with(['materials', 'session_exercises', 'session_exercises.exercise'])->find($session->session_id);

                        $sessionData['session_material'] = $sessionWithDetails->materials->map(function ($material) {
                            return [
                                'id' => $material->id,
                                'material_name' => $material->material_name,
                                'material_image' => $material->material_image ? asset(Storage::url($material->material_image)) : '',
                            ];
                        });

                        $sessionData['session_exercises'] = $sessionWithDetails->session_exercises->map(function ($exercise) use ($sessionWithDetails) {
                            return $this->formatSessionExerciseData($exercise, $sessionWithDetails);
                        })->filter()->values();

                        return $sessionData;
                    })
                ];
            })->values()->toArray();
    }

    private function sanitizeText($text)
    {
        $text = $text ?? '';
        // Remove everything except Unicode letters and whitespace
        $clean = preg_replace('/[^\p{L}\s]/u', '', $text);
        // Collapse multiple spaces and trim
        $clean = preg_replace('/\s+/u', ' ', $clean);
        return trim($clean);
    }

    public function categoryWisePrograms(Request $request)
    {
        try {
            /* Validate Data */
            $validation = [
                'program_category_id' => ['required', 'numeric'],
                'search' => ['nullable', 'string'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $query = Category::where('status', 1)->with(['programs' => function ($query) use ($request) {
                if ($request->filled('search')) {
                    $search = $request->input('search');
                    $query->where(function ($query) use ($search) {
                        $query->where('title', 'like', '%' . $search . '%')
                            ->orWhere('body_area', 'like', '%' . $search . '%')
                            ->orWhere('duration', 'like', '%' . $search . '%')
                            ->orWhere('frequency', 'like', '%' . $search . '%');
                    });
                }
                $query->where('status', 1); // Ensure programs have status = 1
                $query->where('category_id', $request->program_category_id);
            }]);

            $getProgramList = $query->get();

            $categoryWiseProgramList = [];
            foreach ($getProgramList as $key => $categoryProgram) {
                $programList = [];
                foreach ($categoryProgram['programs'] as $key => $program) {
                    $getLevel = ProgramLevel::where(['id' => $program['level_id']])->first();
                    $programAlreadyJoined = UserProgram::where([
                        'program_id' => $program->id,
                        'user_id' => Auth::user()->id,
                        'status' => 1,
                    ])->exists() ? 1 : 0;

                    $programList[] = [

                        'programId'             => $program['id'],
                        'programTitle'          => $program['title'],
                        'programImage'          => $program['program_image'] ? asset(Storage::url($program['program_image'])) : '',
                        'programLevel'          => $getLevel['level_title'],
                        'program_session_count' => $program['program_session'] ? collect($program['program_session'])->groupBy('session_week')->keys()->count() : 0,
                        'programAlreadyJoined'  => $programAlreadyJoined,
                    ];
                }

                $programList = collect($programList);

                if ($programList->isNotEmpty()) {
                    $categoryWiseProgramList[] = [
                        'categoryId'            => $categoryProgram['id'],
                        'categoryName'          => $this->sanitizeText($categoryProgram['category_name']),
                        'categorySlug'          => $categoryProgram['slug'],
                        'categoryDescription'   => $categoryProgram['description'],
                        'categoryStatus'        => $categoryProgram['status'],
                        'programs'              => $programList,
                    ];
                }
            }

            (int) $limit = 15;
            if (!isset($request->page) && (!isset($request->page))) {
                (int) $page = 1;
            } else {
                (int) $page = $request->page;
            }

            $result = $this->pagination($categoryWiseProgramList, $page, $limit);

            if (count($categoryWiseProgramList) > 0) {
                return $this->successResponse("Program Found", $result['data'], 200);
            } else {
                return $this->notFoundResponse("Program Not Found", (object)[], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function updateUserSessionStatus(Request $request)
    {
        try {
            $firstDayOfWeek = date('Y-m-d', strtotime('monday this week'));
            $lastDayOfWeek = date('Y-m-d', strtotime('sunday this week'));
            $todayDate = date('Y-m-d');

            // Validation
            $request->validate([
                'program_id' => ['required', 'numeric'],
                'program_session_id' => ['required', 'numeric'],
                'user_session_status' => ['required', 'numeric', 'in:0,1'],
            ], [
                'program_id.required' => "Program Id is required.",
                'program_id.numeric' => "Program Id must be a numeric value.",
                'program_session_id.required' => "Program Session Id is required.",
                'program_session_id.numeric' => "Program Session Id must be a numeric value.",
                'user_session_status.required' => "User Session Status is required.",
                'user_session_status.numeric' => "User Session Status must be a numeric value.",
                'user_session_status.in' => "User Session Status must be 0 or 1. 0: Pending, 1: Completed.",
            ]);

            $user = Auth::user();

            // Get session_id and session_week from program_sessions
            $programSession = ProgramSession::where('id', $request->program_session_id)
                ->where('program_id', $request->program_id)
                ->first();

            if (!$programSession) {
                return $this->notFoundResponse("Program Session Not Found", (object)[], 200);
            }

            $session_id = $programSession->session_id; // Fetch session_id
            $session_week = $programSession->session_week; // Fetch session_week

            // Check if the user is subscribed
            if ($user->isSubscribedUser === 1) {
                $userAssignedProgram = UserProgram::where([
                    'user_id' => $user->id,
                    'program_id' => $request->program_id
                ])->first();

                if (!$userAssignedProgram) {
                    return $this->successResponse("You have not been assigned this program.", (object)[], 200);
                }
            }

            // Check if user session status exists with session_week
            $userSessionStatus = UserSessionStatus::where([
                'user_id' => $user->id,
                'program_id' => $request->program_id,
                'session_id' => $session_id,
                'session_week' => $session_week
            ])->first();

            if ($userSessionStatus) {
                $userSessionStatus->update([
                    'user_session_status' => $request->user_session_status,
                    'program_session_id' => $request->program_session_id // Store program_session_id
                ]);
            } else {
                $userSessionStatus = UserSessionStatus::create([
                    'user_id' => $user->id,
                    'program_id' => $request->program_id,
                    'session_id' => $session_id,
                    'session_week' => $session_week,
                    'program_session_id' => $request->program_session_id, // Store program_session_id
                    'user_session_status' => $request->user_session_status
                ]);
            }

            // Update user streak for subscribed users
            if ($user->isSubscribedUser === 1 && ($todayDate >= $firstDayOfWeek && $todayDate <= $lastDayOfWeek)) {
                $userAssignedProgram->update([
                    'user_streak' => $userAssignedProgram->user_streak === 3 ? 1 : $userAssignedProgram->user_streak + 1
                ]);
            }

            // Check if all sessions are completed by the user
            $allSessions = ProgramSession::where('program_id', $request->program_id)->get();
            $allSessionIdsWithWeeks = $allSessions->map(function ($item) {
                return $item->session_id . '_' . $item->session_week;
            });

            $userSessionStatuses = UserSessionStatus::where([
                'user_id' => $user->id,
                'program_id' => $request->program_id
            ])->get()->keyBy(function ($item) {
                return $item->session_id . '_' . $item->session_week;
            });

            $isLastSessionOfLastWeek = $allSessionIdsWithWeeks->every(function ($key) use ($userSessionStatuses) {
                return isset($userSessionStatuses[$key]) && $userSessionStatuses[$key]->user_session_status == 1;
            }) ? 1 : 0;

            return $this->successResponse("User Session Status Updated", [
                'id' => (int) $userSessionStatus->id,
                'user_id' => (int) $userSessionStatus->user_id,
                'program_id' => (int) $userSessionStatus->program_id,
                'program_session_id' => (int) $userSessionStatus->program_session_id,
                'session_id' => (int) $userSessionStatus->session_id,
                'session_week' => $userSessionStatus->session_week, // Keep as string
                'user_session_status' => (int) $userSessionStatus->user_session_status,
                'created_at' => $userSessionStatus->created_at,
                'updated_at' => $userSessionStatus->updated_at,
                'isLastSessionOfLastWeek' => $isLastSessionOfLastWeek
            ], 200);

        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function joinProgram(Request $request)
    {
        try {
            /* Validate Data */
            $validation = [
                'program_id' => ['required', 'numeric'],
            ];

            $validation_messages = [
                'program_id.required' => 'Please select program you want to join.',
            ];
            $validator = Validator::make($request->all(), $validation, $validation_messages);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $data = $request->all();

            if (Auth::check()) {

                if(Program::where(['id' => $data['program_id']])->count() > 0){
                    if (UserProgram::where(['program_id' => $data['program_id'], 'user_id' => Auth::user()->id, 'status' => 1])->count() > 0) {
                        return $this->successResponse("You already joined this program", (object)[], 200);
                    }

                    if (UserProgram::where(['user_id' => Auth::user()->id, 'status' => 1])->count() > 0) {
                        UserProgram::where(['user_id' => Auth::user()->id, 'status' => 1])->update(['program_id' => $data['program_id'], 'join_date' => Carbon::today(), 'user_streak' => 0]);
                    } else {
                        $userProgram = new UserProgram;
                        $userProgram->user_id = Auth::user()->id;
                        $userProgram->program_id = $data['program_id'];
                        $userProgram->join_date = date("Y-m-d");
                        $userProgram->status = 1;
                        $userProgram->user_streak = 0;
                        $userProgram->save();
                    }
                }else{
                    return $this->successResponse("Program Not Found", (object)[], 200);
                }

                $userAssignedProgram = UserProgram::with('program')->where(['user_id' => Auth::user()->id])->first();
                if (!empty($userAssignedProgram)) {
                    $program = Program::with('category', 'program_session')->where(['status' => 1, 'id' => $userAssignedProgram['program_id']])->first();

                    $videos = explode("|", asset(Storage::url($program['video'])));
                    foreach ($videos as $video) {
                        $programVideos[] = asset(Storage::url($video));
                    }
                    $getLevel = ProgramLevel::where(['id' => $program['level_id']])->first();

                    $programData = [
                        'categoryId' => $program['category']['id'],
                        'categoryName' => $this->sanitizeText($program['category']['category_name']),
                        'categorySlug' => $program['category']['slug'],
                        'categoryDescription' => $program['category']['description'],
                        'categoryStatus' => $program['category']['status'],
                        'programs' => [
                            'programId' => $program['id'],
                            'programCategoryId' => $program['category_id'],
                            'programTags' => explode("|", $program['program_tag']),
                            'programTitle' => $program['title'],
                            'programDescription' => $program['description'],
                            'programObjective' => $program['objective'],
                            'programSlug' => $program['slug'],
                            'programTrailer' => asset(Storage::url($program['trailer'])),
                            // 'programVideos' => $programVideos,
                            'programLevel' => $getLevel['level_title'],
                            'programBodyArea' => $program['body_area'],
                            'programDuration' => $program['duration'],
                            'programFrequency' => $program['frequency'],
                            'programCardioId' => $program['cardio_id'],
                            'programMuscleStrengthId' => $program['muscle_strength_id'],
                            'programFreeAccess' => $program['free_access'],
                            'programStatus' => $program['status'],
                            'programSession' => collect($program->program_session)->groupBy('session_week')->map(function ($sessions, $week) {
                                return [
                                    'week' => $week, // Use $week as the key for week name
                                    'session' => $sessions->map(function ($session) {
                                        $sessionDetails = Session::where(['id' => $session->session_id])->first();
                                        $getUserSessionStatus = UserSessionStatus::where(['user_id' => Auth::user()->id, 'program_id' => $session->program_id, 'program_session_id' => $session->id])->first();
                                        if (!$getUserSessionStatus) {
                                            $sessionStatus = 0;
                                        } else {
                                            $sessionStatus = $getUserSessionStatus->user_session_status;
                                        }

                                        if ($sessionStatus == 0) {
                                            $status = "pending";
                                        } else {
                                            $status = "completed";
                                        }
                                        return [
                                            'session_id' => $session->session_id,
                                            'session_title' => $sessionDetails->title_for_frontend,
                                            'difficulty_level' => $sessionDetails->difficulty_level,
                                            'body_area' => $sessionDetails->body_area,
                                            'time' => $sessionDetails->time,
                                            'calories' => $sessionDetails->calories,
                                            'session_type' => $sessionDetails->session_type,
                                            'summary' => $sessionDetails->summary,
                                            'user_session_status' => $status,
                                            'session_exercises' => $session->session_exercises->map(function ($exercise) use ($session) {
                                                $sessionDetails = Session::where(['id' => $session->session_id])->first();
                                                return $this->formatSessionExerciseData($exercise, $sessionDetails);
                                            })->filter()->values()
                                        ];
                                    })
                                ];
                            })->values()->toArray()
                        ],
                        'information' => [
                            'programObjective' => $program['objective'],
                            'programLevel' => $getLevel['level_title'],
                            'programBodyArea' => $program['body_area'],
                            'programDuration' => $program['duration'] . ' weeks',
                            'programFrequency' => $program['frequency'] . ' per week'
                        ],
                        'user_streak' => $userAssignedProgram->user_streak
                    ];

                    return $this->successResponse("You just joined a program", $programData, 200);
                } else {
                    return $this->successResponse("Failed to join program", (object)[], 200);
                }
            } else {
                return $this->successResponse("You does not have permission.", (object)[], 400);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}
