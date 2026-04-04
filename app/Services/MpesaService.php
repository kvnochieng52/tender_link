<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $shortCode;
    private string $passkey;
    private string $callbackUrl;
    private bool $sandbox;

    public function __construct()
    {
        $this->consumerKey    = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->shortCode      = config('mpesa.short_code');
        $this->passkey        = config('mpesa.passkey');
        $this->callbackUrl    = config('mpesa.callback_url');
        $this->sandbox        = config('mpesa.sandbox', true);
    }

    /**
     * Get the base URL depending on environment.
     */
    private function baseUrl(): string
    {
        return $this->sandbox
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';
    }

    /**
     * Generate OAuth token.
     */
    public function getAccessToken(): ?string
    {
        try {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get("{$this->baseUrl()}/oauth/v1/generate", ['grant_type' => 'client_credentials']);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('M-Pesa token error', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('M-Pesa token exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Initiate STK Push (Lipa Na M-Pesa Online).
     *
     * @param  string  $phone   Phone in 254XXXXXXXXX format
     * @param  float   $amount
     * @param  string  $transRef  Unique reference shown on M-Pesa receipt
     * @param  string  $description
     * @return array{success: bool, checkout_request_id: ?string, merchant_request_id: ?string, message: string}
     */
    public function stkPush(string $phone, float $amount, string $transRef, string $description = 'Payment'): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['success' => false, 'checkout_request_id' => null, 'merchant_request_id' => null, 'message' => 'Could not obtain M-Pesa access token'];
        }

        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($this->shortCode . $this->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->shortCode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) ceil($amount),
            'PartyA'            => $phone,
            'PartyB'            => $this->shortCode,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $this->callbackUrl,
            'AccountReference'  => $transRef,
            'TransactionDesc'   => $description,
        ];

        try {
            $response = Http::withToken($token)
                ->post("{$this->baseUrl()}/mpesa/stkpush/v1/processrequest", $payload);

            Log::info('M-Pesa STK Push response', ['response' => $response->json()]);

            if ($response->successful() && $response->json('ResponseCode') === '0') {
                return [
                    'success'              => true,
                    'checkout_request_id'  => $response->json('CheckoutRequestID'),
                    'merchant_request_id'  => $response->json('MerchantRequestID'),
                    'message'              => $response->json('CustomerMessage', 'Request accepted for processing'),
                ];
            }

            return [
                'success'              => false,
                'checkout_request_id'  => null,
                'merchant_request_id'  => null,
                'message'              => $response->json('errorMessage') ?? $response->json('ResponseDescription') ?? 'STK push failed',
            ];
        } catch (\Exception $e) {
            Log::error('M-Pesa STK Push exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'checkout_request_id' => null, 'merchant_request_id' => null, 'message' => 'Connection error: ' . $e->getMessage()];
        }
    }

    /**
     * Query the status of an STK Push transaction from Safaricom.
     *
     * Returns an array: ['result_code' => int, 'result_desc' => string]
     * result_code 0  = success/paid
     * result_code 1032 = cancelled by user
     * result_code 1 = insufficient funds / other failure
     * null result_code means we could not determine status yet.
     */
    public function stkQuery(string $checkoutRequestId): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['result_code' => null, 'result_desc' => 'Could not obtain access token'];
        }

        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($this->shortCode . $this->passkey . $timestamp);

        try {
            $response = Http::withToken($token)
                ->post("{$this->baseUrl()}/mpesa/stkpushquery/v1/query", [
                    'BusinessShortCode' => $this->shortCode,
                    'Password'          => $password,
                    'Timestamp'         => $timestamp,
                    'CheckoutRequestID' => $checkoutRequestId,
                ]);

            Log::info('M-Pesa STK Query response', ['response' => $response->json()]);

            $resultCode = $response->json('ResultCode');

            return [
                'result_code' => $resultCode !== null ? (int) $resultCode : null,
                'result_desc' => $response->json('ResultDesc') ?? $response->json('errorMessage') ?? 'Unknown',
            ];
        } catch (\Exception $e) {
            Log::error('M-Pesa STK Query exception', ['error' => $e->getMessage()]);
            return ['result_code' => null, 'result_desc' => 'Connection error'];
        }
    }

    /**
     * Normalise a Kenyan phone number to 254XXXXXXXXX format.
     */
    public static function normalisePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '+254')) {
            $phone = ltrim($phone, '+');
        } elseif (!str_starts_with($phone, '254')) {
            $phone = '254' . $phone;
        }

        return $phone;
    }
}
