<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\Auth\ApiAuthController as APIAuthControllerV1;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ApiAuthController extends APIAuthControllerV1
{
    public function userLocation(Request $request) {
        try {
            if ($request->has('user_id') && !empty($request->user_id)) {
                $authUserId = Auth::user()->id;
                $user_id = $request->user_id;
                if ($authUserId == $user_id) {

                    $user = User::find($authUserId);
                    $message = "User location found.";

                    if ($request->isMethod('POST')) {
                        $validator = Validator::make($request->all(), 
                        [
                            'name' => 'required',
                            'address' => 'required',
                            'postal_code' => 'required',
                            'city' => 'required',
                            'country' => 'required'
                        ], [
                            'name.required' => 'Please enter your name.',
                            'address.required' => 'Please enter your address.',
                            'postal_code.required' => 'Please enter your postal code.',
                            'postal_code.numeric' => 'Invalid postal code, please enter numeric value.',
                            'city.required' => 'Please enter your city.',
                            'country.required' => 'Please enter your country.' 
                        ]);

                        if ($validator->fails()) {
                            return $this->validationError("Fail", $validator->errors()->first(), 200);
                        }

                        $user->name = $request->name;
                        $user->address = $request->address;
                        $user->postal_code = $request->postal_code;
                        $user->city = $request->city;
                        $user->country = $request->country;
                        if ($request->has('company') && !empty($request->company)) {
                            $user->company = $request->company;
                        }
                        if ($request->has('first_name') && !empty($request->first_name)) {
                            $user->first_name = $request->first_name;
                        }
                        if ($request->has('last_name') && !empty($request->last_name)) {
                            $user->last_name = $request->last_name;
                        }
                        $user->save();
                        $message = "User location saved.";
                    }

                    $data = [
                        'id' => $user->id,
                        'name' => $user->name ?? "",
                        'address' => $user->address ?? "",
                        'postal_code' => $user->postal_code ?? "",
                        'city' => $user->city ?? "",
                        'country' => $user->country ?? "",
                        'company' => $user->company ?? "",
                        'first_name' => $user->first_name ?? "",
                        'last_name' => $user->last_name ?? ""
                    ];

                    return $this->successResponse($message, $data, 200);
                }
                return $this->errorResponse("Unauthenticated!", [], 401);
            }
            return $this->validationError("Fail", "User id is missing!", 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}
