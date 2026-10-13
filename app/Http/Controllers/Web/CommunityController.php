<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CommunityController extends Controller
{
    public function communityIndex()
    {
        Session::put('page', 'community');
        $topics = Discussion::get()->toArray();
        // Trending Discussion
        // Retrieve Trending Topics along with their comments count
        $trendingTopics = Discussion::withCount('reply')->with('reply')->get();

        // Sort Trending Topics by comments count in descending order
        $trendingTopics = $trendingTopics->sortByDesc('reply_count');

        // Take the first five topics
        $trendingTopics = $trendingTopics->take(5);

        // Display or process sorted Trending Topics
        $topicList = [];
        foreach ($trendingTopics as $topic) {
            $topicList[] = [
                "id" => $topic['id'],
                'topic' => $topic['topic'],
                'content' => $topic['content'],
                'slug' => $topic['slug'],
                'totalReply' => $topic['reply_count']
            ];
        }

        $trending = $topicList;

        // History of user discussion
        $userDiscussion = Discussion::withCount('userReply')->with('userReply')->get();

        $userDiscussion = $userDiscussion->sortByDesc('user_reply_count');

        $userCommentList = [];
        foreach ($userDiscussion as $userDiscussion) {
            // dd($userDiscussion);
            $userCommentList[] = [
                "id" => $userDiscussion['id'],
                'topic' => $userDiscussion['topic'],
                'content' => $userDiscussion['content'],
                'slug' => $userDiscussion['slug'],
                'totalUserComment' => $userDiscussion['user_reply_count'],
                'userComment' => $userDiscussion['userReply']
            ];
        }
        // echo "<pre>"; print_r($userCommentList); die;
        return view('front.community.community_index', ['topics' => $topics, 'trending' => $trending, 'userCommentList' => $userCommentList]);
    }

    public function topicDetails(Request $request, $id = null)
    {
        Session::put('page', 'community');
        $topicDetails = Discussion::with('reply')->where(['id' => $id])->first()->toArray();
        // $topicPostedDate = $topicDetails['created_at'] = Carbon::today()->toDateString("M-d-Y");
        $topicPostedDate = $topicDetails['created_at'] = date('M,d,Y');
        // echo "<pre>"; print_r(); die;
        $discussionReply = DiscussionReply::with('user')->get()->toArray();
        return view('front.community.topic_details', ['topicDetails' => $topicDetails, 'postedDate' => $topicPostedDate, 'discussionReply' => $discussionReply]);
    }

    public function reply(Request $request, $id = null)
    {
        Session::put('page', 'community');
        $data = $request->all();
        $validation = [
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'comment' => ['required', 'string', 'max:3000'],
        ];
        $validator = Validator::make($data, $validation);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
        }

        $discussionReply = new DiscussionReply;
        $discussionReply->user_id = Auth::guard('user')->user()->id;
        $discussionReply->discussion_id = $id;
        $discussionReply->discussion_reply = $data['comment'];
        $discussionReply->save();

        return redirect()->back()->with('success', 'You just replied to a discussion');
    }

    public function getReplies(Request $request)
    {
        Session::put('page', 'discussion');
        $discussionReply = DiscussionReply::with('user')->get()->toArray();
        // dd($discussionReply);
        return view('front.discussion.discussion')->with(compact('discussionsAddedBy', 'discussionData', 'discussionReply'));
    }
}
