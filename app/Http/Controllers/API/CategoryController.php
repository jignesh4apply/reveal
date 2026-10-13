<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class CategoryController extends ApiController
{
    public function categoryList(Request $request)
    {
        try {
            /* Validate Data */
            $validation = [
                'search' => ['nullable', 'string'],
            ];
            $validator = Validator::make($request->all(), $validation);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            if (Auth::check()) {
                if ($request->has('search') && !empty($request->search)) {
                    $search = $request->search;
                    $getCategoryList = Category::where(function ($query) use ($search) {
                        $query->where('category_name', 'like', '%' . $search . '%');
                    })
                        ->where(['status' => 1])
                        ->get()->toArray();
                } else {
                    $getCategoryList = Category::where(['status' => 1])->get()->toArray();
                }
                (int) $limit = 15;
                if (!isset($request->page) && (!isset($request->page))) {
                    (int) $page = 1;
                } else {
                    (int) $page = $request->page;
                }

                $result = $this->pagination($getCategoryList, $page, $limit);


                if (count($getCategoryList) > 0) {

                    return response()->json(
                        [
                            "message" => "Category Found",
                            "data" => $result['data'],
                            "hasMore" => $result['hasMore'],
                            "status" => "success",
                        ],
                        200
                    );
                } else {
                    return $this->errorResponse("Category Not Found", [], 200);
                }
            } else {
                return $this->successResponse("You does not have permission.");
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function createCategory(Request $request)
    {
        try {
            $data = $request->all();
            $validation = [
                'category_name' => ['required', 'string', 'max:50'],
                'description' => ['required', 'string', 'max:300'],
            ];
            $validation_messages = [
                'category_name.required' => 'Please enter category name.',
                'category_name.max' => 'Category name should not be greater than 50 characters.',
                'description.required' => 'Please write a short description about category.',
                'description.max' => 'Category description should not be greater than 300 characters.',
            ];
            $validator = Validator::make($request->all(), $validation, $validation_messages);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }

            $slug = Str::slug($data['category_name']);

            if (!isset($data['description']) || empty($data['description'])) {
                $data['description'] = "";
            }

            $category = new Category;
            $category->category_name = $data['category_name'];
            $category->description = $data['description'];
            $category->slug = $slug;
            $category->status = 1;
            if ($category->save()) {
                return $this->getSingleResponse("Category Created Successfully", $category, 200);
            } else {
                return $this->errorResponse("Something went wrong. Please try again", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function updateCategory(Request $request)
    {
        try {
            $data = $request->all();

            $validation = [
                'category_id' => ['required', 'numeric'],
                'category_name' => ['required', 'string', 'max:50'],
                'description' => ['required', 'string', 'max:300'],
            ];
            $validation_messages = [
                'category_id.required' => 'Please provide category ID.',
                'category_id.numeric' => 'Category ID should be in numbers.',
                'category_name.required' => 'Please enter category name.',
                'category_name.max' => 'Category name should not be greater than 50 characters.',
                'description.required' => 'Please write a short description about category.',
                'description.max' => 'Category description should not be greater than 300 characters.',
            ];
            $validator = Validator::make($request->all(), $validation, $validation_messages);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $category = Category::where(['id' => $data['category_id']])->first();

            if (empty($data['status'])) {
                $status = 0;
            } else {
                $status = 1;
            }

            if (!isset($data['description']) || empty($data['description'])) {
                $data['description'] = "";
            }

            $slug = Str::slug($data['category_name']);

            $updateCategory = Category::where(['id' => $category->id])->update(['category_name' => $data['category_name'], 'description' => $data['description'], 'slug' => $slug, 'status' => $status]);

            if ($updateCategory) {
                $categoryData = Category::where('id', $category->id)->first();
                return $this->getSingleResponse("Category Updated Successfully", $categoryData, 200);
            } else {
                return $this->errorResponse("Something went wrong. Please try again", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function deleteCategory(Request $request)
    {
        try {
            $data = $request->all();

            $validation = [
                'category_id' => ['required', 'numeric'],
            ];
            $validation_messages = [
                'category_id.required' => 'Please provide category ID.',
                'category_id.numeric' => 'Category ID should be in numbers.',
            ];
            $validator = Validator::make($request->all(), $validation, $validation_messages);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $category = Category::where('id', $data['category_id'])->first();

            if ($category) {
                if ($category->delete()) {
                    return response()->json(
                        [
                            "message" => "Category Deleted Successfully",
                            "data" => (object)[],
                            "status" => "success",
                        ],
                        200
                    );
                } else {
                    return $this->errorResponse("Something went wrong. Please try again", [], 200);
                }
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}
