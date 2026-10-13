<?php

namespace App\Http\Controllers\Admin\Challenges;

use App\Http\Controllers\Controller;
use App\Models\ChallengeExercise;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChallengeExerciseController extends Controller
{
    public function index()
    {
        Session::put('page', 'challengeExercise');

        $exerciseData = ChallengeExercise::get()->toArray();
        return view('admin.challenges.challengeExercise.index', ['exerciseData' => $exerciseData]);
    }

    public function create(Request $request)
    {
        Session::put('page', 'challengeExercise');

        if ($request->isMethod('post')) {
            $data = $request->all();

            $validator = Validator::make($data, [
                'title' => ['required', 'string', 'max:255']
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            // Handle file uploads (GIF)
            $data['gif'] = $this->handleFileUpload($request, 'gif');

            // Set status based on checkbox
            $status = !empty($data['status']) ? 1 : 0;

            // Generate slug
            $slug = Str::slug($data['title']);

            // Save exercise to database
            $exercise = new ChallengeExercise;
            $exercise->fill($data);
            $exercise->slug = $slug;
            $exercise->status = $status;
            $exercise->save();

            return redirect('admin/challenge-exercise-index')->with('success', "Challenge Exercise added successfully.");
        }

        return view('admin.challenges.challengeExercise.create');
    }

    private function handleFileUpload(Request $request, string $fileField)
    {
        if ($request->hasFile($fileField)) {
            $file = $request->file($fileField);
            $fileName = time() . '.' . $file->extension();
            $filePath = "challengeExercise/$fileField";

            if (!Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->makeDirectory($filePath);
            }

            $file->storeAs($filePath, $fileName, 'public');

            return "challengeExercise/$fileField/$fileName";
        }

        return null;
    }

    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'challengeExercise');
        $exercise = ChallengeExercise::where(['slug' => $slug, 'id' => $id])->first();

        if ($request->isMethod('post')) {
            $data = $request->all();

            // Validation rules
            $validation = [
                'title' => ['required', 'string', 'max:255']
            ];

            $validator = Validator::make($data, $validation);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

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

            $exerciseData = ChallengeExercise::findOrFail($id);

            $exerciseData->title = $data['title'];
            $exerciseData->slug = $slug;
            $exerciseData->title_for_frontend = $data['title_for_frontend'];
            $exerciseData->instructions = $data['instructions'];
            $exerciseData->video_url_exercise = $data['video_url_exercise'];
            $exerciseData->video_url_presentation = $data['video_url_presentation'];
            $exerciseData->gif = $data['gif'];
            $exerciseData->status = $status;
            $exerciseData->save();

            return redirect('admin/challenge-exercise-index')->with('success', "Challenge Exercise updated successfully.");
        }

        return view('admin.challenges.challengeExercise.edit')->with(compact('exercise'));
    }

    private function deleteFile($filePath)
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }

    private function storeFile($file, $directory)
    {
         $fileName = time() . '.' . $file->extension();
         $filePath = "challengeExercise/$directory";

         if (!Storage::disk('public')->exists($filePath)) {
             Storage::disk('public')->makeDirectory($filePath);
         }

         $file->storeAs($filePath, $fileName, 'public');

         return "challengeExercise/$directory/$fileName";
    }

    public function updateChallengeExerciseStatus(Request $request)
    {
        Session::put('page', 'challengeExercise');
        if ($request->ajax()) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            if ($data['status'] == "Active") {
                $status = 0;
            } else {
                $status = 1;
            }
            ChallengeExercise::where(['id' => $data['exercise_id']])->update(['status' => $status]);

            return response()->json(['status' => $status, 'exercise_id' => $data['exercise_id']]);
        }
    }

    public function destroy($slug, $id)
    {
        Session::put('page', 'challengeExercise');
        ChallengeExercise::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(2);
        return redirect()->back();
    }

    public function getExercise(Request $request)
    {
        $exerciseData = ChallengeExercise::where(['status' => 1])->get();
        $formattedOptions = $exerciseData->map(function ($option) {
            return [
                'value' => $option->id,
                'label' => $option->title,
            ];
        });

        return response()->json($formattedOptions);
    }

    public function getExerciseName($id)
    {
        $exercise = ChallengeExercise::find($id);
        return response()->json(['name' => $exercise ? $exercise->title : null]);
    }
}
