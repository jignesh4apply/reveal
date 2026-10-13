<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Session::put('page', 'activities');
        $activities = Activity::orderBy('id', 'desc')->get();
        return view('admin.activities.index')->with(compact('activities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Session::put('page', 'activities');
        return view('admin.activities.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        
        $rules = [
            'name' => 'required|string|max:255',
            'default_burnout_per_hour' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|boolean'
        ];

        $messages = [
            'name.required' => 'Please enter activity name',
            'default_burnout_per_hour.required' => 'Please enter default burnout per hour',
            'default_burnout_per_hour.numeric' => 'Burnout per hour must be a number',
            'image.image' => 'The file must be an image',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg',
            'image.max' => 'The image may not be greater than 2MB',
            'status.required' => 'Please select status'
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator->messages());
        }

        if ($request->hasFile('image')) {
            // Ensure directory exists
            if (!Storage::disk('public')->exists('activities')) {
                Storage::disk('public')->makeDirectory('activities');
            }

            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();

            $image->storeAs('activities', $imageName, 'public');

            $data['image'] = 'activities/' . $imageName;
        }

        Activity::create($data);

        return redirect()->route('admin.activities.index')->with('success', 'Activity created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        Session::put('page', 'activities');
        $activity = Activity::findOrFail($id);
        return view('admin.activities.edit')->with(compact('activity'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);
        $data = $request->all();
        $rules = [
            'name' => 'required|string|max:255',
            'default_burnout_per_hour' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|boolean'
        ];

        $messages = [
            'name.required' => 'Please enter activity name',
            'default_burnout_per_hour.required' => 'Please enter default burnout per hour',
            'default_burnout_per_hour.numeric' => 'Burnout per hour must be a number',
            'image.image' => 'The file must be an image',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg',
            'image.max' => 'The image may not be greater than 2MB',
            'status.required' => 'Please select status'
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator->messages());
        }

        // Handle image upload
        if ($request->hasFile('image')) {

            // Delete old image if exists
            if ($activity->image && Storage::disk('public')->exists($activity->image)) {
                Storage::disk('public')->delete($activity->image);
            }

            // Ensure directory exists
            if (!Storage::disk('public')->exists('activities')) {
                Storage::disk('public')->makeDirectory('activities');
            }

            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();

            // Store new image
            $image->storeAs('activities', $imageName, 'public');

            $data['image'] = 'activities/' . $imageName;
        }

        $activity->update($data);

        return redirect()->route('admin.activities.index')->with('success', 'Activity updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);
        
        // Delete image if exists
        if ($activity->image && Storage::exists('public/' . $activity->image)) {
            Storage::delete('public/' . $activity->image);
        }
        
        $activity->delete();
        
        return redirect()->back()->with('success', 'Activity deleted successfully.');
    }
    
    public function updateStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            
            if ($data['status'] == "Active") {
                $status = 0;
            } else {
                $status = 1;
            }
            
            Activity::where('id', $data['activity_id'])->update(['status' => $status]);
            
            return response()->json([
                'status' => $status,
                'activity_id' => $data['activity_id']
            ]);
        }
    }
}
