<?php

namespace App\Http\Controllers\Admin\Challenges;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeDay;
use App\Models\ChallengeLevel;
use App\Models\ExerciseOfChallenge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

    class ChallengeController extends Controller
    {
        public function index()
        {
            Session::put('page', 'challenges');
            $challengeData = Challenge::get()->toArray();
            return view('admin.challenges.challenge.index',['challengeData' => $challengeData]);
        }

        public function create(Request $request){
            $challengeLevels = ChallengeLevel::get()->toArray();
            if ($request->isMethod('post')) {
                $data = request()->all();
                // dd($data);
                $rules = [
                    'title' => ['required', 'string', 'max:255'],
                    'title_for_frontend' => ['required', 'string', 'max:255'],
                    'description' => ['required', 'string'],
                    'video_url' => ['nullable','string'],
                    'gif' => ['nullable', 'image'],
                    'levels' => ['required', 'array', 'min:1'],
                    'levels.*.id' => ['required', 'exists:challenge_levels,id'],
                    'levels.*.duration' => ['required', 'integer', 'min:1', 'max:31'],
                    'levels.*.days' => ['required', 'array'],
                    'levels.*.days.*' => ['required', 'array'],
                    'levels.*.days.*.description' => ['required', 'string'],
                ];
                
                $customMessages = [
                    'title.required' => 'Challenge title is required',
                    'title.max' => 'Challenge title cannot exceed 255 characters',
                    
                    'title_for_frontend.required' => 'Frontend title is required',
                    'title_for_frontend.max' => 'Frontend title cannot exceed 255 characters',
                    
                    'description.required' => 'Challenge description is required',
                    
                    'video_url.required' => 'Video URL is required',
                    'video_url.url' => 'Please enter a valid video URL',
                    
                    'gif.required' => 'GIF file is required',
                    'gif.image' => 'The file must be an image',
                    
                    'levels.required' => 'At least one level is required',
                    'levels.min' => 'At least one level is required',
                    
                    'levels.*.id.required' => 'Level selection is required',
                    'levels.*.id.exists' => 'Selected level is invalid',
                    
                    'levels.*.duration.required' => 'Level duration is required',
                    'levels.*.duration.integer' => 'Duration must be a number',
                    'levels.*.duration.min' => 'Duration must be at least 1 day',
                    'levels.*.duration.max' => 'Duration cannot exceed 31 days',
                    
                    'levels.*.days.*.description.required' => 'Day description is required',
                    
                ];
                
                $this->validate(request(), $rules, $customMessages);

                $slug = Str::slug($data['title']);
                $data['gif'] = $this->handleFileUpload($request, 'gif');
                $status = !empty($data['status']) ? 1 : 0;

                $challenge = new Challenge();
                $challenge->title = $data['title'];
                $challenge->slug = $slug;
                $challenge->title_for_frontend = $data['title_for_frontend'];
                $challenge->description = $data['description'];
                $challenge->video_url = $data['video_url'];
                $challenge->gif_url = $data['gif'];
                $challenge->status = $status;
                $challenge->save();

                if($challenge && !empty($data['levels'])){
                    foreach($data['levels'] as $level){
                        $challengeLevel = ChallengeLevel::find($level['id']);
                        if($level['days']){
                            foreach($level['days'] as $key => $day){
                                $challengeDay = new ChallengeDay();
                                $challengeDay->challenge_id = $challenge->id;
                                $challengeDay->challenge_level_id = $challengeLevel->id;
                                $challengeDay->day = $key;
                                $challengeDay->description = $day['description'];
                                $challengeDay->save();
                                if ($challengeDay && !empty($day['exercises'])) {
                                    foreach ($day['exercises'] as $index => $exerciseId) {
                                        $exerciseType = $day['types'][$index] ?? null;
                                        $repetition = $day['repetitions'][$index] ?? null;
                                        $duration = $day['durations'][$index] ?? null;
                                        $restPeriod = $day['rest_periods'][$index] ?? null;

                                        // Ensure only one of repetition or duration is stored
                                        $no_of_repetition = ($exerciseType === 'repetitions') ? $repetition : null;
                                        $exercise_duration = ($exerciseType === 'duration') ? $duration : null;

                                        $exerciseOfChallenge = new ExerciseOfChallenge();
                                        $exerciseOfChallenge->challenge_day_id = $challengeDay->id;
                                        $exerciseOfChallenge->exercise_id = $exerciseId;
                                        $exerciseOfChallenge->exercise_type = $exerciseType;
                                        $exerciseOfChallenge->no_of_repetition = $no_of_repetition;
                                        $exerciseOfChallenge->duration = $exercise_duration;
                                        $exerciseOfChallenge->rest_period = $restPeriod;
                                        $exerciseOfChallenge->order = $index + 1; // Storing order of exercises
                                        $exerciseOfChallenge->save();
                                    }
                                }
                            }
                        }
                    }
                }
                return redirect()->route('admin.challenges.index')->with('success', 'Challenge has been added successfully');
            }
            return view('admin.challenges.challenge.create',[ 'challengeLevels' => $challengeLevels]);
        }

        public function edit(Request $request, $id) {
            $challengeLevels = ChallengeLevel::get()->toArray();
            $challenge = Challenge::with([
                'challengeDays.exercisesOfChallenge.exercise', 
                'challengeDays.challengeLevel'
            ])->findOrFail($id);

            if ($request->isMethod('post')) {
                $data = $request->all();
                
                $rules = [
                    'title' => ['required', 'string', 'max:255'],
                    'title_for_frontend' => ['required', 'string', 'max:255'],
                    'description' => ['required', 'string'],
                    'video_url' => ['nullable', 'string'],
                    'gif' => ['nullable', 'image'],
                    'levels' => ['required', 'array', 'min:1'],
                    'levels.*.id' => ['required', 'exists:challenge_levels,id'],
                    'levels.*.duration' => ['required', 'integer', 'min:1', 'max:31'],
                    'levels.*.days' => ['required', 'array'],
                    'levels.*.days.*' => ['required', 'array'],
                    'levels.*.days.*.description' => ['required', 'string'],
                ];
                
                $customMessages = [
                    'title.required' => 'Challenge title is required',
                    'title.max' => 'Challenge title cannot exceed 255 characters',
                    'title_for_frontend.required' => 'Frontend title is required',
                    'title_for_frontend.max' => 'Frontend title cannot exceed 255 characters',
                    'description.required' => 'Challenge description is required',
                    'video_url.required' => 'Video URL is required',
                    'video_url.url' => 'Please enter a valid video URL',
                    'gif.image' => 'The file must be an image',
                    'levels.required' => 'At least one level is required',
                    'levels.min' => 'At least one level is required',
                    'levels.*.id.required' => 'Level selection is required',
                    'levels.*.id.exists' => 'Selected level is invalid',
                    'levels.*.duration.required' => 'Level duration is required',
                    'levels.*.duration.integer' => 'Duration must be a number',
                    'levels.*.duration.min' => 'Duration must be at least 1 day',
                    'levels.*.duration.max' => 'Duration cannot exceed 31 days',
                    'levels.*.days.*.description.required' => 'Day description is required',
                ];
                
                $this->validate($request, $rules, $customMessages);

                try {
                    DB::beginTransaction();
                    
                    // Update challenge basic info
                    $slug = Str::slug($data['title']);
                    
                    if ($request->hasFile('gif')) {
                        $gifPath = $this->handleFileUpload($request, 'gif');
                        // Delete old gif if exists
                        if ($challenge->gif_url) {
                            Storage::disk('public')->delete($challenge->gif_url);
                        }
                    }

                    $challenge->title = $data['title'];
                    $challenge->slug = $slug;
                    $challenge->title_for_frontend = $data['title_for_frontend'];
                    $challenge->description = $data['description'];
                    $challenge->video_url = $data['video_url'];
                    if (isset($gifPath)) {
                        $challenge->gif_url = $gifPath;
                    }
                    $challenge->status = !empty($data['status']) ? 1 : 0;
                    $challenge->save();

                    // Delete existing challenge days and related exercises
                    $challenge->challengeDays()->each(function ($day) {
                        $day->exercisesOfChallenge()->delete();
                    });
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                    $challenge->challengeDays()->delete();
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                    // Create new challenge days and exercises
                    if (!empty($data['levels'])) {
                        foreach ($data['levels'] as $level) {
                            $challengeLevel = ChallengeLevel::find($level['id']);
                            if ($level['days']) {
                                foreach ($level['days'] as $key => $day) {
                                    $challengeDay = new ChallengeDay();
                                    $challengeDay->challenge_id = $challenge->id;
                                    $challengeDay->challenge_level_id = $challengeLevel->id;
                                    $challengeDay->day = $key;
                                    $challengeDay->description = $day['description'];
                                    $challengeDay->save();

                                    if (!empty($day['exercises'])) {
                                        foreach ($day['exercises'] as $index => $exerciseId) {
                                            $exerciseType = $day['types'][$index] ?? null;
                                            $repetition = $day['repetitions'][$index] ?? null;
                                            $duration = $day['durations'][$index] ?? null;
                                            $restPeriod = $day['rest_periods'][$index] ?? null;

                                            // Ensure only one of repetition or duration is stored
                                            $no_of_repetition = ($exerciseType === 'repetitions') ? $repetition : null;
                                            $exercise_duration = ($exerciseType === 'duration') ? $duration : null;

                                            $exerciseOfChallenge = new ExerciseOfChallenge();
                                            $exerciseOfChallenge->challenge_day_id = $challengeDay->id;
                                            $exerciseOfChallenge->exercise_id = $exerciseId;
                                            $exerciseOfChallenge->exercise_type = $exerciseType;
                                            $exerciseOfChallenge->no_of_repetition = $no_of_repetition;
                                            $exerciseOfChallenge->duration = $exercise_duration;
                                            $exerciseOfChallenge->rest_period = $restPeriod;
                                            $exerciseOfChallenge->order = $index + 1;
                                            $exerciseOfChallenge->save();
                                        }
                                    }
                                }
                            }
                        }
                    }

                    DB::commit();
                    return redirect()->route('admin.challenges.index')->with('success', 'Challenge has been updated successfully');
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    return redirect()->back()->with('error', 'An error occurred while updating the challenge: ' . $e->getMessage());
                }
            }

            // Transform the data for the view
            $challengeData = $this->transformChallengeDataForView($challenge);
            
            return view('admin.challenges.challenge.edit', [
                'challengeLevels' => $challengeLevels,
                'challenge' => $challenge,
                'challengeData' => $challengeData
            ]);
        }

        public function delete($slug, $id)
        {
            try {
                DB::beginTransaction();

                // Find the challenge by slug and id
                $challenge = Challenge::where(['slug' => $slug, 'id' => $id])->firstOrFail();

                // Delete related challenge days and exercises
                $challenge->challengeDays()->each(function ($day) {
                    $day->exercisesOfChallenge()->delete(); // Delete related exercises
                });

                // Disable foreign key checks temporarily to delete challenge days
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                $challenge->challengeDays()->delete();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                // Delete the GIF if it exists
                if ($challenge->gif_url) {
                    Storage::disk('public')->delete($challenge->gif_url);
                }

                // Finally, delete the challenge itself
                $challenge->delete();

                DB::commit();

                return redirect()->route('admin.challenges.index')->with('success', 'Challenge has been deleted successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'An error occurred while deleting the challenge: ' . $e->getMessage());
            }
        }

        private function transformChallengeDataForView($challenge) {
            $challengeData = [];
            
            foreach ($challenge->challengeDays as $day) {
                $levelId = $day->challengeLevel->id;
                
                if (!isset($challengeData[$levelId])) {
                    $challengeData[$levelId] = [
                        'id' => $levelId,
                        'duration' => 0,
                        'days' => []
                    ];
                }
                
                // Update duration if this day number is higher
                $challengeData[$levelId]['duration'] = max($challengeData[$levelId]['duration'], $day->day);
                
                $exercises = [];
                foreach ($day->exercisesOfChallenge as $exercise) {
                    $exercises[] = [
                        'id' => $exercise->exercise_id,
                        'name' => $exercise->exercise->name ?? 'Exercise',
                        'type' => $exercise->no_of_repetition ? 'repetitions' : 'duration',
                        'repetitions' => $exercise->no_of_repetition,
                        'duration' => $exercise->duration,
                        'rest_period' => $exercise->rest_period
                    ];
                }
                
                $challengeData[$levelId]['days'][$day->day] = [
                    'description' => $day->description,
                    'exercises' => $exercises
                ];
            }
            
            return array_values($challengeData);
        }
        public function levelIndex()
        {
            Session::put('page', 'challengeLevel');
            $challengeLevelData = ChallengeLevel::get()->toArray();
            return view('admin.challenges.challenge_level.index',['challengeLevelData' => $challengeLevelData]);
        }

        public function levelCreate(Request $request)
        {
            Session::put('page', 'challengeLevel');
            if ($request->isMethod('post')) {
                $data = request()->all();
                $rules = [
                    'title' => ['required', 'string', 'max:255'],
                    'description' => ['required','string'],
                    'status' => ['required', 'numeric', 'in:0,1'],
                ];
                $customMessages = [
                    'title.required' => 'Title is required',
                    'description.required' => 'Description is required'
                ];
                $this->validate(request(), $rules, $customMessages);
                $challengeLevel = new ChallengeLevel();
                $challengeLevel->title = $data['title'];
                $challengeLevel->slug = Str::slug($data['title']);
                $challengeLevel->description = $data['description'];
                $challengeLevel->status = $data['status'];
                $challengeLevel->save();
                return redirect()->route('admin.challenge-level.index')->with('success', 'Challenge Level has been added successfully');
            }
            return view('admin.challenges.challenge_level.create');
        }

        public function levelEdit(Request $request, $slug, $id){
            $levvelData = ChallengeLevel::find($id);
            if($request->isMethod('post')){
                $data = request()->all();
                $rules = [
                    'title' => ['required', 'string', 'max:255'],
                    'description' => ['required','string'],
                    'status' => ['required', 'numeric', 'in:0,1'],
                ];
                $customMessages = [
                    'title.required' => 'Title is required',
                    'description.required' => 'Description is required'
                ];
                $this->validate(request(), $rules, $customMessages);

                $challengeLevel = ChallengeLevel::find($id);
                $challengeLevel->title = $data['title'];
                $challengeLevel->slug = Str::slug($data['title']);
                $challengeLevel->description = $data['description'];
                $challengeLevel->status = $data['status'];
                $challengeLevel->save();
                return redirect()->route('admin.challenge-level.index')->with('success', 'Challenge Level has been updated successfully');
            }
            return view('admin.challenges.challenge_level.edit',['levelData' => $levvelData]);
        }

        public function levelDestroy($slug, $id){
            $challengeLevel = ChallengeLevel::where(['slug' => $slug, 'id' => $id])->first();
            $challengeLevel->delete();
            return redirect()->route('admin.challenge-level.index')->with('success', 'Challenge Level has been deleted successfully');

        }

        public function updateChallengeStatus(Request $request){
            if ($request->ajax()) {
                $data = $request->all();

                if ($data['status'] == "Active") {
                    $status = 0;
                } else {
                    $status = 1;
                }
                Challenge::where(['id' => $data['challenge_id']])->update(['status' => $status]);

                return response()->json(['status' => $status, 'challenge_id' => $data['challenge_id']]);
            }
        }
        public function updateChallengeLevelStatus(Request $request){
            if ($request->ajax()) {
                $data = $request->all();

                // echo "<pre>"; print_r($data); die;
                if ($data['status'] == "Active") {
                    $status = 0;
                } else {
                    $status = 1;
                }
                ChallengeLevel::where(['id' => $data['level_id']])->update(['status' => $status]);

                return response()->json(['status' => $status, 'level_id' => $data['level_id']]);
            }
        }

        private function handleFileUpload(Request $request, string $fileField)
        {
            if ($request->hasFile($fileField)) {
                $file = $request->file($fileField);
                $fileName = time() . '.' . $file->extension();
                $filePath = "challenge/$fileField";
    
                if (!Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->makeDirectory($filePath);
                }
    
                $file->storeAs($filePath, $fileName, 'public');
    
                return "challenge/$fileField/$fileName";
            }
    
            return null;
        }
    }