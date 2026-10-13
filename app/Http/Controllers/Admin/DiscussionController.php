<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DiscussionController extends Controller
{
    public function index()
    {
        Session::put('page', 'discussion');
        $discussionData = Discussion::get()->toArray();
        return view('admin.discussion.index', ['discussionData' => $discussionData]);
    }

    public function create(Request $request)
    {
        Session::put('page', 'discussion');
        if ($request->isMethod('post')) {
            $data = $request->all();

            $validation = [
                'topic' => ['required', 'string', 'max:100'],
                'content' => ['required', 'string']
            ];
            $validator = Validator::make($data, $validation);
            $input = $request->except(['_token']);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }

            $slug = Str::slug($data['topic']);

            $discussion = new Discussion;
            $discussion->added_by = Auth::guard('admin')->user()->id;
            $discussion->topic = $data['topic'];
            $discussion->content = $data['content'];
            $discussion->slug = $slug;
            $discussion->status = $status;
            $discussion->save();
            return redirect('/admin/discussion-index')->with('success', 'Discussion Inserted Successfully !!!');
        }
        return view('admin.discussion.create');
    }

    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'discussion');
        if ($request->isMethod('post')) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            $validation = [
                'topic' => ['required', 'string'],
                'content' => ['required', 'string']
            ];
            $validator = Validator::make($data, $validation);
            $input = $request->except(['_token']);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }

            $slug = Str::slug($data['topic']);

            $updateDiscussion = Discussion::where(['id' => $id])->update(['added_by' => Auth::guard('admin')->user()->id, 'topic' => $data['topic'], 'slug' => $slug, 'content' => $data['content'], 'status' => $status]);
            return redirect('/admin/discussion-index')->with('success', 'Discussion updated successfully !!!');
        }

        $discussion = Discussion::where(['slug' => $slug, 'id' => $id])->first();
        return view('admin.discussion.create')->with(compact('discussion'));
    }

    public function destroy($slug, $id)
    {
        Session::put('page', 'discussion');
        Discussion::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(2);
        return redirect()->back();
    }

    public function getDiscussion(Request $request)
    {
        Session::put('page', 'discussion');
        $discussionsAddedBy = User::where(['type' => 'superadmin'])->first();
        $discussionData = Discussion::get()->toArray();
        $discussionReply = DiscussionReply::with('user')->get()->toArray();
        // dd($discussionReply);
        return view('front.discussion.discussion')->with(compact('discussionsAddedBy', 'discussionData', 'discussionReply'));
    }

    public function reply(Request $request)
    {
        Session::put('page', 'discussion');
        $data = $request->all();
        $validation = [
            'discussion_reply' => ['required', 'string', 'max:3000'],
        ];
        $validator = Validator::make($data, $validation);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
        }

        $discussionReply = new DiscussionReply;
        $discussionReply->user_id = Auth::user()->id;
        $discussionReply->topic_id = $data['topic_id'];
        $discussionReply->discussion_reply = $data['discussion_reply'];
        $discussionReply->save();

        return redirect()->back()->with('success', 'You just replied to a discussion');
    }

    public function discussionDetail(Request $request, $slug, $id)
    {
        Session::put('page', 'discussion');
        $discussion = Discussion::with('reply')->where(['slug' => $slug, 'id' => $id])->first();

        return view('admin.discussion.discussionDetail', ['discussionDetails' => $discussion]);
    }
}
