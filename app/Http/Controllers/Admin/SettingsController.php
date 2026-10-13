<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitConversion;
use App\Models\VideoAbout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function videoAboutIndex()
    {
        Session::put('page', 'videoAbout');
        $videoAboutData = VideoAbout::get()->toArray();
        return view('admin.settings.videoAbout.index', ['videoAboutData' => $videoAboutData]);
    }

    public function createVideoAbout(Request $request)
    {
        Session::put('page', 'videoAbout');

        if ($request->isMethod('post')) {
            $data = $request->all();
            // Validation
            $rules = [
                'screen_name' => ['required', 'string'],
                'video_link' => ['required', 'string'],
            ];

            $messages = [
                'screen_name.required' => "Please enter screen_name.",
                'video_link.required' => "Please enter video link."
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }

            $slug = Str::slug($data['screen_name']);
            $unitConverter = new VideoAbout;
            $unitConverter->screen_name = $data['screen_name'];
            $unitConverter->video_link = $data['video_link'];
            $unitConverter->slug = $slug;
            $unitConverter->status = $status;
            $unitConverter->save();

            return redirect('admin/video-about-index')->with('success', "Video About saved successfully.");
        }
        return view('admin.settings.videoAbout.create');
    }

    public function updateVideoAbout(Request $request, $slug, $id)
    {
        Session::put('page', 'videoAbout');
        $videoAbout = VideoAbout::where(['id' => $id, 'slug' => $slug])->first()->toArray();
        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [
                'screen_name' => ['required', 'string'],
                'video_link' => ['required', 'string'],
            ];

            $messages = [
                'screen_name.required' => "Please enter screen_name.",
                'video_link.required' => "Please enter video link."
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }

            VideoAbout::where(['id' => $id, 'slug' => $slug])->update(['screen_name' => $data['screen_name'], 'video_link' => $data['video_link'], 'status' => $status]);

            return redirect('admin/video-about-index')->with('success', "Video About updated successfully");
        }
        return view('admin.settings.videoAbout.edit', ['videoAbout' => $videoAbout]);
    }

    public function destroyVideoAbout($slug, $id)
    {
        Session::put('page', 'videoAbout');
        VideoAbout::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(2);
        return redirect()->back();
    }
}
