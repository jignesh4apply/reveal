<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TransitionController extends Controller
{
    public function index()
    {
        Session::put('page', 'transition');
        $transitions = Transition::get()->toArray();
        return view('admin.quiz.transition.index', ['transitions' => $transitions]);
    }

    public function addTransition(Request $request)
    {
        Session::put('page', 'transition');
        if ($request->isMethod('post')) {
            $data = $request->all();
            // echo "<pre>"; print_r($data); die;

            $rules = [
                'title' => ['required', 'string'],
                'trans_description' => ['required', 'string'],
                'button_label' => ['required', 'string'],
            ];
            $message = [
                'title.required' => "Please enter Question Title.",
                'trans_description.required' => "Please enter transition description.",
                'button_label.required' => "Please enter button label.",
            ];

            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }
            $slug = Str::slug($data['title']);

            if (!isset($data['is_term_and_cond'])) {
                $data['is_term_and_cond'] = 0;
            } else {
                $data['is_term_and_cond'] = 1;
            }

            if (!isset($data['is_animation'])) {
                $data['is_animation'] = 0;
            } else {
                $rules = [
                    'animation_text' => ['required', 'string'],
                ];
                $message = [
                    'animation_text.required' => "Please enter Animation Text.",
                ];

                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) {
                    return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
                }

                $data['is_animation'] = 1;
            }

            if (!isset($data['is_amplitude_track'])) {
                $data['is_amplitude_track'] = 0;
                $data['amplitude_tracking_word'] = null;
            } else {
                $rules = [
                    'amplitude_tracking_word' => ['required', 'string'],
                ];
                $message = [
                    'amplitude_tracking_word.required' => "Please enter Amplitude Tracking Word.",
                ];

                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) {
                    return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
                }

                $data['is_amplitude_track'] = 1;
            }

            // echo "<pre>";print_r($data['is_paywall']);die;
            if (!isset($data['is_paywall'])) {
                $data['is_paywall'] = 0;
            } else {
                $data['is_paywall'] = 1;
            }

            if (!isset($data['is_chart_trans'])) {
                $data['is_chart'] = 0;
                $data['chartOptions'] = "";
            } else {
                $data['is_chart'] = 1;
            }

            if (isset($data['status'])) {
                $data['status'] = 1;
            } else {
                $data['status'] = 0;
            }

            $transition = new Transition;
            $transition->title = $data['title'];
            $transition->slug = $slug;
            $transition->color = $data['color'];
            $transition->trans_description = $data['trans_description'];

            if (isset($data['is_trans_image']) && $data['is_trans_image'] == 'on') {
                $transition->is_trans_image = 1;

                $transition_image = time() . '.' . $data['transition_image']->extension();
                if (!Storage::disk('public')->exists("/transition/image")) {
                    Storage::disk('public')->makeDirectory("/transition/image"); //creates directory
                }
                $request->transition_image->storeAs("transition/image", $transition_image, 'public');
                $transition_image = "transition/image/$transition_image";

                $transition->transition_image = $transition_image;
            }
            $transition->is_term_and_cond = $data['is_term_and_cond'];
            $transition->is_animation = $data['is_animation'];
            $transition->animation_text = $data['animation_text'];
            $transition->is_paywall = $data['is_paywall'];
            $transition->is_chart = $data['is_chart'];
            $transition->chart_name = $data['chartOptions'];
            $transition->button_label = $data['button_label'];
            $transition->is_amplitude_track = $data['is_amplitude_track'];
            $transition->amplitude_tracking_word = $data['amplitude_tracking_word'];
            $transition->status = $data['status'];
            $transition->save();

            return redirect('/admin/transition-index')->with('success', 'Transition Inserted Successfully !!!');
        }
        return view('admin.quiz.transition.create');
    }

    public function updateTransition(Request $request, $slug, $id)
    {
        Session::put('page', 'transition');
        $transitionData = Transition::where(['id' => $id, 'slug' => $slug])->first()->toArray();
        // echo "<pre>";print_r($transitionData);die;
        if ($request->isMethod('post')) {
            $data = $request->all();
            // echo "<pre>"; print_r($data); die;
            // dd($data);
            $rules = [
                'title' => ['required', 'string'],
                'button_label' => ['required', 'string'],
            ];
            $message = [
                'title.required' => "Please enter Question Title.",
                'button_label.required' => "Please enter button label.",
            ];

            $validator = Validator::make($request->all(), $rules, $message);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }
            if (!isset($data['is_term_and_cond'])) {
                $data['is_term_and_cond'] = 0;
            } else {
                $data['is_term_and_cond'] = 1;
            }
            if (!isset($data['is_animation'])) {
                $data['is_animation'] = 0;
            } else {
                $rules = [
                    'animation_text' => ['required', 'string'],
                ];
                $message = [
                    'animation_text.required' => "Please enter Animation Text.",
                ];

                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) {
                    return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
                }

                $data['is_animation'] = 1;
            }

            
            if (!isset($data['is_amplitude_track'])) {
                $data['is_amplitude_track'] = 0;
                $data['amplitude_tracking_word'] = null;
            } else {
                $rules = [
                    'amplitude_tracking_word' => ['required', 'string'],
                ];
                $message = [
                    'amplitude_tracking_word.required' => "Please enter Amplitude Tracking Word.",
                ];

                $validator = Validator::make($request->all(), $rules, $message);
                if ($validator->fails()) {
                    return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
                }

                $data['is_amplitude_track'] = 1;
            }

            if (!isset($data['is_paywall'])) {
                $data['is_paywall'] = 0;
            } else {
                $data['is_paywall'] = 1;
            }
            if (!isset($data['is_chart_trans'])) {
                $data['is_chart'] = 0;
                $data['chartOptions'] = "";
            } else {
                $data['is_chart'] = 1;
            }
            $slug = Str::slug($data['title']);

            if (isset($data['is_trans_image']) && $data['is_trans_image'] == 'on' && isset($data['transition_image'])) {
                $is_trans_image = 1;

                $transition_image = time() . '.' . $data['transition_image']->extension();
                if (!Storage::disk('public')->exists("/transition/image")) {
                    Storage::disk('public')->makeDirectory("/transition/image"); //creates directory
                }
                $request->transition_image->storeAs("transition/image", $transition_image, 'public');
                $transition_image = "transition/image/$transition_image";
            } else {
                if (!is_null($transitionData['transition_image'])) {
                    $is_trans_image = 0;
                    Storage::disk('public')->delete($transitionData['transition_image']);
                }
                $transition_image = null;
            }
            if (isset($data['status'])) {
                $data['status'] = 1;
            } else {
                $data['status'] = 0;
            }
            $trans = Transition::where('id', $id)->Update([
                'title' => $data['title'],
                'slug' => $slug,
                'color' => $data['color'],
                'trans_description' => $data['trans_description'],
                'is_trans_image' => $is_trans_image ?? $transitionData['is_trans_image'],
                'transition_image' => $transition_image,
                'is_term_and_cond' => $data['is_term_and_cond'],
                'is_animation' => $data['is_animation'],
                'animation_text' => $data['animation_text'],
                'is_paywall' => $data['is_paywall'],
                'chart_name' => $data['chartOptions'],
                'is_chart' => $data['is_chart'],
                'button_label' => $data['button_label'],
                'is_amplitude_track' => $data['is_amplitude_track'],
                'amplitude_tracking_word' => $data['amplitude_tracking_word'],
                'status' => $data['status'],
            ]);
            if ($trans) {
                return redirect('/admin/transition-index')->with('success', 'Transition Updated Successfully !!!');
            }
        }
        return view('admin.quiz.transition.edit', ['transitionData' => $transitionData]);
    }

    public function destroyTransition($slug, $id)
    {
        Session::put('page', 'transition');
        Transition::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back();
    }
}
