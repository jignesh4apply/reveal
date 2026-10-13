<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class CMSPageController extends Controller
{
    public function page($slug)
    {
        $page = Page::where('slug',$slug)->first();
        
        if($page){
            return view('front.page.index',compact('page'));
        }
        return view('page404');
    }
}
