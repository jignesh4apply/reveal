<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A payment or refund reported (or to be reported) to Google Play's externaltransactions API.
 */
class GoogleExternalTransaction extends Model
{
    public const TYPE_INITIAL = 'initial';
    public const TYPE_RENEWAL = 'renewal';
    public const TYPE_REFUND = 'refund';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENDING = 'sending';
    public const STATUS_REPORTED = 'reported';
    public const STATUS_FAILED = 'failed';
    public const STATUS_NEEDS_ATTENTION = 'needs_attention';

    protected $table = 'google_external_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'offer_link_id',
        'stripe_subscription_id',
        'stripe_invoice_id',
        'stripe_refund_id',
        'refund_of_id',
        'is_full_refund',
        'external_transaction_id',
        'initial_external_transaction_id',
        'gross_amount_cents',
        'pre_tax_micros',
        'tax_micros',
        'currency',
        'region_code',
        'transaction_time',
        'status',
        'attempts',
        'last_error',
        'google_response',
        'reported_at',
    ];

    protected $casts = [
        'is_full_refund' => 'boolean',
        'transaction_time' => 'datetime',
        'reported_at' => 'datetime',
        'google_response' => 'array',
    ];

    public function offerLink()
    {
        return $this->belongsTo(GoogleExternalOfferLink::class, 'offer_link_id');
    }

    public function refundOf()
    {
        return $this->belongsTo(self::class, 'refund_of_id');
    }
}
