<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuizRequest;
use App\Models\Answer;
use App\Models\Cardio;
use App\Models\MuscleStrength;
use App\Models\Quiz;
use App\Models\QuizGroup;
use App\Models\Transition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    public function index()
    {
        Session::put('page', "quiz");
        $quizzes = Quiz::orderBy('quiz_position', 'asc')->with('quiz_group')->get()->toArray();
        $quizGroupData = QuizGroup::get()->toArray();
        $transitions = Transition::all();
        $quizGroups = QuizGroup::all();
        $quizes = Quiz::all();
        return view('admin.quiz.questions.index', ['quizzes' => $quizzes, 'quizGroupData' => $quizGroupData, 'transitions' => $transitions, 'quizGroups' => $quizGroups, 'quizes' => $quizes]);
    }

    public function setQuizPosition(Request $request)
    {
        $positions = Quiz::all();
        foreach ($positions as $position) {
            foreach ($request->order as $order) {
                if ($order['id'] == $position->id) {
                    $position->update(['quiz_position' => $order['position']]);
                }
            }
        }

        return response('Update Successfully.', 200);
    }

    public function addQuiz(Request $request)
    {
        Session::put('page', "quiz");
        $transitions = Transition::all();
        $quizGroups = QuizGroup::all();
        $quizes = Quiz::all();

        if ($request->isMethod('post')) {
            $data = $request->all();
            // echo "<pre>"; print_r($data); die;
            $validator = $this->validateQuizData($data);
            if ($validator->fails()) {
                return $this->handleValidationFailure($validator);
            }

            // Additional validations
            $this->additionalValidations($data);

            // Prepare data
            $this->prepareQuizData($data);
            $data['ques_image'] = $this->handleQuizImage($request, $data);

            DB::transaction(function () use ($data) {
                $quiz = $this->createQuiz($data);
                $answers = $this->prepareAnswers($data, $quiz->id);
                Answer::insert($answers);
            });

            return redirect('/admin/quiz-index')->with('success', 'Question Inserted Successfully !!!');
        }

        return view('admin.quiz.questions.create', compact('transitions', 'quizGroups', 'quizes'));
    }

    private function validateQuizData(array $data)
    {
        $rules = [
            'title' => ['required', 'string'],
            'ques_type' => ['required', 'integer'],
            'quiz_group_id' => ['required'],
            'answer_type' => ['required'],
            'ques_image' => ['sometimes', 'required', 'mimes:jpeg,jpg,png,gif'],
            'ques_for_gender' => ['required', 'string'],
        ];

        $messages = [
            'title.required' => "Please enter Question Title.",
            'ques_type.required' => "Please select type of question.",
            'quiz_group_id.required' => "Please Select Quiz Group.",
            'answer_type.required' => "Please select answer type.",
            'ques_image.required' => "Please select an image for this question.",
            'ques_image.mimes' => "Please select an image with jpeg, jpg, png, or gif extensions.",
            'ques_for_gender.required' => "Please select one gender from the 'This Question is for' section."
        ];

        return Validator::make($data, $rules, $messages);
    }

    private function handleValidationFailure($validator)
    {
        return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
    }

    private function additionalValidations(array $data)
    {
        if ($data['ques_type'] == 0 && empty($data['ques_for'])) {
            throw new \Exception("Please select an option in 'Use in Profile Section'.");
        }

        if ($data['ques_type'] == 1 && !isset($data['answer_type'])) {
            throw new \Exception("Please select an answer type.");
        }

        if (isset($data['have_transition']) && !isset($data['trans_logic'])) {
            throw new \Exception("Please select transition logic.");
        }

        if (isset($data['have_transition']) && $data['trans_logic'] === "No" && empty($data['common_transition_id'])) {
            throw new \Exception("Please select a transition.");
        }
    }

    private function prepareQuizData(array &$data)
    {
        $data['ques_for'] = $data['ques_for'] ?? ($data['ques_type'] == 1 ? "cardio" : "none");
        $data['sales_page'] = isset($data['sales_page']) ? 1 : 0;
        $data['calory_calc'] = isset($data['calory_calc']) ? 1 : 0;
        $data['another_ques'] = isset($data['another_ques']) ? 1 : 0;
        $data['have_transition'] = isset($data['have_transition']) ? 1 : 0;
        $data['trans_logic'] = $data['trans_logic'] ?? null;

        // Set instruction details
        $data['haveInstruction'] = isset($data['haveInstruction']) ? 1 : 0;
        $data['instructionMessage'] = $data['haveInstruction'] ? $data['instructionMessage'] : '';
        $data['answer_format'] = $data['answer_format'] ?? null;
    }

    private function handleQuizImage(Request $request, array &$data)
    {
        if (isset($data['isQuesImage']) && $request->has('ques_image')) {
            $quizImage = time() . '.' . $data['ques_image']->extension();
            $data['ques_image']->storeAs("quiz/image", $quizImage, 'public');
            return "quiz/image/$quizImage";
        }
        return null;
    }

    private function createQuiz(array $data)
    {
        $slug = Str::slug($data['title']);
        return Quiz::create([
            'ques_title' => $data['title'],
            'quiz_group_id' => $data['quiz_group_id'],
            'slug' => $slug,
            'ques_description' => $data['quesDescription'] ?? '',
            'is_ques_image' => isset($data['isQuesImage']) ? 1 : 0,
            'ques_image' => $data['ques_image'],
            'ques_type' => $data['ques_type'],
            'ques_for' => $data['ques_for'],
            'is_sales_page' => $data['sales_page'],
            'is_calory_calc' => $data['calory_calc'],
            'is_another_ques' => $data['another_ques'],
            'ques_id' => $data['ques_id'] ?? null,
            'is_have_transition' => $data['have_transition'],
            'transition_logic' => $data['trans_logic'],
            'answer_type' => $data['answer_type'],
            'answer_format' => $data['answer_format'] ?? null,
            'have_instruction' => $data['haveInstruction'],
            'instruction_message' => $data['instructionMessage'],
            'ques_for_gender' => $data['ques_for_gender'] ?? "all",
        ]);
    }

    private function prepareAnswers(array $data, $quizId)
    {
        $answers = [];
        $transitionLogicNo = $data['trans_logic'] === "No";

        $answerFormat = $data['answer_format'] ?? null;

        // Handling single answers
        if ($data['answer_type'] == "single") {
            $this->handleSingleAnswers($data, $quizId, $answers, $transitionLogicNo);
        }

        // Handling multiple answers
        if ($data['answer_type'] == "multiple") {
            $this->handleMultipleAnswers($data, $quizId, $answers);
        }

        // Handling user input answers
        if (($data['ques_type'] == 0) && ($data['answer_type'] == "userInput")) {
            $this->handleUserInputAnswers($data, $answers, $quizId, $transitionLogicNo);
        }

        return $answers;
    }

    private function handleSingleAnswers(array $data, $quizId, array &$answers, $transitionLogicNo)
    {
        if (!$data['have_transition'] && $data['ques_type'] != 1) {
            foreach ($data['singleAnswers'] as $singleAnswer) {
                $answers[] = $this->buildAnswerArray($singleAnswer, $quizId, $data);
            }
        } else {
            if ($transitionLogicNo) {
                if (isset($data['singleAnswers']) && is_array($data['singleAnswers'])) {
                    foreach ($data['singleAnswers'] as $answerWithCommonTransition) {
                        $answers[] = $this->buildAnswerArray($answerWithCommonTransition, $quizId, $data, true);
                    }
                }
            } else {
                if (isset($data['singleAnswersWithTransition']) && is_array($data['singleAnswersWithTransition'])) {
                    foreach ($data['singleAnswersWithTransition'] as $singleAnswersWithTransition) {
                        $answers[] = $this->buildAnswerArray($singleAnswersWithTransition, $quizId, $data, true);
                    }
                }
            }
        }

        if ($data['ques_type'] == 1) {
            $this->handleCardioAndMuscleAnswers($data, $answers, $quizId);
        }
    }

    private function handleMultipleAnswers(array $data, $quizId, array &$answers)
    {
        if ($data['ques_type'] == 1) {
            $this->handleCardioAndMuscleAnswers($data, $answers, $quizId, true);
        } else {
            foreach ($data['multipleAnswers'] as $multipleAnswer) {
                $answers[] = $this->buildAnswerArray($multipleAnswer, $quizId, $data);
            }
        }
    }

    private function buildAnswerArray($answer, $quizId, array $data, $hasTransition = false)
    {
        if ($hasTransition && $data['trans_logic'] == 'Yes') {
            $transition_id = implode("|", $answer['transition_id']);
        } elseif (!$hasTransition && $data['trans_logic'] == 'No') {
            $transition_id = implode("|", $data['common_transition_id']) ?? [];
        } else {
            if (isset($data['common_transition_id']) && !empty($data['common_transition_id'])) {
                $transition_id = implode("|", $data['common_transition_id']);
            } else {
                $transition_id = null;
            }
        }

        $answerFormat = $data['answer_format'] ?? null;
        $answerImage = "";

        if (isset($answer['answer_img'])) {
            $answerImage = time() . '.' . md5(rand(1000, 10000)) . $answer['answer_img']->extension();
            $answer['answer_img']->storeAs("quiz/answer/image", $answerImage, 'public');
            $answerImage = "quiz/answer/image/$answerImage";
        }

        return [
            'question_id' => $quizId,
            'answer_type' => $data['answer_type'],
            'answer_format' => $answerFormat,
            'ques_answers' => $answer['answer'] ?? '',
            'answer_img' => $answerImage,
            'cardio_and_muscle_id' => 0,
            'have_transition' => $hasTransition ? 1 : 0,
            'transition_id' => $transition_id,
            'transition_logic' => $data['trans_logic'] ?? null,
            'ques_type' => $data['ques_type'] ?? null,
            'ans_points' => $answer['points'] ?? null,
        ];
    }

    // The handleCardioAndMuscleAnswers and handleUserInputAnswers methods remain unchanged.

    private function handleCardioAndMuscleAnswers(array $data, array &$answers, $quizId, $isMultiple = false)
    {
        $answerFormat = $data['answer_format'] ?? null;

        if ($data['ques_for'] === 'cardio') {
            foreach ($isMultiple ? $data['multipleAnsForCardio'] : $data['singleAnsForCardio'] as $answer) {
                $cardio = Cardio::find($answer['cardio_id']);
                if ($cardio) {
                    $answers[] = [
                        'question_id' => $quizId,
                        'answer_type' => $data['answer_type'],
                        'answer_format' => $answerFormat,
                        'ques_answers' => $cardio->title,
                        'cardio_and_muscle_id' => $answer['cardio_id'],
                        'have_transition' => 0,
                        'transition_id' => null,
                        'transition_logic' => null,
                        'ques_type' => $data['ques_type'] ?? null,
                        'ans_points' => 0,
                    ];
                }
            }
        }

        if ($data['ques_for'] === 'musclestrengthening') {
            foreach ($isMultiple ? $data['multipleAnsForMuscle'] : $data['singleAnsForMuscle'] as $answer) {
                $muscleStrength = MuscleStrength::find($answer['muscle_id']);
                if ($muscleStrength) {
                    $answers[] = [
                        'question_id' => $quizId,
                        'answer_type' => $data['answer_type'],
                        'answer_format' => $answerFormat,
                        'ques_answers' => $muscleStrength->title,
                        'cardio_and_muscle_id' => $answer['muscle_id'],
                        'have_transition' => 0,
                        'transition_id' => null,
                        'transition_logic' => null,
                        'ques_type' => $data['ques_type'] ?? null,
                        'ans_points' => 0,
                    ];
                }
            }
        }

        if ($data['ques_for'] === 'level') {
            foreach ($isMultiple ? $data['multipleAnsForLevel'] : $data['singleAnsForLevel'] as $answer) {
                $answers[] = [
                    'question_id' => $quizId,
                    'answer_type' => $data['answer_type'],
                    'answer_format' => $answerFormat,
                    'ques_answers' => $answer['level'] ?? '',
                    'cardio_and_muscle_id' => 0,
                    'have_transition' => 0,
                    'transition_id' => null,
                    'transition_logic' => null,
                    'ques_type' => $data['ques_type'] ?? null,
                    'ans_points' => $answer['points'] ?? null,
                ];
            }
        }
    }

    private function handleUserInputAnswers(array $data, array &$answers, $quizId, $transitionLogicNo)
    {
        // Ensure you access the answer correctly
        $inputAnswer = $data['userQues'] ?? ''; // Get the user input answer or default to an empty string

        if (!empty($inputAnswer)) {
            $answers[] = [
                'question_id' => $quizId,
                'answer_type' => $data['answer_type'], // This will be 'userInput'
                'answer_format' => $data['answer_format'] ?? null, // Default to null if not set
                'ques_answers' => $inputAnswer, // The actual user input answer
                'label' => $data['answer'], // The actual user input answer
                'have_transition' => $transitionLogicNo ? 1 : 0,
                'is_numeric' => $data['isNumericAnswer'],
                'transition_id' => $transitionLogicNo ? implode("|", $data['common_transition_id'] ?? []) : null,
                'transition_logic' => $data['trans_logic'] ?? null,
            ];
        }
    }

    // Update Start

    public function updateQuiz(Request $request, $slug, $id)
    {
        Session::put('page', "quiz");
        $transitions = Transition::all();
        $quizGroups = QuizGroup::all();
        $quizes = Quiz::all();
        $quiz = Quiz::findOrFail($id); // Find the existing quiz by ID
        $quizData = Quiz::with('answers')->where(['id' => $id, 'slug' => $slug])->firstOrFail()->toArray();

        if ($request->isMethod('post')) {
            $data = $request->all();
            // echo "<pre>";
            // print_r($data);
            // die;
            $validator = $this->validateQuizDataUpdate($data, $id); // Pass ID for any specific validation
            if ($validator->fails()) {
                return $this->handleValidationFailureUpdate($validator);
            }

            // Additional validations
            $this->additionalValidationsUpdate($data);

            // Prepare data
            $this->prepareQuizDataUpdate($data);
            $data['ques_image'] = $this->handleQuizImageUpdate($request, $data, $quiz); // Handle existing image if no new one is uploaded

            DB::transaction(function () use ($data, $quiz) {
                $quiz->update($this->prepareQuizUpdateDataUpdate($data)); // Update existing quiz
                $this->updateAnswers($data, $quiz->id); // Update answers
            });

            return redirect('/admin/quiz-index')->with('success', 'Question Updated Successfully !!!');
        }

        return view('admin.quiz.questions.edit', compact('transitions', 'quizGroups', 'quizes', 'quiz', 'quizData'));
    }

    private function validateQuizDataUpdate(array $data, $id = null)
    {
        $rules = [
            'title' => ['required', 'string'],
            'ques_type' => ['required', 'integer'],
            'quiz_group_id' => ['required'],
            'answer_type' => ['required'],
            'ques_image' => ['sometimes', 'required', 'mimes:jpeg,jpg,png,gif'],
            'ques_for_gender' => ['required', 'string'],
        ];

        $messages = [
            'title.required' => "Please enter Question Title.",
            'ques_type.required' => "Please select type of question.",
            'quiz_group_id.required' => "Please Select Quiz Group.",
            'answer_type.required' => "Please select answer type.",
            'ques_image.required' => "Please select an image for this question.",
            'ques_image.mimes' => "Please select an image with jpeg, jpg, png, or gif extensions.",
            'ques_for_gender.required' => "Please select one gender from the 'This Question is for' section."
        ];

        return Validator::make($data, $rules, $messages);
    }

    private function handleValidationFailureUpdate($validator)
    {
        return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
    }

    private function additionalValidationsUpdate(array $data)
    {
        if ($data['ques_type'] == 0 && empty($data['ques_for'])) {
            throw new \Exception("Please select an option in 'Use in Profile Section'.");
        }

        if ($data['ques_type'] == 1 && !isset($data['answer_type'])) {
            throw new \Exception("Please select an answer type.");
        }

        if (isset($data['have_transition']) && !isset($data['trans_logic'])) {
            throw new \Exception("Please select transition logic.");
        }

        if (isset($data['have_transition']) && $data['trans_logic'] === "No" && empty($data['common_transition_id'])) {
            throw new \Exception("Please select a transition.");
        }
    }

    private function prepareQuizDataUpdate(array &$data)
    {
        $data['ques_for'] = $data['ques_for'] ?? ($data['ques_type'] == 1 ? "cardio" : "none");
        $data['sales_page'] = isset($data['sales_page']) ? 1 : 0;
        $data['calory_calc'] = isset($data['calory_calc']) ? 1 : 0;
        $data['another_ques'] = isset($data['another_ques']) ? 1 : 0;
        $data['have_transition'] = isset($data['have_transition']) ? 1 : 0;
        $data['trans_logic'] = $data['trans_logic'] ?? null;

        // Set instruction details
        $data['haveInstruction'] = isset($data['haveInstruction']) ? 1 : 0;
        $data['instructionMessage'] = $data['haveInstruction'] ? $data['instructionMessage'] : '';
        $data['answer_format'] = $data['answer_format'] ?? null;
    }

    private function handleQuizImageUpdate(Request $request, array &$data, Quiz $quiz)
    {
        if (isset($data['isQuesImage']) && $request->has('ques_image')) {
            $quizImage = time() . '.' . $data['ques_image']->extension();
            $data['ques_image']->storeAs("quiz/image", $quizImage, 'public');
            return "quiz/image/$quizImage";
        }

        // If no new image is uploaded, keep the existing one
        return $quiz->ques_image; // Return existing image path
    }

    private function prepareQuizUpdateDataUpdate(array &$data)
    {
        return [
            'ques_title' => $data['title'],
            'quiz_group_id' => $data['quiz_group_id'],
            'ques_description' => $data['quesDescription'] ?? '',
            'is_ques_image' => isset($data['isQuesImage']) ? 1 : 0,
            'ques_image' => $data['ques_image'],
            'ques_type' => $data['ques_type'],
            'ques_for' => $data['ques_for'],
            'is_sales_page' => $data['sales_page'],
            'is_calory_calc' => $data['calory_calc'],
            'is_another_ques' => $data['another_ques'],
            'ques_id' => $data['ques_id'] ?? null,
            'is_have_transition' => $data['have_transition'],
            'transition_logic' => $data['trans_logic'],
            'answer_type' => $data['answer_type'],
            'answer_format' => $data['answer_format'] ?? null,
            'have_instruction' => $data['haveInstruction'],
            'instruction_message' => $data['instructionMessage'],
            'ques_for_gender' => $data['ques_for_gender'] ?? "all",
        ];
    }

    private function updateAnswers(array $data, $quizId)
    {
        // Logic to update answers based on the quizId.
        // Remove existing answers
        Answer::where('question_id', $quizId)->delete();

        // Prepare new answers and insert them
        $answers = $this->prepareAnswersUpdate($data, $quizId);
        Answer::insert($answers);
    }

    private function prepareAnswersUpdate(array $data, $quizId)
    {
        $answers = [];
        $transitionLogicNo = $data['trans_logic'] === "No";

        // Handling single answers
        if ($data['answer_type'] == "single") {
            $this->handleSingleAnswersUpdate($data, $quizId, $answers, $transitionLogicNo);
        }

        // Handling multiple answers
        if ($data['answer_type'] == "multiple") {
            $this->handleMultipleAnswersUpdate($data, $quizId, $answers);
        }

        // Handling user input answers
        if (($data['ques_type'] == 0) && ($data['answer_type'] == "userInput")) {
            $this->handleUserInputAnswersUpdate($data, $answers, $quizId, $transitionLogicNo);
        }

        return $answers;
    }

    private function handleSingleAnswersUpdate(array $data, $quizId, array &$answers, $transitionLogicNo)
    {
        if (!$data['have_transition'] && $data['ques_type'] != 1) {
            foreach ($data['singleAnswers'] as $singleAnswer) {
                $answers[] = $this->buildAnswerArrayUpdate($singleAnswer, $quizId, $data);
            }
        } else {
            if ($transitionLogicNo) {
                if (isset($data['singleAnswers']) && is_array($data['singleAnswers'])) {
                    foreach ($data['singleAnswers'] as $answerWithCommonTransition) {
                        $answers[] = $this->buildAnswerArrayUpdate($answerWithCommonTransition, $quizId, $data, true);
                    }
                }
            } else {
                if (isset($data['singleAnswersWithTransition']) && is_array($data['singleAnswersWithTransition'])) {
                    foreach ($data['singleAnswersWithTransition'] as $singleAnswersWithTransition) {
                        $answers[] = $this->buildAnswerArrayUpdate($singleAnswersWithTransition, $quizId, $data, true);
                    }
                }
            }
        }

        if ($data['ques_type'] == 1) {
            $this->handleCardioAndMuscleAnswersUpdate($data, $answers, $quizId);
        }
    }

    private function handleMultipleAnswersUpdate(array $data, $quizId, array &$answers)
    {
        if ($data['ques_type'] == 1) {
            $this->handleCardioAndMuscleAnswersUpdate($data, $answers, $quizId, true);
        } else {
            foreach ($data['multipleAnswers'] as $multipleAnswer) {
                $answers[] = $this->buildAnswerArrayUpdate($multipleAnswer, $quizId, $data);
            }
        }
    }

    private function buildAnswerArrayUpdate($answer, $quizId, array $data, $hasTransition = false)
    {
        $transition_id = null;
        if ($hasTransition && $data['trans_logic'] == 'Yes') {
            $transition_id = implode("|", $answer['transition_id']);
        } elseif (!$hasTransition && $data['trans_logic'] == 'No') {
            $transition_id = implode("|", $data['common_transition_id']) ?? [];
        } else {
            if (isset($data['common_transition_id']) && !empty($data['common_transition_id'])) {
                $transition_id = implode("|", $data['common_transition_id']);
            }
        }

        $answerImage = "";
        if (isset($answer['answer_img'])) {
            $answerImage = time() . '.' . md5(rand(1000, 10000)) . $answer['answer_img']->extension();
            $answer['answer_img']->storeAs("quiz/answer/image", $answerImage, 'public');
            $answerImage = "quiz/answer/image/$answerImage";
        }

        return [
            'question_id' => $quizId,
            'answer_type' => $data['answer_type'],
            'answer_format' => $data['answer_format'] ?? null,
            'ques_answers' => $answer['answer'] ?? '',
            'answer_img' => $answerImage,
            'cardio_and_muscle_id' => 0,
            'have_transition' => $hasTransition ? 1 : 0,
            'transition_id' => $transition_id,
            'transition_logic' => $data['trans_logic'] ?? null,
            'ques_type' => $data['ques_type'] ?? null,
            'ans_points' => $answer['points'] ?? null,
        ];
    }

    private function handleCardioAndMuscleAnswersUpdate(array $data, array &$answers, $quizId, $isMultiple = false)
    {
        // Logic remains unchanged
        $answerFormat = $data['answer_format'] ?? null;

        if ($data['ques_for'] === 'cardio') {
            foreach ($isMultiple ? $data['multipleAnsForCardio'] : $data['singleAnsForCardio'] as $answer) {
                $cardio = Cardio::find($answer['cardio_id']);
                if ($cardio) {
                    $answers[] = [
                        'question_id' => $quizId,
                        'answer_type' => $data['answer_type'],
                        'answer_format' => $answerFormat,
                        'ques_answers' => $cardio->title,
                        'cardio_and_muscle_id' => $answer['cardio_id'],
                        'have_transition' => 0,
                        'transition_id' => null,
                        'transition_logic' => null,
                        'ques_type' => $data['ques_type'] ?? null,
                        'ans_points' => 0,
                    ];
                }
            }
        }

        if ($data['ques_for'] === 'musclestrengthening') {
            foreach ($isMultiple ? $data['multipleAnsForMuscle'] : $data['singleAnsForMuscle'] as $answer) {
                $muscleStrength = MuscleStrength::find($answer['muscle_id']);
                if ($muscleStrength) {
                    $answers[] = [
                        'question_id' => $quizId,
                        'answer_type' => $data['answer_type'],
                        'answer_format' => $answerFormat,
                        'ques_answers' => $muscleStrength->title,
                        'cardio_and_muscle_id' => $answer['muscle_id'],
                        'have_transition' => 0,
                        'transition_id' => null,
                        'transition_logic' => null,
                        'ques_type' => $data['ques_type'] ?? null,
                        'ans_points' => 0,
                    ];
                }
            }
        }

        if ($data['ques_for'] === 'level') {
            foreach ($isMultiple ? $data['multipleAnsForLevel'] : $data['singleAnsForLevel'] as $answer) {
                $answers[] = [
                    'question_id' => $quizId,
                    'answer_type' => $data['answer_type'],
                    'answer_format' => $answerFormat,
                    'ques_answers' => $answer['level'] ?? '',
                    'cardio_and_muscle_id' => 0,
                    'have_transition' => 0,
                    'transition_id' => null,
                    'transition_logic' => null,
                    'ques_type' => $data['ques_type'] ?? null,
                    'ans_points' => $answer['points'] ?? null,
                ];
            }
        }
    }

    private function handleUserInputAnswersUpdate(array $data, array &$answers, $quizId, $transitionLogicNo)
    {
        // Ensure you access the answer correctly
        $inputAnswer = $data['userQues'] ?? ''; // Get the user input answer or default to an empty string

        if (!empty($inputAnswer)) {
            $answers[] = [
                'question_id' => $quizId,
                'answer_type' => $data['answer_type'], // This will be 'userInput'
                'answer_format' => $data['answer_format'] ?? null, // Default to null if not set
                'ques_answers' => $inputAnswer, // The actual user input answer
                'label' => $data['answer'], // The actual user input answer
                'have_transition' => $transitionLogicNo ? 1 : 0,
                'is_numeric' => $data['isNumericAnswer'],
                'transition_id' => $transitionLogicNo ? implode("|", $data['common_transition_id'] ?? []) : null,
                'transition_logic' => $data['trans_logic'] ?? null,
            ];
        }
    }

    // Update End



    // public function addQuiz(Request $request)
    // {
    //     Session::put('page', "quiz");
    //     $transitions = Transition::all();
    //     $quizGroups = QuizGroup::all();
    //     $quizes = Quiz::all();

    //     if ($request->isMethod('post')) {
    //         $data = $request->all();
    //         // echo "<pre>"; print_r($data); die;
    //         // Validation rules and messages
    //         $rules = [
    //             'title' => ['required', 'string'],
    //             'ques_type' => ['required', 'integer'],
    //             'quiz_group_id' => ['required'],
    //             'answer_type' => ['required'],
    //             'ques_image' => ['sometimes', 'required', 'mimes:jpeg,jpg,png,gif'],
    //             'ques_for_gender' => ['required', 'string']
    //         ];

    //         $messages = [
    //             'title.required' => "Please enter Question Title.",
    //             'ques_type.required' => "Please select type of question.",
    //             'quiz_group_id.required' => "Please Select Quiz Group.",
    //             'answer_type.required' => "Please select answer type.",
    //             'ques_image.required' => "Please select an image for this question.",
    //             'ques_image.mimes' => "Please select with jpeg,jpg,png,gif these mentioned extensions.",
    //             'ques_for_gender.required' => "Please select one gender from This Question is for section" 
    //         ];

    //         $validator = Validator::make($data, $rules, $messages);

    //         // Additional Validations
    //         if ($data['ques_type'] == 0 && empty($data['ques_for'])) {
    //             return redirect()->back()->with('error', "Please select an option in 'Use in Profile Section'.");
    //         }

    //         // if ($data['ques_type'] == 1 && (!isset($data['cardio_id']) || empty($data['cardio_id']))) {
    //         //     return redirect()->back()->with('error', "Please select an option in 'Use in Program Section'.");
    //         // }

    //         if ($data['ques_type'] == 1 && !isset($data['answer_type'])) {
    //             return redirect()->back()->with('error', "Please select an answer type.");
    //         }

    //         if (isset($data['have_transition']) && !isset($data['trans_logic'])) {
    //             return redirect()->back()->with('error', "Please select transition logic.");
    //         }

    //         if (isset($data['have_transition']) && $data['trans_logic'] === "No" && empty($data['common_transition_id'])) {
    //             return redirect()->back()->with('error', "Please select a transition.");
    //         }

    //         if ($data['ques_type'] == 1 && (!isset($data['ques_for']) || empty($data['ques_for']))) {
    //             $data['ques_for'] = "cardio";
    //         }
    //         // if (!isset($data['cardio_id']) || empty($data['cardio_id'])) {
    //         //     $data['cardio_id'] = 0;
    //         // }

    //         $data['sales_page'] = isset($data['sales_page']) ? 1 : 0;
    //         $data['calory_calc'] = isset($data['calory_calc']) ? 1 : 0;
    //         $data['another_ques'] = isset($data['another_ques']) ? 1 : 0;
    //         $data['have_transition'] = isset($data['have_transition']) ? 1 : 0;
    //         $data['trans_logic'] = $data['trans_logic'] ?? null;

    //         // Handle quiz image
    //         if (isset($data['isQuesImage']) && $request->has('ques_image')) {
    //             $quizImage = time() . '.' . $data['ques_image']->extension();
    //             $data['ques_image']->storeAs("quiz/image", $quizImage, 'public');
    //             $data['ques_image'] = "quiz/image/$quizImage";
    //             $data['isQuesImage'] = 1;
    //         } else {
    //             $data['isQuesImage'] = 0;
    //             $data['ques_image'] = null;
    //         }

    //         if ($data['ques_type'] == 2) {
    //             $data['ques_for'] = "none";
    //         }

    //         if (!isset($data['answer_format'])) {
    //             $data['answer_format'] = Null;
    //         }
    //         if (isset($data['haveInstruction'])){
    //             $data['haveInstruction'] = 1;
    //         }else{
    //             $data['haveInstruction'] = 0;
    //             $data['instructionMessage'] = '';
    //         } 

    //         DB::transaction(function () use ($data) {
    //             $slug = Str::slug($data['title']);

    //             // Insert main quiz details
    //             $quiz = new Quiz;
    //             $quiz->ques_title = $data['title'];
    //             $quiz->quiz_group_id = $data['quiz_group_id'];
    //             $quiz->slug = $slug;
    //             $quiz->ques_description = $data['quesDescription'] ?? '';
    //             $quiz->is_ques_image = $data['isQuesImage'];
    //             $quiz->ques_image = $data['ques_image'];
    //             $quiz->ques_type = $data['ques_type'];
    //             $quiz->ques_for = $data['ques_for'];
    //             // $quiz->cardio_id = $data['cardio_id'];
    //             $quiz->is_sales_page = $data['sales_page'];
    //             $quiz->is_calory_calc = $data['calory_calc'];
    //             $quiz->is_another_ques = $data['another_ques'];
    //             $quiz->ques_id = $data['ques_id'] ?? null;
    //             $quiz->is_have_transition = $data['have_transition'];
    //             $quiz->transition_logic = $data['trans_logic'];
    //             $quiz->answer_type = $data['answer_type'];
    //             $quiz->answer_format = $data['answer_format'];
    //             $quiz->have_instruction = $data['haveInstruction'];
    //             $quiz->instruction_message = $data['instructionMessage'];
    //             $quiz->ques_for_gender = isset($data['ques_for_gender']) ? $data['ques_for_gender'] : "all";
    //             $quiz->save();

    //             $answers = [];
    //             $transitionLogicNo = $data['trans_logic'] === "No";


    //             if ($data['answer_type'] == "single") {
    //                 if (!$data['have_transition'] && $data['ques_type'] != 1) {
    //                     foreach ($data['singleAnswers'] as $singleAnswer) {
    //                         if (isset($data['answer_format'])) {
    //                             $answerImage = time() . '.' . md5(rand(1000, 10000)) . $singleAnswer['answer_img']->extension();
    //                             $singleAnswer['answer_img']->storeAs("quiz/answer/image", $answerImage, 'public');
    //                             $singleAnswer['answer_img'] = "quiz/answer/image/$answerImage";
    //                         } else {
    //                             $singleAnswer['answer_img'] = "";
    //                         }
    //                         $answers[] = [
    //                             'question_id' => $quiz->id,
    //                             'answer_type' => $data['answer_type'],
    //                             'answer_format' => $data['answer_format'],
    //                             'ques_answers' => $singleAnswer['answer'],
    //                             'answer_img' => $singleAnswer['answer_img'],
    //                             'cardio_and_muscle_id' => 0,
    //                             'have_transition' => 0,
    //                             'transition_id' => null,
    //                             'transition_logic' => $data['trans_logic'],
    //                             'ques_type' => $data['ques_type'],
    //                             'ans_points' => $singleAnswer['points'] ?? null,
    //                         ];
    //                     }
    //                 } else {
    //                     if ($transitionLogicNo) {                            
    //                         foreach ($data['singleAnswers'] as $answerWithCommonTransition) {
    //                             if (isset($data['answer_format'])) {
    //                                 $answerImage = time() . '.' . md5(rand(1000, 10000)) .       $answerWithCommonTransition['answer_img']->extension();
    //                                 $answerWithCommonTransition['answer_img']->storeAs("quiz/answer/image", $answerImage, 'public');
    //                                 $answerWithCommonTransition['answer_img'] = "quiz/answer/image/$answerImage";
    //                             } else {
    //                                 $answerWithCommonTransition['answer_img'] = "";
    //                             }
    //                             $answers[] = [
    //                                 'question_id' => $quiz->id,
    //                                 'answer_type' => $data['answer_type'],
    //                                 'answer_format' => $data['answer_format'],
    //                                 'ques_answers' => $answerWithCommonTransition['answer'],
    //                                 'answer_img' => isset($answerWithCommonTransition['answer_img']),
    //                                 'cardio_and_muscle_id' => 0,
    //                                 'have_transition' => 1,
    //                                 'transition_id' => implode("|",$data['common_transition_id']),
    //                                 'transition_logic' => $data['trans_logic'],
    //                                 'ques_type' => $data['ques_type'],
    //                                 'ans_points' => $answerWithCommonTransition['points'] ?? null,
    //                             ];
    //                         }
    //                     }else{
    //                         foreach ($data['singleAnswersWithTransition'] as $singleAnswersWithTransition) {
    //                             if (isset($data['answer_format'])) {
    //                                 $answerImage = time() . '.' . md5(rand(1000, 10000)) . $singleAnswersWithTransition['answer_img']->extension();
    //                                 $singleAnswersWithTransition['answer_img']->storeAs("quiz/answer/image", $answerImage, 'public');
    //                                 $singleAnswersWithTransition['answer_img'] = "quiz/answer/image/$answerImage";
    //                             } else {
    //                                 $singleAnswersWithTransition['answer_img'] = "";
    //                             }
    //                             $answers[] = [
    //                                 'question_id' => $quiz->id,
    //                                 'answer_type' => $data['answer_type'],
    //                                 'answer_format' => $data['answer_format'],
    //                                 'ques_answers' => $singleAnswersWithTransition['answer'],
    //                                 'answer_img' => $singleAnswersWithTransition['answer_img'],
    //                                 'cardio_and_muscle_id' => 0,
    //                                 'have_transition' => 1,
    //                                 'transition_id' => implode("|",$singleAnswersWithTransition['transition_id']),
    //                                 'transition_logic' => $data['trans_logic'],
    //                                 'ques_type' => $data['ques_type'],
    //                                 'ans_points' => $singleAnswersWithTransition['points'] ?? null,
    //                             ];
    //                         }
    //                     }

    //                     if ($data['ques_type'] == 1) {
    //                         if ($data['ques_for'] === 'cardio') {
    //                             foreach ($data['singleAnsForCardio'] as $singleAnsForCardio) {
    //                                 $cardio = Cardio::where(['id' => $singleAnsForCardio['cardio_id']])->first();
    //                                 $answers[] = [
    //                                     'question_id' => $quiz->id,
    //                                     'answer_type' => $data['answer_type'],
    //                                     'answer_format' => $data['answer_format'],
    //                                     'ques_answers' => $cardio['title'],
    //                                     'cardio_and_muscle_id' => $singleAnsForCardio['cardio_id'],
    //                                     'have_transition' => 0,
    //                                     'transition_id' => null,
    //                                     'transition_logic' => null,
    //                                     'ques_type' => $data['ques_type'],
    //                                     'ans_points' => 0,
    //                                 ];
    //                             }
    //                         }
    //                         if ($data['ques_for'] === 'musclestrengthening') {
    //                             foreach ($data['singleAnsForMuscle'] as $singleAnsForMuscle) {
    //                                 $muscleStrength = MuscleStrength::where(['id' => $singleAnsForMuscle['muscle_id']])->first();
    //                                 $answers[] = [
    //                                     'question_id' => $quiz->id,
    //                                     'answer_type' => $data['answer_type'],
    //                                     'answer_format' => $data['answer_format'],
    //                                     'ques_answers' => $muscleStrength['title'],
    //                                     'cardio_and_muscle_id' => $singleAnsForMuscle['muscle_id'],
    //                                     'have_transition' => 0,
    //                                     'transition_id' => null,
    //                                     'transition_logic' => null,
    //                                     'ques_type' => $data['ques_type'],
    //                                     'ans_points' => 0,
    //                                 ];
    //                             }
    //                         }

    //                         if ($data['ques_for'] === 'level') {
    //                             foreach ($data['singleAnsForLevel'] as $singleAnsForLevel) {
    //                                 $answers[] = [
    //                                     'question_id' => $quiz->id,
    //                                     'answer_type' => $data['answer_type'],
    //                                     'answer_format' => $data['answer_format'],
    //                                     'ques_answers' => $singleAnsForLevel['level'],
    //                                     'cardio_and_muscle_id' => 0,
    //                                     'have_transition' => 0,
    //                                     'transition_id' => null,
    //                                     'transition_logic' => null,
    //                                     'ques_type' => $data['ques_type'],
    //                                     'ans_points' => $singleAnsForLevel['points'],
    //                                 ];
    //                             }
    //                         }
    //                     }
    //                 }
    //             }

    //             if ($data['answer_type'] == "multiple") {

    //                 if ($data['ques_type'] != 1) {
    //                     $have_transition = isset($data['have_transition']) && $transitionLogicNo && !empty($data['common_transition_id']);
    //                     $transition_id = $have_transition ? $data['common_transition_id'] : null;
    //                     foreach ($data['multipleAnswers'] as $multipleAnswer) {
    //                         if (isset($data['answer_format'])) {
    //                             $answerImage = time() . '.' . $multipleAnswer['answer_img']->extension();
    //                             $multipleAnswer['answer_img']->storeAs("quiz/answer/image", $answerImage, 'public');
    //                             $multipleAnswer['answer_img'] = "quiz/answer/image/$answerImage";
    //                         } else {
    //                             $multipleAnswer['answer_img'] = "";
    //                         }
    //                         if (!empty($multipleAnswer['answer'])) {
    //                             $answers[] = [
    //                                 'question_id' => $quiz->id,
    //                                 'answer_type' => $data['answer_type'],
    //                                 'answer_format' => $data['answer_format'],
    //                                 'answer_img' => $multipleAnswer['answer_img'],
    //                                 'ques_answers' => $multipleAnswer['answer'],
    //                                 'answer_img' => $multipleAnswer['answer_img'],
    //                                 'cardio_and_muscle_id' => 0,
    //                                 'have_transition' => $have_transition,
    //                                 'transition_id' => implode("|",$transition_id),
    //                                 'transition_logic' => $data['trans_logic'],
    //                                 'ques_type' => $data['ques_type'],
    //                                 'ans_points' => $multipleAnswer['points'] ?? null,
    //                             ];
    //                         }
    //                     }
    //                 }
    //                 if ($data['ques_type'] == 1) {
    //                     if ($data['ques_for'] === 'cardio') {
    //                         foreach ($data['multipleAnsForCardio'] as $multipleAnsForCardio) {
    //                             $cardio = Cardio::where(['id' => $multipleAnsForCardio['cardio_id']])->first();
    //                             $answers[] = [
    //                                 'question_id' => $quiz->id,
    //                                 'answer_type' => $data['answer_type'],
    //                                 'answer_format' => $data['answer_format'],
    //                                 'ques_answers' => $cardio['title'],
    //                                 'cardio_and_muscle_id' => $multipleAnsForCardio['cardio_id'],
    //                                 'have_transition' => 0,
    //                                 'transition_id' => null,
    //                                 'transition_logic' => null,
    //                                 'ques_type' => $data['ques_type'],
    //                                 'ans_points' => 0,
    //                             ];
    //                         }
    //                     }
    //                     if ($data['ques_for'] === 'musclestrengthening') {
    //                         foreach ($data['multipleAnsForMuscle'] as $multipleAnsForMuscle) {
    //                             $muscleStrength = MuscleStrength::where(['id' => $multipleAnsForMuscle['muscle_id']])->first();
    //                             $answers[] = [
    //                                 'question_id' => $quiz->id,
    //                                 'answer_type' => $data['answer_type'],
    //                                 'answer_format' => $data['answer_format'],
    //                                 'ques_answers' => $muscleStrength['title'],
    //                                 'cardio_and_muscle_id' => $multipleAnsForMuscle['muscle_id'],
    //                                 'have_transition' => 0,
    //                                 'transition_id' => null,
    //                                 'transition_logic' => null,
    //                                 'ques_type' => $data['ques_type'],
    //                                 'ans_points' => 0,
    //                             ];
    //                         }
    //                     }

    //                     if ($data['ques_for'] === 'level') {
    //                         foreach ($data['multipleAnsForLevel'] as $multipleAnsForLevel) {
    //                             $answers[] = [
    //                                 'question_id' => $quiz->id,
    //                                 'answer_type' => $data['answer_type'],
    //                                 'answer_format' => $data['answer_format'],
    //                                 'ques_answers' => $multipleAnsForLevel['level'],
    //                                 'cardio_and_muscle_id' => 0,
    //                                 'have_transition' => 0,
    //                                 'transition_id' => null,
    //                                 'transition_logic' => null,
    //                                 'ques_type' => $data['ques_type'],
    //                                 'ans_points' => $multipleAnsForLevel['points'],
    //                             ];
    //                         }
    //                     }
    //                 }
    //             }

    //             if (($data['ques_type'] == 0) && ($data['answer_type'] == "userInput")) {
    //                 $rules = [
    //                     'userQues' => ['required', 'string'],
    //                     'answer' => ['required', 'string'],
    //                 ];

    //                 $messages = [
    //                     'userQues.required' => "Please select an option from dropdown.",
    //                     'answer.required' => "Please enter label.",
    //                 ];

    //                 $validator = Validator::make($data, $rules, $messages);
    //                 if ($validator->fails()) {
    //                     return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
    //                 }

    //                 if (!isset($data['isNumericAnswer'])) {
    //                     $data['isNumericAnswer'] = "no";
    //                 }

    //                 if ($transitionLogicNo) {
    //                     $answers = [
    //                         'question_id' => $quiz->id,
    //                         'answer_type' => $data['answer_type'],
    //                         'answer_format' => $data['answer_format'],
    //                         'ques_answers' => $data['userQues'],
    //                         'label' => $data['answer'],
    //                         'have_transition' => 1,
    //                         'transition_id' => implode("|",$data['common_transition_id']),
    //                         'transition_logic' => $data['trans_logic'],
    //                         'ques_type' => $data['ques_type'],
    //                         'is_numeric' => $data['isNumericAnswer'],
    //                         'ans_points' => 0,
    //                     ];
    //                 } else {
    //                     $answers = [
    //                         'question_id' => $quiz->id,
    //                         'answer_type' => $data['answer_type'],
    //                         'answer_format' => $data['answer_format'],
    //                         'ques_answers' => $data['userQues'],
    //                         'label' => $data['answer'],
    //                         'have_transition' => 0,
    //                         'transition_id' => null,
    //                         'transition_logic' => null,
    //                         'ques_type' => $data['ques_type'],
    //                         'is_numeric' => $data['isNumericAnswer'],
    //                         'ans_points' => 0,
    //                     ];
    //                 }
    //             }

    //             // Batch insert answers
    //             Answer::insert($answers);
    //         });

    //         return redirect('/admin/quiz-index')->with('success', 'Quiz Inserted Successfully !!!');
    //     }

    //     return view('admin.quiz.questions.create', compact('transitions', 'quizGroups', 'quizes'));
    // }

    // public function updateQuiz(Request $request, $slug, $id)
    // {
    //     Session::put('page', "quiz");

    //     // Fetch quiz data with answers
    //     $quizData = Quiz::with('answers')->where(['id' => $id, 'slug' => $slug])->firstOrFail()->toArray();

    //     // Get all transitions and quiz groups
    //     $transitions = Transition::get()->toArray();
    //     $quizGroups = QuizGroup::get()->toArray();
    //     $quizes = Quiz::get()->toArray();

    //     if ($request->isMethod('post')) {
    //         $data = $request->all();
    //         // echo "<pre>"; print_r($data); die;
    //         // Validate request data
    //         $validator = Validator::make($data, [
    //             'title' => ['required', 'string'],
    //             'ques_type' => ['required', 'integer'],
    //             'quiz_group_id' => ['required'],
    //         ], [
    //             'title.required' => "Please enter Question Title.",
    //             'ques_type.required' => "Please select type of question.",
    //             'quiz_group_id.required' => "Please Select Quiz Group.",
    //         ]);

    //         if ($validator->fails()) {
    //             return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
    //         }

    //         // Additional validations
    //         if (($data['ques_type'] == 0 || $data['ques_type'] == 1) && empty($data['ques_for'])) {
    //             return redirect()->back()->with('error', "Please select one of the options in 'Use in Profile' or 'Use in Program' section.");
    //         }

    //         if ($data['ques_type'] == 1 && !isset($data['answer_type'])) {
    //             return redirect()->back()->with('error', "Please select an answer type.");
    //         }

    //         if (isset($data['have_transition']) && !isset($data['trans_logic'])) {
    //             return redirect()->back()->with('error', "Please select transition logic with either Yes or No.");
    //         }

    //         if (isset($data['have_transition']) && $data['trans_logic'] === "No" && empty($data['common_transition_id'])) {
    //             return redirect()->back()->with('error', "Please select Transition in Answer Section.");
    //         }

    //         if ($data['ques_type'] == 1 && (!isset($data['ques_for']) || empty($data['ques_for']))) {
    //             $data['ques_for'] = "cardio";
    //         }
    //         if ($data['ques_type'] == 2) {
    //             $data['ques_for'] = "none";
    //         }
    //         if (!isset($data['cardio_id']) || empty($data['cardio_id'])) {
    //             $data['cardio_id'] = 0;
    //         }

    //         // Assign default values
    //         $data['sales_page'] = isset($data['sales_page']) ? 1 : 0;
    //         $data['calory_calc'] = isset($data['calory_calc']) ? 1 : 0;
    //         $data['another_ques'] = isset($data['another_ques']) ? 1 : 0;
    //         if (!isset($data['have_transition'])) {
    //             $data['trans_logic'] = null;
    //         } else {
    //             $data['trans_logic'] = "No";
    //         }
    //         $data['have_transition'] = isset($data['have_transition']) ? 1 : 0;

    //         // Generate slug and handle image upload
    //         $slug = Str::slug($data['title']);

    //         if (isset($data['isQuesImage']) || !empty($data['isQuesImage'])) {
    //             $data['isQuesImage'] = $quizData['is_ques_image'];
    //             if (!isset($data['ques_image']) || empty($data['ques_image'])) {
    //                 $data['ques_image'] = $quizData['ques_image'];
    //             } else {
    //                 if (!Storage::disk('public')->exists("/quiz/image")) {
    //                     Storage::disk('public')->makeDirectory("/quiz/image"); //creates directory
    //                 }
    //                 if (empty($quizData['ques_image'])) {
    //                     $quizImage = time() . '.' . $data['ques_image']->extension();
    //                 } else {
    //                     Storage::delete($quizData['ques_image']);
    //                     $quizImage = time() . '.' . $data['ques_image']->extension();
    //                 }
    //                 $request->ques_image->storeAs("quiz/image", $quizImage, 'public');
    //                 $data['ques_image'] = "quiz/image/$quizImage";
    //                 $data['isQuesImage'] = 1;
    //             }
    //         } else {
    //             $data['isQuesImage'] = 0;
    //             if ($quizData['ques_image'] != null) {
    //                 Storage::delete($quizData['ques_image']);
    //             }
    //             $data['ques_image'] = Null;
    //         }

    //         $quiz = Quiz::findOrFail($id);

    //         $quiz->ques_title = $data['title'];
    //         $quiz->quiz_group_id = $data['quiz_group_id'];
    //         $quiz->slug = $slug;
    //         $quiz->ques_description = $data['quesDescription'] ?? '';
    //         $quiz->is_ques_image = $data['isQuesImage'];
    //         $quiz->ques_image = $data['ques_image'];
    //         $quiz->ques_type = $data['ques_type'];
    //         $quiz->ques_for = $data['ques_for'];
    //         $quiz->cardio_id = $data['cardio_id'];
    //         $quiz->is_sales_page = $data['sales_page'];
    //         $quiz->is_calory_calc = $data['calory_calc'];
    //         $quiz->is_another_ques = $data['another_ques'];
    //         $quiz->ques_id = $data['ques_id'];
    //         $quiz->is_have_transition = $data['have_transition'];
    //         $quiz->transition_logic = $data['trans_logic'];
    //         $quiz->answer_type = $data['answer_type'];
    //         $quiz->save();

    //         Answer::where(['question_id' => $id])->delete();
    //         // Insert answers
    //         $answers = [];
    //         $transitionLogicNo = $data['trans_logic'] === "No";

    //         if ($data['answer_type'] == "single") {
    //             if (!$data['have_transition'] && $data['ques_type'] != 1) {
    //                 foreach ($data['singleAnswers'] as $singleAnswer) {
    //                     $answers[] = [
    //                         'question_id' => $quiz->id,
    //                         'answer_type' => $data['answer_type'],
    //                         'ques_answers' => $singleAnswer['answer'],
    //                         'cardio_and_muscle_id' => 0,
    //                         'have_transition' => 0,
    //                         'transition_id' => null,
    //                         'transition_logic' => $data['trans_logic'],
    //                         'ques_type' => $data['ques_type'],
    //                         'ans_points' => $singleAnswer['points'] ?? null,
    //                     ];
    //                 }
    //             } else {
    //                 if ($transitionLogicNo) {
    //                     foreach ($data['singleAnswers'] as $answerWithCommonTransition) {
    //                         $answers[] = [
    //                             'question_id' => $quiz->id,
    //                             'answer_type' => $data['answer_type'],
    //                             'ques_answers' => $answerWithCommonTransition['answer'],
    //                             'cardio_and_muscle_id' => 0,
    //                             'have_transition' => 1,
    //                             'transition_id' => $data['common_transition_id'],
    //                             'transition_logic' => $data['trans_logic'],
    //                             'ques_type' => $data['ques_type'],
    //                             'ans_points' => $answerWithCommonTransition['points'] ?? null,
    //                         ];
    //                     }
    //                 }

    //                 if ($data['ques_type'] == 1) {
    //                     if ($data['ques_for'] === 'cardio') {
    //                         foreach ($data['singleAnsForCardio'] as $singleAnsForCardio) {
    //                             $cardio = Cardio::where(['id' => $singleAnsForCardio['cardio_id']])->first();
    //                             $answers[] = [
    //                                 'question_id' => $quiz->id,
    //                                 'answer_type' => $data['answer_type'],
    //                                 'ques_answers' => $cardio['title'],
    //                                 'cardio_and_muscle_id' => $singleAnsForCardio['cardio_id'],
    //                                 'have_transition' => 0,
    //                                 'transition_id' => null,
    //                                 'transition_logic' => null,
    //                                 'ques_type' => $data['ques_type'],
    //                                 'ans_points' => 0,
    //                             ];
    //                         }
    //                     }
    //                     if ($data['ques_for'] === 'musclestrengthening') {
    //                         foreach ($data['singleAnsForMuscle'] as $singleAnsForMuscle) {
    //                             $muscleStrength = MuscleStrength::where(['id' => $singleAnsForMuscle['muscle_id']])->first();
    //                             $answers[] = [
    //                                 'question_id' => $quiz->id,
    //                                 'answer_type' => $data['answer_type'],
    //                                 'ques_answers' => $muscleStrength['title'],
    //                                 'cardio_and_muscle_id' => $singleAnsForMuscle['muscle_id'],
    //                                 'have_transition' => 0,
    //                                 'transition_id' => null,
    //                                 'transition_logic' => null,
    //                                 'ques_type' => $data['ques_type'],
    //                                 'ans_points' => 0,
    //                             ];
    //                         }
    //                     }

    //                     if ($data['ques_for'] === 'level') {
    //                         foreach ($data['singleAnsForLevel'] as $singleAnsForLevel) {
    //                             $answers[] = [
    //                                 'question_id' => $quiz->id,
    //                                 'answer_type' => $data['answer_type'],
    //                                 'ques_answers' => $singleAnsForLevel['level'],
    //                                 'cardio_and_muscle_id' => 0,
    //                                 'have_transition' => 0,
    //                                 'transition_id' => null,
    //                                 'transition_logic' => null,
    //                                 'ques_type' => $data['ques_type'],
    //                                 'ans_points' => $singleAnsForLevel['points'],
    //                             ];
    //                         }
    //                     }
    //                 } else {
    //                     if (!$data['singleAnswers']) {
    //                         foreach ($data['singleAnswersWithTransition'] as $answerWithIndividualTransition) {
    //                             $answers[] = [
    //                                 'question_id' => $quiz->id,
    //                                 'answer_type' => $data['answer_type'],
    //                                 'ques_answers' => $answerWithIndividualTransition['answer'],
    //                                 'cardio_and_muscle_id' => 0,
    //                                 'have_transition' => 1,
    //                                 'transition_id' => $answerWithIndividualTransition['transition_id'],
    //                                 'transition_logic' => $data['trans_logic'],
    //                                 'ques_type' => $data['ques_type'],
    //                                 'ans_points' => $answerWithIndividualTransition['points'] ?? null,
    //                             ];
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //         if ($data['answer_type'] == "multiple") {
    //             if ($data['ques_type'] != 1) {
    //                 $have_transition = isset($data['have_transition']) && $transitionLogicNo && !empty($data['common_transition_id']);
    //                 $transition_id = $have_transition ? $data['common_transition_id'] : null;
    //                 foreach ($data['multipleAnswers'] as $multipleAnswer) {
    //                     if (!empty($multipleAnswer['answer'])) {
    //                         $answers[] = [
    //                             'question_id' => $quiz->id,
    //                             'answer_type' => $data['answer_type'],
    //                             'ques_answers' => $multipleAnswer['answer'],
    //                             'cardio_and_muscle_id' => 0,
    //                             'have_transition' => $have_transition,
    //                             'transition_id' => $transition_id,
    //                             'transition_logic' => $data['trans_logic'],
    //                             'ques_type' => $data['ques_type'],
    //                             'ans_points' => $multipleAnswer['points'] ?? null,
    //                         ];
    //                     }
    //                 }
    //             }
    //             if ($data['ques_type'] == 1) {
    //                 if ($data['ques_for'] === 'cardio') {
    //                     foreach ($data['multipleAnsForCardio'] as $multipleAnsForCardio) {
    //                         $cardio = Cardio::where(['id' => $multipleAnsForCardio['cardio_id']])->first();
    //                         $answers[] = [
    //                             'question_id' => $quiz->id,
    //                             'answer_type' => $data['answer_type'],
    //                             'ques_answers' => $cardio['title'],
    //                             'cardio_and_muscle_id' => $multipleAnsForCardio['cardio_id'],
    //                             'have_transition' => 0,
    //                             'transition_id' => null,
    //                             'transition_logic' => null,
    //                             'ques_type' => $data['ques_type'],
    //                             'ans_points' => 0,
    //                         ];
    //                     }
    //                 }
    //                 if ($data['ques_for'] === 'musclestrengthening') {
    //                     foreach ($data['multipleAnsForMuscle'] as $multipleAnsForMuscle) {
    //                         $muscleStrength = MuscleStrength::where(['id' => $multipleAnsForMuscle['muscle_id']])->first();
    //                         $answers[] = [
    //                             'question_id' => $quiz->id,
    //                             'answer_type' => $data['answer_type'],
    //                             'ques_answers' => $muscleStrength['title'],
    //                             'cardio_and_muscle_id' => $multipleAnsForMuscle['muscle_id'],
    //                             'have_transition' => 0,
    //                             'transition_id' => null,
    //                             'transition_logic' => null,
    //                             'ques_type' => $data['ques_type'],
    //                             'ans_points' => 0,
    //                         ];
    //                     }
    //                 }

    //                 if ($data['ques_for'] === 'level') {
    //                     foreach ($data['multipleAnsForLevel'] as $multipleAnsForLevel) {
    //                         $answers[] = [
    //                             'question_id' => $quiz->id,
    //                             'answer_type' => $data['answer_type'],
    //                             'ques_answers' => $multipleAnsForLevel['level'],
    //                             'cardio_and_muscle_id' => 0,
    //                             'have_transition' => 0,
    //                             'transition_id' => null,
    //                             'transition_logic' => null,
    //                             'ques_type' => $data['ques_type'],
    //                             'ans_points' => $multipleAnsForLevel['points'],
    //                         ];
    //                     }
    //                 }
    //             }
    //         }

    //         if (($data['ques_type'] == 0) && ($data['answer_type'] == "userInput")) {
    //             $rules = [
    //                 'userQues' => ['required', 'string'],
    //                 'answer' => ['required', 'string'],
    //             ];

    //             $messages = [
    //                 'userQues.required' => "Please select an option from dropdown.",
    //                 'answer.required' => "Please enter label.",
    //             ];

    //             $validator = Validator::make($data, $rules, $messages);
    //             if ($validator->fails()) {
    //                 return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
    //             }

    //             if (!isset($data['isNumericAnswer'])) {
    //                 $data['isNumericAnswer'] = "no";
    //             }

    //             if ($transitionLogicNo) {
    //                 $answers = [
    //                     'question_id' => $quiz->id,
    //                     'answer_type' => $data['answer_type'],
    //                     'ques_answers' => $data['userQues'],
    //                     'label' => $data['answer'],
    //                     'have_transition' => 1,
    //                     'transition_id' => $data['common_transition_id'],
    //                     'transition_logic' => $data['trans_logic'],
    //                     'ques_type' => $data['ques_type'],
    //                     'is_numeric' => $data['isNumericAnswer'],
    //                     'ans_points' => 0,
    //                 ];
    //             } else {
    //                 $answers = [
    //                     'question_id' => $quiz->id,
    //                     'answer_type' => $data['answer_type'],
    //                     'ques_answers' => $data['userQues'],
    //                     'label' => $data['answer'],
    //                     'have_transition' => 0,
    //                     'transition_id' => null,
    //                     'transition_logic' => null,
    //                     'ques_type' => $data['ques_type'],
    //                     'is_numeric' => $data['isNumericAnswer'],
    //                     'ans_points' => 0,
    //                 ];
    //             }
    //         }
    //         // Batch insert answers
    //         Answer::insert($answers);
    //         return redirect('admin/quiz-index')->with('success', "Question Updated Successfully");
    //     }
    //     return view('admin.quiz.questions.edit', ['quizData' => $quizData, 'transitions' => $transitions, 'quizGroups' => $quizGroups, 'quizes' => $quizes]);
    // }

    public function getGroupWiseQuiz(Request $request)
    {
        $data = $request->all();
        if ($data['quiz_group_id'] === 'All') {
            $quizzes = Quiz::orderBy('quiz_position', 'asc')->with('quiz_group')->get();
        } else {
            $quizzes = Quiz::orderBy('quiz_position', 'asc')->with('quiz_group')->where(['quiz_group_id' => $data['quiz_group_id']])->get();
        }
        return response()->json($quizzes);
    }

    public function destroyQuiz($slug, $id)
    {
        Session::put('page', 'quiz');
        Quiz::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back();
    }

    public function quizGroupIndex()
    {
        Session::put('page', 'quizGroup');
        $quizGroups = QuizGroup::orderBy('quiz_group_order', 'asc')->get()->toArray();

        $getQuizGroup = QuizGroup::whereNotNull('quiz_group_order')->get('quiz_group_order');
        $quizGroupOrder = [];
        foreach ($getQuizGroup as $order) {
            $quizGroupOrder[] = $order['quiz_group_order'];
        }
        return view('admin.quiz.quiz_group.index', ['quizGroups' => $quizGroups, 'quizGroupOrder' => $quizGroupOrder]);
    }

    public function addQuizGroup(Request $request)
    {
        Session::put('page', 'quizGroup');
        $getQuizGroup = QuizGroup::whereNotNull('quiz_group_order')->get('quiz_group_order');
        $quizGroupOrder = $getQuizGroup->pluck('quiz_group_order')->toArray();

        // Check if the table is empty
        if ($getQuizGroup->isEmpty()) {
            $getLastQuizGroupOrder = null;
        } else {
            $getLastQuizGroupOrder = $getQuizGroup->last()->toArray();
        }

        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [
                'title' => ['required', 'string'],
                'color' => ['required', 'string'],
                'quiz_group_order' => ['required', 'numeric']
            ];

            $message = [
                'title.required' => "Please enter title.",
                'quiz_group_order.required' => "Please enter quiz group order.",
                'quiz_group_order.numeric' => "Quiz group order should be a numeric value.",
            ];

            $validator = Validator::make($request->all(), $rules, $message);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            $slug = Str::slug($data['title']);
            $existingQuizGroupOrder = QuizGroup::where(['quiz_group_order' => $data['quiz_group_order']])->first();

            // If there are no quiz groups, start from order 1; otherwise, increment the last order
            $updatedQuizGroupOrder = $getLastQuizGroupOrder ? $getLastQuizGroupOrder['quiz_group_order'] + 1 : 1;

            if (!empty($existingQuizGroupOrder)) {
                QuizGroup::where(['id' => $existingQuizGroupOrder['id'], 'quiz_group_order' => $data['quiz_group_order']])
                    ->update(['quiz_group_order' => $updatedQuizGroupOrder]);
            }

            $quizGroup = new QuizGroup;
            $quizGroup->title = $data['title'];
            $quizGroup->slug = $slug;
            $quizGroup->quiz_group_order = $data['quiz_group_order'];
            $quizGroup->color = $data['color'];
            $quizGroup->save();

            return redirect('admin/quiz-group-index')->with('success', "Quiz Group Inserted successfully");
        }

        return view('admin.quiz.quiz_group.create', ['quizGroupOrder' => $quizGroupOrder, 'getLastQuizGroupOrder' => $getLastQuizGroupOrder]);
    }

    public function updateQuizGroup(Request $request, $slug, $id)
    {
        Session::put('page', 'quizGroup');
        $quizGroupData = QuizGroup::where(['slug' => $slug, 'id' => $id])->first()->toArray();
        $getQuizGroup = QuizGroup::where('quiz_group_order', '!=', $quizGroupData['quiz_group_order'])->get('quiz_group_order');
        $quizGroupOrder = [];
        foreach ($getQuizGroup as $order) {
            $quizGroupOrder[] = $order['quiz_group_order'];
        }
        // echo "<pre>"; print_r($quizGroupOrder); die;
        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [
                'color' => ['required', 'string'],
                'title' => ['required', 'string']
            ];

            $message = [
                'title.required' => "Please enter title."
            ];

            $validator = Validator::make($request->all(), $rules, $message);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            $slug = Str::slug($data['title']);

            $existingQuizGroupOrder = QuizGroup::where(['quiz_group_order' => $data['quiz_group_order']])->first();
            if (!empty($existingQuizGroupOrder['quiz_group_order'])) {
                QuizGroup::where(['id' => $existingQuizGroupOrder['id'], 'quiz_group_order' => $data['quiz_group_order']])->update(['quiz_group_order' => $quizGroupData['quiz_group_order']]);
                // Update in Current Quiz Group
                QuizGroup::where(['id' => $id])->update(['title' => $data['title'], 'slug' => $slug, 'quiz_group_order' => $existingQuizGroupOrder['quiz_group_order'], 'color' => $data['color']]);
            } else {
                QuizGroup::where(['id' => $id])->update(['title' => $data['title'], 'slug' => $slug, 'quiz_group_order' => $data['quiz_group_order'], 'color' => $data['color']]);
            }

            return redirect('admin/quiz-group-index')->with('success', "Quiz Group Updated successfully");
        }
        return view('admin.quiz.quiz_group.edit', ['quizGroupData' => $quizGroupData, 'quizGroupOrder' => $quizGroupOrder]);
    }

    public function destroyQuizGroup($slug, $id)
    {
        Session::put('page', 'quizGroup');
        QuizGroup::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back();
    }

    public function setQuizOrderPosition(Request $request)
    {
        $positions = QuizGroup::all();
        foreach ($positions as $position) {
            foreach ($request->order as $order) {
                if ($order['id'] == $position->id) {
                    $position->update(['quiz_group_order' => $order['position']]);
                }
            }
        }

        return response('Update Successfully.', 200);
    }
}
