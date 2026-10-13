<?php

namespace App\Http\Controllers;

use App\Mail\ForgetPassword;
use App\Models\UnitConversion;
use App\Models\UnitConverter;
use App\Models\User;
use App\Services\CustomerIoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UtitlitiesController extends Controller
{
    public function forgotPassword(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'email' => 'required|email',
            ];

            $messages = [
                'email.requierd' => 'Please enter your email',
            ];

            $validation = Validator::make($data, $rules, $messages);

            if ($validation->fails()) {
                return redirect()->back()->withInput()->withErrors($validation->messages());
            }

            // Check if email exists or not
            $user = User::where(['email' => $data['email']])->first();
            if ($user) {
                // $markdown = 'admin.email.forgotPassword';
                $token = $token = Str::random(64);

                DB::table('password_resets')->insert([
                    'email' => $request->email,
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]);
                $name = $user->name;
                if($user->type == 0){
                    $resetPasswordUrl = url('admin/reset-password/'. $token);
                }else{
                    $resetPasswordUrl = url('user-reset-password/'. $token);
                }

                $customerIo = new CustomerIoService();
                $customerIo->sendTransactionalEmail($request->email, '5', ['reset_password_url' => $resetPasswordUrl, 'name' => $name]);

                return redirect()->back()->withSuccess("Please Check Your Inbox");
            } else {
                return redirect()->back()->withError("Email does not exist");
            }
        }
        return view('front.auth.forgetPassword');
    }

    public function resetPassword(Request $request, $token)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'email' => 'required|email',
                'password' => [
                    'required',
                    'min:6',
                    'regex:/[a-z]/',     // must contain at least one lowercase letter
                    'regex:/[A-Z]/',     // must contain at least one uppercase letter
                    'regex:/[0-9]/',     // must contain at least one digit
                    'regex:/[@$!%*#?&]/' // must contain a special character
                ],
                'cpassword' => 'required_with:password|same:password'
            ];

            $messages = [
                'email.requierd' => 'Please enter your email',
                'email.email' => 'Please enter valid email',
                'password.required' => 'Please enter password',
                'password.min' => 'Password should be six character long',
                'password.regex' => 'Password must contains at least one lowercase letter, one uppercase letter, one digit, and a special character.',
                'cpassword.same' => 'Password and Confirm Password must be same'
            ];

            $validation = Validator::make($data, $rules, $messages);

            if ($validation->fails()) {
                return redirect()->back()->withInput()->withErrors($validation->messages());
            }

            $resetPassData = DB::table('password_resets')->where(['token' => $token])->first();

            if (!$resetPassData) {
                return back()->withInput()->with('error', 'Invalid token!');
            }

            if ($resetPassData->email === $data['email']) {
                $user = User::where('email', $data['email'])->update(['password' => Hash::make($request->password)]);

                $resetPassData = DB::table('password_resets')->where(['token' => $token])->delete();
                $userDetail = User::where(['email' => $data['email']])->first();
                $customerIo = new CustomerIoService();
                $customerIo->sendTransactionalEmail($userDetail->email, '6', ['name' => $userDetail->name]);

                return redirect('/user-login')->with('success', 'Password reset successfully');
            } else {
                return back()->with('error', 'Invalid Email!');
            }
        }
        return view('front.auth.resetPassword')->with(compact('token'));
    }

    public function adminForgotPassword(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'email' => 'required|email',
            ];

            $messages = [
                'email.requierd' => 'Please enter your email',
            ];

            $validation = Validator::make($data, $rules, $messages);

            if ($validation->fails()) {
                return redirect()->back()->withInput()->withErrors($validation->messages());
            }

            // Check if email exists or not
            $user = User::where(['email' => $data['email']])->first();
            if ($user) {
                // $markdown = 'admin.email.forgotPassword';
                $token = $token = Str::random(64);

                DB::table('password_resets')->insert([
                    'email' => $request->email,
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]);
                if (Mail::to($data['email'])->send(new ForgetPassword($user, $token))) {
                    return redirect()->back()->withSuccess("Please Check Your Inbox");
                };
            } else {
                return redirect()->back()->withError("Email does not exist");
            }
        }
        return view('password.forgotPassword');
    }

    public function adminResetPassword(Request $request, $token)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'email' => 'required|email',
                'password' => [
                    'required',
                    'min:6',
                    'regex:/[a-z]/',     // must contain at least one lowercase letter
                    'regex:/[A-Z]/',     // must contain at least one uppercase letter
                    'regex:/[0-9]/',     // must contain at least one digit
                    'regex:/[@$!%*#?&]/' // must contain a special character
                ],
                'cpassword' => 'required_with:password|same:password'
            ];

            $messages = [
                'email.requierd' => 'Please enter your email',
                'email.email' => 'Please enter valid email',
                'password.required' => 'Please enter password',
                'password.min' => 'Password should be six character long',
                'password.regex' => 'Password must contains at least one lowercase letter, one uppercase letter, one digit, and a special character.',
                'cpassword.same' => 'Password and Confirm Password must be same'
            ];

            $validation = Validator::make($data, $rules, $messages);

            if ($validation->fails()) {
                return redirect()->back()->withInput()->withErrors($validation->messages());
            }

            $resetPassData = DB::table('password_resets')->where(['token' => $token])->first();

            if (!$resetPassData) {
                return back()->withInput()->with('error', 'Invalid token!');
            }

            if ($resetPassData->email === $data['email']) {
                $user = User::where('email', $data['email'])->update(['password' => Hash::make($request->password)]);

                $resetPassData = DB::table('password_resets')->where(['token' => $token])->delete();
                return redirect('/admin')->with('success', 'Password reset successfully');
            } else {
                return back()->with('error', 'Invalid Email!');
            }
        }
        return view('password.resetPassword')->with(compact('token'));
    }

    public function unitIndex(Request $request)
    {
        Session::put('page', 'unit');

        $unitData = UnitConverter::get()->toArray();
        return view('admin.settings.unitConverter.index', ['unitData' => $unitData]);
    }

    public function unitCreate(Request $request)
    {
        Session::put('page', 'unit');

        $unitData = UnitConverter::get()->toArray();

        if ($request->isMethod('post')) {
            $data = $request->all();
            // Validation
            $rules = [
                'from_unit' => ['required', 'string'],
            ];

            $messages = [
                'from_unit.required' => "Please enter from_unit.",
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if (isset($data['isConvert']) || !empty($data['isConvert'])) {
                $rules = [
                    'operation' => ['required', 'string'],
                    'operation_value' => ['required', 'integer'],
                    'to_unit' => ['required', 'string'],
                ];

                $messages = [
                    'operation.required' => "Please select operation.",
                    'operation_value.required' => "Please enter operation value.",
                    'to_unit.required' => "Please enter to unit."
                ];

                $validator = Validator::make($request->all(), $rules, $messages);

                if ($validator->fails()) {
                    return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
                }

                $data['isConvert'] = 1;
            }else{
                $data['isConvert'] = 0;
            }

            if(empty($data['to_unit_id'])){
                $data['to_unit_id'] = 0;
            }

            $slug = Str::slug($data['from_unit']);
            $unitConverter = new UnitConverter;
            $unitConverter->from_unit = $data['from_unit'];
            $unitConverter->slug = $slug;
            $unitConverter->operation = $data['operation'];
            $unitConverter->operation_value = $data['operation_value'];
            $unitConverter->to_unit = $data['to_unit'];
            $unitConverter->to_unit_id = $data['to_unit_id'];
            $unitConverter->isConvert = $data['isConvert'];
            $unitConverter->save();

            return redirect('admin/unit-index')->with('success', "Unit saved successfully.");
        }
        return view('admin.settings.unitConverter.create', ['unitData' => $unitData]);
    }

    public function unitUpdate(Request $request, $slug, $id)
    {
        Session::put('page', 'unit');
        $unit = UnitConverter::where(['id' => $id, 'slug' => $slug])->first()->toArray();
        $unitData = UnitConverter::get()->toArray();
        if ($request->isMethod('post')) {
            $data = $request->all();
            // Validation
            $rules = [
                'from_unit' => ['required', 'string'],
            ];

            $messages = [
                'from_unit.required' => "Please enter from_unit.",
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if (isset($data['isConvert']) || !empty($data['isConvert'])) {
                $rules = [
                    'operation' => ['required', 'string'],
                    'operation_value' => ['required', 'integer'],
                    'to_unit' => ['required', 'string'],
                ];

                $messages = [
                    'operation.required' => "Please select operation.",
                    'operation_value.required' => "Please enter operation value.",
                    'to_unit.required' => "Please enter to unit."
                ];

                $validator = Validator::make($request->all(), $rules, $messages);

                if ($validator->fails()) {
                    return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
                }

                $data['isConvert'] = 1;
            }else{
                $data['isConvert'] = 0;
            }

            if(empty($data['to_unit_id'])){
                $data['to_unit_id'] = 0;
            }

            UnitConverter::where(['id' => $id, 'slug' => $slug])->update(['from_unit' => $data['from_unit'], 'operation' => $data['operation'], 'operation_value' => $data['operation_value'], 'to_unit' => $data['to_unit'], 'to_unit_id' => $data['to_unit_id'], 'isConvert' => $data['isConvert']]);

            return redirect('admin/unit-index')->with('success', "Unit updated successfully");
        }
        return view('admin.settings.unitConverter.edit', ['unit' => $unit, 'unitData' => $unitData]);
    }

    public function unitDestroy($slug, $id)
    {
        Session::put('page', 'unit');
        UnitConverter::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(2);
        return redirect()->back();
    }
}
