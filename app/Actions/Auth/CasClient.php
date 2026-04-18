<?php

namespace App\Actions\Auth;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;

class CasClient
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function buildLoginUrl(string $serviceUrl): string
    {
        return $this->buildCasUrl('/login', [
            'service' => $serviceUrl,
        ]);
    }

    public function buildLogoutUrl(string $serviceUrl): string
    {
        return $this->buildCasUrl(config('cas.logout_path', '/logout'), [
            'service' => $serviceUrl,
        ]);
    }

    /**
     * @return array{username: string, attributes: array<string, mixed>}|null
     */
    public function validateTicket(string $serviceUrl, string $ticket): ?array
    {
        $validateUrl = $this->buildCasUrl(config('cas.validate_path', '/serviceValidate'), [
            'service' => $serviceUrl,
            'ticket' => $ticket,
        ]);

        $response = $this->http
            ->withOptions([
                'verify' => (bool) config('cas.verify_ssl', true),
            ])
            ->accept('application/xml,text/xml')
            ->get($validateUrl);

        if (! $response->successful()) {
            return null;
        }

        return $this->parseValidationResponse($response->body());
    }

    private function buildCasUrl(string $path, array $query): string
    {
        $baseUrl = rtrim((string) config('cas.base_url'), '/');
        $normalizedPath = '/'.ltrim($path, '/');

        return $baseUrl.$normalizedPath.'?'.Arr::query($query);
    }

    /**
     * @return array{username: string, attributes: array<string, mixed>}|null
     */
    private function parseValidationResponse(string $xml): ?array
    {
        if ($xml === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $element = simplexml_load_string($xml);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($element === false) {
            return null;
        }

        $successNodes = $element->xpath('//*[local-name()="authenticationSuccess"]');
        if ($successNodes === false || $successNodes === []) {
            return null;
        }

        $successNode = $successNodes[0];
        $userNodes = $successNode->xpath('./*[local-name()="user"]');

        if ($userNodes === false || $userNodes === []) {
            return null;
        }

        $username = trim((string) $userNodes[0]);
        if ($username === '') {
            return null;
        }

        $attributes = [];
        $attributeNodes = $successNode->xpath('./*[local-name()="attributes"]/*');

        if ($attributeNodes !== false && $attributeNodes !== []) {
            foreach ($attributeNodes as $attributeNode) {
                $key = trim((string) $attributeNode->getName());
                $value = trim((string) $attributeNode);

                if ($key !== '' && $value !== '') {
                    $attributes[$key] = $value;
                }
            }
        }

        return [
            'username' => $username,
            'attributes' => $attributes,
        ];
    }
}


