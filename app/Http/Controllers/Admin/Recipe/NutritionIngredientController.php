<?php

namespace App\Http\Controllers\Admin\Recipe;

use App\Http\Controllers\Controller;
use App\Models\NutritionIngredient;
use App\Models\NutritionIngredientBarcode;
use App\Models\NutritionIngredientCategory;
use App\Models\NutritionIngredientCategoryPortionSize;
use App\Models\NutritionIngredientUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;


class NutritionIngredientController extends Controller
{
    public function index(Request $request)
    {
        Session::put('page', 'nutrition-ingredients');

        if ($request->ajax()) {
            $ingredientData = NutritionIngredient::select(['id', 'name', 'kcal', 'slug', 'mark_as_reviewed']);
            return DataTables::of($ingredientData)
                ->addIndexColumn() // This adds the DT_RowIndex column
                ->addColumn('action', function ($row) {
                    $editUrl = url('admin/nutrition-ingredient-update/' . $row->slug . '/' . $row->id);
                    $deleteButton = "<a href='javascript:void(0);' class='btn btn-danger confirmDelete' module='nutrition-ingredient' slug='{$row->slug}' moduleId='{$row->id}' message='Nutrition Ingredient Deleted Successfully'><i class='fas fa-trash'></i></a>";
                    
                    return "<a href='{$editUrl}' class='btn btn-primary'><i class='fas fa-edit'></i></a> " . $deleteButton;
                })
                ->setRowClass(function ($row) {
                    return $row->mark_as_reviewed != 1 ? 'bg-warning' : '';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.recipeDiet.nutrition_ingredient.index');
    }
    public function create(Request $request)
    {
        Session::put('page', 'nutrition-ingredients');
        if ($request->isMethod('post')) {
            $data = $request->all();
            $validation = [
                'name' => ['required', 'string'],
                'kcal' => ['required', 'string'],
                'protein' => ['required', 'string'],
                'fats' => ['required', 'string'],
                'carbs' => ['required', 'string'],
                'sugar' => ['required', 'string'],
                'salt' => ['required', 'string'],
                'small_image_url' => ['nullable', 'string'],
                'image_url' => ['nullable', 'string'],
            ];
            $validator = Validator::make($data, $validation);
            $input = $request->except(['_token']);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }
            if (!isset($data['barcodes_list']) || is_null($data['barcodes_list'])) {
                return redirect()->back()->withInput()->withErrors( "Please enter atleast one barcode for this ingredient");
            }

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }
            if (empty($data['mark_as_reviewed'])) {
                $mark_as_reviewed = 0;
            } else {
                $mark_as_reviewed = 1;
            }

            $slug = Str::slug($data['name']);

            $ingredient = new NutritionIngredient;
            $ingredient->name = $data['name'];
            $ingredient->kcal = $data['kcal'];
            $ingredient->protein = $data['protein'];
            $ingredient->fats = $data['fats'];
            $ingredient->carbs = $data['carbs'];
            $ingredient->sugar = $data['sugar'];
            $ingredient->salt = $data['salt'];
            $ingredient->small_image_url = $data['small_image_url'];
            $ingredient->image_url = $data['image_url'];
            $ingredient->slug = $slug;
            $ingredient->mark_as_reviewed = $mark_as_reviewed;
            $ingredient->status = $status;

            if ($ingredient->save()) {
                if(!empty($data['barcodes_list'])){
                    foreach ($data['barcodes_list'] as $barcode) {
                        // Only save if barcode is not null or empty
                        if (!empty($barcode['barcode'])) {
                            $ingredientBarcode = new NutritionIngredientBarcode;
                            $ingredientBarcode->nutrition_ingredient_id = $ingredient->id;
                            $ingredientBarcode->barcode = $barcode['barcode'];
                            $ingredientBarcode->save();
                        }
                    }
                }

                if(!empty($data['serving_units'])) {
                    foreach ($data['serving_units'] as $unit) {
                        $IngredientUnit = new NutritionIngredientUnit;
                        $IngredientUnit->nutrition_ingredient_id = $ingredient->id;
                        $IngredientUnit->size_key = $unit['size_key'];
                        $IngredientUnit->value = $unit['value'];
                        $IngredientUnit->units = $unit['units'];
                        $IngredientUnit->save();
                    }
                }
            }


            return redirect('/admin/nutrition-ingredient-index')->with('success', 'Nutrition Ingredient Inserted Successfully !!!');
        }
        return view('admin.recipeDiet.nutrition_ingredient.create');
    }
    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'nutrition-ingredients');
        $ingredient = NutritionIngredient::where(['id' => $id])->first();
        $ingredientBarcodes = NutritionIngredientBarcode::where(['nutrition_ingredient_id' => $id])->get();
        $ingredientServingUnits = NutritionIngredientUnit::where(['nutrition_ingredient_id' => $id])->get();
        if ($request->isMethod('post')) {
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            $validation = [
                'name' => ['required', 'string'],
                'kcal' => ['required', 'string'],
                'protein' => ['required', 'string'],
                'fats' => ['required', 'string'],
                'carbs' => ['required', 'string'],
                'sugar' => ['required', 'string'],
                'salt' => ['required', 'string'],
                'small_image_url' => ['nullable', 'string'],
                'image_url' => ['nullable', 'string'],
            ];
            $validator = Validator::make($data, $validation);
            $input = $request->except(['_token']);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }
            if (!isset($data['barcodes_list']) || is_null($data['barcodes_list'])) {
                return redirect()->back()->withInput()->withErrors( "Please enter atleast one barcode for this ingredient");
            }

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }
            if (empty($data['mark_as_reviewed'])) {
                $mark_as_reviewed = 0;
            } else {
                $mark_as_reviewed = 1;
            }

            $slug = Str::slug($data['name']);

            NutritionIngredient::where(['id' => $id])
                ->update([
                    'name' => $data['name'],
                    'kcal' => $data['kcal'], 
                    'protein' => $data['protein'], 
                    'fats' => $data['fats'], 
                    'carbs' => $data['carbs'], 
                    'sugar' => $data['sugar'], 
                    'salt' => $data['salt'], 
                    'small_image_url' => $data['small_image_url'], 
                    'image_url' => $data['image_url'], 
                    'slug' => $slug, 
                    'mark_as_reviewed' => $mark_as_reviewed,
                    'status' => $status
                ]);

            NutritionIngredientBarcode::where(['nutrition_ingredient_id' => $id])->delete();
            if(!empty($data['barcodes_list'])){
                foreach ($data['barcodes_list'] as $barcode) {
                    // Only save if barcode is not null or empty
                    if (!empty($barcode['barcode'])) {
                        $NutriIngreBarcode = new NutritionIngredientBarcode;
                        $NutriIngreBarcode->nutrition_ingredient_id = $id;
                        $NutriIngreBarcode->barcode = $barcode['barcode'];
                        $NutriIngreBarcode->save();
                    }
                }
            }
            NutritionIngredientUnit::where(['nutrition_ingredient_id' => $id])->delete();
            if(!empty($data['serving_units'])){
                foreach ($data['serving_units'] as $unit) {
                    $NutriIngreServingUnits = new NutritionIngredientUnit;
                    $NutriIngreServingUnits->nutrition_ingredient_id = $id;
                    $NutriIngreServingUnits->size_key = $unit['size_key'];
                    $NutriIngreServingUnits->value = $unit['value'];
                    $NutriIngreServingUnits->units = $unit['units'];
                    $NutriIngreServingUnits->save();
                }
            }

            return redirect('/admin/nutrition-ingredient-index')->with('success', 'Nutrition Ingredient updated successfully !!!');
        }
        return view('admin.recipeDiet.nutrition_ingredient.edit', ['ingredient' => $ingredient, 'barcodes' => $ingredientBarcodes, 'servingUnits' => $ingredientServingUnits]);
    }
    public function destroy($slug, $id)
    {
        Session::put('page', 'nutrition-ingredients');
        NutritionIngredient::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back();
    }

    public function categoryIndex(Request $request){
        Session::put('page', 'nutrition-ingredients-category');
        $categoryData = NutritionIngredientCategory::with('portionSizes')->get();
        // dd($categoryData->get());
        return view('admin.recipeDiet.nutrition_ingredient.category.index', ['categoryData' => $categoryData]);
    }

    public function categoryCreate(Request $request){
        Session::put('page', 'nutrition-ingredients-category');
        if($request->isMethod('post')){
            $data = $request->all();
            $validation = [
                'name' => ['required', 'string'],
                'image_url' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
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
            $imgUrl = null;

            if ($request->has('image_url')) {
                $imgUrl = time() . '.' . $data['image_url']->extension();
                if (!Storage::disk('public')->exists("/nutrition-ingredient-category")) {
                    Storage::disk('public')->makeDirectory("/nutrition-ingredient-category"); //creates directory
                }
                $request->image_url->storeAs("nutrition-ingredient-category", $imgUrl, 'public');
                $imgUrl = "nutrition-ingredient-category/$imgUrl";
            }
            $category = NutritionIngredientCategory::create([
                'name' => $data['name'],
                'slug' => $slug,
                'image_url' => $imgUrl,
                'status' => $status
            ]);

            if(!empty($data['portion_sizes'])){
                foreach ($data['portion_sizes'] as $portionSize) {
                    $tips = new NutritionIngredientCategoryPortionSize;
                    $tips->nutrition_ingredient_category_id = $category->id;
                    $tips->name = $portionSize['name'];
                    $tips->quantity = $portionSize['quantity'];
                    $tips->units = $portionSize['units'];
                    $tips->save();
                }
            }

            return redirect('/admin/nutrition-ingredient-category-index')->with('success', 'Nutrition Ingredient Category Inserted Successfully !!!');
        }
        return view('admin.recipeDiet.nutrition_ingredient.category.create');
    }

    public function categoryUpdate(Request $request, $slug, $id){
        Session::put('page', 'nutrition-ingredients-category');
        $category = NutritionIngredientCategory::where(['id' => $id])->with('portionSizes')->first();

        if($request->isMethod('post')) {
            $data = $request->all();
            $validation = [
                'name' => ['required', 'string'],
                'image_url' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
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
            if ($request->has('image_url')) {
                // Delete existing image from local folder
                if ($category->image_url && Storage::disk('public')->exists($category->image_url)) {
                    Storage::disk('public')->delete($category->image_url);
                }

                $imgUrl = time() . '.' . $data['image_url']->extension();
                if (!Storage::disk('public')->exists("/nutrition-ingredient-category")) {
                    Storage::disk('public')->makeDirectory("/nutrition-ingredient-category"); //creates directory
                }
                $request->image_url->storeAs("nutrition-ingredient-category", $imgUrl, 'public');
                $imgUrl = "nutrition-ingredient-category/$imgUrl";
            } else {
                $imgUrl = $category->image_url;
            }
            NutritionIngredientCategory::where(['id' => $id])
                ->update([
                    'name' => $data['name'],
                    'slug' => $slug,
                    'image_url' => $imgUrl,
                    'status' => $status
                ]);
            NutritionIngredientCategoryPortionSize::where(['nutrition_ingredient_category_id' => $id])->delete();
            if(!empty($data['portion_sizes'])){
                foreach ($data['portion_sizes'] as $portionSize) {
                    $tips = new NutritionIngredientCategoryPortionSize;
                    $tips->nutrition_ingredient_category_id = $id;
                    $tips->name = $portionSize['name'];
                    $tips->quantity = $portionSize['quantity'];
                    $tips->units = $portionSize['units'];
                    $tips->save();
                }
            }
            return redirect('/admin/nutrition-ingredient-category-index')->with('success', 'Nutrition Ingredient Category updated successfully !!!');
        }
        return view('admin.recipeDiet.nutrition_ingredient.category.edit', ['category' => $category]);
    }

    public function categoryDestroy($slug, $id){
        Session::put('page', 'nutrition-ingredients-category');
        NutritionIngredientCategory::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back();
    }
}
