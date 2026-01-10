<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BrevoMailService
{
    /**
     * Sendet eine E-Mail er die Brevo Transactional Email API.
     *
     * @return array{messageId?:string} Brevo Response (z.B. messageId)
     */
    public function sendTransactional(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlContent,
        ?string $textContent = null,
        array $headers = []
    ): array {
        $apiKey = config('services.brevo.api_key');
        if (!$apiKey) {
            throw new \RuntimeException('BREVO_API_KEY ist nicht gesetzt.');
        }

        $fromEmail = config('services.brevo.from.email');
        $fromName = config('services.brevo.from.name');

        if (!$fromEmail) {
            throw new \RuntimeException('Brevo From-Adresse ist nicht konfiguriert (BREVO_FROM_EMAIL oder MAIL_FROM_ADDRESS).');
        }

        $payload = [
            'sender' => array_filter([
                'email' => $fromEmail,
                'name' => $fromName,
            ]),
            'to' => [
                array_filter([
                    'email' => $toEmail,
                    'name' => $toName ?: null,
                ]),
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ];

        if ($textContent) {
            $payload['textContent'] = $textContent;
        }

        if (!empty($headers)) {
            $payload['headers'] = $headers;
        }

        $verifySsl = (bool) config('services.brevo.verify_ssl', true);

        $response = Http::timeout(15)
            ->withOptions([
                'verify' => $verifySsl,
            ])
            ->withHeaders([
                'api-key' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (!$response->successful()) {
            $rid = (string) Str::uuid();
            throw new \RuntimeException('Brevo API Fehler (' . $rid . '): ' . $response->status() . ' ' . $response->body());
        }

        return (array) ($response->json() ?? []);
    }
}
