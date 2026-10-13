<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PageController extends Controller
{
    // public function json()
    // {
    //     $pages = Page::all(['id', 'image','title', 'slug', 'created_at']);

    //     return DataTables::of($pages)
    //         ->addColumn('actions', function ($page) {
    //             $actions = "";
    //             $actions .= "<a href='" . route('admin.pages.edit', $page->id) . "' class='btn btn-primary' title='Edit'><i class='bi bi-pencil-square'></i></a>&nbsp;";
    //             $actions .= "<a href='javascript:void(0);' onclick='deletePage($page->id)' class='btn btn-danger' title='Delete'><i class='bi bi-trash'></i></a>";

    //             return $actions;
    //         })
    //         ->editColumn('title', function ($page) {
    //             $image = "<img src='".asset('assets/img/default.png')."' alt='Avatar' width='60px' class='rounded img-thumbnail'>";
    //             if (! empty($page->image)) {
    //                 $image = "<img src='".asset("storage/$page->image")."' alt='Avatar' width='60px' class='rounded img-thumbnail'>";
    //             }

    //             return "$image $page->title";
    //         })
    //         ->editColumn('created_at', function ($page) {
    //             return $page->created_at->format('d-m-Y H:i:s');
    //         })
    //         ->rawColumns(['title', 'actions'])
    //         ->make(true);
    // }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Session::put('page', 'pages');
        $pages = Page::all(['id', 'image', 'title', 'slug', 'created_at']);
        return view('admin.pages.index', ['pages' => $pages]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        Session::put('page', 'pages');
        if ($request->isMethod('post')) {
            $data = $request->all();
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                // 'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
                'content' => ['required']
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }

            $page = new Page();
            $page->title = $request->title;
            $page->slug = Str::slug($request->title, '-');
            $page->content = $request->content;

            if ($request->has('image')) {
                $pageImage = time() . '.' . $data['image']->extension();
                if (!Storage::disk('public')->exists("/page")) {
                    Storage::disk('public')->makeDirectory("/page"); //creates directory
                }

                $request->image->storeAs("page", $pageImage, 'public');
                $page->image = "page/$pageImage";
            }
            $page->save();

            return redirect('admin/page-index')->with('success', 'Page created successfully');
        }
        return view('admin.pages.create');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'pages');
        $page = Page::where(['id' => $id, 'slug' => $slug])->first();
        if($request->isMethod('post')){
            $data = $request->all();

            // echo "<pre>"; print_r($data); die;
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->getMessageBag());
            }
            $slug = Str::slug($request->title, '-');

            if (!isset($data['image']) || empty($data['image'])) {
                $data['image'] = $page->image;
            } else {
                if (!Storage::disk('public')->exists("/page")) {
                    Storage::disk('public')->makeDirectory("/page"); //creates directory
                }
                if (empty($page->image)) {
                    $pageImage = time() . '.' . $data['image']->extension();
                } else {
                    Storage::delete($page['image']);
                    $pageImage = time() . '.' . $data['image']->extension();
                }
                $request->image->storeAs("page/", $pageImage, 'public');
                $data['image'] = "page/$pageImage";
            }

            Page::where(['id' => $id])->update(['title' => $data['title'], 'slug' => $slug, 'content' => $data['content'], 'image' => $data['image']]);
            return redirect('admin/page-index')->with('success', "Page updated successfully");
        }
        return view('admin.pages.create',['page' => $page]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($slug, $id)
    {
        Session::put('page', 'pages');
        Page::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(2);
        return redirect()->back();
    }
}
