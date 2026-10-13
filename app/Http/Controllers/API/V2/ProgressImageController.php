<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\API\ApiController;
use App\Models\UserProgressImage;
use App\Models\UsersAppearanceInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProgressImageController extends ApiController
{
    /**
     * Save or update the user's before/after image selection
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveProgressImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'before_image_id' => 'nullable|exists:users_appearance_infos,id',
            'after_image_id' => 'nullable|exists:users_appearance_infos,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError("Fail", $validator->errors()->first(), 200);
        }

        // At least one image must be provided
        if (!$request->filled('before_image_id') && !$request->filled('after_image_id')) {
            return $this->validationError("Fail", "At least one image ID (before_image_id or after_image_id) is required", 200);
        }

        $userId = Auth::id();

        // Verify that the images belong to the user
        $beforeImage = null;
        $afterImage = null;

        if ($request->filled('before_image_id')) {
            $beforeImage = UsersAppearanceInfo::where('id', $request->input('before_image_id'))
                ->where('user_id', $userId)
                ->first();

            if (!$beforeImage) {
                return $this->errorResponse('Before image not found or does not belong to the user', [],404);
            }
        }

        if ($request->filled('after_image_id')) {
            $afterImage = UsersAppearanceInfo::where('id', $request->input('after_image_id'))
                ->where('user_id', $userId)
                ->first();

            if (!$afterImage) {
                return $this->errorResponse('After image not found or does not belong to the user', [],404);
            }
        }

        if ($request->filled('before_image_id') && $request->filled('after_image_id') && $request->input('before_image_id') == $request->input('after_image_id')) {
            return $this->validationError(
                "Fail",
                "Before image and after image cannot be the same",
                200
            );
        }


        try {
            DB::beginTransaction();

            // Check if a progress image record already exists for this user
            $existingProgressImage = UserProgressImage::where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            if ($existingProgressImage) {
                $updateData = [];

                // If before_image_id is provided
                if ($request->filled('before_image_id')) {

                    // If same image is already set as AFTER → unset after
                    if ($existingProgressImage->after_image_id == $request->before_image_id) {
                        $updateData['after_image_id'] = null;
                    }

                    $updateData['before_image_id'] = $request->before_image_id;
                }

                // If after_image_id is provided
                if ($request->filled('after_image_id')) {

                    // If same image is already set as BEFORE → unset before
                    if ($existingProgressImage->before_image_id == $request->after_image_id) {
                        $updateData['before_image_id'] = null;
                    }

                    $updateData['after_image_id'] = $request->after_image_id;
                }

                $existingProgressImage->update($updateData);
                $progressImage = $existingProgressImage;

            } else {
                // Create new progress image record
                $progressImage = UserProgressImage::create([
                    'user_id' => $userId,
                    'before_image_id' => $request->input('before_image_id'),
                    'after_image_id'  => $request->input('after_image_id'),
                    'is_active' => true,
                ]);
            }

            DB::commit();

            // Auto-fill missing before/after images based on imageDate
            $totalImages = UsersAppearanceInfo::where('user_id', $userId)->count();
            $beforeId = $progressImage->before_image_id;
            $afterId = $progressImage->after_image_id;

            // If after_image_id is set but before_image_id is not, and there are more than 1 images
            if (!$beforeId && $afterId && $totalImages > 1) {
                $afterImage = UsersAppearanceInfo::find($afterId);
                if ($afterImage) {
                    $previousImage = UsersAppearanceInfo::where('user_id', $userId)
                        ->where('imageDate', '<', $afterImage->imageDate)
                        ->orderBy('imageDate', 'desc')
                        ->first();
                    if ($previousImage) {
                        $progressImage->update(['before_image_id' => $previousImage->id]);
                    }
                }
            }

            // If before_image_id is set but after_image_id is not
            if (!$afterId && $beforeId) {
                $latestImage = UsersAppearanceInfo::where('user_id', $userId)
                    ->orderBy('imageDate', 'desc')
                    ->first();
                // Set after_image_id to latest only if before_image_id is not the latest
                if ($latestImage && $latestImage->id != $beforeId) {
                    $progressImage->update(['after_image_id' => $latestImage->id]);
                }
            }

            $progressImage->load(['beforeImage', 'afterImage']);

            return $this->successResponse('Progress images saved successfully', [
                'progress_image' => $progressImage
            ],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to save progress images: ' . $e->getMessage(),[] ,500);
        }
    }

    /**
     * Get the user's active progress images
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProgressImages()
    {
        $userId = Auth::id();

        $progressImage = UserProgressImage::with(['beforeImage', 'afterImage'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        return $this->successResponse('Success', [
            'progress_image' => $progressImage
        ],400);
    }
}
