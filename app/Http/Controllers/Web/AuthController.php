<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserReferenceAnswer;
use App\Models\UserSecurityToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    public function userLogin(Request $request)
    {
        Session::put('page', 'login');
        if (Auth::guard('user')->check()) {
            $user = Auth::guard('user')->user();
            if ($user->isQuestionsAttempted == 1) {
                $userToken = UserSecurityToken::where('user_id', Auth::guard('user')->user()->id)->first();
                if (!$userToken){
                    $userToken = UserSecurityToken::Create([
                        'user_id' => Auth::guard('user')->user()->id,
                        'security_token' => Str::random(60)
                    ]);
                }
                return redirect('/subscription-details?uid=' . Auth::guard('user')->user()->id . '&st=' . $userToken['security_token']);
                // return redirect('user-profile');
            }
        }
        if ($request->isMethod('post')) {
            $data = $request->all();
            if ($request->isMethod('post')) {
                $data = $request->all();

                $rules = [
                    'email' => 'required|email',
                    'password' => 'required'
                ];

                $messages = [
                    'email.required' => 'Veuillez entrer votre email',
                    'email.email' => 'Veuillez entrer une adresse e-mail valide',
                    'password.required' => 'Veuillez entrer le mot de passe'
                ];

                $validation = Validator::make($data, $rules, $messages);

                if ($validation->fails()) {
                    return redirect()->back()->withInput()->withErrors($validation->messages());
                }

                $remember = (!empty($data['remember']));

                // ✅ allow type 0 and 2
                $user = User::where('email', $data['email'])
                    ->whereIn('type', [0, 2])
                    ->first();

                if ($user && Hash::check($data['password'], $user->password)) {

                    Auth::guard('user')->login($user, $remember);

                    if ($remember) {
                        setcookie('email', $data['email'], time() + 3600);
                        setcookie('password', $data['password'], time() + 3600);
                    } else {
                        setcookie('email', '');
                        setcookie('password', '');
                    }

                    $data['device_token'] = $data['device_token'] ?? "";
                    $data['device_type'] = $data['device_type'] ?? "";

                    $login = [
                        'login_key' => $this->getLoginKey($user->id),
                        'device_token' => $data['device_token'],
                        'device_type' => $data['device_type']
                    ];

                    User::where(['id' => $user->id, 'email' => $data['email']])
                        ->update($login);

                    $securityToken = Str::random(60);

                    UserSecurityToken::updateOrCreate(
                        ['user_id' => $user->id],
                        ['security_token' => $securityToken]
                    );

                    $userToken = UserSecurityToken::where('user_id', $user->id)->first();

                    return redirect('/subscription-details?uid=' . $user->id . '&st=' . $userToken['security_token']);
                }

                return redirect()->back()->with('error', "E-mail ou mot de passe incorrecte");
            }
        }
        return view('front.auth.login');
    }

    public function userRegister(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                /* Validate Data */
                $validation = [
                    'email' => ['required', 'regex:/(.+)@(.+)\.(.+)/i', 'unique:users,email'],
                    'password' => [
                        'required',
                        'min:6',
                        'regex:/[a-z]/',     // must contain at least one lowercase letter
                        'regex:/[A-Z]/',     // must contain at least one uppercase letter
                        'regex:/[0-9]/'      // must contain at least one digit
                    ],
                    'cnf_password' => ['required', 'same:password'],
                ];
                $validation_messages = [
                    'email.required' => 'The E-Mail field is required.',
                    'email.regex' => 'Enter valid email address.',
                    'email.unique' => 'Entered E-mail has already been taken.',
                    'password.required' => 'The Password field is required.',
                    'password.regex' => 'Password must contains at least one lowercase, one uppercase and one digit.',
                    'cnf_password.required' => 'The Confirm Password field is required.',
                    'cnf_password.same' => 'The Password and Confirm Password should be same.',
                ];
                $validator = Validator::make($request->all(), $validation, $validation_messages);
                if ($validator->fails()) {
                    return redirect()->back()->withInput()->withErrors($validator->messages());
                }

                $data = $request->all();

                $usersCount = User::where('email', $data['email'])->count();
                //Check if User already exists

                if ($usersCount > 0) {
                    return redirect()->back()->with('error', 'This email is already exists with another account.');
                } else {

                    $password = Hash::make($data['password']);
                    $user = new User();
                    $user->name = "";
                    $user->email = $data['email'];
                    $user->password = $password;
                    $user->type = 2;
                    $user->mobile = "";
                    $user->status = 1;
                    $user->save();
                    return redirect('user-login')->with('success', "Registered Successfully. Please Login");
                }
            }
        } catch (Throwable $th) {
            dd($th);
        }
        return view('front.auth.register');
    }

    public function getLoginKey($user_id)
    {
        $salt = "23df$#%%^66sd$^%fg%^sjgdk90fdklndg099ndfg09LKJDJ*@##lkhlkhlsa#$%";
        $login_key = hash('sha1', $salt . $user_id . time());
        return $login_key;
    }

    public function userForgotPassword(Request $request)
    {
        return view('front.auth.forgetPassword');
    }

    public function googleLogin()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleHandle(Request $request)
    {
        try {
            $user = Socialite::driver('google')->user();

            $findUser = User::where(['email' => $user->email])->first();
            $sessionId = Session::get('quiz_session_id');

            if (!is_null($sessionId) && !$findUser) {
                $sessionEmail = UserReferenceAnswer::where(['session_id' => $sessionId, 'key' => 'email'])->first();
                User::where(['email' => $sessionEmail['value']])->update(['email' => $user->email, 'password' => bcrypt("Test@12345"), 'avatar' => $user->avatar, 'type' => 2, 'isQuestionsAttempted' => 1, 'isSubscribedUser' => 1]);

                $findUser = User::where(['email' => $user->email])->first();
                $request->session()->flush();
            } elseif (!is_null($sessionId) && $findUser['isQuestionsAttempted'] == 0) {
                $sessionEmail = UserReferenceAnswer::where(['session_id' => $sessionId, 'key' => 'email'])->first();
                User::where(['email' => $sessionEmail['value']])->update(['email' => $user->email, 'isQuestionsAttempted' => 1, 'isSubscribedUser' => 1]);
                $findUser = User::where(['email' => $user->email])->first();
                $request->session()->flush();
            } elseif (!is_null($sessionId) && $findUser['isQuestionsAttempted'] == 1) {
                $token = session()->get('resetToken');
                return redirect()->route('user-finish-registration', $token)->with('error', "You already have an account with this {$user->email}");
            } else {
                if (!$findUser) {
                    $findUser = new User;
                    $findUser->name = $user->name;
                    $findUser->email = $user->email;
                    $findUser->password = bcrypt("Test@12345");
                    $findUser->avatar = $user->avatar;
                    $findUser->type = 2;
                    $findUser->save();
                }
            }

            if (Auth::guard('user')->attempt(['email' => $findUser->email, 'password' => "Test@12345", 'type' => $findUser->type])) {
                if (!isset($data['device_token']) || empty($data['device_token'])) {
                    $data['device_token'] = "";
                }

                if (!isset($data['device_type']) || empty($data['device_type'])) {
                    $data['device_type'] = "";
                }

                $login = array(
                    'login_key' => $this->getLoginKey(Auth::guard('user')->user()->id),
                    'device_token' => $data['device_token'],
                    'device_type' => $data['device_type']
                );

                $updateUser = User::where(['id' => $findUser->id, 'email' => $findUser->email])->update($login);

                Auth::guard('user')->loginUsingId($findUser->id);

                $userData = User::where(['id' => Auth::guard('user')->user()->id])->first();

                if ($userData['isQuestionsAttempted'] == 0) {
                    return redirect()->route('questions')->with('success', "You have been successfully logged in. Please answer all questions.");
                }
            }
            return redirect()->route('dashboard')->with('success', "You have been successfully registered");
        } catch (Throwable $th) {
            dd($th);
        }
    }

    public function logout(Request $request)
    {
        if (Auth::guard('user')->check()) {
            // Log the user out
            Auth::guard('user')->logout();
        }
        // Invalidate the session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to the login page
        return redirect()->route('user-login');
    }

    public function userProfile(Request $request)
    {
        $user = User::where(['id' => Auth::guard('user')->user()->id, 'isQuestionsAttempted' => 1])->first();
        if (!$user) {
            return redirect()->route('questions')->with('error', "Please login first");
        }
        if ($request->isMethod('post')) {
            $data = $request->all();
            // echo "<pre>"; print_r($data); die;

            if (!isset($data['avatar']) || empty($data['avatar'])) {
                $profilePic = Auth::guard('user')->user()->avatar;
            } else {
                $profilePic = time() . '.' . $data['avatar']->extension();
                if (!Storage::disk('public')->exists("/users/avatars")) {
                    Storage::disk('public')->makeDirectory("/users/avatars"); //creates directory
                }
                if (Storage::disk('public')->exists("/users/" . Auth::guard('user')->user()->avatar)) {
                    Storage::disk('public')->delete("/users/" . Auth::guard('user')->user()->avatar);
                }
                $request->avatar->storeAs("users/avatars", $profilePic, 'public');

                $profilePic = "users/avatars/$profilePic";
            }
            $user = User::where(['id' => Auth::guard('user')->user()->id])->update(['name' => $data['name'], 'mobile' => $data['mobile']]);
            return redirect()->back()->with('success', 'User details updated successfully.');
        }
        return view('front.auth.profile');
    }

    public function userChangePassword(Request $request)
    {
        $data = $request->all();
        $validation = [
            'old_password' => ['required'],
            'new_password' => [
                'required',
                'min:6',
                'regex:/[a-z]/',     // must contain at least one lowercase letter
                'regex:/[A-Z]/',     // must contain at least one uppercase letter
                'regex:/[0-9]/'      // must contain at least one digit

            ],
            'confirm_password' => ['required', 'same:new_password'],
        ];
        $validator = Validator::make($data, $validation);
        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
        }
        $currentPasswordStatus = Hash::check($data['old_password'], Auth::guard('user')->user()->password);
        if ($currentPasswordStatus) {
            $password = Hash::make($data['new_password']);
            User::where(['id' => Auth::guard('user')->user()->id])->update(['password' => $password]);

            return redirect()->back()->with('success', "Your Password Changed Successfully.");
        } else {
            return redirect()->back()->with('error', "Your current password is wrong.");
        }
    }

    public function deleteAccount(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();

            // Validate Data
            $validator = Validator::make($data, [
                'email' => ['required', 'regex:/(.+)@(.+)\.(.+)/i'],
                'password' => ['required']
            ], [
                'email.required' => 'The E-Mail field is required.',
                'email.regex' => 'Enter a valid email address.',
                'password.required' => 'The Password field is required.'
            ]);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            if (Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'type' => 2])) {
                $user = User::find(Auth::user()->id);
                $user->delete();

                return redirect()->route('questions')->with('success', "Account Deleted");
            } else {
                return redirect()->back()->with('error', "Invalid Credentials");
            }
        }
        return view('front.auth.deleteAccount');
    }

    public function deleteUser(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();

            $validator = Validator::make($data, [
                'email' => ['required', 'regex:/(.+)@(.+)\.(.+)/i'],
                'password' => ['required']
            ], [
                'email.required' => 'The E-Mail field is required.',
                'email.regex' => 'Enter a valid email address.',
                'password.required' => 'The Password field is required.'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()]);
            }

            if (Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'type' => 2])) {
                $user = User::find(Auth::user()->id);
                return response()->json([
                    'status' => 'success',
                    'userId' => $user->id
                ]);
            } else {
                return response()->json(['status' => 'fail', 'message' => "Invalid Credentials"]);
            }
        }
        return view('front.auth.deleteUser');
    }

    public function deleteUserAccount(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            // echo "<pre>";print_r($data);die;
            $user = User::findOrFail($request->userId);
            $user->delete();

            return response()->json(['status' => 'success', 'message' => 'Account deleted successfully.']);
        }
    }
}
