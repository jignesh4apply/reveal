<?php

namespace App\Http\Controllers\Admin\Program;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ExerciseController extends Controller
{
    public function exerciseIndex()
    {
        Session::put('page', 'exercises');

        $exerciseData = Exercise::get()->toArray();
        return view('admin.programs.exercise.index', ['exerciseData' => $exerciseData]);
    }

    public function createExercise(Request $request)
    {
        Session::put('page', 'exercises');

        if ($request->isMethod('post')) {
            $data = $request->all();
            $data['is_new_exercise_flow'] = !empty($data['is_new_exercise_flow']) ? 1 : 0;
            $useLegacyFields = $data['is_new_exercise_flow'] == 0;

            $validationRules = [
                'title' => ['required', 'string', 'max:255'],
                'title_for_frontend' => ['required', 'string', 'max:255'],
                'instructions' => ['required'],
                'body_zone' => ['required'],
                'exercise_type' => ['required'],
                'video' => ['required', 'string'],
            ];

            if ($useLegacyFields) {
                $validationRules = array_merge($validationRules, [
                    'exercise_form' => ['required', 'in:duration,repetition'],
                    'rest_period' => ['required'],
                    'duration' => [
                        'required_if:exercise_form,duration',
                        function ($attribute, $value, $fail) {
                            if ($value === 0) {
                                $fail('The ' . $attribute . ' field must not be zero.');
                            }
                        }
                    ],
                    'no_of_repetition' => [
                        'required_if:exercise_form,repetition',
                        function ($attribute, $value, $fail) {
                            if ($value === 0) {
                                $fail('The ' . $attribute . ' field must not be zero.');
                            }
                        }
                    ],
                    'range_of_repetition' => [
                        'required_if:exercise_form,repetition',
                        function ($attribute, $value, $fail) {
                            if ($value === 0) {
                                $fail('The ' . $attribute . ' field must not be zero.');
                            }
                        }
                    ],
                ]);
            }

            $validator = Validator::make($data, $validationRules);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            // Handle file uploads (GIF)
            $data['gif'] = $this->handleFileUpload($request, 'gif');

            if ($data['is_new_exercise_flow']) {
                $data['exercise_form'] = null;
                $data['duration'] = null;
                $data['rest_period'] = null;
                $data['no_of_repetition'] = null;
                $data['range_of_repetition'] = null;
            } else {
                if ($data['exercise_form'] == "duration") {
                    $data['no_of_repetition'] = 0;
                    $data['range_of_repetition'] = 0;
                } else {
                    $data['duration'] = null;
                }
            }

            // Set status based on checkbox
            $status = !empty($data['status']) ? 1 : 0;

            // Generate slug
            $slug = Str::slug($data['title']);

            // Save exercise to database
            $exercise = new Exercise;
            $exercise->fill($data);
            $exercise->slug = $slug;
            $exercise->status = $status;
            $exercise->save();

            return redirect('admin/exercise-index')->with('success', "Exercise added successfully.");
        }

        return view('admin.programs.exercise.create');
    }

    /**
     * Handle file upload for gif files.
     *
     * @param Request $request
     * @param string $fileField
     * @return string|null
     */
    private function handleFileUpload(Request $request, string $fileField)
    {
        if ($request->hasFile($fileField)) {
            $file = $request->file($fileField);
            $fileName = time() . '.' . $file->extension();
            $filePath = "session/$fileField";

            if (!Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->makeDirectory($filePath);
            }

            $file->storeAs($filePath, $fileName, 'public');

            return "session/$fileField/$fileName";
        }

        return null;
    }

    public function updateExercise(Request $request, $slug, $id)
    {
        Session::put('page', 'exercises');
        $exercise = Exercise::where(['slug' => $slug, 'id' => $id])->first();

        if ($request->isMethod('post')) {
            $data = $request->all();
            $data['is_new_exercise_flow'] = !empty($data['is_new_exercise_flow']) ? 1 : 0;
            $useLegacyFields = $data['is_new_exercise_flow'] == 0;

            // echo "<pre>"; print_r($data); die;
            // Validation rules
            $validation = [
                'title' => ['required', 'string', 'max:255'],
                'title_for_frontend' => ['required', 'string', 'max:255'],
                'instructions' => ['required'],
                'video' => ['required', 'string'],
                'body_zone' => ['required'],
                'exercise_type' => ['required'],
            ];

            if ($useLegacyFields) {
                $validation = array_merge($validation, [
                    'exercise_form' => ['required', 'in:duration,repetition'],
                    'rest_period' => ['required'],
                ]);
            }

            $validator = Validator::make($data, $validation);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if ($useLegacyFields && $data['exercise_form'] == "duration") {
                $rules = [
                    'duration' => [
                        'required',
                        function ($attribute, $value, $fail) {
                            if ($value == 0) {
                                $fail('The ' . $attribute . ' field must not be zero.');
                            }
                        }
                    ],
                ];

                $message = [
                    'duration.required' => "Please enter duration."
                ];

                $validator = Validator::make($data, $rules, $message);
                if ($validator->fails()) {
                    return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
                }
            }

            if ($useLegacyFields && $data['exercise_form'] == "repetition") {
                $rules = [
                    'no_of_repetition' => [
                        'required',
                        function ($value, $fail) {
                            if ($value == 0) {
                                $fail('The number of repetition field must not be zero.');
                            }
                        }
                    ],
                    'range_of_repetition' => [
                        'required',
                        function ($value, $fail) {
                            if ($value == 0) {
                                $fail('The range of repetition field must not be zero.');
                            }
                        }
                    ],
                ];

                $message = [
                    'no_of_repetition.required' => "Please enter No of Repetiotion.",
                    'range_of_repetition.required' => "Please enter Range of Repetiotion.",
                ];

                $validator = Validator::make($data, $rules, $message);
                if ($validator->fails()) {
                    return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
                }
            }

            if ($data['is_new_exercise_flow']) {
                $data['exercise_form'] = null;
                $data['duration'] = null;
                $data['rest_period'] = null;
                $data['no_of_repetition'] = null;
                $data['range_of_repetition'] = null;
            } else {
                if ($data['exercise_form'] == "duration") {
                    $data['no_of_repetition'] = 0;
                    $data['range_of_repetition'] = 0;
                } else {
                    $data['duration'] = null;
                }
            }

            // echo "<pre>"; print_r($data); die;
            // Determine status
            $status = !empty($data['status']) ? 1 : 0;

            // Update slug
            $slug = Str::slug($data['title']);

            // Handle file uploads for gif
            if ($request->hasFile('gif')) {
                $this->deleteFile($exercise->gif); // Delete old file
                $data['gif'] = $this->storeFile($request->file('gif'), 'gif');
            } else {
                $data['gif'] = $exercise->gif;
            }

            $exerciseData = Exercise::findOrFail($id);

            $exerciseData->title = $data['title'];
            $exerciseData->title_for_frontend = $data['title_for_frontend'];
            $exerciseData->slug = $slug;
            $exerciseData->instructions = $data['instructions'];
            $exerciseData->exercise_form = $data['exercise_form'];
            $exerciseData->duration = $data['duration'];
            $exerciseData->rest_period = $data['rest_period'];
            $exerciseData->gif = $data['gif'];
            $exerciseData->video = $data['video'];
            $exerciseData->no_of_repetition = $data['no_of_repetition'];
            $exerciseData->range_of_repetition = $data['range_of_repetition'];
            $exerciseData->body_zone = $data['body_zone'];
            $exerciseData->exercise_type = $data['exercise_type'];
            $exerciseData->is_new_exercise_flow = $data['is_new_exercise_flow'];
            $exerciseData->status = $status;
            $exerciseData->save();

            return redirect('admin/exercise-index')->with('success', "Exercise updated successfully.");
        }

        return view('admin.programs.exercise.edit')->with(compact('exercise'));
    }

    /**
     * Delete a file from storage.
     *
     * @param string|null $filePath
     * @return void
     */
    private function deleteFile($filePath)
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }

    /**
     * Store a file in storage and return its path.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @return string|null
     */
    private function storeFile($file, $directory)
    {
        $fileName = time() . '.' . $file->extension();
        $filePath = "session/$directory";

        if (!Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->makeDirectory($filePath);
        }

        $file->storeAs($filePath, $fileName, 'public');

        return "session/$directory/$fileName";
    }

    public function updateExerciseStatus(Request $request)
    {
        Session::put('page', 'exercises');
        if ($request->ajax()) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            if ($data['status'] == "Active") {
                $status = 0;
            } else {
                $status = 1;
            }
            Exercise::where(['id' => $data['exercise_id']])->update(['status' => $status]);

            return response()->json(['status' => $status, 'exercise_id' => $data['exercise_id']]);
        }
    }

    public function destroyExercise($slug, $id)
    {
        Session::put('page', 'exercises');
        Exercise::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(2);
        return redirect()->back();
    }
}
