<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TaskMilestone;
use App\Models\UserLevelTaskLog;
use App\Models\userMilestone;
use App\Models\UsersAppearanceInfo;
use App\Models\UsersCurrentMeasurement;
use App\Models\UsersInitialMeasurement;
use App\Models\UsersTargetMeasurement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UserMeasurementController extends ApiController
{
    public function userMeasures(Request $request)
    {
        try {
            $data = $request->all();
            $fields = ['weight', 'waist', 'chest', 'bicep', 'neck', 'thighs', 'hips', 'calfs'];

            // Prepare only the fields that are present in the request
            $updateData = [];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = number_format((float)$data[$field], 1, '.', '');
                }
            }

            // Measurement Type 0 (Initial Measurement)
            if ($request->measurementType == 0) {
                $usersInitialMeasurement = UsersInitialMeasurement::where([
                    'user_id' => Auth::user()->id,
                    'last_modified_date' => Carbon::today()
                ])->orderBy('id', 'desc')->first();

                if ($usersInitialMeasurement) {
                    $usersInitialMeasurement->update(array_merge($updateData, [
                        'last_modified_date' => Carbon::today(),
                    ]));
                    $updatedData = UsersInitialMeasurement::find($usersInitialMeasurement->id);
                    foreach ($fields as $field) {
                        $updatedData->$field = number_format((float)$updatedData->$field, 1, '.', '');
                    }
                    return $this->successResponse('User initial measurement updated successfully.', $updatedData, 200);
                } else {
                    $usersInitialMeasurement = new UsersInitialMeasurement;
                    $usersInitialMeasurement->user_id = Auth::user()->id;
                    foreach ($fields as $field) {
                        $usersInitialMeasurement->$field = isset($updateData[$field]) ? $updateData[$field] : 0;
                    }
                    $usersInitialMeasurement->added_date = Carbon::today();
                    $usersInitialMeasurement->last_modified_date = Carbon::today();
                    $usersInitialMeasurement->save();
                    // Format fields as string with decimal
                    foreach ($fields as $field) {
                        $usersInitialMeasurement->$field = number_format((float)$usersInitialMeasurement->$field, 1, '.', '');
                    }
                    return $this->successResponse("User initial measurement added successfully.", $usersInitialMeasurement, 200);
                }
            }

            // Measurement Type 1 (Target Measurement)
            if ($request->measurementType == 1) {
                $usersTargetMeasurement = UsersTargetMeasurement::where([
                    'user_id' => Auth::user()->id,
                    'last_modified_date' => Carbon::today()
                ])->orderBy('id', 'desc')->first();

                if ($usersTargetMeasurement) {
                    $usersTargetMeasurement->update(array_merge($updateData, [
                        'last_modified_date' => Carbon::today(),
                    ]));
                    $updatedData = UsersTargetMeasurement::find($usersTargetMeasurement->id);
                    foreach ($fields as $field) {
                        $updatedData->$field = number_format((float)$updatedData->$field, 1, '.', '');
                    }
                    return $this->successResponse('User target measurement updated successfully.', $updatedData, 200);
                } else {
                    $usersTargetMeasurement = new UsersTargetMeasurement;
                    $usersTargetMeasurement->user_id = Auth::user()->id;
                    foreach ($fields as $field) {
                        $usersTargetMeasurement->$field = isset($updateData[$field]) ? $updateData[$field] : 0;
                    }
                    $usersTargetMeasurement->last_modified_date = Carbon::today();
                    $usersTargetMeasurement->save();
                    // Format fields as string with decimal
                    foreach ($fields as $field) {
                        $usersTargetMeasurement->$field = number_format((float)$usersTargetMeasurement->$field, 1, '.', '');
                    }
                    return $this->successResponse("User target measurement added successfully.", $usersTargetMeasurement, 200);
                }
            }

            // Measurement Type 2 (Current Measurement)
            if ($request->measurementType == 2) {
                $usersCurrentMeasurement = UsersCurrentMeasurement::where([
                    'user_id' => Auth::user()->id,
                    'isWeight' => 0,
                    'last_modified_date' => Carbon::today()
                ])->orderBy('id', 'desc')->first();

                if ($usersCurrentMeasurement) {
                    $usersCurrentMeasurement->update(array_merge($updateData, [
                        'last_modified_date' => Carbon::today(),
                    ]));
                    $updatedData = UsersCurrentMeasurement::find($usersCurrentMeasurement->id);
                    foreach ($fields as $field) {
                        $updatedData->$field = number_format((float)$updatedData->$field, 1, '.', '');
                    }
                    return $this->successResponse('User current measurement updated successfully.', $updatedData, 200);
                } else {
                    $usersCurrentMeasurement = new UsersCurrentMeasurement;
                    $usersCurrentMeasurement->user_id = Auth::user()->id;
                    foreach ($fields as $field) {
                        $usersCurrentMeasurement->$field = isset($updateData[$field]) ? $updateData[$field] : 0;
                    }
                    $usersCurrentMeasurement->last_modified_date = Carbon::today();
                    $usersCurrentMeasurement->save();
                    foreach ($fields as $field) {
                        $usersCurrentMeasurement->$field = number_format((float)$usersCurrentMeasurement->$field, 1, '.', '');
                    }
                    return $this->successResponse("User current measurement added successfully.", $usersCurrentMeasurement, 200);
                }
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function getUserMeasurements(Request $request)
    {
        // echo "<pre>";print_r(Auth::user()->id);die;
        try {
            $userMeasurement = UsersInitialMeasurement::where(['user_id' => Auth::user()->id])->first();
            $userCurrentMeasurement = UsersCurrentMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
            $userTargetMeasurement = UsersTargetMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
            $starting = (object)[];
            $targetGoals = (object)[];
            $current = (object)[];

        if (!empty($userMeasurement)) {
            $starting = [
                'id' => $userMeasurement->id ?? 0,
                'user_id' => $userMeasurement->user_id ?? 0,
                'waist' => number_format($userMeasurement->waist ?? 0, 1, '.', ''),
                'chest' => number_format($userMeasurement->chest ?? 0, 1, '.', ''),
                'bicep' => number_format($userMeasurement->bicep ?? 0, 1, '.', ''),
                'neck' => number_format($userMeasurement->neck ?? 0, 1, '.', ''),
                'thighs' => number_format($userMeasurement->thighs ?? 0, 1, '.', ''),
                'calfs' => number_format($userMeasurement->calfs ?? 0, 1, '.', ''),
                'hips' => number_format($userMeasurement->hips ?? 0, 1, '.', ''),
                'weight' => number_format($userMeasurement->weight ?? 0, 1, '.', ''),
            ];
        }

        if (!empty($userCurrentMeasurement)) {
            $current = [
                'id' => $userCurrentMeasurement->id ?? 0,
                'user_id' => $userCurrentMeasurement->user_id ?? 0,
                'waist' => number_format($userCurrentMeasurement->waist ?? 0, 1, '.', ''),
                'chest' => number_format($userCurrentMeasurement->chest ?? 0, 1, '.', ''),
                'bicep' => number_format($userCurrentMeasurement->bicep ?? 0, 1, '.', ''),
                'neck' => number_format($userCurrentMeasurement->neck ?? 0, 1, '.', ''),
                'thighs' => number_format($userCurrentMeasurement->thighs ?? 0, 1, '.', ''),
                'calfs' => number_format($userCurrentMeasurement->calfs ?? 0, 1, '.', ''),
                'hips' => number_format($userCurrentMeasurement->hips ?? 0, 1, '.', ''),
                'weight' => number_format($userCurrentMeasurement->weight ?? 0, 1, '.', ''),
            ];
        }

        if (!empty($userTargetMeasurement)) {
            $targetGoals = [
                'id' => $userTargetMeasurement->id ?? 0,
                'user_id' => $userTargetMeasurement->user_id ?? 0,
                'waist' => number_format($userTargetMeasurement->waist ?? 0, 1, '.', ''),
                'chest' => number_format($userTargetMeasurement->chest ?? 0, 1, '.', ''),
                'bicep' => number_format($userTargetMeasurement->bicep ?? 0, 1, '.', ''),
                'neck' => number_format($userTargetMeasurement->neck ?? 0, 1, '.', ''),
                'thighs' => number_format($userTargetMeasurement->thighs ?? 0, 1, '.', ''),
                'calfs' => number_format($userTargetMeasurement->calfs ?? 0, 1, '.', ''),
                'hips' => number_format($userTargetMeasurement->hips ?? 0, 1, '.', ''),
                'weight' => number_format($userTargetMeasurement->weight ?? 0, 1, '.', ''),
            ];
        }


            $responseData['starting'] = $starting;
            $responseData['currentMeasurement'] = $current;
            $responseData['targetGoals'] = $targetGoals;

            if (!empty($responseData['starting']) || !empty($responseData['targetGoals'])) {
                return $this->successResponse("Data Found", $responseData, 200);
            } else {
                return $this->successResponse("Data Not Found", (object)[], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function addEvolution(Request $request)
    {
        try {
            $rules = [
                'imageDate' => ['required', 'date', 'date_format:Y-m-d'],
                'image' => ['required', 'image', 'mimes:jpeg,jpg,png']
            ];

            $message = [
                'imageDate.required' => "Please select date.",
                'imageDate.date-format' => "Please provide image in Y-m-d format only.",
                'image.required' => "Please select an image.",
                'image.mimes' => "Choose an image with jpeg,jpg and in png format only"
            ];
            $validator = Validator::make($request->all(), $rules, $message);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }
            if ($request->has('image')) {
                $appearanceImage = time() . '.' . $request->image->extension();
                if (!Storage::disk('public')->exists("/user/appearance/image")) {
                    Storage::disk('public')->makeDirectory("/user/appearance/image"); //creates directory
                }

                $request->image->storeAs("user/appearance/image", $appearanceImage, 'public');
                $data['image'] = "user/appearance/image/$appearanceImage";
            }
            $userAppearance = new UsersAppearanceInfo;
            $userAppearance->user_id = Auth::user()->id;
            $userAppearance->imageDate = $request->imageDate;
            $userAppearance->image = $data['image'];
            $userAppearance->save();

            $userAppearancedata = [
                'user_id' => $userAppearance->user_id,
                'imageDate' => $userAppearance->imageDate,
                'image' => asset(Storage::url($userAppearance->image)),
                'updated_at' => $userAppearance->updated_at,
                'created_at' => $userAppearance->created_at,
                'id' => $userAppearance->id,
            ];


            return $this->successResponse("User evolution added successfully.", $userAppearancedata, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function getUserEvolutionMeasurement()
    {
        try {
            if (UsersAppearanceInfo::where(['user_id' => Auth::user()->id])->count() > 0) {
                $userAppearance = UsersAppearanceInfo::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->get();
                // print_r($userAppearance); die;
                $evolution = [];
                foreach ($userAppearance as $key => $userEvolution) {
                    $evolution[] = [
                        'id' => $userEvolution['id'],
                        'imageDate' => $userEvolution['imageDate'],
                        'image' => asset(Storage::url($userEvolution['image']))
                    ];
                }
            } else {
                $evolution = [];
            }

            $userMeasurementData = UsersCurrentMeasurement::with('initialMeasurement', 'targetMeasurement')->where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
            $userInitialMeasurement = UsersInitialMeasurement::where(['user_id' => Auth::user()->id])->first();
            $userTargetMeasurement = UsersTargetMeasurement::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
            if (!empty($userMeasurementData)) {
                $userEvolutionData = [
                    [
                        "Name" => "Poids",
                        "slug" => "weight",
                        "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['weight'] : 0, 1, '.', ''),
                        "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['weight'] : 0, 1, '.', ''),
                        "Current" => number_format($userMeasurementData['weight'] ? $userMeasurementData['weight'] : 0, 1, '.', ''),
                        "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['weight'] : 0) - ($userMeasurementData['weight'] ? $userMeasurementData['weight'] : 0)), 1, '.', ''),
                    ],
                    [
                        "Name" => "Taille",
                        "slug" => "waist",
                        "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['waist'] : 0, 1, '.', ''),
                        "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['waist'] : 0, 1, '.', ''),
                        "Current" => number_format($userMeasurementData['waist'] ? $userMeasurementData['waist'] : 0, 1, '.', ''),
                        "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['waist'] : 0) - ($userMeasurementData['waist'] ? $userMeasurementData['waist'] : 0)), 1, '.', ''),
                    ],
                    [
                        "Name" => "Poitrine",
                        "slug" => "chest",
                        "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['chest'] : 0, 1, '.', ''),
                        "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['chest'] : 0, 1, '.', ''),
                        "Current" => number_format($userMeasurementData['chest'] ? $userMeasurementData['chest'] : 0, 1, '.', ''),
                        "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['chest'] : 0) - ($userMeasurementData['chest'] ? $userMeasurementData['chest'] : 0)), 1, '.', ''),
                    ],
                    [
                        "Name" => "Biceps",
                        "slug" => "bicep",
                        "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['bicep'] : 0, 1, '.', ''),
                        "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['bicep'] : 0, 1, '.', ''),
                        "Current" => number_format($userMeasurementData['bicep'] ? $userMeasurementData['bicep'] : 0, 1, '.', ''),
                        "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['bicep'] : 0) - ($userMeasurementData['bicep'] ? $userMeasurementData['bicep'] : 0)), 1, '.', ''),
                    ],
                    [
                        "Name" => "Cou",
                        "slug" => "neck",
                        "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['neck'] : 0, 1, '.', ''),
                        "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['neck'] : 0, 1, '.', ''),
                        "Current" => number_format($userMeasurementData['neck'] ? $userMeasurementData['neck'] : 0, 1, '.', ''),
                        "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['neck'] : 0) - ($userMeasurementData['neck'] ? $userMeasurementData['neck'] : 0)), 1, '.', ''),
                    ],
                    [
                        "Name" => "Cuisses",
                        "slug" => "thighs",
                        "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['thighs'] : 0, 1, '.', ''),
                        "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['thighs'] : 0, 1, '.', ''),
                        "Current" => number_format($userMeasurementData['thighs'] ? $userMeasurementData['thighs'] : 0, 1, '.', ''),
                        "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['thighs'] : 0) - ($userMeasurementData['thighs'] ? $userMeasurementData['thighs'] : 0)), 1, '.', ''),
                    ],
                    [
                        "Name" => "Veaux",
                        "slug" => "Veaux",
                        "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['calfs'] : 0, 1, '.', ''),
                        "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['calfs'] : 0, 1, '.', ''),
                        "Current" => number_format($userMeasurementData['calfs'] ? $userMeasurementData['calfs'] : 0, 1, '.', ''),
                        "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['calfs'] : 0) - ($userMeasurementData['calfs'] ? $userMeasurementData['calfs'] : 0)), 1, '.', ''),
                    ],
                    [
                        "Name" => "Les hanches",
                        "slug" => "hips",
                        "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['hips'] : 0, 1, '.', ''),
                        "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['hips'] : 0, 1, '.', ''),
                        "Current" => number_format($userMeasurementData['hips'] ? $userMeasurementData['hips'] : 0, 1, '.', ''),
                        "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['hips'] : 0) - ($userMeasurementData['hips'] ? $userMeasurementData['hips'] : 0)), 1, '.', ''),
                    ],
                ];
            } else {
                $userEvolutionData = [
                    [
                        "Name" => "Poids",
                        "slug" => "weight",
                        "Initial" => number_format($userInitialMeasurement->weight ?? 0, 1, '.', ''),
                        "Target" => number_format($userTargetMeasurement->weight ?? 0, 1, '.', ''),
                        "Current" => number_format($userInitialMeasurement->weight ?? 0, 1, '.', ''),
                        "Evolution" => number_format(0, 1, '.', ''),
                    ],
                    [
                        "Name" => "Taille",
                        "slug" => "waist",
                        "Initial" => number_format($userInitialMeasurement->waist ?? 0, 1, '.', ''),
                        "Target" => number_format($userTargetMeasurement->waist ?? 0, 1, '.', ''),
                        "Current" => number_format($userInitialMeasurement->waist ?? 0, 1, '.', ''),
                        "Evolution" => number_format(0, 1, '.', ''),
                    ],
                    [
                        "Name" => "Poitrine",
                        "slug" => "chest",
                        "Initial" => number_format($userInitialMeasurement->chest ?? 0, 1, '.', ''),
                        "Target" => number_format($userTargetMeasurement->chest ?? 0, 1, '.', ''),
                        "Current" => number_format($userInitialMeasurement->chest ?? 0, 1, '.', ''),
                        "Evolution" => number_format(0, 1, '.', ''),
                    ],
                    [
                        "Name" => "Biceps",
                        "slug" => "bicep",
                        "Initial" => number_format($userInitialMeasurement->bicep ?? 0, 1, '.', ''),
                        "Target" => number_format($userTargetMeasurement->bicep ?? 0, 1, '.', ''),
                        "Current" => number_format($userInitialMeasurement->bicep ?? 0, 1, '.', ''),
                        "Evolution" => number_format(0, 1, '.', ''),
                    ],
                    [
                        "Name" => "Cou",
                        "slug" => "neck",
                        "Initial" => number_format($userInitialMeasurement->neck ?? 0, 1, '.', ''),
                        "Target" => number_format($userTargetMeasurement->neck ?? 0, 1, '.', ''),
                        "Current" => number_format($userInitialMeasurement->neck ?? 0, 1, '.', ''),
                        "Evolution" => number_format(0, 1, '.', ''),
                    ],
                    [
                        "Name" => "Cuisses",
                        "slug" => "thighs",
                        "Initial" => number_format($userInitialMeasurement->thighs ?? 0, 1, '.', ''),
                        "Target" => number_format($userTargetMeasurement->thighs ?? 0, 1, '.', ''),
                        "Current" => number_format($userInitialMeasurement->thighs ?? 0, 1, '.', ''),
                        "Evolution" => number_format(0, 1, '.', ''),
                    ],
                    [
                        "Name" => "Veaux",
                        "slug" => "calfs",
                        "Initial" => number_format($userInitialMeasurement->calfs ?? 0, 1, '.', ''),
                        "Target" => number_format($userTargetMeasurement->calfs ?? 0, 1, '.', ''),
                        "Current" => number_format($userInitialMeasurement->calfs ?? 0, 1, '.', ''),
                        "Evolution" => number_format(0, 1, '.', ''),
                    ],
                    [
                        "Name" => "Les hanches",
                        "slug" => "hips",
                        "Initial" => number_format($userInitialMeasurement->hips ?? 0, 1, '.', ''),
                        "Target" => number_format($userTargetMeasurement->hips ?? 0, 1, '.', ''),
                        "Current" => number_format($userInitialMeasurement->hips ?? 0, 1, '.', ''),
                        "Evolution" => number_format(0, 1, '.', ''),
                    ],
                ];
            }

            $responseData['Evolution'] = $evolution;
            $responseData['Measurement'] = $userEvolutionData;
            return $this->successResponse("Data Found", $responseData, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function deleteEvolution(Request $request)
    {
        try {
            $rules = [
                'id' => ['required', 'integer']
            ];

            $message = [
                'id.required' => "Plese enter evolution id.",
                'id.integer' => "Id should be a numeric value."
            ];

            $validator = Validator::make($request->all(), $rules, $message);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            if (UsersAppearanceInfo::where(['id' => $request->id, 'user_id' => Auth::user()->id])->count() > 0) {
                UsersAppearanceInfo::where(['id' => $request->id, 'user_id' => Auth::user()->id])->delete();

                if (UsersAppearanceInfo::where(['user_id' => Auth::user()->id])->count() > 0) {
                   $userAppearance = UsersAppearanceInfo::where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->get();
                    // print_r($userAppearance); die;
                    $evolution = [];
                    foreach ($userAppearance as $key => $userEvolution) {
                        $evolution[] = [
                            'id' => $userEvolution['id'],
                            'imageDate' => $userEvolution['imageDate'],
                            'image' => asset(Storage::url($userEvolution['image']))
                        ];
                    }
                } else {
                    $evolution = [];
                }

                $userMeasurementData = UsersCurrentMeasurement::with('initialMeasurement', 'targetMeasurement')->where(['user_id' => Auth::user()->id])->orderBy('id', 'desc')->first();
                if (!empty($userMeasurementData)) {
                    $userEvolutionData = [
                        [
                            "Name" => "Weight",
                            "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['weight'] : 0, 1, '.', ''),
                            "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['weight'] : 0, 1, '.', ''),
                            "Current" => number_format($userMeasurementData['weight'] ? $userMeasurementData['weight'] : 0, 1, '.', ''),
                            "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['weight'] : 0) - ($userMeasurementData['weight'] ? $userMeasurementData['weight'] : 0)), 1, '.', ''),
                        ],
                        [
                            "Name" => "Waist",
                            "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['waist'] : 0, 1, '.', ''),
                            "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['waist'] : 0, 1, '.', ''),
                            "Current" => number_format($userMeasurementData['waist'] ? $userMeasurementData['waist'] : 0, 1, '.', ''),
                            "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['waist'] : 0) - ($userMeasurementData['waist'] ? $userMeasurementData['waist'] : 0)), 1, '.', ''),
                        ],
                        [
                            "Name" => "Chest",
                            "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['chest'] : 0, 1, '.', ''),
                            "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['chest'] : 0, 1, '.', ''),
                            "Current" => number_format($userMeasurementData['chest'] ? $userMeasurementData['chest'] : 0, 1, '.', ''),
                            "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['chest'] : 0) - ($userMeasurementData['chest'] ? $userMeasurementData['chest'] : 0)), 1, '.', ''),
                        ],
                        [
                            "Name" => "Bicep",
                            "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['bicep'] : 0, 1, '.', ''),
                            "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['bicep'] : 0, 1, '.', ''),
                            "Current" => number_format($userMeasurementData['bicep'] ? $userMeasurementData['bicep'] : 0, 1, '.', ''),
                            "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['bicep'] : 0) - ($userMeasurementData['bicep'] ? $userMeasurementData['bicep'] : 0)), 1, '.', ''),
                        ],
                        [
                            "Name" => "Neck",
                            "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['neck'] : 0, 1, '.', ''),
                            "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['neck'] : 0, 1, '.', ''),
                            "Current" => number_format($userMeasurementData['neck'] ? $userMeasurementData['neck'] : 0, 1, '.', ''),
                            "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['neck'] : 0) - ($userMeasurementData['neck'] ? $userMeasurementData['neck'] : 0)), 1, '.', ''),
                        ],
                        [
                            "Name" => "Thighs",
                            "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['thighs'] : 0, 1, '.', ''),
                            "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['thighs'] : 0, 1, '.', ''),
                            "Current" => number_format($userMeasurementData['thighs'] ? $userMeasurementData['thighs'] : 0, 1, '.', ''),
                            "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['thighs'] : 0) - ($userMeasurementData['thighs'] ? $userMeasurementData['thighs'] : 0)), 1, '.', ''),
                        ],
                        [
                            "Name" => "Calfs",
                            "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['calfs'] : 0, 1, '.', ''),
                            "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['calfs'] : 0, 1, '.', ''),
                            "Current" => number_format($userMeasurementData['calfs'] ? $userMeasurementData['calfs'] : 0, 1, '.', ''),
                            "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['calfs'] : 0) - ($userMeasurementData['calfs'] ? $userMeasurementData['calfs'] : 0)), 1, '.', ''),
                        ],
                        [
                            "Name" => "Hips",
                            "Initial" => number_format($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['hips'] : 0, 1, '.', ''),
                            "Target" => number_format($userMeasurementData['targetMeasurement'] ? $userMeasurementData['targetMeasurement']['hips'] : 0, 1, '.', ''),
                            "Current" => number_format($userMeasurementData['hips'] ? $userMeasurementData['hips'] : 0, 1, '.', ''),
                            "Evolution" => number_format(abs(($userMeasurementData['initialMeasurement'] ? $userMeasurementData['initialMeasurement']['hips'] : 0) - ($userMeasurementData['hips'] ? $userMeasurementData['hips'] : 0)), 1, '.', ''),
                        ],
                    ];
                } else {
                    $userEvolutionData = [
                        [
                            "Name" => "Weight",
                            "Initial" => number_format($userInitialMeasurement->weight ?? 0, 1, '.', ''),
                            "Target" => number_format($userTargetMeasurement->weight ?? 0, 1, '.', ''),
                            "Current" => number_format($userInitialMeasurement->weight ?? 0, 1, '.', ''),
                            "Evolution" => number_format(0, 1, '.', ''),
                        ],
                        [
                            "Name" => "Waist",
                            "Initial" => number_format($userInitialMeasurement->waist ?? 0, 1, '.', ''),
                            "Target" => number_format($userTargetMeasurement->waist ?? 0, 1, '.', ''),
                            "Current" => number_format($userInitialMeasurement->waist ?? 0, 1, '.', ''),
                            "Evolution" => number_format(0, 1, '.', ''),
                        ],
                        [
                            "Name" => "Chest",
                            "Initial" => number_format($userInitialMeasurement->chest ?? 0, 1, '.', ''),
                            "Target" => number_format($userTargetMeasurement->chest ?? 0, 1, '.', ''),
                            "Current" => number_format($userInitialMeasurement->chest ?? 0, 1, '.', ''),
                            "Evolution" => number_format(0, 1, '.', ''),
                        ],
                        [
                            "Name" => "Bicep",
                            "Initial" => number_format($userInitialMeasurement->bicep ?? 0, 1, '.', ''),
                            "Target" => number_format($userTargetMeasurement->bicep ?? 0, 1, '.', ''),
                            "Current" => number_format($userInitialMeasurement->bicep ?? 0, 1, '.', ''),
                            "Evolution" => number_format(0, 1, '.', ''),
                        ],
                        [
                            "Name" => "Neck",
                            "Initial" => number_format($userInitialMeasurement->neck ?? 0, 1, '.', ''),
                            "Target" => number_format($userTargetMeasurement->neck ?? 0, 1, '.', ''),
                            "Current" => number_format($userInitialMeasurement->neck ?? 0, 1, '.', ''),
                            "Evolution" => number_format(0, 1, '.', ''),
                        ],
                        [
                            "Name" => "Thighs",
                            "Initial" => number_format($userInitialMeasurement->thighs ?? 0, 1, '.', ''),
                            "Target" => number_format($userTargetMeasurement->thighs ?? 0, 1, '.', ''),
                            "Current" => number_format($userInitialMeasurement->thighs ?? 0, 1, '.', ''),
                            "Evolution" => number_format(0, 1, '.', ''),
                        ],
                        [
                            "Name" => "Calfs",
                            "Initial" => number_format($userInitialMeasurement->calfs ?? 0, 1, '.', ''),
                            "Target" => number_format($userTargetMeasurement->calfs ?? 0, 1, '.', ''),
                            "Current" => number_format($userInitialMeasurement->calfs ?? 0, 1, '.', ''),
                            "Evolution" => number_format(0, 1, '.', ''),
                        ],
                        [
                            "Name" => "Hips",
                            "Initial" => number_format($userInitialMeasurement->hips ?? 0, 1, '.', ''),
                            "Target" => number_format($userTargetMeasurement->hips ?? 0, 1, '.', ''),
                            "Current" => number_format($userInitialMeasurement->hips ?? 0, 1, '.', ''),
                            "Evolution" => number_format(0, 1, '.', ''),
                        ],
                    ];
                }

                $responseData['Evolution'] = $evolution;
                $responseData['Measurement'] = $userEvolutionData;
                return $this->successResponse("User transformation image deleted successfully", $responseData, 200);
            } else {
                return $this->successResponse("Data Not Found.", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function changeTrackingCurrent(Request $request)
    {
        try {
            $data = $request->all();
            $fields = ['weight', 'waist', 'chest', 'bicep', 'thighs', 'hips', 'calfs', 'neck'];
            foreach ($data as $field => $value) {
                if ($field != 'weight') {
                    return $this->successResponse("Weight field is required", [], 200);
                }

                $rules = [
                    $field => ['required', 'numeric'],
                ];

                $message = [
                    "$field.required" => "Please enter $field."
                ];
                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) {
                    return $this->validationError("Fails", $validator->errors()->first(), 200);
                }
            }

            $trackingCurrent = UsersCurrentMeasurement::where(['user_id' => Auth::user()->id, 'last_modified_date' => Carbon::today()])->orderBy('id', 'desc')->first();
            $prevCurrentMeasurement = UsersCurrentMeasurement::where(['user_id' => Auth::user()->id])->orWhere('last_modified_date', '!=', Carbon::today())->orderBy('id', 'desc')->first();
            if ($trackingCurrent) {
                // Update existing record
                if ($prevCurrentMeasurement) {
                    $trackingCurrent->update([
                        'waist' => $prevCurrentMeasurement['waist'],
                        'chest' => $prevCurrentMeasurement['chest'],
                        'bicep'  => $prevCurrentMeasurement['bicep'],
                        'neck'  => $prevCurrentMeasurement['neck'],
                        'thighs' => $prevCurrentMeasurement['thighs'],
                        'hips' => $prevCurrentMeasurement['hips'],
                        'calfs' => $prevCurrentMeasurement['calfs'],
                        'weight' => $data['weight'],
                        'last_modified_date' => Carbon::today(),
                    ]);
                } else {
                    $trackingCurrent->update(array_merge($data, [
                        'last_modified_date' => Carbon::today(),
                    ]));
                }

                $updatedData = UsersCurrentMeasurement::find($trackingCurrent->id);

                $responseData = [
                    'user_id'   => Auth::user()->id,
                    'weight'    => number_format((float) $updatedData['weight'], 1, '.', ''),
                ];
            } else {
                // Create new record
                $usersCurrentMeasurement = new UsersCurrentMeasurement;
                $usersCurrentMeasurement->user_id = Auth::user()->id;
                if ($prevCurrentMeasurement) {
                    $usersCurrentMeasurement['waist']               = $prevCurrentMeasurement['waist'];
                    $usersCurrentMeasurement['chest']               = $prevCurrentMeasurement['chest'];
                    $usersCurrentMeasurement['bicep']                = $prevCurrentMeasurement['bicep'];
                    $usersCurrentMeasurement['neck']                = $prevCurrentMeasurement['neck'];
                    $usersCurrentMeasurement['thighs']              = $prevCurrentMeasurement['thighs'];
                    $usersCurrentMeasurement['hips']                = $prevCurrentMeasurement['hips'];
                    $usersCurrentMeasurement['calfs']            = $prevCurrentMeasurement['calfs'];
                    $usersCurrentMeasurement['weight']              = (float) $data['weight'];
                    $usersCurrentMeasurement['last_modified_date']  = Carbon::today();
                }else{
                    foreach ($fields as $field) {
                        if (isset($data[$field])) {
                            $usersCurrentMeasurement->$field = $data[$field];
                        } else {
                            $usersCurrentMeasurement->$field = 0;
                        }
                    }
                }
                $usersCurrentMeasurement->last_modified_date = Carbon::today();
                $usersCurrentMeasurement->save();

                $responseData = [
                    'user_id'   => Auth::user()->id,
                    'weight'    => number_format((float) $usersCurrentMeasurement['weight'], 1, '.', ''),
                ];
            }

            return $this->successResponse('Weight updated successfully.', $responseData, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function updateEvolutionImage(Request $request)
    {
        try {
            $rules = [
                'id' => ['required', 'integer'],
                'imageDate' => ['required', 'date', 'date_format:Y-m-d'],
                'image' => ['required', 'image', 'mimes:jpeg,jpg,png']
            ];

            $message = [
                'id.required' => "User Evolution ID cannot be empty.",
                'id.integer' => "Please enter only numeric value.",
                'imageDate.required' => "Please select image date.",
                'imageDate.date-format' => "Please provide image in Y-m-d format only.",
                'image.required' => "Please select an image.",
                'image.mimes' => "Choose an image with jpeg,jpg and in png format only"
            ];

            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $getEvolution = UsersAppearanceInfo::where(['id' => $request->id, 'user_id' => Auth::user()->id])->first();
            $getEvolution = collect($getEvolution);
            if (count($getEvolution) > 0) {
                if ($request->has('image')) {
                    $appearanceImage = time() . '.' . $request->image->extension();
                    Storage::delete($getEvolution['image']);
                    if (!Storage::disk('public')->exists("/user/appearance/image")) {
                        Storage::disk('public')->makeDirectory("/user/appearance/image"); //creates directory
                    }

                    $request->image->storeAs("user/appearance/image", $appearanceImage, 'public');
                    $data['image'] = "user/appearance/image/$appearanceImage";
                }
                UsersAppearanceInfo::where(['id' => $request->id, 'user_id' => Auth::user()->id])->update(['imageDate' => $request->imageDate, 'image' => $data['image']]);


                $getUpdatedData = UsersAppearanceInfo::where(['id' => $request->id, 'user_id' => Auth::user()->id])->first();

                return $this->successResponse("User Evolution updated successfully.", $getUpdatedData, 200);
            } else {
                return $this->successResponse("Data Not Found.", [], 200);
            }
        } catch (\Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}
