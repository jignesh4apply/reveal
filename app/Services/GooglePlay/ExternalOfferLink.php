<?php

namespace App\Services\GooglePlay;

use App\Models\GoogleExternalOfferLink;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Captures the Google Play external offer token sent by the Android app (?ext_token=...)
 * and provides the Stripe subscription metadata that links a subscription to it.
 */
class ExternalOfferLink
{
    public const QUERY_PARAM = 'ext_token';
    public const SESSION_KEY = 'google_offer_ref';

    /** Stripe metadata key on a new subscription (first purchase). */
    public const META_OFFER_REF = 'google_offer_ref';

    /** Stripe metadata key set when the plan is changed after following a link (new purchase for Google). */
    public const META_CHANGE_OFFER_REF = 'google_change_offer_ref';

    /**
     * Call on every page the Android app links to. Does nothing when no token is present.
     */
    public static function captureFromRequest(Request $request, string $source): void
    {
        try {
            self::capture($request, $source);
        } catch (\Throwable $e) {
            // Never break the quiz / packages / subscription pages because of this.
            Log::error('Google external offer: token capture failed', ['source' => $source, 'error' => $e->getMessage()]);
        }
    }

    private static function capture(Request $request, string $source): void
    {
        $token = trim((string) $request->input(self::QUERY_PARAM, ''));

        if ($token === '' || strlen($token) > 8192) {
            return;
        }

        // The Android debug test token is ignored only when reporting is on (production).
        // On staging (reporting off) it is recorded, so the whole flow can be tested end to end.
        if (config('google_play.enabled') && in_array($token, config('google_play.ignored_tokens', []), true)) {
            Log::info('Google external offer: ignored test token', ['source' => $source]);
            return;
        }

        $tokenHash = hash('sha256', $token);

        try {
            $link = GoogleExternalOfferLink::firstOrCreate(
                ['token_hash' => $tokenHash],
                [
                    'ref' => (string) Str::uuid(),
                    'token' => $token,
                    'source' => $source,
                    'user_id' => Auth::guard('user')->id(),
                ]
            );
        } catch (QueryException $e) {
            // Two requests with the same token at the same time (WebView reload): the other one inserted it.
            $link = GoogleExternalOfferLink::where('token_hash', $tokenHash)->firstOrFail();
        }

        session([self::SESSION_KEY => $link->ref]);
        Log::info('Google external offer: token captured', ['source' => $source, 'ref' => $link->ref]);
    }

    /** Metadata to merge into a NEW Stripe subscription. Empty when the user did not come from the Android link. */
    public static function subscriptionMetadata(?int $userId = null): array
    {
        $ref = session(self::SESSION_KEY);
        if (!$ref) {
            return [];
        }

        $metadata = [self::META_OFFER_REF => $ref];
        if ($userId) {
            $metadata['user_id'] = (string) $userId;
        }

        return $metadata;
    }

    /** Metadata to merge into a Stripe subscription whose PLAN is changed. */
    public static function planChangeMetadata(): array
    {
        $ref = session(self::SESSION_KEY);

        return $ref ? [self::META_CHANGE_OFFER_REF => $ref] : [];
    }
}
