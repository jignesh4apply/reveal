<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BehaviourTestQuizAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BehaviourTestQuizActionController extends Controller
{
    public function index()
    {
        Session::put('page', 'behaviourTestAction');
        $actions = BehaviourTestQuizAction::orderBy('created_at', 'desc')->get()->toArray();

        return view('admin.behaviour_test_quiz_action.index', [
            'actions' => $actions,
        ]);
    }

    public function create(Request $request)
    {
        Session::put('page', 'behaviourTestAction');

        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [
                'title' => ['required', 'string'],
            ];

            $messages = [
                'title.required' => 'Please enter a title.',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            $slug = Str::slug($data['title']);
            $action = new BehaviourTestQuizAction();
            $action->title = $data['title'];
            $action->slug = $slug;
            $action->description = $data['description'] ?? null;
            $action->tag = $data['tag'] ?? null;
            $action->video_link = $data['video_link'] ?? null;
            $action->extra_link = $data['extra_link'] ?? null;
            $action->action_button_text = $data['action_button_text'] ?? null;
            $action->trigger_email = isset($data['trigger_email']) ? 1 : 0;
            $action->is_active = isset($data['is_active']) ? 1 : 0;
            $action->save();

            return redirect('admin/behaviour-test-quiz-action-index')->with('success', 'Action inserted successfully');
        }

        return view('admin.behaviour_test_quiz_action.create');
    }

    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'behaviourTestAction');
        $actionData = BehaviourTestQuizAction::where(['slug' => $slug, 'id' => $id])->firstOrFail()->toArray();

        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [
                'title' => ['required', 'string'],
            ];

            $messages = [
                'title.required' => 'Please enter a title.',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            $slug = Str::slug($data['title']);
            BehaviourTestQuizAction::where(['id' => $id])->update([
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'tag' => $data['tag'] ?? null,
                'video_link' => $data['video_link'] ?? null,
                'extra_link' => $data['extra_link'] ?? null,
                'action_button_text' => $data['action_button_text'] ?? null,
                'trigger_email' => isset($data['trigger_email']) ? 1 : 0,
                'is_active' => isset($data['is_active']) ? 1 : 0,
            ]);

            return redirect('admin/behaviour-test-quiz-action-index')->with('success', 'Action updated successfully');
        }

        return view('admin.behaviour_test_quiz_action.edit', [
            'actionData' => $actionData,
        ]);
    }

    public function destroy($slug, $id)
    {
        Session::put('page', 'behaviourTestAction');
        BehaviourTestQuizAction::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back();
    }
}
