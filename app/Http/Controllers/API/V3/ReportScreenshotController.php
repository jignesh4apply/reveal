<?php

namespace App\Http\Controllers\API\V3;

use App\Http\Controllers\API\ApiController;
use App\Models\UserMorphology;
use App\Models\UserMorphologyBehaviourTest;
use App\Services\CustomerIoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportScreenshotController extends ApiController
{
    /**
     * Upload report screenshots, store them, and trigger Customer.io email.
     */
    public function upload(Request $request)
    {
        try {
            $validated = $request->validate([
                'session_id' => ['required', 'string'],
                'files'      => ['required', 'array', 'max:10'],
                'files.*'    => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);

            $user = Auth::user();
            $sessionId = $validated['session_id'];

            // Look up session in user_morphologies first, then behaviour_tests
            $morphology = UserMorphology::where('session_id', $sessionId)
                ->where('user_id', $user->id)
                ->first();

            $behaviourTest = null;
            $reportType = null;

            if ($morphology) {
                $reportType = $morphology->type;
            } else {
                $behaviourTest = UserMorphologyBehaviourTest::where('session_id', $sessionId)
                    ->where('user_id', $user->id)
                    ->first();

                if ($behaviourTest) {
                    $reportType = 'Behaviour Test';
                }
            }

            if (!$morphology && !$behaviourTest) {
                return $this->errorResponse('Session not found for this user.', [], 404);
            }

            // Upload files
            $storagePath = "user/morphology/report-screenshots/{$sessionId}";
            if (!Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->makeDirectory($storagePath);
            }

            $screenshotUrls = [];

            foreach ($request->file('files') as $index => $file) {
                $extension = $file->getClientOriginalExtension();
                $fileName = $index . '_' . time() . '_' . Str::random(8) . '.' . $extension;

                $file->storeAs($storagePath, $fileName, 'public');

                $filePath = $storagePath . '/' . $fileName;
                $screenshotUrls[] = asset(Storage::url($filePath));
            }

            // Save screenshot URLs to the appropriate model
            if ($morphology) {
                $morphology->update(['report_screenshots' => $screenshotUrls]);
            } else {
                $behaviourTest->update(['report_screenshots' => $screenshotUrls]);
            }

            // Determine recipient email: check if there is any baseline report with a report_email for this user
            $recipientEmail = $user->email;
            $baselineReport = UserMorphology::where('user_id', $user->id)
                ->where('type', 'Baseline Report')
                ->whereNotNull('report_email')
                ->where('report_email', '<>', '')
                ->first();

            if ($baselineReport) {
                $recipientEmail = $baselineReport->report_email;
            }

            // Trigger Customer.io transactional email
            $customerIo = new CustomerIoService();
            $customerIo->sendTransactionalEmail(
                $recipientEmail,
                'report_shared',
                [
                    'user_id'     => (string) $user->id,
                    'user_name'   => $user->name ?? '',
                    'report_type' => $reportType,
                    'screenshots' => array_map(function ($url) {
                        return ['url' => $url];
                    }, $screenshotUrls),
                ]
            );

            return $this->successResponse(
                'Report screenshots uploaded and email triggered successfully.',
                [
                    'session_id'         => $sessionId,
                    'report_type'        => $reportType,
                    'screenshots'        => $screenshotUrls,
                    'email_sent_to'      => $recipientEmail,
                ],
                200
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed.', $e->errors(), 422);
        } catch (\Throwable $th) {
            Log::error("Report screenshot upload failed: " . $th->getMessage());
            return $this->throwableError("Something went wrong", $th->getMessage(), 500);
        }
    }
}
