<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\ApiLog;
use App\Models\AppPurchase;
use App\Models\Program;
use App\Models\StepsGoal;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserProgram;
use App\Models\UserReferenceAnswer;
use App\Models\UsersInitialMeasurement;
use App\Models\UsersTargetMeasurement;
use App\Services\CustomerIoService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AppPurchaseController extends ApiController
{
    public function appPurchase(Request $request)
    {
        try {
            $validation = [
                'product_id' => ['required'],
                'price' => ['required'],
                'joining_date' => ['required', 'date_format:Y-m-d'],
                'payment_reciept' => ['required'],

                'user_id' => ['nullable', 'integer'],
                'session_id' => ['required_without:user_id', 'string'],

                'session_id' => ['nullable', 'string'],
                'user_id' => ['required_without:session_id', 'integer'],

            ];
            $validation_messages = [
                'product_id.required' => 'Please enter product id.',
                'price.required' => 'Please enter price.',
                'joining_date.required' => 'Please enter joining date.',
                'payment_reciept.required' => 'Please enter payment reciept.',
            ];
            $validator = Validator::make($request->all(), $validation, $validation_messages);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }
            $data = $request->all();

            $authId = $data['user_id'] ?? $data['session_id'];

            $userApp = new AppPurchase;
            $userApp->user_id = $data['user_id'] ?? null;
            $userApp->session_id = $data['session_id'] ?? "";
            $userApp->product_id = $data['product_id'];
            $userApp->price = $data['price'];
            $userApp->joining_date = $data['joining_date'];
            $userApp->payment_reciept = $data['payment_reciept'];
            $userApp->save();

            if (isset($data['session_id'])) {
                $getEmail = UserReferenceAnswer::where(['session_id' => $userApp->session_id, 'key' => 'email'])->first();
                $email = $getEmail['value'];
                if (User::where('email', $email)->exists()) {
                    return $this->errorResponse("This email already exists with another account.", [], 200);
                }
                $user = $this->createNewUser($email);

                if ($user) {
                    $price = $userApp->price;
                    $numericPrice = preg_replace('/[^\d.]/', '', $price);
                    $floatPrice = floatval($numericPrice);
                    $subData = [
                        'name' => 'Subscriber',
                        'subscriptionId' => $userApp->product_id,
                        'startDate' => $userApp->joining_date,
                        // 'planName' => $getPackage->name,
                        // 'billingDate' => Carbon::now()->format('Y-m-d'),
                        // 'nextBillingDate' => date("Y-m-d", $subscription->current_period_end),
                        'totalAmountCharged' => $floatPrice / 100,
                    ];
                    // dd($subData);
                    $mailStatus = $this->sendMailForFinishRegistration($user->email, $userApp->session_id);
                    $customerIo = new CustomerIoService();
                    $customerIo->sendTransactionalEmail($user->email, '8', $subData);

                    if (!$mailStatus) {
                        Log::error('Email sending failed for user: ' . $user->email);
                    }
                        $assignedProgram = $this->assignProgram($userApp->session_id);

                        $this->saveUserProgram($user->id, $assignedProgram['id']);

                        $userEvolutionData = UserReferenceAnswer::where(['session_id' => $userApp->session_id])->get();
                        $this->saveUserEvolutionData($user->id, $userEvolutionData);

                        $user = User::where('id', $user->id)->first();
                        $user->isQuestionsAttempted = 1;
                        $user->iosSubscribedUser = 1;
                        $user->isSubscribedUser = 1;
                        $user->update();
                        
                        $app_purchase_id = $userApp->id;

                        AppPurchase::where('id', $app_purchase_id)->update(['user_id' => $user->id]);

                        $app_purchase_record = AppPurchase::where('id', $app_purchase_id)->first();

                        $app_purchase_record_array = $app_purchase_record ? $app_purchase_record->toArray() : [];

                        foreach ($app_purchase_record_array as $key => $value) {
                            if (is_null($value)) {
                                $app_purchase_record_array[$key] = ''; // Set to empty string if value is null
                            }
                        }

                        return $this->successResponse("Data save successfully", $app_purchase_record_array, 200);
                }
            } else {
                $user = User::where('id', $userApp->user_id)->first();
                $user->iosSubscribedUser = 1;
                $user->isSubscribedUser = 1;
                $user->update();
            }

            return $this->successResponse("Data save successfully", $userApp, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    private function createNewUser($email)
    {
        $user = new User();
        $user->name = "";
        $user->email = $email;
        $user->password = Hash::make('password');
        $user->type = 2;
        $user->mobile = "";
        $user->isQuestionsAttempted = 0;
        $user->isSubscribedUser = 0;
        $user->status = 1;
        $user->save();

        return $user;
    }

    public function assignProgram($sessionId)
    {
        $userAnswers = UserAnswer::where([
            'question_type' => 1,
            'session_id' => $sessionId
        ])->get();

        $totalPoints = 0;
        $cardioProgramIds = [];
        $muscleProgramIds = [];

        foreach ($userAnswers as $userAnswer) {
            $answer = Answer::with('question')->where([
                'id' => $userAnswer->answer_id,
                'question_id' => $userAnswer->question_id
            ])->first();

            if ($answer) {
                $totalPoints += $answer->ans_points;

                if ($answer->question->ques_for === 'cardio' && $answer->cardio_and_muscle_id) {
                    $cardioProgramIds[] = $answer->cardio_and_muscle_id;
                } elseif ($answer->question->ques_for === 'musclestrengthening' && $answer->cardio_and_muscle_id) {
                    $muscleProgramIds[] = $answer->cardio_and_muscle_id;
                }
            }
        }

        // Retrieve programs for cardio and muscle based on IDs
        $cardioPrograms = Program::whereIn('id', $cardioProgramIds)
            ->where('status', 1)
            ->get()
            ->toArray();

        $musclePrograms = Program::whereIn('id', $muscleProgramIds)
            ->where('status', 1)
            ->get()
            ->toArray();

        // Merge cardio and muscle programs
        $allPrograms = array_merge($cardioPrograms, $musclePrograms);

        if (count($allPrograms) <= 0) {
            $allPrograms = Program::where('status', 1)->get()->toArray();
        }

        // Determine the appropriate level_id based on totalPoints
        $levelId = $this->getLevelIdByPoints($totalPoints);

        // Filter programs to include only those with the appropriate level_id
        $filteredPrograms = array_filter($allPrograms, function ($program) use ($levelId) {
            return $program['level_id'] == $levelId;
        });

        if (empty($filteredPrograms)) {
            $allPrograms = Program::where('status', 1)->get()->toArray();

            // Determine the appropriate level_id based on totalPoints
            $levelId = $this->getLevelIdByPoints($totalPoints);

            // Filter programs to include only those with the appropriate level_id
            $filteredPrograms = array_filter($allPrograms, function ($program) use ($levelId) {
                return $program['level_id'] == $levelId;
            });
        }

        // Select the most suitable program from the filtered list
        $selectedProgram = $this->selectProgram($filteredPrograms);

        return $selectedProgram;
    }

    private function getLevelIdByPoints($totalPoints)
    {
        if ($totalPoints <= 2.5) {
            return 1; // Beginner
        } elseif ($totalPoints > 2.5 && $totalPoints <= 4.5) {
            return 2; // Professional
        } elseif ($totalPoints > 4.5 && $totalPoints <= 6) {
            return 3; // Expert
        }
        return null; // No matching level
    }

    private function selectProgram($programs)
    {
        if (empty($programs)) {
            return null; // No programs available for the specified level
        }

        // Convert the array to a zero-indexed array if it's not already
        $programs = array_values($programs);

        // If there are multiple programs, you might want to select the first one
        // or apply additional criteria if needed
        return $programs[0]; // Selecting the first program in the filtered list
    }

    private function saveUserProgram($userId, $programId)
    {
        $userProgram = new UserProgram();
        $userProgram->user_id = $userId;
        $userProgram->program_id = $programId;
        $userProgram->join_date = date("Y-m-d");
        $userProgram->status = 1;
        $userProgram->save();
    }

    private function saveUserEvolutionData($userId, $userEvolutionData)
    {
        // Fetch column listings for initial and target measurements
        $dbColumnsForInitialMeasurements = Schema::getColumnListing('users_initial_measurements');
        $dbColumnsForTargetMeasurements = Schema::getColumnListing('users_target_measurements');

        foreach ($userEvolutionData as $userEvolution) {
            $key = $userEvolution['key'];
            $value = $userEvolution['value'];
            $answer = $userEvolution['answer'];

            // Handle initial measurements
            if ($key == 'current_weight') {
                UsersInitialMeasurement::updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'weight' => $value,
                        'added_date' => Carbon::today()
                    ]
                );
            } else if (in_array($key, $dbColumnsForInitialMeasurements)) {
                UsersInitialMeasurement::updateOrCreate(
                    ['user_id' => $userId],
                    [
                        $key => $value,
                        'added_date' => Carbon::today()
                    ]
                );
            }

            // Handle target measurements
            if ($key == 'desire_weight') {
                UsersTargetMeasurement::updateOrCreate(
                    ['user_id' => $userId],
                    ['weight' => $value]
                );
            } else if (in_array($key, $dbColumnsForTargetMeasurements)) {
                UsersTargetMeasurement::updateOrCreate(
                    ['user_id' => $userId],
                    [$key => $value]
                );
            }

            // Handle User Steps Goal
            if ($key == 'steps_goal') {
                $goalDate = new DateTime('now');
                $goalDate = $goalDate->format('Y-m-d');

                StepsGoal::updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'steps_goal' => $value,
                        'goal_date' => $goalDate
                    ]
                );
            }

            if ($key == 'activity_level') {
                StepsGoal::updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'activity_level' => $answer,
                        'activity_factor' => $value,
                    ]
                );
            }

            if ($key == 'height') {
                User::where(['id' => $userId])->update([$key => $value]);
            }

            if ($key == 'gender') {
                User::where(['id' => $userId])->update([$key => $value]);
            }
        }
    }

    private function sendMailForFinishRegistration($email, $sessionId)
    {
        // Check if email exists or not
        $user = User::where(['email' => $email])->first();
        if ($user) {
            $token = Str::random(64);

            // Generate the registration URL
            $registrationUrl = url('user-finish-registration/' . $token);

            // Insert reset token into password_resets table
            DB::table('password_resets')->insert([
                'email' => $email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            // Retrieve all data related to that session ID
            $userReferenceData = UserReferenceAnswer::where('session_id', $sessionId)
                ->get(['session_id', 'key', 'value'])
                ->groupBy('session_id');

            // Prepare user data to send to Customer.io
            $customerData = [
                'session_id' => $sessionId,
                'email' => $user->email,
                'registeration_url' => $registrationUrl, // Include registration URL
            ];

            foreach ($userReferenceData as $sessionId => $data) {
                foreach ($data as $item) {
                    $customerData[$item->key] = $item->value;
                }
            }

            // convert to an object:
            $customerData = (object) $customerData;

            $customerIo = new CustomerIoService();
            $customerIo->addOrUpdateCustomer($sessionId, $customerData, 19);  // Add to Segment 19 => Subscribed User

            $emailsent = $customerIo->sendTransactionalEmail($email, '4', ['registeration_url' => $registrationUrl]);

            return true;
        }
    }

    public function cancelAppPurchase(Request $request)
    {
        $data = $request->all();

        if (empty($data['user_id'])) {
            return response()->json(['message' => 'User ID is required'], 400);
        }

        $user = User::find($data['user_id']);

        $userAppPurchases = AppPurchase::where('user_id', $user->id)->get();

        if ($userAppPurchases->isEmpty()) {
            return response()->json(['message' => 'No app purchases found for the user'], 404);
        }

        $user->iosSubscribedUser = 0;
        $user->isSubscribedUser = 0;
        $user->update();

        AppPurchase::where('user_id', $user->id)->delete();
        return $this->successResponse("All app purchases deleted successfully, and user subscription updated.", [
            "id" => $user->id,
            "name" => $user->name,
            "email" => $user->email,
            "mobile" => $user->mobile,
            "device_type" => $user->device_type,
            "device_token" => $user->device_token,
            "login_key" => $user->login_key,
            "status" => $user->status,
            "iosSubscribedUser" => $user->iosSubscribedUser,
            "isSubscribedUser" => $user->isSubscribedUser,
        ]);
    }

    public function iosDeviceLog(Request $request){
        $validated = $request->validate([
            'action_name' => 'required|string',
            'params' => 'nullable|array',
            'response' => 'nullable|array',
            'user_id' => 'nullable',
            'session_id' => 'nullable|string',
        ]);

        $log = ApiLog::create([
            'action_name' => $validated['action_name'],
            'params' => $validated['params'] ?? null,
            'response' => $validated['response'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
        ]);

        return response()->json(['message' => 'Log saved', 'log_id' => $log->id], 201);
    }
}
