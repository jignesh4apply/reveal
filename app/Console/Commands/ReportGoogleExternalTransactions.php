<?php

namespace App\Console\Commands;

use App\Services\GooglePlay\ExternalOfferReporter;
use Illuminate\Console\Command;

/**
 * Retries Google Play external transaction reports that are pending or failed,
 * and logs a critical error for any that approach Google's 24-hour limit.
 */
class ReportGoogleExternalTransactions extends Command
{
    protected $signature = 'google-play:report-external-transactions';

    protected $description = 'Send pending/failed Google Play external offer transactions to Google';

    public function handle(ExternalOfferReporter $reporter): int
    {
        $reporter->sendPending();

        return self::SUCCESS;
    }
}
