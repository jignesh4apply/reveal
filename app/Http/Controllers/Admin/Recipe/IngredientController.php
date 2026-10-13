<?php

namespace App\Http\Controllers\Admin\Recipe;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\UnitConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IngredientController extends Controller
{
    public function index()
    {
        Session::put('page', 'ingredients');
        $ingredientData = Ingredient::get()->toArray();
        // dd($categoryData);
        return view('admin.recipeDiet.ingredient.index', ['ingredientData' => $ingredientData]);
    }

    public function create(Request $request)
    {
        Session::put('page', 'ingredients');
        if ($request->isMethod('post')) {
            $data = $request->all();

            $validation = [
                'name' => ['required', 'string'],
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

            $slug = Str::slug($data['name']);

            $ingredient = new Ingredient;
            $ingredient->name = $data['name'];
            $ingredient->slug = $slug;
            $ingredient->save();
            return redirect('/admin/ingredient-index')->with('success', 'Ingredient Inserted Successfully !!!');
        }
        return view('admin.recipeDiet.ingredient.create');
    }

    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'ingredients');
        $ingredient = Ingredient::where(['id' => $id])->first();
        if ($request->isMethod('post')) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            $validation = [
                'name' => ['required', 'string'],
            ];
            $validator = Validator::make($data, $validation);
            $input = $request->except(['_token']);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            $slug = Str::slug($data['name']);

            Ingredient::where(['id' => $id])->update(['name' => $data['name'], 'slug' => $slug]);
            return redirect('/admin/ingredient-index')->with('success', 'Ingredient updated successfully !!!');
        }
        return view('admin.recipeDiet.ingredient.edit', ['ingredient' => $ingredient]);
    }

    public function destroy($slug, $id)
    {
        Session::put('page', 'ingredients');
        Ingredient::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back();
    }

    public function categoryIndex(Request $request)
    {
        Session::put('page', 'recipeIngredientCategory');
        $ingredientCategory = IngredientCategory::get()->toArray();
        return view('admin.recipeDiet.ingredient_category.index')->with(compact('ingredientCategory'));
    }

    public function categoryCreate(Request $request)
    {
        Session::put('page', 'recipeIngredientCategory');
        if ($request->isMethod('post')) {
            $data = $request->all();

            $validation = [
                'category_name' => ['required', 'string'],
                'category_image' => ['required', 'image', 'mimes:png,jpg,jpeg'],
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

            // Ingredient Image
            if ($request->has('category_image')) {
                $ingredientCategoryImage = time() . '.' . $data['category_image']->extension();
                if (!Storage::disk('public')->exists("/recipes/ingredientCategory")) {
                    Storage::disk('public')->makeDirectory("/recipes/ingredientCategory"); //creates directory
                }

                $request->category_image->storeAs("recipes/ingredientCategory", $ingredientCategoryImage, 'public');
                $data['category_image'] = "recipes/ingredientCategory/$ingredientCategoryImage";
            }

            $ingredientCategory = new IngredientCategory;
            $ingredientCategory->category_name = $data['category_name'];
            $ingredientCategory->slug = $slug;
            $ingredientCategory->category_image = $data['category_image'];
            $ingredientCategory->status = $status;
            $ingredientCategory->save();
            return redirect('/admin/ingredient-category-index')->with('success', 'Ingredient Category Inserted Successfully !!!');
        }
        return view('admin.recipeDiet.ingredient_category.create');
    }

    public function categoryUpdate(Request $request, $slug, $id)
    {
        Session::put('page', 'recipeIngredientCategory');
        $ingredientCategory = IngredientCategory::where(['id' => $id])->first();
        if ($request->isMethod('post')) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            $validation = [
                'category_name' => ['required', 'string'],
                'category_image' => ['image', 'mimes:png,jpg,jpeg'],
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

            // Recipe Image
            if (!isset($data['category_image']) || empty($data['category_image'])) {
                $data['category_image'] = $ingredientCategory->category_image;
            } else {
                if (!Storage::disk('public')->exists("/recipes/ingredientCategory")) {
                    Storage::disk('public')->makeDirectory("/recipes/ingredientCategory"); //creates directory
                }
                if (empty($ingredientCategory->category_image)) {
                    $ingredientCategoryImage = time() . '.' . $data['category_image']->extension();
                } else {
                    Storage::delete($ingredientCategory['category_image']);
                    $ingredientCategoryImage = time() . '.' . $data['category_image']->extension();
                }
                $request->category_image->storeAs("recipes/ingredientCategory", $ingredientCategoryImage, 'public');
                $data['category_image'] = "recipes/ingredientCategory/$ingredientCategoryImage";
            }

            IngredientCategory::where(['id' => $id])->update(['category_name' => $data['category_name'], 'slug' => $slug, 'category_image' => $data['category_image'], 'status' => $status]);
            return redirect('/admin/ingredient-category-index')->with('success', 'Ingredient updated successfully !!!');
        }
        return view('admin.recipeDiet.ingredient_category.edit', ['ingredientCategory' => $ingredientCategory]);
    }

    public function categoryDestroy($slug, $id)
    {
        Session::put('page', 'recipeIngredientCategory');
        IngredientCategory::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back();
    }
    public function addIngredientName(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $validation = [
                'ingredientName' => ['required', 'string'],
            ];
            $validator = Validator::make($data, $validation);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->getMessageBag()], 422);
            }

            // Check if an ingredient with the same name already exists
            $existingIngredient = Ingredient::where('name', $data['ingredientName'])->first();
            if ($existingIngredient) {
                return response()->json(['error' => 'Ingredient with the same name already exists.'], 409);
            }

            $slug = Str::slug($data['ingredientName']);

            $ingredient = new Ingredient;
            $ingredient->name = $data['ingredientName'];
            $ingredient->slug = $slug;
            $ingredient->save();

            return response()->json(['ingredient' => $ingredient]);
        }
    }
    public function fetchIngredients()
    {
        $ingredients = Ingredient::all();
        return response()->json(['ingredients' => $ingredients]);
    }
}
