<?php

namespace App\Http\Controllers\Admin\Program;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Program;
use App\Models\Session;
use App\Models\SessionExercise;
use App\Models\SessionMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session as FacadesSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Vimeo\Laravel\Facades\Vimeo;

class SessionController extends Controller
{
    public function index()
    {
        FacadesSession::put('page', 'sessions');
        $sessionData = Session::with('program')->get()->toArray();
        // dd($sessionData);
        return view('admin.programs.session.index', ['sessionData' => $sessionData]);
    }

    public function create(Request $request)
    {
        FacadesSession::put('page', 'sessions');
        $programs = Program::with('category')->get()->toArray();
        $exerciseData = Exercise::where(['status' => 1])->get()->toArray();
        if ($request->isMethod('post')) {
            $data = $request->all();
            // echo "<pre>"; print_r($data); die;

            $message = [
                'title.required' => "Title is required.",
                'title_for_frontend.required' => "Title for Backend is required.",
                'difficulty_level.required' => "Please select difficulty level.",
                'body_area.required' => "Please enter body area this session will work on.",
                'time.required' => "Please enter session time.",
                'summary.required' => "Please write summary of this session.",
                'calories.required' => "Please enter how much calories will burn in this session.",
                'session_type.required' => "Please select type of session for this session.",
                'video_link.required' => "Please enter a video link."
            ];

            $request->validate([
                'title' => 'required|string|max:255',
                'title_for_frontend' => 'required|string|max:255',
                'difficulty_level' => 'required',
                'body_area' => 'required',
                'time' => 'required',
                'summary' => 'required',
                'calories' => 'required',
                'session_type' => 'required',
                'video_link' => 'required'
            ], $message);
            if (!empty($data['item'])) {
                foreach ($data['items'] as $key => $material) {
                    // dd($material);
                    if (is_null($material['materialName']) && !isset($material['materialImage'])) {
                        return redirect()->back()->with('error', "Please enter material name and image in every generated field.");
                    }
                }
            }

            if (empty($data['listItems'])) {
                return redirect()->back()->with('error', "Please select exercises for this session");
            }

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }

            $isNewExerciseFlow = !empty($data['is_new_exercise_flow']) ? 1 : 0;
            $sessionExerciseMeta = [];
            if ($isNewExerciseFlow) {
                $sessionExerciseMeta = json_decode($data['session_exercise_meta'] ?? '[]', true);
                if (!is_array($sessionExerciseMeta)) {
                    $sessionExerciseMeta = [];
                }

                $exerciseIds = array_filter(array_map('trim', explode(',', $data['listItems'] ?? '')));
                if (count($sessionExerciseMeta) !== count($exerciseIds)) {
                    return redirect()->back()->withInput()->with('error', 'Please configure exercise metadata for every listed exercise in the new session flow.');
                }

                foreach ($sessionExerciseMeta as $meta) {
                    $formType = $meta['exercise_form'] ?? null;
                    if (!in_array($formType, ['repetition', 'duration'], true)) {
                        return redirect()->back()->withInput()->with('error', 'Please select Exercise Form for all exercises when using the new session flow.');
                    }
                    if (empty(trim((string)($meta['rest_period'] ?? '')))) {
                        return redirect()->back()->withInput()->with('error', 'Please enter Rest Period for all exercises when using the new session flow.');
                    }
                    if ($formType === 'repetition' && strlen(trim((string)($meta['repetition'] ?? ''))) === 0) {
                        return redirect()->back()->withInput()->with('error', 'Please enter Reps for all repetition-based exercises.');
                    }
                    if ($formType === 'duration' && empty(trim((string)($meta['duration'] ?? '')))) {
                        return redirect()->back()->withInput()->with('error', 'Please enter Duration for all duration-based exercises.');
                    }
                }
            }

            $x = preg_replace('/\s*:\s*/', ':', $data['time']);
            $time = date("H:i", strtotime($x));

            $slug = Str::slug($data['title']);

            $session = new Session;
            $session->title = $data['title'];
            $session->title_for_frontend = $data['title_for_frontend'];
            $session->exercise = $data['listItems'];
            $session->difficulty_level = $data['difficulty_level'];
            $session->body_area = $data['body_area'];
            $session->time = $time;
            $session->video_link = $data['video_link'];
            $session->slug = $slug;
            $session->summary = $data['summary'];
            $session->calories = $data['calories'];
            $session->session_type = $data['session_type'];
            $session->status = $status;
            $session->is_new_exercise_flow = $isNewExerciseFlow;
            $session->save();

            // Storing Session Exercises in session_exercise table
            if ($isNewExerciseFlow) {
                foreach ($sessionExerciseMeta as $meta) {
                    $exerciseId = $meta['exercise_id'] ?? null;
                    if (empty($exerciseId)) {
                        continue;
                    }

                    $sessExercise = new SessionExercise;
                    $sessExercise->session_id = $session->id;
                    $sessExercise->session_exercise_id = $exerciseId;
                    $sessExercise->exercise_form = $meta['exercise_form'] ?? null;
                    $sessExercise->rest_period = $meta['rest_period'] ?? null;
                    $sessExercise->duration = $meta['duration'] ?? null;
                    $sessExercise->repetition = $meta['repetition'] ?? null;
                    $sessExercise->repetition_range = $meta['repetition_range'] ?? null;
                    $sessExercise->save();
                }
            } else {
                $exercises = explode(",", $data['listItems']);
                foreach ($exercises as $exercise) {
                    $sessExercise = new SessionExercise;
                    $sessExercise->session_id = $session->id;
                    $sessExercise->session_exercise_id = $exercise;
                    $sessExercise->save();
                }
            }

            // Material Title And Material Image
            $materials = [];
            if (!empty($data['items'])) {
                foreach ($data['items'] as $key => $sessionMaterial) {
                    $materials[] = [
                        'title' => $sessionMaterial['materialName'],
                        'image' => $sessionMaterial['materialImage']
                    ];
                }
            }

            // echo "<pre>"; print_r($materials);

            foreach ($materials as $material) {
                $image = "";
                $reqMatImage = $material['image']->getClientOriginalName() . '.' . $material['image']->extension();
                if (!Storage::disk('public')->exists("/session_material_image/materialImage")) {
                    Storage::disk('public')->makeDirectory("/session_material_image/materialImage"); //creates directory
                }
                $material['image']->storeAs("session_material_image/materialImage", $reqMatImage, 'public');
                $image = "session_material_image/materialImage/$reqMatImage";
                $title = $material['title'];

                $sessionMaterialData = new SessionMaterial;
                $sessionMaterialData->session_id = $session->id;
                $sessionMaterialData->material_name = $title;
                $sessionMaterialData->material_image = $image;
                $sessionMaterialData->save();
            }
            return redirect('/admin/session-index')->with('success', 'Session Inserted Successfully !!!');
        }
        return view('admin.programs.session.create', ['programs' => $programs, 'exerciseData' => $exerciseData]);
    }

    public function update(Request $request, $slug, $id)
    {
        FacadesSession::put('page', 'sessions');
        $session = Session::with('materials', 'session_exercises')->where(['id' => $id])->first();
        // echo "<pre>"; print_r($session); die;
        // dd(explode(",", $session->exercise));
        if ($request->isMethod('post')) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            $validation = [
                'title' => ['required', 'string', 'max:255'],
                'title_for_frontend' => ['required', 'string', 'max:255'],
                'difficulty_level' => ['required'],
                'body_area' => ['required'],
                'time' => ['required'],
                'summary' => ['required'],
                'calories' => ['required'],
                'session_type' => ['required'],
                'video_link' => ['required'],
            ];
            $validator = Validator::make($data, $validation);
            $input = $request->except(['_token']);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if (empty($data['listItems'])) {
                $data['listItems'] = $session->exercise;
            }

            if (empty($data['listItems'])) {
                return redirect()->back()->with('error', "Please select exercises for this session");
            }

            $isNewExerciseFlow = !empty($data['is_new_exercise_flow']) ? 1 : 0;
            $sessionExerciseMeta = [];
            if ($isNewExerciseFlow) {
                $sessionExerciseMeta = json_decode($data['session_exercise_meta'] ?? '[]', true);
                if (!is_array($sessionExerciseMeta)) {
                    $sessionExerciseMeta = [];
                }

                $exerciseIds = array_filter(array_map('trim', explode(',', $data['listItems'] ?? $session->exercise)));
                if (count($sessionExerciseMeta) !== count($exerciseIds)) {
                    return redirect()->back()->withInput()->with('error', 'Please configure exercise metadata for every listed exercise when using the new session flow.');
                }

                foreach ($sessionExerciseMeta as $meta) {
                    $formType = $meta['exercise_form'] ?? null;
                    if (!in_array($formType, ['repetition', 'duration'], true)) {
                        return redirect()->back()->withInput()->with('error', 'Please select Exercise Form for all exercises when using the new session flow.');
                    }
                    if (empty(trim((string)($meta['rest_period'] ?? '')))) {
                        return redirect()->back()->withInput()->with('error', 'Please enter Rest Period for all exercises when using the new session flow.');
                    }
                    if ($formType === 'repetition' && strlen(trim((string)($meta['repetition'] ?? ''))) === 0) {
                        return redirect()->back()->withInput()->with('error', 'Please enter Reps for all repetition-based exercises.');
                    }
                    if ($formType === 'duration' && empty(trim((string)($meta['duration'] ?? '')))) {
                        return redirect()->back()->withInput()->with('error', 'Please enter Duration for all duration-based exercises.');
                    }
                }
            }

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }

            if (!isset($data['videos'])) {
                $data['videos'] = $session->video;
            }

            $slug = Str::slug($data['title']);

            //  if (($request->has('videos'))) {
            //     Vimeo::request($session->video_uri, [], 'DELETE');

            //     $uri = Vimeo::upload($request->videos, [
            //        'name' => $data['title'],
            //        'description' => $data['summary'],
            //        // "privacy" => ["view" => "anybody"]
            //        // "privacy" => ["view" => "nobody"]
            //        // "privacy" => ["view" => "password"]
            //        // "privacy" => ["view" => "unlisted"]
            //        // "privacy" => ["view" => "disable"]
            //     ]);

            //     Vimeo::request($uri . '/privacy/domains/http://54.66.206.242', [], 'PUT');
            //     Vimeo::request($uri, ['privacy' => ['embed' => 'whitelist']], 'PATCH');


            //     // $embed = Vimeo::request($uri, [
            //     //         'embed' => ['domains' => ['www.facebook.com']]
            //     // ], 'PATCH');
            //     $vimeoVideo = Vimeo::request($uri, [], 'GET');
            //     $video_link = $vimeoVideo['body']['player_embed_url'];
            //  } else {
            //     $uri = $session->video_uri;
            //     $video_link = $session->video_link;
            //  }

            //  $updateProgram = Session::where(['id' => $id])->update(['exercise' => $data['listItems'], 'title' => $data['title'], 'difficulty_level' => $data['difficulty_level'], 'body_area' => $data['body_area'], 'time' => $data['time'], 'slug' => $slug, 'summary' => $data['summary'], 'calories' => $data['calories'], 'session_type' => $data['session_type'], 'video_uri' => $uri, 'video_link' => $video_link, 'status' => $status]);

            $updateProgram = Session::where(['id' => $id])->update([
                'exercise' => $data['listItems'],
                'title' => $data['title'],
                'title_for_frontend' => $data['title_for_frontend'],
                'difficulty_level' => $data['difficulty_level'],
                'body_area' => $data['body_area'],
                'time' => $data['time'],
                'slug' => $slug,
                'summary' => $data['summary'],
                'calories' => $data['calories'],
                'session_type' => $data['session_type'],
                'video_link' => $data['video_link'],
                'status' => $status,
                'is_new_exercise_flow' => $isNewExerciseFlow,
            ]);

            // Update Session Exercise Table
            SessionExercise::where(['session_id' => $id])->delete();
            if ($isNewExerciseFlow) {
                foreach ($sessionExerciseMeta as $meta) {
                    $exerciseId = $meta['exercise_id'] ?? null;
                    if (empty($exerciseId)) {
                        continue;
                    }
                    $sessExercise = new SessionExercise;
                    $sessExercise->session_id = $session->id;
                    $sessExercise->session_exercise_id = $exerciseId;
                    $sessExercise->exercise_form = $meta['exercise_form'] ?? null;
                    $sessExercise->rest_period = $meta['rest_period'] ?? null;
                    $sessExercise->duration = $meta['duration'] ?? null;
                    $sessExercise->repetition = $meta['repetition'] ?? null;
                    $sessExercise->repetition_range = $meta['repetition_range'] ?? null;
                    $sessExercise->save();
                }
            } else {
                $exercises = explode(",", $data['listItems']);
                foreach ($exercises as $exercise) {
                    $sessExercise = new SessionExercise;
                    $sessExercise->session_id = $session->id;
                    $sessExercise->session_exercise_id = $exercise;
                    $sessExercise->save();
                }
            }

            // Material Title And Material Image
            // Retrieve existing materials from the database
            $existingMaterials = SessionMaterial::where('session_id', $id)->get()->keyBy('id');

            $newMaterials = [];
            $existingMaterialIds = [];

            if (isset($data['items']) || !empty($data['items'])) {
                foreach ($data['items'] as $key => $sessionMaterial) {
                    if (!empty($sessionMaterial['materialName'])) {
                        // Check if the material ID exists in the existing materials
                        if (!empty($sessionMaterial['materialId']) && isset($existingMaterials[$sessionMaterial['materialId']])) {
                            // Retain existing materials
                            $existingMaterialIds[] = $sessionMaterial['materialId'];
                            // Update existing material
                            $material = $existingMaterials[$sessionMaterial['materialId']];
                            $material->material_name = $sessionMaterial['materialName'];
                            if (!empty($sessionMaterial['materialImage']) && $sessionMaterial['materialImage'] instanceof \Illuminate\Http\UploadedFile) {
                                $reqMatImage = uniqid() . '_' . $sessionMaterial['materialImage']->getClientOriginalName(); // Generate a unique name
                                if (!Storage::disk('public')->exists("/session_material_image/materialImage")) {
                                    Storage::disk('public')->makeDirectory("/session_material_image/materialImage"); // Creates directory
                                }
                                $sessionMaterial['materialImage']->storeAs("session_material_image/materialImage", $reqMatImage, 'public');
                                $material->material_image = "session_material_image/materialImage/$reqMatImage";
                            }
                            $material->save();
                        } else {
                            // Handle new materials
                            $newMaterials[] = [
                                'title' => $sessionMaterial['materialName'],
                                'image' => isset($sessionMaterial['materialImage']) ? $sessionMaterial['materialImage'] : null
                            ];
                        }
                    }
                }

                // Delete existing materials that are no longer present
                SessionMaterial::where('session_id', $id)
                    ->whereNotIn('id', $existingMaterialIds)
                    ->delete();

                // Insert new materials
                foreach ($newMaterials as $material) {
                    $image = null;
                    if ($material['image']) {
                        $reqMatImage = uniqid() . '_' . $material['image']->getClientOriginalName(); // Generate a unique name
                        if (!Storage::disk('public')->exists("/session_material_image/materialImage")) {
                            Storage::disk('public')->makeDirectory("/session_material_image/materialImage"); //creates directory
                        }
                        $material['image']->storeAs("session_material_image/materialImage", $reqMatImage, 'public');
                        $image = "session_material_image/materialImage/$reqMatImage";
                    }
                    $title = $material['title'];

                    $sessionMaterialData = new SessionMaterial;
                    $sessionMaterialData->session_id = $id;
                    $sessionMaterialData->material_name = $title;
                    $sessionMaterialData->material_image = $image;
                    $sessionMaterialData->save();
                }
            }
            return redirect('/admin/session-index')->with('success', 'Session updated successfully !!!');
        }

        $programs = Program::with('category')->get()->toArray();
        $exerciseData = Exercise::where(['status' => 1])->get();
        return view('admin.programs.session.edit')->with(compact('programs', 'session', 'exerciseData'));
    }

    public function duplicate($slug, $id)
    {
        FacadesSession::put('page', 'sessions');
        $session = Session::with(['session_exercises', 'materials'])->find($id);

        if (!$session) {
            return redirect()->back()->with('error', 'Session not found.');
        }

        DB::beginTransaction();

        try {
            $newSlugBase = Str::slug($session->title ?: 'session');
            $newSlug = $newSlugBase;
            $counter = 1;
            while (Session::where('slug', $newSlug)->exists()) {
                $newSlug = $newSlugBase . '-' . $counter;
                $counter++;
            }

            $newSession = new Session;
            $newSession->title = $session->title;
            $newSession->title_for_frontend = $session->title_for_frontend;
            $newSession->exercise = $session->exercise;
            $newSession->difficulty_level = $session->difficulty_level;
            $newSession->body_area = $session->body_area;
            $newSession->time = $session->time;
            $newSession->video_link = $session->video_link;
            $newSession->slug = $newSlug;
            $newSession->summary = $session->summary;
            $newSession->calories = $session->calories;
            $newSession->session_type = $session->session_type;
            $newSession->status = $session->status;
            $newSession->is_new_exercise_flow = $session->is_new_exercise_flow;
            $newSession->save();

            foreach ($session->session_exercises as $sessionExercise) {
                $newSessionExercise = new SessionExercise;
                $newSessionExercise->session_id = $newSession->id;
                $newSessionExercise->session_exercise_id = $sessionExercise->session_exercise_id;
                $newSessionExercise->exercise_form = $sessionExercise->exercise_form;
                $newSessionExercise->rest_period = $sessionExercise->rest_period;
                $newSessionExercise->duration = $sessionExercise->duration;
                $newSessionExercise->repetition = $sessionExercise->repetition;
                $newSessionExercise->repetition_range = $sessionExercise->repetition_range;
                $newSessionExercise->save();
            }

            foreach ($session->materials as $material) {
                $newMaterial = new SessionMaterial;
                $newMaterial->session_id = $newSession->id;
                $newMaterial->material_name = $material->material_name;
                $newMaterial->material_image = $material->material_image;
                $newMaterial->save();
            }

            DB::commit();

            return redirect('/admin/session-update/'.$newSession->slug.'/'.$newSession->id)->with('success', 'Session duplicated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to duplicate session.');
        }
    }

    public function updateSessionStatus(Request $request)
    {
        FacadesSession::put('page', 'sessions');
        if ($request->ajax()) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            if ($data['status'] == "Active") {
                $status = 0;
            } else {
                $status = 1;
            }
            Session::where(['id' => $data['session_id']])->update(['status' => $status]);

            return response()->json(['status' => $status, 'session_id' => $data['session_id']]);
        }
    }

    public function destroy($slug, $id)
    {
        FacadesSession::put('page', 'sessions');
        Session::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(2);
        return redirect()->back();
    }

    public function getSession(Request $request)
    {
        $sessionData = Session::where(['status' => 1])->get();
        $formattedOptions = $sessionData->map(function ($option) {
            // echo "<pre>"; print_r($option); die;
            return [
                'value' => $option->id,
                'label' => $option->title,
            ];
        });

        return response()->json($formattedOptions);
    }

    public function deleteSessionExercise(Request $request, $id = null, $sessionid = null)
    {
        $getSessionExercise = SessionExercise::where(['id' => $id])->first();

        if (!empty($getSessionExercise)) {
            $getSession = Session::where(['id' => $sessionid])->first();
            $exercise = explode(",", $getSession->exercise);
            $item = array_search($getSessionExercise->session_exercise_id, $exercise);
            if ($item !== false) {
                unset($exercise[$item]);

                if (empty(implode(",", $exercise))) {
                    Session::where(['id' => $sessionid])->update(['exercise' => null]);
                } else {
                    Session::where(['id' => $sessionid])->update(['exercise' => implode(",", $exercise)]);
                }
            }
            $getSessionExercise->delete();
            return response()->json(['success'=> 'Session Exercise has been deleted successfully!!!']);
        } else {
            return response()->json(['error'=>"something went wrong!!"]);
        }
    }
}
