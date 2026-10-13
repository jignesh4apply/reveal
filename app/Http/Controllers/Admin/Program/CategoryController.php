<?php

namespace App\Http\Controllers\Admin\Program;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        Session::put('page', 'categories');
        $categoryData = Category::get()->toArray();
        // dd($categoryData);
        return view('admin.programs.category.index')->with(compact('categoryData'));
    }

    public function create(Request $request)
    {
        Session::put('page', 'categories');
        if ($request->isMethod('post')) {
            $data = $request->all();

            $validation = [
                'category_name' => ['required', 'string', 'nullable', 'max:255'],
            ];
            $validator = Validator::make($data, $validation);
            $input = $request->except(['_token']);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }

            $slug = Str::slug($data['category_name']);

            $category = new Category;
            $category->category_name = $data['category_name'];
            $category->description = $data['description'] ?? '';
            $category->slug = $slug;
            $category->status = $status;
            $category->save();
            return redirect('/admin/category-index')->with('success', 'Category Inserted Successfully !!!');
        }
        return view('admin.programs.category.create');
    }

    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'categories');
        if ($request->isMethod('post')) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            $validation = [
                'category_name' => ['required', 'string', 'nullable', 'max:255'],
            ];
            $validator = Validator::make($data, $validation);
            $input = $request->except(['_token']);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }

            $slug = Str::slug($data['category_name']);

            $updateCategory = Category::where(['id' => $id])->update(['category_name' => $data['category_name'], 'slug' => $slug, 'description' => $data['description'] ?? "", 'status' => $status]);
            return redirect('/admin/category-index')->with('success', 'Category updated successfully !!!');
        }

        $category = Category::where(['slug' => $slug, 'id' => $id])->first();
        return view('admin.programs.category.edit')->with(compact('category'));
    }

    public function updateCategoryStatus(Request $request)
    {
        Session::put('page', 'categories');
        if ($request->ajax()) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            if ($data['status'] == "Active") {
                $status = 0;
            } else {
                $status = 1;
            }
            Category::where(['id' => $data['category_id']])->update(['status' => $status]);

            return response()->json(['status' => $status, 'category_id' => $data['category_id']]);
        }
    }

    public function destroy($slug, $id)
    {
        Session::put('page', 'categories');
        Category::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(2);
        return redirect()->back();
    }
}
