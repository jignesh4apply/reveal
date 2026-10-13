<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\UserMorphology;
use App\Models\UserMorphologyBehaviourTest;
use App\Models\User;
use App\Services\FCMService;
use App\Services\CustomerIoService;

class SendBehaviorTestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-behavior-test-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends push notifications and Customer.io emails based on baseline report generation date';

    /**
     * Execute the console command.
     */
    public function handle(FCMService $fcmService, CustomerIoService $customerIoService)
    {
        $this->info('Starting behavior test notification workflow...');

        // 1. Notification 1: exactly 14 days after baseline report generation
        $fourteenDaysAgo = Carbon::today()->subDays(14);
        $morphologies14 = UserMorphology::whereDate('report_generated_at', $fourteenDaysAgo)->get();

        foreach ($morphologies14 as $morphology) {
            $user = User::find($morphology->user_id);
            if ($user) {
                // Push Notification
                if ($user->device_token) {
                    $title = "Behavior Test Available Tomorrow!";
                    $body = "You have completed your 14-day baseline period. Your Behavior Test will be available tomorrow.";
                    $fcmService->pushNotification($title, $body, $user->device_token, [
                        "type" => "behaviour_test",
                        "session_id" => $morphology->session_id
                    ]);
                }

                // Customer.io Email
                if ($user->email) {
                    $customerIoService->sendTransactionalEmail($user->email, "day_14_pre_availability_notification", [
                        'name' => $user->name,
                        'deep_link' => "reveal://behaviour-test/" . $morphology->session_id
                    ]);
                }
            }
        }

        // 2. Notification 2: exactly 15 days after baseline report generation
        $fifteenDaysAgo = Carbon::today()->subDays(15);
        $morphologies15 = UserMorphology::whereDate('report_generated_at', $fifteenDaysAgo)->get();

        foreach ($morphologies15 as $morphology) {
            $user = User::find($morphology->user_id);
            if ($user) {
                // Push Notification
                if ($user->device_token) {
                    $title = "Behavior Test Now Open!";
                    $body = "Your Behavior Test is now open. Take the test to receive your personalized report.";
                    $fcmService->pushNotification($title, $body, $user->device_token, [
                        "type" => "behaviour_test",
                        "session_id" => $morphology->session_id
                    ]);
                }

                // Customer.io Email
                if ($user->email) {
                    $customerIoService->sendTransactionalEmail($user->email, "day_15_behavior_test_available", [
                        'name' => $user->name,
                        'deep_link' => "reveal://behaviour-test/" . $morphology->session_id
                    ]);
                }
            }
        }

        // 3. Notification 3: Reminder for Uncompleted Behavior Tests
        $reminderDays = 5;
        $reminderDate = Carbon::today()->subDays(15 + $reminderDays);
        $morphologiesReminder = UserMorphology::whereDate('report_generated_at', $reminderDate)->get();

        foreach ($morphologiesReminder as $morphology) {
            $user = User::find($morphology->user_id);
            if ($user) {
                // Check if behavior test is completed
                $isCompleted = UserMorphologyBehaviourTest::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->exists();

                if (!$isCompleted) {
                    // Push Notification
                    if ($user->device_token) {
                        $title = "Reminder: Complete Your Behavior Test";
                        $body = "Don't forget to complete your Behavior Test to generate your personalized report.";
                        $fcmService->pushNotification($title, $body, $user->device_token, [
                            "type" => "behaviour_test",
                            "session_id" => $morphology->session_id
                        ]);
                    }

                    // Customer.io Email
                    if ($user->email) {
                        $customerIoService->sendTransactionalEmail($user->email, "reminder_to_complete_behaviour_test", [
                            'name' => $user->name,
                            'days_since_available' => $reminderDays,
                            'deep_link' => "reveal://behaviour-test/" . $morphology->session_id
                        ]);
                    }
                }
            }
        }

        $this->info('Behavior test notifications dispatched successfully.');
    }
}
