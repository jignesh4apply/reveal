<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CommunityController extends ApiController
{
    public function discussionList(Request $request)
    {
        try {
            /* Validate Data */
            $validation = [
                'search' => ['nullable', 'string'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            if (Auth::check()) {
                if ($request->has('search') && !empty($request->search)) {
                    $search = $request->search;
                    $getDiscussionList = Discussion::where(function ($query) use ($search) {
                        $query->where('topic', 'like', '%' . $search . '%');
                    })
                        ->where(['status' => 1])
                        ->get()->toArray();
                } else {
                    $getDiscussionList = Discussion::where(['status' => 1])->get()->toArray();
                }
                (int) $limit = 15;
                if (!isset($request->page) && (!isset($request->page))) {
                    (int) $page = 1;
                } else {
                    (int) $page = $request->page;
                }

                $result = $this->pagination($getDiscussionList, $page, $limit);

                if (count($getDiscussionList) > 0) {
                    return $this->successResponse("Discussion Found", $result['data'], 200);
                } else {
                    return $this->errorResponse("Discussion Not Found", [], 200);
                }
            } else {
                return $this->errorResponse("You does not have permission.", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(),500);
        }
    }

    public function replyDiscussion(Request $request)
    {
        try {
            /* Validate Data */
            $validation = [
                'discussion_id' => ['required', 'numeric'],
                'discussion_reply' => ['required', 'string'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $data = $request->all();

            if (Auth::check()) {
                $checkExistDiscussion = Discussion::where(['id' => $data['discussion_id']])->count();
                if ($checkExistDiscussion > 0) {
                    $reply = new DiscussionReply;
                    $reply->user_id = Auth::user()->id;
                    $reply->discussion_id = $data['discussion_id'];
                    $reply->discussion_reply = $data['discussion_reply'];
                    $reply->save();

                    return $this->getSingleResponse("Your answer submitted successfully", $reply, 200);
                } else {
                    return $this->successResponse("No Discussion Found with submitted discussion ID", (object)[], 200);
                }
            } else {
                return $this->errorResponse("You does not have permission.", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(),500);
        }
    }

    public function discussionDetail(Request $request)
    {
        try {
            /* Validate Data */
            $validation = [
                'discussion_id' => ['required', 'numeric'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }
            $data = $request->all();
            if (Auth::check()) {
                $getDiscussionDetail = Discussion::with('reply')->where(['id' => $data['discussion_id']])->get()->toArray();
                if (!empty($getDiscussionDetail)) {
                    return $this->successResponse("Discussion Found", $getDiscussionDetail, 200);
                } else {
                    return $this->successResponse("No Discussion Found with submitted discussion ID", (object)[], 200);
                }
            } else {
                return $this->successResponse("You does not have permission.");
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(),500);
        }
    }
}
