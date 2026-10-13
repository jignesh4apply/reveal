<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupSubscriptionDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:cleanup-duplicates';

    /**
     * The console command description.
     */
    protected $description = 'Delete duplicate subscription records and keep only one per group';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Scanning for duplicate subscription records...");

        // Step 1: Find duplicate groups
        $duplicates = DB::table('users_subscriptions')
            ->select(
                'user_id', 'plan_id', 'next_plan_id', 'customer_id', 'subscription_id',
                'amount', 'billing_cycle', 'status', 'is_refunded', 'is_applied_for_trial',
                'is_applied_for_cancel', 'is_applied_for_discount', 'start_date', 'end_date',
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy(
                'user_id', 'plan_id', 'next_plan_id', 'customer_id', 'subscription_id',
                'amount', 'billing_cycle', 'status', 'is_refunded', 'is_applied_for_trial',
                'is_applied_for_cancel', 'is_applied_for_discount', 'start_date', 'end_date'
            )
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info("No duplicates found. Everything is clean! 🎉");
            return;
        }

        $this->info("Found " . $duplicates->count() . " duplicate groups.");
        $totalDeleted = 0;
        dd($duplicates);
        foreach ($duplicates as $dup) {

            // Step 2: Get all records of this group ordered by ID (keep lowest ID)
            $records = DB::table('users_subscriptions')
                ->where('user_id', $dup->user_id)
                ->where('plan_id', $dup->plan_id)
                ->where('next_plan_id', $dup->next_plan_id)
                ->where('customer_id', $dup->customer_id)
                ->where('subscription_id', $dup->subscription_id)
                ->where('amount', $dup->amount)
                ->where('billing_cycle', $dup->billing_cycle)
                ->where('status', $dup->status)
                ->where('is_refunded', $dup->is_refunded)
                ->where('is_applied_for_trial', $dup->is_applied_for_trial)
                ->where('is_applied_for_cancel', $dup->is_applied_for_cancel)
                ->where('is_applied_for_discount', $dup->is_applied_for_discount)
                ->where('start_date', $dup->start_date)
                ->where('end_date', $dup->end_date)
                ->orderBy('id', 'asc')
                ->get();

            // Step 3: Keep the first record, delete the rest
            $idsToDelete = $records->pluck('id')->slice(1)->toArray();

            if (!empty($idsToDelete)) {
                DB::table('users_subscriptions')
                    ->whereIn('id', $idsToDelete)
                    ->delete();

                $this->info("Deleted " . count($idsToDelete) . " duplicates for user_id {$dup->user_id}");
                $totalDeleted += count($idsToDelete);
            }
        }

        $this->info("--------------------------------------------------");
        $this->info("Duplicate cleanup completed.");
        $this->info("Total duplicate records deleted: $totalDeleted");
        $this->info("--------------------------------------------------");

        return 0;
    }
}
