<?php

namespace App\Services\GooglePlay;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal client for the Google Play Developer API "externaltransactions" resource.
 * Authenticates with a service account (JWT signed with PHP's OpenSSL), so no extra
 * Composer package is needed.
 *
 * create: POST https://androidpublisher.googleapis.com/androidpublisher/v3/applications/{pkg}/externalTransactions?externalTransactionId={id}
 * refund: POST https://androidpublisher.googleapis.com/androidpublisher/v3/applications/{pkg}/externalTransactions/{id}:refund
 */
class GooglePlayExternalTransactionsClient
{
    private const BASE_URL = 'https://androidpublisher.googleapis.com/androidpublisher/v3';
    private const SCOPE = 'https://www.googleapis.com/auth/androidpublisher';
    private const TOKEN_CACHE_KEY = 'google_play_publisher_access_token';

    /**
     * @return array Google's ExternalTransaction response
     * @throws GooglePlayAlreadyReportedException when Google already has this externalTransactionId
     */
    public function createTransaction(string $externalTransactionId, array $body): array
    {
        $url = sprintf(
            '%s/applications/%s/externalTransactions?externalTransactionId=%s',
            self::BASE_URL,
            rawurlencode($this->packageName()),
            rawurlencode($externalTransactionId)
        );

        return $this->post($url, $body);
    }

    /**
     * @return array Google's ExternalTransaction response
     */
    public function refundTransaction(string $externalTransactionId, array $body): array
    {
        $url = sprintf(
            '%s/applications/%s/externalTransactions/%s:refund',
            self::BASE_URL,
            rawurlencode($this->packageName()),
            rawurlencode($externalTransactionId)
        );

        return $this->post($url, $body);
    }

    private function post(string $url, array $body): array
    {
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(20)
            ->post($url, $body);

        if ($response->status() === 409) {
            throw new GooglePlayAlreadyReportedException($response->body());
        }

        if (!$response->successful()) {
            throw new RuntimeException('Google Play API error ' . $response->status() . ': ' . $response->body());
        }

        return $response->json() ?? [];
    }

    private function packageName(): string
    {
        return (string) config('google_play.package_name');
    }

    private function accessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(50), function () {
            $credentials = $this->credentials();
            $tokenUri = $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';
            $now = time();

            $header = ['alg' => 'RS256', 'typ' => 'JWT'];
            $claims = [
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => $tokenUri,
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $unsigned = $this->base64Url(json_encode($header)) . '.' . $this->base64Url(json_encode($claims));

            $signature = '';
            if (!openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Could not sign the Google service account JWT.');
            }

            $response = Http::asForm()->timeout(20)->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned . '.' . $this->base64Url($signature),
            ]);

            if (!$response->successful() || empty($response->json('access_token'))) {
                throw new RuntimeException('Google OAuth token error ' . $response->status() . ': ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    private function credentials(): array
    {
        $path = config('google_play.credentials_path');
        if (empty($path) || !is_readable($path)) {
            throw new RuntimeException('GOOGLE_PLAY_CREDENTIALS_PATH is not set or the file is not readable.');
        }

        $credentials = json_decode((string) file_get_contents($path), true);
        if (empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('The Google service account JSON is missing client_email or private_key.');
        }

        return $credentials;
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
