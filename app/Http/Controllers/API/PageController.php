<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PageController extends ApiController
{
    public function pageList(Request $request)
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
                    $getPageList = Page::where(function ($query) use ($search) {
                        $query->where('title', 'like', '%' . $search . '%')
                            ->orWhere('slug', 'like', '%' . $search . '%');
                    })
                        ->get()->toArray();
                } else {
                    $getPageList = Page::get()->toArray();
                }
                (int) $limit = 15;
                if (!isset($request->page) && (!isset($request->page))) {
                    (int) $page = 1;
                } else {
                    (int) $page = $request->page;
                }

                $result = $this->pagination($getPageList, $page, $limit);


                if (count($getPageList) > 0) {
                    return $this->successResponse("Page Found", $result['data'], 200);
                } else {
                    return $this->errorResponse("Page Not Found", [], 200);
                }
            } else {
                return $this->errorResponse("You does not have permission.", [], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(),500);
        }
    }
}
