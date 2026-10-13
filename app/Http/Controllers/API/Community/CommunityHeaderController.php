<?php

namespace App\Http\Controllers\API\Community;

use App\Http\Controllers\API\ApiController;
use App\Models\CommunityHeader;
use Illuminate\Http\Request;

class CommunityHeaderController extends ApiController
{
    public function fetchCommunityHeader()
    {
        // Fetch the first record
        $communityHeader = CommunityHeader::first();

        if (!$communityHeader) {
            return $this->successResponse("Community header not found.", [], 200);
        }
        $header = [
            'title' => $communityHeader->title,
            'description' => $communityHeader->description,
            'button_text' => $communityHeader->button_text ?? '',
            'button_type' => $communityHeader->button_type ?? '',
            'button_url' => $communityHeader->button_url ?? '',
            'button_redirect_to' => $communityHeader->button_redirect_to ?? '',
            'image' => $communityHeader->image ? asset('storage/' . $communityHeader->image) : ''
        ];
        // If the record exists, return it as a JSON response
        return $this->successResponse("Record Found", $header, 200);
    }
}
