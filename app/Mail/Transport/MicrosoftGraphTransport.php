<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class MicrosoftGraphTransport extends AbstractTransport
{
    protected string $tenantId;
    protected string $clientId;
    protected string $clientSecret;
    protected ?string $accessToken = null;

    public function __construct(string $tenantId, string $clientId, string $clientSecret)
    {
        parent::__construct();
        $this->tenantId = $tenantId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $from = $email->getFrom()[0]->getAddress();

        $payload = [
            'message' => [
                'subject' => $email->getSubject(),
                'body' => [
                    'contentType' => $email->getHtmlBody() ? 'HTML' : 'Text',
                    'content' => $email->getHtmlBody() ?: $email->getTextBody(),
                ],
                'toRecipients' => $this->mapRecipients($email->getTo()),
                'ccRecipients' => $this->mapRecipients($email->getCc()),
                'bccRecipients' => $this->mapRecipients($email->getBcc()),
            ],
            'saveToSentItems' => false,
        ];

        $token = $this->getAccessToken();

        $ch = curl_init("https://graph.microsoft.com/v1.0/users/{$from}/sendMail");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$token}",
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \RuntimeException("Microsoft Graph sendMail failed ({$httpCode}): {$response}");
        }
    }

    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $url = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]),
        ]);

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (empty($response['access_token'])) {
            throw new \RuntimeException('Failed to obtain Microsoft Graph access token: ' . json_encode($response));
        }

        $this->accessToken = $response['access_token'];
        return $this->accessToken;
    }

    protected function mapRecipients(array $addresses): array
    {
        return array_map(fn($addr) => [
            'emailAddress' => [
                'address' => $addr->getAddress(),
                'name' => $addr->getName(),
            ],
        ], $addresses);
    }

    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}
