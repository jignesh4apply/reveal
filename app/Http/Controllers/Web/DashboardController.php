<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    public function dashboard()
    {
        if (Auth::guard('user')->check()) {
            Session::forget('resetToken');
            Session::put('page', 'dashboard');
            return view('front.dashboard');
        }
        return view('front.auth.login');
    }
}
