<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityPostTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CommunityPostTopicsController extends Controller
{
    public function communityTopicsIndex(){
        Session::put('page', 'communityPostTopics');
        $communityPostTopics = CommunityPostTopic::all();
        return view('admin.community.post_topics.index')->with([
            'topics' => $communityPostTopics,
        ]);
    }

    public function communityTopicsCreate(Request $request){
        
        if($request->isMethod('post')){
            
            // will do later
            
            $data = $request->all();

            return redirect()->route('admin.challenges.index')->with('success', 'Community Post Topic has been added successfully!');
        }
        return view('admin.community.post_topics.create');
    }

    public function communityTopicsUpdate(Request $request){
                    // will do later
    }
    public function communityTopicsDestroy(Request $request){
            // will do later
    }
}
