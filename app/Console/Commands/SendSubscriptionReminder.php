<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UsersSubscriptions;
use App\Services\CustomerIoService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:remind';
    protected $description = 'Send subscription reminder emails to users 7 days before expiration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = Carbon::now()->addDays(7)->toDateString();

        Log::channel('subscription_reminder')->info("Command started for date: {$targetDate}");

        $subscriptions = UsersSubscriptions::whereDate('end_date', $targetDate)->get();

        if ($subscriptions->isEmpty()) {
            Log::channel('subscription_reminder')->info("No users found.");
            return;
        }

        foreach ($subscriptions as $subscription) {

            $user = User::find($subscription->user_id);

            if (!$user || !$user->email) {
                Log::channel('subscription_reminder')->warning("User missing for subscription ID: {$subscription->id}");
                continue;
            }

            try {
                $customerIo = new CustomerIoService();

                $response = $customerIo->sendTransactionalEmail(
                    $user->email,
                    '11',
                    [
                        'name' => $user->name ?? "",
                        'endDate' => $subscription->end_date
                    ]
                );

                Log::channel('subscription_reminder')->info("Email sent", [
                    'email' => $user->email,
                    'subscription_id' => $subscription->id,
                    'response' => $response
                ]);

            } catch (\Exception $e) {

                Log::channel('subscription_reminder')->error("Email failed", [
                    'email' => $user->email,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::channel('subscription_reminder')->info("Command finished.");
    }
}
