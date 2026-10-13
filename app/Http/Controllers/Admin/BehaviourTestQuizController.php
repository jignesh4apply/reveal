<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BehaviourTestQuiz;
use App\Models\BehaviourTestQuizGroup;
use App\Models\BehaviourTestQuizAction;
use App\Models\BehaviourTestQuizAnswer;
use App\Models\BehaviourTestQuizAnswerAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BehaviourTestQuizController extends Controller
{
    public function index()
    {
        Session::put('page', 'behaviourTestQuiz');
        $quizzes = BehaviourTestQuiz::with('group')->orderBy('quiz_position', 'asc')->get()->toArray();

        return view('admin.behaviour_test_quiz.index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function create(Request $request)
    {
        Session::put('page', 'behaviourTestQuiz');
        $quizGroups = BehaviourTestQuizGroup::all();
        $actions = BehaviourTestQuizAction::where('is_active', 1)->get();

        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'question' => ['required', 'string'],
                'behaviour_test_quiz_group_id' => ['required', 'integer'],
                'answers' => ['required', 'array'],
                'answers.*.answer' => ['required', 'string'],
                'answers.*.points' => ['required', 'numeric', 'min:0', 'max:1'],
            ];

            $messages = [
                'question.required' => 'Please enter a question.',
                'behaviour_test_quiz_group_id.required' => 'Please select a quiz group.',
                'answers.required' => 'Please add at least one answer.',
                'answers.*.answer.required' => 'Please enter the answer text.',
                'answers.*.points.required' => 'Please enter points for each answer.',
                'answers.*.points.min' => 'Points must be at least 0.',
                'answers.*.points.max' => 'Points must be at most 1.',
            ];

            $validator = Validator::make($data, $rules, $messages);
            $validator->after(function ($validator) use ($data) {
                if (isset($data['is_multiple_choice']) && $data['is_multiple_choice']) {
                    $totalPoints = 0;
                    if (isset($data['answers']) && is_array($data['answers'])) {
                        foreach ($data['answers'] as $ans) {
                            $totalPoints += floatval($ans['points'] ?? 0);
                        }
                    }
                    if ($totalPoints > 1.00) {
                        $validator->errors()->add('answers', 'For multiple choice questions, the sum of all answer points cannot be greater than 1.');
                    }
                }
            });

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            DB::transaction(function () use ($data) {
                $slug = Str::slug($data['question']);
                $maxPosition = BehaviourTestQuiz::max('quiz_position') ?? 0;

                $quiz = BehaviourTestQuiz::create([
                    'question' => $data['question'],
                    'behaviour_test_quiz_group_id' => $data['behaviour_test_quiz_group_id'],
                    'amplitude_word' => $data['amplitude_word'] ?? null,
                    'slug' => $slug,
                    'quiz_position' => $maxPosition + 1,
                    'is_active' => isset($data['is_active']) ? 1 : 0,
                    'is_multiple_choice' => isset($data['is_multiple_choice']) ? 1 : 0,
                ]);

                if (isset($data['answers']) && is_array($data['answers'])) {
                    foreach ($data['answers'] as $ansData) {
                        if (!empty($ansData['answer'])) {
                            $answer = BehaviourTestQuizAnswer::create([
                                'behaviour_test_quiz_id' => $quiz->id,
                                'answer' => $ansData['answer'],
                                'points' => min(1, max(0, floatval($ansData['points'] ?? 0))),
                            ]);

                            if (isset($ansData['actions']) && is_array($ansData['actions'])) {
                                foreach ($ansData['actions'] as $actData) {
                                    if (!empty($actData['behaviour_test_quiz_action_id'])) {
                                        BehaviourTestQuizAnswerAction::create([
                                            'behaviour_test_quiz_answer_id' => $answer->id,
                                            'behaviour_test_quiz_action_id' => $actData['behaviour_test_quiz_action_id'],
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            });

            return redirect('admin/behaviour-test-quiz-index')->with('success', 'Question inserted successfully');
        }

        return view('admin.behaviour_test_quiz.create', compact('quizGroups', 'actions'));
    }

    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'behaviourTestQuiz');
        $quiz = BehaviourTestQuiz::findOrFail($id);
        $quizGroups = BehaviourTestQuizGroup::all();
        $actions = BehaviourTestQuizAction::where('is_active', 1)->get();

        // Load the full quiz structure with its answers and the actions for those answers
        $quizData = BehaviourTestQuiz::with(['answers.actions'])->where('id', $id)->firstOrFail()->toArray();

        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'question' => ['required', 'string'],
                'behaviour_test_quiz_group_id' => ['required', 'integer'],
                'answers' => ['required', 'array'],
                'answers.*.answer' => ['required', 'string'],
                'answers.*.points' => ['required', 'numeric', 'min:0', 'max:1'],
            ];

            $messages = [
                'question.required' => 'Please enter a question.',
                'behaviour_test_quiz_group_id.required' => 'Please select a quiz group.',
                'answers.required' => 'Please add at least one answer.',
                'answers.*.answer.required' => 'Please enter the answer text.',
                'answers.*.points.required' => 'Please enter points for each answer.',
                'answers.*.points.min' => 'Points must be at least 0.',
                'answers.*.points.max' => 'Points must be at most 1.',
            ];

            $validator = Validator::make($data, $rules, $messages);
            $validator->after(function ($validator) use ($data) {
                if (isset($data['is_multiple_choice']) && $data['is_multiple_choice']) {
                    $totalPoints = 0;
                    if (isset($data['answers']) && is_array($data['answers'])) {
                        foreach ($data['answers'] as $ans) {
                            $totalPoints += floatval($ans['points'] ?? 0);
                        }
                    }
                    if ($totalPoints > 1.00) {
                        $validator->errors()->add('answers', 'For multiple choice questions, the sum of all answer points cannot be greater than 1.');
                    }
                }
            });

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            DB::transaction(function () use ($data, $quiz) {
                $slug = Str::slug($data['question']);

                $quiz->update([
                    'question' => $data['question'],
                    'behaviour_test_quiz_group_id' => $data['behaviour_test_quiz_group_id'],
                    'amplitude_word' => $data['amplitude_word'] ?? null,
                    'slug' => $slug,
                    'is_active' => isset($data['is_active']) ? 1 : 0,
                    'is_multiple_choice' => isset($data['is_multiple_choice']) ? 1 : 0,
                ]);

                // Cascade delete existing answers (answer actions will also be deleted automatically)
                $quiz->answers()->delete();

                if (isset($data['answers']) && is_array($data['answers'])) {
                    foreach ($data['answers'] as $ansData) {
                        if (!empty($ansData['answer'])) {
                            $answer = BehaviourTestQuizAnswer::create([
                                'behaviour_test_quiz_id' => $quiz->id,
                                'answer' => $ansData['answer'],
                                'points' => min(1, max(0, floatval($ansData['points'] ?? 0))),
                            ]);

                            if (isset($ansData['actions']) && is_array($ansData['actions'])) {
                                foreach ($ansData['actions'] as $actData) {
                                    if (!empty($actData['behaviour_test_quiz_action_id'])) {
                                        BehaviourTestQuizAnswerAction::create([
                                            'behaviour_test_quiz_answer_id' => $answer->id,
                                            'behaviour_test_quiz_action_id' => $actData['behaviour_test_quiz_action_id'],
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            });

            return redirect('admin/behaviour-test-quiz-index')->with('success', 'Question updated successfully');
        }

        return view('admin.behaviour_test_quiz.edit', compact('quiz', 'quizData', 'quizGroups', 'actions'));
    }

    public function destroy($slug, $id)
    {
        Session::put('page', 'behaviourTestQuiz');
        BehaviourTestQuiz::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back()->with('success', 'Question deleted successfully');
    }

    public function setPosition(Request $request)
    {
        if ($request->has('order') && is_array($request->order)) {
            foreach ($request->order as $order) {
                BehaviourTestQuiz::where('id', $order['id'])->update([
                    'quiz_position' => $order['position']
                ]);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Position updated successfully'
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid ordering parameter'
        ], 400);
    }
}
