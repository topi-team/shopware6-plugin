<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

readonly class WebhookEventTypeResolver
{
    /**
     * Infers the event name (e.g. "offer.accepted", "order.created") from a webhook payload.
     *
     * The payload is the bare offer/order entity without an event envelope, and the
     * status value sets of both entities overlap, so the entity type is detected
     * structurally before being combined with the payload's status.
     *
     * @param array<mixed> $payload decoded webhook payload
     *
     * @return string|null the event name, or null if the payload is ambiguous or unrecognized
     */
    public function resolve(array $payload): ?string
    {
        $isOffer = \array_key_exists('lines', $payload)
            || \array_key_exists('checkout_redirect_url', $payload);
        // offers carry a nullable order_id key, so order detection must not rely on it
        $isOrder = \array_key_exists('offer_id', $payload)
            || \array_key_exists('assets', $payload);

        if ($isOffer === $isOrder) {
            return null;
        }

        $status = $payload['status'] ?? null;
        if (!\is_string($status) || 1 !== preg_match('/^[a-z_]+$/', $status)) {
            return null;
        }

        return ($isOffer ? 'offer' : 'order').'.'.$status;
    }
}
