<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class KratosPayClient
{
    public function refreshAccessToken(): string
    {
        $refreshToken = config('kratospay.refresh_token');

        if ($refreshToken === null || $refreshToken === '') {
            throw new RuntimeException('KRATOS_PAY_REFRESH_TOKEN manquant dans .env');
        }

        $response = $this->http()
            ->post('/api/auth/refresh-token', [
                'refreshToken' => $refreshToken,
            ])
            ->throw()
            ->json();

        $token = $response['content'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Impossible de rafraîchir le token Kratos Pay.');
        }

        Cache::put(
            config('kratospay.access_token_cache_key'),
            $token,
            config('kratospay.access_token_ttl_seconds'),
        );

        return $token;
    }

    public function accessToken(): string
    {
        $cached = Cache::get(config('kratospay.access_token_cache_key'));

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $configured = config('kratospay.access_token');

        if (is_string($configured) && $configured !== '') {
            Cache::put(
                config('kratospay.access_token_cache_key'),
                $configured,
                config('kratospay.access_token_ttl_seconds'),
            );

            return $configured;
        }

        return $this->refreshAccessToken();
    }

    /**
     * @return array<string, mixed>
     */
    public function depositMobileMoney(
        string $paymentMethod,
        float $amount,
        string $accountNumber,
    ): array {
        try {
            return $this->authorized()
                ->post('/api/wallet/public/deposit', [
                    'payment_method' => $paymentMethod,
                    'amount' => $amount,
                    'payment_token' => config('kratospay.payment_token'),
                    'account_number' => $this->normalizePhone($accountNumber),
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            throw $this->toValidationException($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function depositCard(float $amount): array
    {
        try {
            return $this->authorized()
                ->post('/api/wallet/deposit/card', [
                    'amount' => $amount,
                    'payment_token' => config('kratospay.payment_token'),
                ])
                ->throw()
                ->json();
        } catch (RequestException $e) {
            throw $this->toValidationException($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getTransactionByReference(string $reference): array
    {
        return $this->authorized()
            ->get("/api/transactions/reference/{$reference}")
            ->throw()
            ->json();
    }

    public function transactionStatus(array $payload): string
    {
        $candidates = [
            data_get($payload, 'content.statut'),
            data_get($payload, 'content.status'),
            data_get($payload, 'content.transaction.statut'),
            data_get($payload, 'content.transaction.status'),
            data_get($payload, 'transaction.statut'),
            data_get($payload, 'transaction.status'),
            data_get($payload, 'data.statut'),
            data_get($payload, 'data.status'),
            data_get($payload, 'statut'),
            data_get($payload, 'status'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return strtoupper(trim($candidate));
            }
        }

        return '';
    }

    public function isTransactionSuccessful(array $payload): bool
    {
        $status = $this->transactionStatus($payload);

        return in_array($status, [
            'REUSSIE',
            'REUSSI',
            'CONFIRMED',
            'SUCCESS',
            'SUCCESSFUL',
            'COMPLETED',
            'VALIDATED',
            'OK',
            'PAID',
        ], true);
    }

    public function isTransactionFailed(array $payload): bool
    {
        $status = $this->transactionStatus($payload);

        return in_array($status, [
            'ECHEC',
            'Echec',
            'FAILED',
            'FAILURE',
            'ANNULEE',
            'ANNULÉE',
            'CANCELLED',
            'CANCELED',
            'REJECTED',
            'REFUSED',
        ], true);
    }

    public function isTransactionPending(array $payload): bool
    {
        $status = $this->transactionStatus($payload);

        return in_array($status, [
            'EN_ATTENTE',
            'PENDING',
            'PROCESSING',
            'INITIATED',
            'IN_PROGRESS',
            'AWAITING',
        ], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function withdraw(
        string $paymentMethod,
        float $amount,
        string $accountNumber,
        string $beneficiaryName,
    ): array {
        return $this->authorized()
            ->post((string) config('kratospay.withdraw_path'), [
                'payment_method' => $paymentMethod,
                'amount' => $amount,
                'payment_token' => config('kratospay.payment_token'),
                'account_number' => $this->normalizePhone($accountNumber),
                'name' => $beneficiaryName,
            ])
            ->json();
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '237')) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }

    private function authorized(): PendingRequest
    {
        return $this->http()->withToken($this->accessToken());
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('kratospay.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    private function toValidationException(RequestException $e): ValidationException
    {
        $body = $e->response?->json();
        $message = is_string($body['message'] ?? null) ? $body['message'] : null;

        if ($message === null || $message === '') {
            $message = 'Le paiement a été refusé par Kratos Pay. Vérifiez le montant et le numéro.';
        }

        return ValidationException::withMessages([
            'amount' => $this->formatAmountError($message),
        ]);
    }

    private function formatAmountError(string $message): string
    {
        if (preg_match('/intervalle:\s*([\d.,]+)\s*-\s*([\d.,]+)/i', $message, $matches)) {
            $min = (float) str_replace(',', '', $matches[1]);
            $max = (float) str_replace(',', '', $matches[2]);

            return sprintf(
                'Montant hors limites Kratos Pay pour ce moyen de paiement : entre %s et %s XAF.',
                number_format($min, 0, ',', ' '),
                number_format($max, 0, ',', ' '),
            );
        }

        return $message;
    }

    /**
     * @return array{min: float, max: float}
     */
    public function amountLimits(): array
    {
        return [
            'min' => (float) config('kratospay.amount_min', 100),
            'max' => (float) config('kratospay.amount_max', 5_000_000),
        ];
    }
}
