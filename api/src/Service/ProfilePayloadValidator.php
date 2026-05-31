<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class ProfilePayloadValidator
{
    /** @return array<string, mixed> */
    public function payload(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON payload');
        }

        if ($this->hasInvalidIdentityPayload($payload)) {
            throw new UnprocessableEntityHttpException('Firstname and lastname are required');
        }

        if ($this->hasInvalidAddressPayload($payload)) {
            throw new UnprocessableEntityHttpException('Address line1, postalCode, city and country are required');
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function hasInvalidIdentityPayload(array $payload): bool
    {
        $firstname = trim((string) ($payload['firstname'] ?? ''));
        $lastname = trim((string) ($payload['lastname'] ?? ''));

        return $firstname === '' || $lastname === '';
    }

    /** @param array<string, mixed> $payload */
    private function hasInvalidAddressPayload(array $payload): bool
    {
        $addressPayload = $this->addressPayload($payload);

        return trim((string) ($addressPayload['line1'] ?? '')) === ''
            || trim((string) ($addressPayload['postalCode'] ?? '')) === ''
            || trim((string) ($addressPayload['city'] ?? '')) === ''
            || trim((string) ($addressPayload['country'] ?? '')) === '';
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function addressPayload(array $payload): array
    {
        $addressPayload = $payload['address'] ?? [];

        return is_array($addressPayload) ? $addressPayload : [];
    }

    public function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
