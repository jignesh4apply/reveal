<?php

namespace App\Services\GooglePlay;

use RuntimeException;

/**
 * Google answered 409: this externalTransactionId was already reported (e.g. a retry after a timeout).
 */
class GooglePlayAlreadyReportedException extends RuntimeException
{
}
