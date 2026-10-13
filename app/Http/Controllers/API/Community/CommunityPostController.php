<?php

namespace App\Http\Controllers\API\Community;

use App\Http\Controllers\API\ApiController;

use App\Models\CommunityPostReport;
use App\Models\CommunityPosts;
use App\Models\CommunityPostTopic;
use App\Models\CommunityPostComment;
use App\Models\CommunityPostCommentReply;
use App\Models\CommunityPostCommentReport;
use App\Models\CommunityPostLike;
use App\Models\TaskMilestone;
use App\Models\User;
use App\Models\UserLevelTaskLog;
use App\Models\userMilestone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\FCMService;
use Throwable;

class CommunityPostController extends ApiController
{
    public function fetchPostTopics(){
        $communityPostTopics = CommunityPostTopic::where('is_active', 1)->get();
        if($communityPostTopics->isEmpty()) {
            return $this->successResponse('No community post topics found.', [], 200);
        }
        $topicsList =   $communityPostTopics->map(function ($topic) {
                            return [
                                'id' => $topic->id,
                                'name' => $topic->name,
                                'description' => $topic->description ?? '',
                                'image' => $topic->image ? asset('storage/' .  $topic->image) : '',
                            ];
                        });
        return $this->successResponse( 'Community Post Topics fetched successfully.',$topicsList, 200);
    }

    protected function getHumanReadableTime($timestamp)
    {
        $now = Carbon::now();
        $created = Carbon::parse($timestamp);
        $diffInSeconds = $now->diffInSeconds($created);

        if ($diffInSeconds < 60) {
            // return 'Just now';
            return 'À l’instant';
        }

        $diffInMinutes = $now->diffInMinutes($created);
        if ($diffInMinutes < 60) {
            // return $diffInMinutes . ' mins ago';
            return ' Il y a '. $diffInMinutes .' minute' . ($diffInMinutes > 1 ? 's' : '') ;
        }

        $diffInHours = $now->diffInHours($created);
        $remainingMinutes = $diffInMinutes % 60;

        if ($diffInHours < 24) {
            if ($remainingMinutes > 0) {
                // return $diffInHours . ' hour' . ($diffInHours > 1 ? 's' : '') . ' ' . $remainingMinutes . ' mins ago';
                return 'Il y a '. $diffInHours . ' heure' . ($diffInHours > 1 ? 's' : '') . ' ' . $remainingMinutes . ' minutes';
            } else {
                // return $diffInHours . ' hour' . ($diffInHours > 1 ? 's' : '') . ' ago';
                return 'Il y a '. $diffInHours . ' heure' . ($diffInHours > 1 ? 's' : '');
            }
        }

        $diffInDays = $now->diffInDays($created);
        if ($diffInDays == 1) {
            // return 'Yesterday';
            return 'Hier';
        }

        if ($diffInDays < 4) {
            // return $diffInDays . ' days ago';
            return 'Il y a '. $diffInDays . ' jour';
        }

        // Fallback: return date in DD/MM
        return $created->format('d/m');
    }
    public function createPost(Request $request)
    {
        try{
            $data = $request->only(['topic_id', 'content', 'colour_theme', 'image']);
            $validationRules = [
                'topic_id' => ['required', 'integer', 'exists:community_post_topics,id'],
                'content' => ['required', 'string', 'max:5000'],
                'colour_theme' => ['required', 'string', 'max:50'],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,bmp,tiff,heif,heic'],
            ];

            $validationMessages = [
                'topic_id.required' => 'Please select the Topic for the post.',
                'topic_id.integer' => 'Please gave integer in topic_id' ,
                'topic_id.exists' => 'The selected topic does not exist.',
                'content.required' => 'The content field is required.',
                'content.string' => 'The content must be a string.',
                'content.max' => 'The content may not be greater than 5000 characters.',
                'colour_theme.required' => 'The colour theme field is required.',
                'colour_theme.string' => 'The colour theme must be a string.',
                'colour_theme.max' => 'The colour theme may not be greater than 50 characters.',
                'image.image' => 'The image must be an image file.',
                'image.mimes' => 'The image must be a file of type: jpg, jpeg, png, gif, bmp, tiff, heif, heic.',
            ];

            $validator = Validator::make($data, $validationRules, $validationMessages);

            if ($validator->fails()) {
                return $this->validationError("Validation failed", $validator->errors()->first(), 200);
            }

            $topic = CommunityPostTopic::where('id', $data['topic_id'])->where('is_active', 1)->first();
            if (!$topic) {
                return $this->validationError("Validation failed", "Invalid Topic id", 200);
            }

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('community_posts', 'public');
                $data['image'] = $imagePath;
                $data['content_type'] = 'text_image'; // Set content type to text_image if an image is uploaded
            } else {
                $data['image'] = null; // Set to null if no image is uploaded
                $data['content_type'] = 'text'; // Set content type to text if no image is uploaded
            }

            $post = CommunityPosts::create([
                'user_id' => auth()->id(),
                'topic_id' => $data['topic_id'],
                'content' => $data['content'],
                'colour_theme' => $data['colour_theme'],
                'image' => $data['image'],
                'content_type' => $data['content_type'],
            ]);
            $postData = $post->toArray();
            unset($postData['created_at'], $postData['updated_at'], $postData['topic_id']); // Remove created_at and updated_at
            $postData['image'] = $post->image ? asset('storage/' . $post->image) : '';
            $viewerTz = auth()->user()->timezone ?? "Europe/Paris";
            $time = $post->created_at->timezone($viewerTz);

            $postData['timeline'] = $this->getHumanReadableTime($time);
            $postData['topic'] = [
                'id' => $topic->id,
                'name' => $topic->name,
                'image' => $topic->image ? asset('storage/' .  $topic->image) : '',
            ];
            $postData['user'] = [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'profile_image' => $post->user->avatar ? asset('storage/' . $post->user->avatar) : '',
            ];

            return $this->successResponse('Post created successfully.', $postData, 200);
        }catch (\Exception $e) {
            return $this->errorResponse('An error occurred while creating the post: ' , $e->getMessage(), 500);
        }

    }

    public function updatePost(Request $request)
    {
        try {
            
            $data = $request->only(['post_id', 'topic_id', 'content', 'colour_theme', 'image', 'remove_image']);
            $validationRules = [
                'post_id' => ['required', 'integer', 'exists:community_posts,id'],
                'topic_id' => ['required', 'integer', 'exists:community_post_topics,id'],
                'content' => ['required', 'string', 'max:5000'],
                'colour_theme' => ['required', 'string', 'max:50'],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,bmp,tiff,heif,heic'],
                'remove_image' => ['nullable', 'boolean'],
            ];
            
            $validationMessages = [
                'post_id.required' => 'The post ID is required.',
                'post_id.integer' => 'The post ID must be an integer.',
                'post_id.exists' => 'The selected post does not exist.',
                'topic_id.required' => 'Please select the Topic for the post.',
                'topic_id.integer' => 'The topic ID must be an integer.',
                'topic_id.exists' => 'The selected topic does not exist.',
                'content.required' => 'The content field is required.',
                'content.string' => 'The content must be a string.',
                'content.max' => 'The content may not be greater than 5000 characters.',
                'colour_theme.required' => 'The colour theme field is required.',
                'colour_theme.string' => 'The colour theme must be a string.',
                'colour_theme.max' => 'The colour theme may not be greater than 50 characters.',
                'image.image' => 'The image must be an image file.',
                'image.mimes' => 'The image must be a file of type: jpg, jpeg, png, gif, bmp, tiff, heif, heic.',
                'remove_image.boolean' => 'The remove_image field must be true or false.',
            ];
            
            $validator = Validator::make($data, $validationRules, $validationMessages);
            
            if ($validator->fails()) {
                return $this->validationError("Validation failed", $validator->errors()->first(), 200);
            }

            $post = CommunityPosts::find($data['post_id']);
            if (!$post) {
                return $this->errorResponse('Post not found.',[], 404);
            }

            if ($post->user_id !== auth()->id()) {
                return $this->errorResponse('You are not authorized to update this post.',[], 403);
            }
            
            $topic = CommunityPostTopic::where('id', $data['topic_id'])->where('is_active', 1)->first();
            if (!$topic) {
                return $this->validationError("Validation failed", "Invalid Topic id", 200);
            }

            // Handle remove_image logic
            $removeImage = isset($data['remove_image']) && $data['remove_image'] == 1;

            if ($removeImage) {
                // Remove existing image if any
                if ($post->image) {
                    Storage::disk('public')->delete($post->image);
                }
                $data['image'] = null;
                $data['content_type'] = 'text'; // Set content type to text if image is removed
            } elseif ($request->hasFile('image')) {
                // Delete old image if uploading new one
                if ($post->image) {
                    Storage::disk('public')->delete($post->image);
                }
                $imagePath = $request->file('image')->store('community_posts', 'public');
                $data['image'] = $imagePath;
                $data['content_type'] = 'text_image'; // Set content type to text_image if an image is uploaded
            } else {
                $data['image'] = $post->image; // Keep the old image if no new image is uploaded and not removing
                $data['content_type'] = $post->content_type; // Keep the old content type if no new image is uploaded
            }

            $post->update([
                'topic_id' => $data['topic_id'],
                'content' => $data['content'],
                'colour_theme' => $data['colour_theme'],
                'image' => $data['image'],
                'content_type' => $data['content_type'],
            ]);
            $postData = $post->toArray();
            unset($postData['created_at'], $postData['updated_at'], $postData['topic_id']); // Remove created_at and updated_at

            $postData['image'] = $post->image ? asset('storage/' . $post->image) : '';
            $viewerTz = auth()->user()->timezone ?? "Europe/Paris";
            $time = $post->created_at->timezone($viewerTz);

            $postData['timeline'] = $this->getHumanReadableTime($time);
            $postData['topic'] = [
                'id' => $topic->id,
                'name' => $topic->name,
                'image' => $topic->image ? asset('storage/' .  $topic->image) : '',
            ];
            $postData['user'] = [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'profile_image' => $post->user->avatar ? asset('storage/' . $post->user->avatar) : '',
            ];
            // $this->logLevelTaskEntries(auth()->id(), 'community-posts',1,0);

            // $milestoneCompletedArray = $this->sendCommunityMilestoneArray(auth()->id());
            return $this->successResponse('Post updated successfully.', $postData, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while updating the post: ', $e->getMessage(), 500);
        }
    }

    public function deletePost($id){
        try {
            DB::beginTransaction();

            $post = CommunityPosts::find($id);
            if (!$post) {
                return $this->errorResponse('Post not found.', [], 404);
            }

            if ($post->user_id !== auth()->id()) {
                return $this->errorResponse('You are not authorized to delete this post.', [], 403);
            }

            // Delete the image if it exists
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }

            // Delete all post reports
            CommunityPostReport::where('post_id', $post->id)->delete();

            // Get all comments for this post
            $comments = CommunityPostComment::where('post_id', $post->id)->get();

            foreach ($comments as $comment) {
                // Delete all comment reports
                CommunityPostCommentReport::where([
                    'comment_or_reply_id' => $comment->id,
                    'is_comment_or_reply' => 'comment'
                ])->delete();

                // Get all replies for this comment
                $replies = CommunityPostCommentReply::where('comment_id', $comment->id)->get();

                foreach ($replies as $reply) {
                    // Delete all reply reports
                    CommunityPostCommentReport::where([
                        'comment_or_reply_id' => $reply->id,
                        'is_comment_or_reply' => 'reply'
                    ])->delete();
                }

                // Delete all replies for this comment
                CommunityPostCommentReply::where('comment_id', $comment->id)->delete();
            }

            // Delete all comments for this post
            CommunityPostComment::where('post_id', $post->id)->delete();

            // Finally, delete the post
            $post->delete();

            DB::commit();
            return $this->successResponse('Post and all related data deleted successfully.', [], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('An error occurred while deleting the post: ' , $e->getMessage(), 500);
        }
    }

    public function fetchMyPosts(){
        try{
            $posts = CommunityPosts::where('user_id', auth()->id())
                ->with(['user', 'topic'])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($posts->isEmpty()) {
                return $this->successResponse('No posts found for your account.', [], 200);
            }

            $postsList = $posts->map(function ($post) {
                $postData = $post->toArray();
                unset($postData['created_at'], $postData['updated_at'], $postData['topic_id'], $postData['user_id']); // Remove created_at and updated_at
                $postData['image'] = $post->image ? asset('storage/' . $post->image) : '';
                $viewerTz = auth()->user()->timezone ?? "Europe/Paris";
                $time = $post->created_at->timezone($viewerTz);

                $postData['timeline'] = $this->getHumanReadableTime($time);
                $postData['comments_count'] = $post->getCommentsCountAttribute();
                $postData['likes_count'] = $post->getLikesCountAttribute();
                $postData['is_liked'] = $post->likes()->where('user_id', auth()->id())->exists() ? 1 : 0;
                $postData['is_reported'] = $post->reports()->where('user_id', auth()->id())->exists() ? 1 : 0;
                $postData['created_by_you'] = $post->user_id === auth()->id() ? 1 : 0;
                $postData['topic'] = [
                    'id' => $post->topic->id,
                    'name' => $post->topic->name,
                    'image' => $post->topic->image ? asset('storage/' .  $post->topic->image) : '',
                ];
                $postData['user'] = [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'profile_image' => $post->user->avatar ? asset('storage/' . $post->user->avatar) : '',
                ];
                return $postData;
            });

            return $this->successResponse('Your posts fetched successfully.', $postsList, 200);
        }catch (\Exception $e) {
            return $this->errorResponse('An error occurred while fetching your posts' , $e->getMessage(), 500);
        }
    }

    public function addCommunityPostComment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'post_id' => ['required', 'integer', 'exists:community_posts,id'],
                'comment' => ['required', 'string', 'max:1000'],
            ], [
                'post_id.required' => 'The post id is required.',
                'post_id.integer' => 'The post ID must be an integer.',
                'post_id.exists' => 'The post does not exist.',
                'comment.required' => 'The comment is required.',
                'comment.string' => 'The comment must be a string.',
                'comment.max' => 'The comment may not be greater than 1000 characters.',
            ]);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $user_id = Auth::id();
            $communityPostComment = new CommunityPostComment;
            $communityPostComment->post_id = $request->post_id;
            $communityPostComment->user_id = $user_id;
            $communityPostComment->comment = $request->comment;
            if ($communityPostComment->save()) {
                // If commented on a post successfully then send notification to post owner
                $authUser = Auth::user();
                if (isset($communityPostComment->post) && ($authUser->id != $communityPostComment->post->user_id)) {
                    $user = User::find($communityPostComment->post->user_id);
                    if ($user) {
                        if (isset($user->device_token) && $user->device_token != '') {
                            $postComments = CommunityPostComment::where(['post_id' => $communityPostComment->post_id])->where('user_id', '!=', $communityPostComment->post->user_id)->get()->count();
                            $this->sendAchievementNotification('comments', $postComments, $user->device_token);
                            
                            /*$title = "Comment on a post";
                            $body = $authUser->name . ' commented "' . $communityPostComment->comment . '" on your post.';
                            
                            $FCMService = new FCMService;
                            $response = $FCMService->communityPostNotification($title, $body, $user->device_token);*/
                        }
                    }
                }
                $data = $this->getPostComments($communityPostComment->post_id);

                return $this->successResponse("Successfully commented on a community post", $data, 200);
            }
            return $this->errorResponse("Failed to comment on a community post", [], 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function replyToComment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'comment_id' => ['required', 'integer', 'exists:community_post_comments,id'],
                'reply' => ['required', 'string', 'max:1000'],
            ], [
                'comment_id.required' => 'The comment id is required.',
                'comment_id.integer' => 'The comment id must be an integer.',
                'comment_id.exists' => 'The comment does not exist.',
                'reply.required' => 'The reply is required.',
                'reply.string' => 'The reply must be a string.',
                'reply.max' => 'The reply may not be greater than 1000 characters.',
            ]);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $user_id = Auth::id();
            $communityPostCommentReply = new CommunityPostCommentReply;
            $communityPostCommentReply->comment_id = $request->comment_id;
            $communityPostCommentReply->user_id = $user_id;
            $communityPostCommentReply->reply = $request->reply;
            if ($communityPostCommentReply->save()) {
                // If replied to a comment successfully then send notification to post owner and commented user
                $authUser = Auth::user();
                if (isset($communityPostCommentReply->comment)) {
                    if ($authUser->id != $communityPostCommentReply->comment->user_id) {
                        $commentedUser = User::find($communityPostCommentReply->comment->user_id);
                        if ($commentedUser) {
                            if (isset($commentedUser->device_token) && $commentedUser->device_token != '') {
                                $title = "Reply to a comment";
                                $body = $authUser->name . ' replied "' . $communityPostCommentReply->reply . '" to your comment.';
                                
                                $FCMService = new FCMService;
                                $response = $FCMService->communityPostNotification($title, $body, $commentedUser->device_token);
                            }
                        }
                    }
                    
                    /*if (isset($communityPostCommentReply->comment->post) && ($authUser->id != $communityPostCommentReply->comment->post->user_id)) {
                        $postUser = User::find($communityPostCommentReply->comment->post->user_id);
                        if ($postUser) {
                            if (isset($postUser->device_token) && $postUser->device_token != '') {
                                $title = "Comment on a post";
                                $body = $authUser->name . ' replied "' . $communityPostCommentReply->reply . '" to a comment on your post.';
                                
                                $FCMService = new FCMService;
                                $response = $FCMService->communityPostNotification($title, $body, $postUser->device_token);
                            }
                        }
                    }*/
                }
                $comment = CommunityPostComment::find($communityPostCommentReply->comment_id);
                $data = $this->getPostComments($comment->post_id);

                return $this->successResponse("Successfully replied to a comment", $data, 200);
            }
            return $this->errorResponse("Failed to reply a comment", [], 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function reportToComment(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'comment_id' => ['required', 'integer', 'exists:community_post_comments,id'],
                'reason' => ['required', 'string', 'max:255'],
                'feedback' => ['nullable', 'string', 'max:1000'],
            ], [
                'comment_id.required' => 'The comment ID is required.',
                'comment_id.integer' => 'The comment ID must be an integer.',
                'comment_id.exists' => 'The selected comment does not exist.',
                'reason.required' => 'The reason is required.',
                'reason.string' => 'The reason must be a string.',
                'reason.max' => 'The reason may not be greater than 255 characters.',
                'feedback.string' => 'The feedback must be a string.',
                'feedback.max' => 'The feedback may not be greater than 1000 characters.',
            ]);

            if ($validator->fails()) {
                return $this->validationError("Validation Failed", $validator->errors()->first(), 422); // Use 422 for validation errors
            }

            $communityPostCommentReport = CommunityPostCommentReport::where(['comment_or_reply_id' => $request->comment_id, 'user_id' => auth()->id(), 'is_comment_or_reply' => 'comment'])->first();
            if ($communityPostCommentReport) {
                return $this->errorResponse('You have already reported this comment, and not allow to report again.', [], 409);
            }

            $user_id = Auth::id();
            $communityPostCommentReport = new CommunityPostCommentReport;
            $communityPostCommentReport->comment_or_reply_id = $request->comment_id;
            $communityPostCommentReport->user_id = $user_id;
            $communityPostCommentReport->is_comment_or_reply = 'comment';
            $communityPostCommentReport->reason = $request->reason;
            if ($request->has('feedback') && $request->feedback != '') {
                $communityPostCommentReport->feedback = $request->feedback;
            }
            $communityPostCommentReport->save();

            $comment = CommunityPostComment::find($request->comment_id);
            $data = $this->getPostComments($comment->post_id);

            return $this->successResponse("Successfully reported to a comment", $data, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function reportToCommentReply(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'reply_id' => ['required', 'integer', 'exists:community_post_comment_replies,id'],
                'reason'   => ['required', 'string', 'max:255'],
                'feedback' => ['nullable', 'string', 'max:1000'],
            ], [
                'reply_id.required' => 'The reply ID is required.',
                'reply_id.integer'  => 'The reply ID must be an integer.',
                'reply_id.exists'   => 'The selected reply does not exist.',
                'reason.required'   => 'The reason is required.',
                'reason.string'     => 'The reason must be a string.',
                'reason.max'        => 'The reason may not be greater than 255 characters.',
                'feedback.string'   => 'The feedback must be a string.',
                'feedback.max'      => 'The feedback may not be greater than 1000 characters.',
            ]);

            if ($validator->fails()) {
                return $this->validationError("Validation Failed", $validator->errors()->first(), 422);
            }

            $communityPostCommentReplyReport = CommunityPostCommentReport::where(['comment_or_reply_id' => $request->reply_id, 'user_id' => auth()->id(), 'is_comment_or_reply' => 'reply'])->first();
            if ($communityPostCommentReplyReport) {
                return $this->errorResponse('You have already reported this reply, and not allow to report again.', [], 409);
            }

            $user_id = Auth::id();
            $communityPostCommentReplyReport = new CommunityPostCommentReport;
            $communityPostCommentReplyReport->comment_or_reply_id = $request->reply_id;
            $communityPostCommentReplyReport->user_id = $user_id;
            $communityPostCommentReplyReport->is_comment_or_reply = 'reply';
            $communityPostCommentReplyReport->reason = $request->reason;
            if ($request->has('feedback') && $request->feedback != '') {
                $communityPostCommentReplyReport->feedback = $request->feedback;
            }
            $communityPostCommentReplyReport->save();

            $reply = CommunityPostCommentReply::find($request->reply_id);
            $data = $this->getPostComments($reply->comment->post_id);

            return $this->successResponse("Successfully reported to a comment reply", $data, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function getCommunityPostComments(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'post_id' => ['required', 'integer', 'exists:community_posts,id'],
            ], [
                'post_id.required' => 'The post id is required.',
                'post_id.integer' => 'The post id must be an integer.',
                'post_id.exists' => 'The post does not exist.',
            ]);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $data = $this->getPostComments($request->post_id);

            return $this->successResponse("Comments found successfully", $data, 200);
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function deleteCommunityPostComment($id) {
        try {
            // Start a database transaction
            DB::beginTransaction();

            $communityPostComment = CommunityPostComment::where(['id' => $id])->first();
            if ($communityPostComment) {
                if ($communityPostComment->post) {
                    if ($communityPostComment->post->user_id == auth()->id() || $communityPostComment->user_id == auth()->id()) {
                        // Delete all replies for this comment
                        $replyIds = CommunityPostCommentReply::where('comment_id', $id)->pluck('id');
                        if ($replyIds->isNotEmpty()) {
                            // Delete reports for all replies
                            CommunityPostCommentReport::whereIn('comment_or_reply_id', $replyIds)
                                ->where('is_comment_or_reply', 'reply')
                                ->delete();
                        }
                        // Delete replies themselves
                        CommunityPostCommentReply::where(['comment_id' => $id])->delete();
                        // Delete reports for the comment
                        CommunityPostCommentReport::where(['comment_or_reply_id' => $id, 'is_comment_or_reply' => 'comment'])->delete();
                        // Delete the comment
                        CommunityPostComment::where(['id' => $id])->delete();

                        // Commit transaction
                        DB::commit();

                        $data = $this->getPostComments($communityPostComment->post_id);

                        return $this->successResponse("Comment deleted successfully", $data, 200);
                    }
                    return $this->errorResponse('You are not authorized to delete this comment.', [], 403);
                }
                return $this->errorResponse('Post not found.', [], 404);
            }
            return $this->errorResponse('Comment not found.', [], 404);
        } catch (\Exception $e) {
            // Rollback in case of error
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete comment', 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteCommentReply($id) {
        try {
            // Start a database transaction
            DB::beginTransaction();

            $communityPostCommentReply = CommunityPostCommentReply::where(['id' => $id])->first();
            if ($communityPostCommentReply) {
                if ($communityPostCommentReply->comment) {
                    if ($communityPostCommentReply->comment->post) {
                        if ($communityPostCommentReply->comment->post->user_id == auth()->id() || $communityPostCommentReply->user_id == auth()->id()) {
                            // Delete records from related tables
                            CommunityPostCommentReply::where(['id' => $id])->delete();
                            CommunityPostCommentReport::where(['comment_or_reply_id' => $id, 'is_comment_or_reply' => 'reply'])->delete();

                            // Commit transaction
                            DB::commit();

                            $data = $this->getPostComments($communityPostCommentReply->comment->post_id);

                            return $this->successResponse("Reply deleted successfully", $data, 200);
                        }
                        return $this->errorResponse('You are not authorized to delete this reply.', [], 403);
                    }
                    return $this->errorResponse('Post not found.', [], 404);
                }
                return $this->errorResponse('Comment not found.', [], 404);
            }
            return $this->errorResponse('Comment reply not found.', [], 404);
        } catch (\Exception $e) {
            // Rollback in case of error
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete reply', 'message' => $e->getMessage()], 500);
        }
    }

    public function fetchAllPosts(Request $request)
    {
        try {
            // Validation rules
            $validationRules = [
                'page' => ['nullable', 'integer', 'min:1'],
                'sort_by' => ['nullable', 'string', 'in:created_at,likes_count,comments_count', 'max:50'],
                'content_type' => ['nullable', 'string', 'in:text,text_image', 'max:50'],
                'topic_id' => ['nullable', 'string'],  // topic_id is now a string (either single ID or comma-separated list)
                'timeframe' => ['nullable', 'string', 'in:today,this_week,last_week,last_2_weeks,this_month,last_month,all_time,oldest,newest', 'max:50'],
            ];

            $validationMessages = [
                'page.integer' => 'The page must be an integer.',
                'page.min' => 'The page must be at least 1.',
                'sort_by.string' => 'The sort by field must be a string.',
                'sort_by.in' => 'The sort by field must be one of the following: created_at, likes_count, comments_count.',
                'sort_by.max' => 'The sort by field may not be greater than 50 characters.',
                'content_type.string' => 'The content type must be a string.',
                'content_type.in' => 'The content type must be one of the following: text, text_image.',
                'content_type.max' => 'The content type may not be greater than 50 characters.',
                'topic_id.string' => 'The topic_id must be a string (either a single ID or comma-separated list).',
                'timeframe.string' => 'The timeframe must be a string.',
                'timeframe.in' => 'The timeframe must be one of the following: today, this_week, last_week, last_2_weeks, this_month, last_month, all_time.',
                'timeframe.max' => 'The timeframe may not be greater than 50 characters.',
            ];

            // Validate the request data
            $validator = Validator::make($request->all(), $validationRules, $validationMessages);
            if ($validator->fails()) {
                return $this->validationError("Validation failed", $validator->errors()->first(), 200);
            }

            // Get the page value, defaulting to 1 if not provided
            $page = $request->input('page', 1);

            // Initialize query
            $query = CommunityPosts::with(['user', 'topic']);

            // Track how many filters are applied
            $filtersApplied = 0;

            // Apply filters if they exist in the request
            if ($request->has('sort_by') && !is_null($request->input('sort_by')) && $request->input('sort_by') !== '') {
                $sortBy = $request->input('sort_by');
                if ($sortBy === 'likes_count') {
                    $query->withCount('likes')->orderBy('likes_count', 'desc');
                } elseif ($sortBy === 'comments_count') {
                    $query->withCount('comments')->orderBy('comments_count', 'desc');
                } else {
                    $query->orderBy($sortBy, 'desc');
                }
                // $filtersApplied++;
                // Always order by created_at desc after the specific sort
                $query->orderBy('created_at', 'desc');
            } else {
                $query->orderBy('created_at', 'desc'); // Default sorting by created_at
            }
            // Filter by content_type if provided
            if ($request->has('content_type') && !is_null($request->input('content_type')) && $request->input('content_type') !== '') {
                $contentType = $request->input('content_type');
                $query->where('content_type', $contentType);
                // $filtersApplied++;
            }

            // Filter by topic_id (either single ID or comma-separated list)
            if ($request->has('topic_id') && !is_null($request->input('topic_id')) && $request->input('topic_id') !== '') {
                $topicIds = $request->input('topic_id');

                // Check if the topic_id is a comma-separated list
                if (strpos($topicIds, ',') !== false) {
                    // Convert the comma-separated list into an array of integers
                    $topicIds = array_map('intval', explode(',', $topicIds));
                } else {
                    // Otherwise, treat it as a single integer
                    $topicIds = [(int)$topicIds];
                }

                // Apply the whereIn filter
                $query->whereIn('topic_id', $topicIds);
                // $filtersApplied++;
            }

            // Apply timeframe filter if provided
            if ($request->has('timeframe') && !is_null($request->input('timeframe')) && $request->input('timeframe') !== '') {
                $timeframe = $request->input('timeframe');
                $currentDate = now();
                switch ($timeframe) {
                    case 'today':
                        $query->whereDate('created_at', $currentDate->copy()->toDateString());
                        break;
                    case 'this_week':
                        $query->whereBetween('created_at', [
                            $currentDate->copy()->startOfWeek()->toDateString(),
                            $currentDate->copy()->endOfWeek()->toDateString(),
                        ]);
                        break;
                    case 'last_week':
                        // Now calculate the start and end of last week from the original $currentDate
                        $startOfLastWeek = $currentDate->copy()->subWeek()->startOfWeek()->startOfDay()->toDateString();  // Start of last week
                        $endOfLastWeek = $currentDate->copy()->subWeek()->endOfWeek()->endOfDay()->toDateString();  // End of last week
                        $query->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek]);
                        break;
                    case 'last_2_weeks':
                        // Calculate the start of the week before last (i.e. two weeks ago)
                        $startOfTwoWeeksAgo = $currentDate->copy()->subWeeks(2)->startOfWeek()->startOfDay()->toDateString(); // Start of week before last
                        $endOfLastWeek = $currentDate->copy()->subWeek()->endOfWeek()->endOfDay()->toDateString(); // End of last week
                        $query->whereBetween('created_at', [$startOfTwoWeeksAgo, $endOfLastWeek]);
                        break;
                    case 'this_month':
                        $query->whereMonth('created_at', $currentDate->month)
                            ->whereYear('created_at', $currentDate->year);  
                        break;
                    case 'last_month':
                        $query->whereMonth('created_at', $currentDate->copy()->subMonth()->month)
                            ->whereYear('created_at', $currentDate->year);
                        break;
                    case 'all_time':
                        // No filter for all_time
                        break;
                    case 'oldest':
                        $query->getQuery()->orders = null; // Clear previous orderBy clauses
                        $query->orderBy('created_at', 'asc');
                        break;
                    case 'newest':
                        $query->orderBy('created_at', 'desc');
                        break;
                    default:
                        break;
                }
                $filtersApplied++;
            }

            // Pagination
            $posts = $query->paginate(10, ['*'], 'page', $page);

            if ($posts->isEmpty()) {
                return $this->successResponse('No posts found.', [
                    'data' => [],
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'last_page' => $posts->lastPage(),
                        'per_page' => $posts->perPage(),
                        'total' => $posts->total(),
                    ],
                    'filters' => [
                        'filters_applied' => $filtersApplied,
                        'sort_by' => $request->input('sort_by', 'created_at'),
                        'content_type' => $request->input('content_type', ''),
                        'topic_id' => $request->input('topic_id', ''),
                        'timeframe' => $request->input('timeframe', ''),
                    ]
                ], 200);
            }

            // Process posts
            $postsList = $posts->getCollection()->map(function ($post) {
                $postData = $post->toArray();
                unset($postData['created_at'], $postData['updated_at'], $postData['topic_id'], $postData['user_id']); // Remove unnecessary fields
                $postData['image'] = $post->image ? asset('storage/' . $post->image) : '';
                
                $viewerTz = auth()->user()->timezone ?? "Europe/Paris";
                $time = $post->created_at->timezone($viewerTz);

                $postData['timeline'] = $this->getHumanReadableTime($time);
                $postData['comments_count'] = $post->getCommentsCountAttribute();
                $postData['likes_count'] = $post->getLikesCountAttribute();
                $postData['is_liked'] = $post->likes()->where('user_id', auth()->id())->exists() ? 1 : 0;
                $postData['is_reported'] = $post->reports()->where('user_id', auth()->id())->exists() ? 1 : 0;
                $postData['created_by_you'] = $post->user_id === auth()->id() ? 1 : 0;
                $postData['topic'] = [
                    'id' => $post->topic->id,
                    'name' => $post->topic->name,
                    'image' => $post->topic->image ? asset('storage/' .  $post->topic->image) : '',
                ];
                $postData['user'] = [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'profile_image' => $post->user->avatar ? asset('storage/' . $post->user->avatar) : '',
                ];
                return $postData;
            });

            return $this->successResponse('Posts fetched successfully.', [
                'data' => $postsList,
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                ],
                'filters' => [
                    'filters_applied' => $filtersApplied,
                    'sort_by' => $request->input('sort_by', ''),
                    'content_type' => $request->filled('content_type') ? $request->input('content_type') : '',
                    'topic_id' => $request->filled('topic_id') ? $request->input('topic_id') : '',
                    'timeframe' => $request->filled('timeframe') ? $request->input('timeframe') : '',
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while fetching posts: ', $e->getMessage(), 500);
        }
    }

    public function likePost(Request $request)
    {
        try{
            $data = $request->only(['post_id']);
            $validationRules = [
                'post_id' => ['required', 'integer', 'exists:community_posts,id'],
            ];
            $validationMessages = [
                'post_id.required' => 'The post ID is required.',
                'post_id.integer' => 'The post ID must be an integer.',
                'post_id.exists' => 'The selected post does not exist.',
            ];
            $validator = Validator::make($data, $validationRules, $validationMessages);
            if ($validator->fails()) {
                return $this->validationError("Validation failed", $validator->errors()->first(), 200);
            }
            $post = CommunityPosts::find($data['post_id']);
            if (!$post) {
                return $this->errorResponse('Post not found.', [], 404);
            }
            $like = $post->likes()->where('user_id', auth()->id())->first();
            if ($like) {
                // If the user has already liked the post, remove the like
                $like->delete();
                return $this->successResponse('Post unliked successfully.', [], 200);
            } else {
                // If the user has not liked the post, create a new like
                $postLike = $post->likes()->create(['user_id' => auth()->id()]);
                if ($postLike) {
                    // If post liked successfully then send notification to post owner
                    $authUser = Auth::user();
                    if ($authUser->id != $post->user_id) {
                        $user = User::find($post->user_id);
                        if ($user) {
                            if (isset($user->device_token) && $user->device_token != '') {
                                $postLikes = CommunityPostLike::where(['post_id' => $post->id])->where('user_id', '!=', $post->user_id)->get()->count();

                                $this->sendAchievementNotification('likes', $postLikes, $user->device_token);

                                /*$title = "Liked post";
                                $body = "$authUser->name liked your post.";

                                $FCMService = new FCMService;
                                $response = $FCMService->communityPostNotification($title, $body, $user->device_token);*/
                            }
                        }
                    }
                    return $this->successResponse('Post liked successfully', [], 200);
                }
                return $this->errorResponse("Post not liked successfully, please try again", [], 200);
            }
            
        }catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ', $e->getMessage(), 500);
        }
    }

    public function reportPost(Request $request){
        try{
            $data = $request->only(['post_id', 'reason', 'description']);
            $validationRules = [
                'post_id' => ['required', 'integer', 'exists:community_posts,id'],
                'reason' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
            ];
            $validationMessages = [
                'post_id.required' => 'The post ID is required.',
                'post_id.integer' => 'The post ID must be an integer.',
                'post_id.exists' => 'The selected post does not exist.',
                'reason.required' => 'The reason for reporting is required.',
                'reason.string' => 'The reason must be a string.',
                'reason.max' => 'The reason may not be greater than 255 characters.',
                'description.string' => 'The description must be a string.',
                'description.max' => 'The description may not be greater than 1000 characters.',
            ];
            $validator = Validator::make($data, $validationRules, $validationMessages);
            if ($validator->fails()) {
                return $this->validationError("Validation failed", $validator->errors()->first(), 200);
            }
            $post = CommunityPosts::find($data['post_id']);
            if (!$post) {
                return $this->errorResponse('Post not found.', [], 404);
            }
            // Check if the user has already reported this post
            $existingReport = CommunityPostReport::where('post_id', $data['post_id'])
                ->where('user_id', auth()->id())
                ->first();
            if ($existingReport) {
                return $this->errorResponse('You have already reported this post.', [], 400);
            }
            // Create a new report
            $report = CommunityPostReport::create([
                'post_id' => $data['post_id'],
                'user_id' => auth()->id(),
                'reason' => $data['reason'],
                'description' => $data['description'] ?? '',
                'mark_as_solved' => '0',
            ]);
            return $this->successResponse('Post reported successfully.', [
                'report_id' => $report->id,
                'post_id' => $report->post_id,
                'user_id' => $report->user_id,
                'reason' => $report->reason,
                'description' => $report->description,
                'mark_as_solved' => $report->mark_as_solved,
            ], 200);

        }catch (\Exception $e) {
            return $this->errorResponse('An error occurred while reporting the post: ', $e->getMessage(), 500);
        }
    }

    public function getPostComments($post_id) {
        $comments = CommunityPostComment::with('commentReplies')->where(['post_id' => $post_id])->get();
        $comments = $comments->map(function ($comment) {
            $communityPostCommentReport = CommunityPostCommentReport::where(['comment_or_reply_id' => $comment->id, 'user_id' => auth()->id(), 'is_comment_or_reply' => 'comment'])->first();
            $viewerTz = auth()->user()->timezone ?? "Europe/Paris";
            $commentTime = $comment->created_at->timezone($viewerTz);
            $humanReadableCommentTime = $this->getHumanReadableTime($commentTime);
            return [
                'id' => $comment->id,
                'post_id' => $comment->post_id,
                'comment' => $comment->comment,
                'created_by_you' => $comment->user_id == auth()->id() ? 1 : 0,
                'is_reported' => $communityPostCommentReport ? 1 : 0,
                'user' => [
                    'id' => optional($comment->user)->id ?? 0,
                    'name' => optional($comment->user)->name ?? 'Deleted user',
                    'avatar' => optional($comment->user)->avatar ? asset('storage/' . optional($comment->user)->avatar) : ''
                ],
                'comment_replies_count' => $comment->commentReplies->count(),
                'commented_time' => $humanReadableCommentTime,
                'comment_replies' => $comment->commentReplies->map(function ($reply) {
                    $communityPostCommentReplyReport = CommunityPostCommentReport::where(['comment_or_reply_id' => $reply->id, 'user_id' => auth()->id(), 'is_comment_or_reply' => 'reply'])->first();
                    $viewerTz = auth()->user()->timezone ?? "Europe/Paris";
                    $replyTime = $reply->created_at->timezone($viewerTz);
                    $humanReadableReplyTime = $this->getHumanReadableTime($replyTime);
                    return [
                        'id' => $reply->id,
                        'reply' => $reply->reply,
                        'replied_time' => $humanReadableReplyTime,
                        'created_by_you' => $reply->user_id == auth()->id() ? 1 : 0,
                        'is_reported' => $communityPostCommentReplyReport ? 1 : 0,
                        'user' => [
                            'id' => optional($reply->user)->id ?? 0,
                            'name' => optional($reply->user)->name ?? 'Deleted user',
                            'avatar' => optional($reply->user)->avatar ? asset('storage/' . optional($reply->user)->avatar) : ''
                        ]
                    ];
                })
            ];
        });
        $data = [
            'comments_count' => $comments->count(),
            'comments' => $comments
        ];

        return $data;
    }

    protected function sendAchievementNotification($type, $count, $device_token) {
        $achievements = config('achievements');
        if (!empty($achievements)) {
            if (isset($achievements[$type]) && $achievements[$type] != '') {
                if (isset($achievements[$type][$count]) && $achievements[$type][$count] != '') {
                    $title = "Nouvelle interaction";
                    $body = $achievements[$type][$count];

                    $FCMService = new FCMService;
                    $response = $FCMService->communityPostNotification($title, $body, $device_token);

                    return true;
                }
            }
        }
        return false;
    }

    protected function sendCommunityMilestoneArray($userId){

        $milestoneCompletedArray = [];

        $communityPostsMilestones = TaskMilestone::where('milestone_type', 'community-posts')->get();

        $userCurrentMilestones = userMilestone::where([
            'user_id' => $userId,
            'milestone_type' => 'community-posts'
        ])->pluck('milestone_id')->toArray();

        $userStreakCompleteCount = UserLevelTaskLog::where([
            'user_id' => $userId,
            'level_task_type' => 'community-posts'
        ])->sum('completed_count');

        foreach ($communityPostsMilestones as $milestone) {
            if (
                $userStreakCompleteCount >= $milestone->milestone_count &&
                !in_array($milestone->id, $userCurrentMilestones)
            ) {
                $milestoneCompletedArray[] = [
                    'type' => 'community-posts',
                    'color' => 'light-purple',
                    'title' => $milestone->milestone_title,
                    'description' => $milestone->milestone_description,
                    'count' => $milestone->milestone_count,
                ];

                // Save to user_milestones table
                UserMilestone::create([
                    'user_id' => $userId,
                    'milestone_id' => $milestone->id,
                    'milestone_type' => 'community-posts',
                ]);
            }
        }

        return $milestoneCompletedArray ;
    }
}
