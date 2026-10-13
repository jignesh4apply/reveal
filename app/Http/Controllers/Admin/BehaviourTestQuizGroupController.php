<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BehaviourTestQuizGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BehaviourTestQuizGroupController extends Controller
{
    public function index()
    {
        Session::put('page', 'behaviourTestQuizGroup');
        $quizGroups = BehaviourTestQuizGroup::orderBy('quiz_group_order', 'asc')->get()->toArray();
        $quizGroupOrder = BehaviourTestQuizGroup::whereNotNull('quiz_group_order')->pluck('quiz_group_order')->toArray();

        return view('admin.behaviour_test_quiz_group.index', [
            'quizGroups' => $quizGroups,
            'quizGroupOrder' => $quizGroupOrder,
        ]);
    }

    public function create(Request $request)
    {
        Session::put('page', 'behaviourTestQuizGroup');
        $quizGroupOrder = BehaviourTestQuizGroup::whereNotNull('quiz_group_order')->pluck('quiz_group_order')->toArray();
        $getLastQuizGroupOrder = BehaviourTestQuizGroup::whereNotNull('quiz_group_order')->orderBy('quiz_group_order', 'desc')->value('quiz_group_order');

        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [
                'title' => ['required', 'string'],
                'quiz_group_order' => ['required', 'numeric'],
            ];

            $message = [
                'title.required' => 'Please enter title.',
                'quiz_group_order.required' => 'Please enter quiz group order.',
                'quiz_group_order.numeric' => 'Quiz group order should be a numeric value.',
            ];

            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            $slug = Str::slug($data['title']);
            $existingQuizGroupOrder = BehaviourTestQuizGroup::where(['quiz_group_order' => $data['quiz_group_order']])->first();
            $updatedQuizGroupOrder = $getLastQuizGroupOrder ? $getLastQuizGroupOrder + 1 : 1;

            if (!empty($existingQuizGroupOrder)) {
                BehaviourTestQuizGroup::where(['id' => $existingQuizGroupOrder['id'], 'quiz_group_order' => $data['quiz_group_order']])
                    ->update(['quiz_group_order' => $updatedQuizGroupOrder]);
            }

            $quizGroup = new BehaviourTestQuizGroup();
            $quizGroup->title = $data['title'];
            $quizGroup->slug = $slug;
            $quizGroup->quiz_group_order = $data['quiz_group_order'];
            $quizGroup->save();

            return redirect('admin/behaviour-test-quiz-group-index')->with('success', 'Behaviour Test Quiz Group inserted successfully');
        }

        return view('admin.behaviour_test_quiz_group.create', [
            'quizGroupOrder' => $quizGroupOrder,
            'getLastQuizGroupOrder' => $getLastQuizGroupOrder,
        ]);
    }

    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'behaviourTestQuizGroup');
        $quizGroupData = BehaviourTestQuizGroup::where(['slug' => $slug, 'id' => $id])->firstOrFail()->toArray();
        $quizGroupOrder = BehaviourTestQuizGroup::where('quiz_group_order', '!=', $quizGroupData['quiz_group_order'])->pluck('quiz_group_order')->toArray();

        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [
                'title' => ['required', 'string'],
                'quiz_group_order' => ['required', 'numeric'],
            ];

            $message = [
                'title.required' => 'Please enter title.',
                'quiz_group_order.required' => 'Please enter quiz group order.',
                'quiz_group_order.numeric' => 'Quiz group order should be a numeric value.',
            ];

            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            $slug = Str::slug($data['title']);
            $existingQuizGroupOrder = BehaviourTestQuizGroup::where(['quiz_group_order' => $data['quiz_group_order']])->first();
            if (!empty($existingQuizGroupOrder)) {
                BehaviourTestQuizGroup::where(['id' => $existingQuizGroupOrder['id'], 'quiz_group_order' => $data['quiz_group_order']])
                    ->update(['quiz_group_order' => $quizGroupData['quiz_group_order']]);
            }

            BehaviourTestQuizGroup::where(['id' => $id])->update([
                'title' => $data['title'],
                'slug' => $slug,
                'quiz_group_order' => $data['quiz_group_order'],
            ]);

            return redirect('admin/behaviour-test-quiz-group-index')->with('success', 'Behaviour Test Quiz Group updated successfully');
        }

        return view('admin.behaviour_test_quiz_group.edit', [
            'quizGroupData' => $quizGroupData,
            'quizGroupOrder' => $quizGroupOrder,
        ]);
    }

    public function destroy($slug, $id)
    {
        Session::put('page', 'behaviourTestQuizGroup');
        BehaviourTestQuizGroup::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back();
    }

    public function setPosition(Request $request)
    {
        $positions = BehaviourTestQuizGroup::all();
        foreach ($positions as $position) {
            foreach ($request->order as $order) {
                if ($order['id'] == $position->id) {
                    $position->update(['quiz_group_order' => $order['position']]);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
