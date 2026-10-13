<?php

namespace App\Http\Controllers\Admin\Recipe;

use App\Http\Controllers\Controller;
use App\Models\MealType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MealController extends Controller
{
    public function index()
    {

        Session::put('page', 'mealType');
        $mealTypetData = MealType::get()->toArray();
        // dd($categoryData);
        return view('admin.recipeDiet.meal_type.index', ['mealTypetData' => $mealTypetData]);
    }

    public function create(Request $request)
    {
        Session::put('page', 'mealType');
        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'title' => ['required', 'string', 'max:255'],
                'meal_type' => ['required', 'string', 'unique:meal_types'],
                'image' => ['required', 'image', 'mimes:jpg,jpeg,png'],
            ];

            $message = [
                'title.required' => "Title is required.",
                'meal_type.required' => "Meal Type field cannot be empty.",
                'meal_type.unique' => "The meal type must be unique. This meal type has already been taken.",
                'image.required' => "Image is required.",
                'image.mimes' => "Please select image with these file format jpg,jpeg,png.",
            ];
            $validator = Validator::make($data, $rules, $message);
            $input = $request->except(['_token']);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if (empty($data['visible_in_tracker'])) {
                $visible_in_tracker = 0;
            } else {
                $visible_in_tracker = 1;
            }

            if (empty($data['is_popular'])) {
                $is_popular = 0;
            } else {
                $is_popular = 1;
            }

            $slug = Str::slug($data['meal_type']);

            if ($request->has('image')) {
                $mealImage = time() . '.' . $data['image']->extension();
                if (!Storage::disk('public')->exists("/meal_type/image")) {
                    Storage::disk('public')->makeDirectory("/meal_type/image"); //creates directory
                }

                $request->image->storeAs("meal_type/image", $mealImage, 'public');
                $data['image'] = "meal_type/image/$mealImage";
            }

            $mealType = new MealType;
            $mealType->title = $data['title'];
            $mealType->meal_type = $data['meal_type'];
            $mealType->image = $data['image'];
            $mealType->slug = $slug;
            $mealType->visible_in_tracker = $visible_in_tracker;
            $mealType->is_popular = $is_popular;
            $mealType->save();
            return redirect('/admin/meal-type-index')->with('success', 'Meal Type Inserted Successfully !!!');
        }
        return view('admin.recipeDiet.meal_type.create');
    }

    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'mealType');
        $mealType = MealType::where(['id' => $id])->first();
        if ($request->isMethod('post')) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            $rules = [
                'title' => ['required', 'string', 'max:255'],
                'meal_type' => ['required', 'string', 'unique:meal_types,meal_type,' . $id],
            ];

            $message = [
                'title.required' => "Title is required.",
                'meal_type.required' => "Meal Type field cannot be empty.",
                'meal_type.unique' => "The meal type must be unique. This meal type has already been taken.",
            ];
            $validator = Validator::make($data, $rules, $message);
            $input = $request->except(['_token']);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if (empty($data['visible_in_tracker'])) {
                $visible_in_tracker = 0;
            } else {
                $visible_in_tracker = 1;
            }

            if (empty($data['is_popular'])) {
                $is_popular = 0;
            } else {
                $is_popular = 1;
            }

            $slug = Str::slug($data['meal_type']);
            // echo "<pre>";print_r($data);die;
            if (!isset($data['image']) || empty($data['image'])) {
                $data['image'] = $mealType['image'];
            } else {
                if (!Storage::disk('public')->exists("/meal_type/image")) {
                    Storage::disk('public')->makeDirectory("/meal_type/image"); //creates directory
                }
                if (empty($mealType['image'])) {
                    $mealImage = time() . '.' . $data['image']->extension();
                } else {
                    Storage::delete($mealType['image']);
                    $mealImage = time() . '.' . $data['image']->extension();
                }
                $request->image->storeAs("meal_type/image", $mealImage, 'public');
                $data['image'] = "meal_type/image/$mealImage";
            }

            MealType::where(['id' => $id])->update(['title' => $data['title'],'meal_type' => $data['meal_type'], 'image' => $data['image'], 'slug' => $slug, 'visible_in_tracker' => $visible_in_tracker, 'is_popular' => $is_popular]);
            return redirect('/admin/meal-type-index')->with('success', 'Meal Type updated successfully !!!');
        }
        return view('admin.recipeDiet.meal_type.create', ['mealType' => $mealType]);
    }

    public function updateMealTypeStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            if ($data['status'] == "Active") {
                $status = 0;
            } else {
                $status = 1;
            }
            MealType::where(['id' => $data['meal_type_id']])->update(['status' => $status]);

            return response()->json(['status' => $status, 'meal_type_id' => $data['meal_type_id']]);
        }
    }

    public function destroy($slug, $id)
    {
        Session::put('page', 'mealType');
        MealType::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back();
    }
}
