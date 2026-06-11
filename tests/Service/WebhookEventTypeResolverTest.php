<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Tests\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TopiPaymentIntegration\Service\WebhookEventTypeResolver;

class WebhookEventTypeResolverTest extends TestCase
{
    private WebhookEventTypeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new WebhookEventTypeResolver();
    }

    /**
     * @param array<mixed> $payload
     */
    #[DataProvider('resolvablePayloadProvider')]
    public function testResolvesEventName(array $payload, string $expectedEvent): void
    {
        $this->assertSame($expectedEvent, $this->resolver->resolve($payload));
    }

    /**
     * @return iterable<string, array{array<mixed>, string}>
     */
    public static function resolvablePayloadProvider(): iterable
    {
        foreach (['accepted', 'declined', 'voided', 'expired', 'pending_review'] as $status) {
            yield 'offer '.$status => [
                [
                    'id' => '9cebdcec-5eec-459f-82c2-2e105aab5b9f',
                    'lines' => [['id' => 'd033c513-c5bb-4cb0-a795-c0ceda9ecaaa']],
                    'checkout_redirect_url' => 'https://dashboard.topi.eu/offer/x',
                    'order_id' => null,
                    'status' => $status,
                ],
                'offer.'.$status,
            ];
        }

        foreach (['created', 'completed', 'canceled'] as $status) {
            yield 'order '.$status => [
                [
                    'id' => '8c9a3939-0125-4d24-a9e1-6727591a1ace',
                    'offer_id' => '9cebdcec-5eec-459f-82c2-2e105aab5b9f',
                    'assets' => [],
                    'status' => $status,
                ],
                'order.'.$status,
            ];
        }

        yield 'offer detected via checkout_redirect_url alone' => [
            ['checkout_redirect_url' => null, 'status' => 'accepted'],
            'offer.accepted',
        ];

        yield 'offer with non-null order_id still resolves as offer' => [
            [
                'lines' => [],
                'order_id' => '8c9a3939-0125-4d24-a9e1-6727591a1ace',
                'status' => 'accepted',
            ],
            'offer.accepted',
        ];

        yield 'order detected via assets alone' => [
            ['assets' => [], 'status' => 'created'],
            'order.created',
        ];
    }

    /**
     * @param array<mixed> $payload
     */
    #[DataProvider('unresolvablePayloadProvider')]
    public function testReturnsNullForUnresolvablePayloads(array $payload): void
    {
        $this->assertNull($this->resolver->resolve($payload));
    }

    /**
     * @return iterable<string, array{array<mixed>}>
     */
    public static function unresolvablePayloadProvider(): iterable
    {
        yield 'empty payload' => [[]];
        yield 'status only' => [['status' => 'accepted']];
        yield 'ambiguous payload with offer and order markers' => [
            ['lines' => [], 'assets' => [], 'status' => 'accepted'],
        ];
        yield 'offer without status' => [['lines' => []]];
        yield 'non-string status' => [['lines' => [], 'status' => 123]];
        yield 'status with invalid characters' => [['lines' => [], 'status' => 'Accepted!']];
    }
}
