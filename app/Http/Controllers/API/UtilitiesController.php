<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\VideoAbout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UtilitiesController extends ApiController
{
    public function videoAboutList(Request $request)
    {
        try {
            $videoAboutList = VideoAbout::get()->toArray();

            (int) $limit = 15;
                if (!isset($request->page) && (!isset($request->page))) {
                    (int) $page = 1;
                } else {
                    (int) $page = $request->page;
                }

                $result = $this->pagination($videoAboutList, $page, $limit);

                if (count($videoAboutList) > 0) {
                    return $this->successResponse("Records Found", $result['data'], 200);
                } else {
                    return $this->successResponse("Data Not Found", (object)[], 200);
                }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }

    public function cmsPageDetail(Request $request)
    {
        try {
            $validation = [
                'cms_page' => ['required', 'string'],
            ];
            $validation_messages = [
                'cms_page.required' => 'Please enter CMS Page name.',
            ];

            $validator = Validator::make($request->all(), $validation, $validation_messages);

            if ($validator->fails()) {
                return $this->validationError("Fail", $validator->errors()->first(), 200);
            }

            $data = $request->all();
            $cmsPage = Page::where('slug',$data['cms_page'])->first();
            
            if ($cmsPage) {
                if($cmsPage->image){
                    $cmsPage->image = asset(Storage::url($cmsPage->image));
                } else {
                    $cmsPage->image = '';
                }
                return $this->successResponse("Records Found", $cmsPage, 200);
            } else {
                return $this->successResponse("Data Not Found", (object)[], 200);
            }
        } catch (Throwable $th) {
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }

    }
}
