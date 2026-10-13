<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class CommunityHeaderController extends Controller
{
    public function communityHeader(Request $request)
    {
        Session::put('page', 'communityHeaders');
        $communityHeader = CommunityHeader::first();

        if ($request->isMethod('get')) {
            return view('admin.community.header_form')->with([
                'header' => $communityHeader,
            ]);
        }

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'button_text' => 'required|string|max:100',
                'button_type' => 'required|string|max:100',
                'button_url' => 'required_without:button_redirect_to|string|max:255|nullable',
                'button_redirect_to' => 'required_without:button_url|string|max:255|nullable',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            ]);

            // Handle image upload if present
            $imagePath = null;
            if ($request->hasFile('image')) {
                // Check if an old image exists and delete it
                if ($communityHeader && $communityHeader->image) {
                    Storage::delete('public/' . $communityHeader->image);
                }

                // Upload the new image
                $image = $request->file('image');
                $imagePath = $image->store('community_headers', 'public');
            }

            // Check if a CommunityHeader record exists
            // Determine which button URL to use based on button_type
            $buttonUrl = null;
            $buttonRedirectTo = null;

            if ($validated['button_type'] === 'app') {
                $buttonRedirectTo = $validated['button_redirect_to'];
                $buttonUrl = null;
            } elseif ($validated['button_type'] === 'external') {
                $buttonUrl = $validated['button_url'];
                $buttonRedirectTo = null;
            }

            $data = [
                'title' => $validated['title'],
                'description' => $validated['description'],
                'button_text' => $validated['button_text'],
                'button_type' => $validated['button_type'],
                'button_url' => $buttonUrl,
                'button_redirect_to' => $buttonRedirectTo,
                'image' => $imagePath ?? ($communityHeader ? $communityHeader->image : null),
            ];

            if ($communityHeader) {
                $communityHeader->update($data);
            } else {
                CommunityHeader::create($data);
            }

            return redirect()->back()->with('success', 'Community Header saved successfully.');
        }
    }


}
