<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A Google Play external offer token received from the Android app (?ext_token=...).
 */
class GoogleExternalOfferLink extends Model
{
    protected $table = 'google_external_offer_links';

    protected $fillable = [
        'ref',
        'token_hash',
        'token',
        'source',
        'user_id',
    ];

    protected $hidden = [
        'token',
    ];
}
