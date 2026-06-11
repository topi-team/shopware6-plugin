<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Tests\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TopiPaymentIntegration\Config\ConfigValue;
use TopiPaymentIntegration\Config\PluginConfigService;
use TopiPaymentIntegration\Controller\WebhookController;
use TopiPaymentIntegration\Event\EventInterface;
use TopiPaymentIntegration\Event\OfferEvent;
use TopiPaymentIntegration\Event\OrderEvent;
use TopiPaymentIntegration\Event\Registry;
use TopiPaymentIntegration\Service\EventProcessing\ProcessorInterface;
use TopiPaymentIntegration\Service\WebhookEventTypeResolver;
use TopiPaymentIntegration\Service\WebhookVerificationService;

class WebhookControllerTest extends TestCase
{
    private ProcessorInterface&MockObject $processor;

    private Context $context;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(ProcessorInterface::class);
        $this->context = new Context(new SystemSource());
    }

    public function testOfferPayloadIsResolvedAndProcessed(): void
    {
        $controller = $this->createController();

        $processedEvent = null;
        $this->processor->method('canProcess')->with('offer.accepted')->willReturn(true);
        $this->processor->expects($this->once())
            ->method('process')
            ->willReturnCallback(function (EventInterface $event) use (&$processedEvent): void {
                $processedEvent = $event;
            });

        $response = $controller->executeWebhook($this->createRequest($this->offerPayload()), $this->context);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertInstanceOf(OfferEvent::class, $processedEvent);
        $this->assertSame('offer.accepted', $processedEvent->getEvent());
        $this->assertSame('9cebdcec-5eec-459f-82c2-2e105aab5b9f', $processedEvent->offer->id);
        $this->assertSame('accepted', $processedEvent->offer->status);
    }

    public function testOrderPayloadIsResolvedAndProcessed(): void
    {
        $controller = $this->createController();

        $processedEvent = null;
        $this->processor->method('canProcess')->with('order.created')->willReturn(true);
        $this->processor->expects($this->once())
            ->method('process')
            ->willReturnCallback(function (EventInterface $event) use (&$processedEvent): void {
                $processedEvent = $event;
            });

        $response = $controller->executeWebhook($this->createRequest($this->orderPayload()), $this->context);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertInstanceOf(OrderEvent::class, $processedEvent);
        $this->assertSame('order.created', $processedEvent->getEvent());
        $this->assertSame('8c9a3939-0125-4d24-a9e1-6727591a1ace', $processedEvent->order->id);
    }

    public function testEventQueryParameterIsIgnored(): void
    {
        $controller = $this->createController();

        $this->processor->method('canProcess')->with('offer.accepted')->willReturn(true);
        $this->processor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf(OfferEvent::class));

        $request = $this->createRequest($this->offerPayload(), '?event=order.created');
        $response = $controller->executeWebhook($request, $this->context);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testUnresolvablePayloadIsRejected(): void
    {
        $controller = $this->createController();

        $this->processor->expects($this->never())->method('process');

        $response = $controller->executeWebhook(
            $this->createRequest(['status' => 'accepted']),
            $this->context
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testFailedVerificationIsRejected(): void
    {
        $controller = $this->createController(signatureChecksEnabled: true);

        $this->processor->expects($this->never())->method('process');

        $response = $controller->executeWebhook($this->createRequest($this->offerPayload()), $this->context);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testProcessorExceptionIsLoggedAndAnsweredWithOkErrorResponse(): void
    {
        $controller = $this->createController();

        $this->processor->method('canProcess')->willReturn(true);
        $this->processor->method('process')->willThrowException(new \RuntimeException('boom'));

        $response = $controller->executeWebhook($this->createRequest($this->offerPayload()), $this->context);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"status":"error","message":"boom"}',
            (string) $response->getContent()
        );
    }

    private function createController(bool $signatureChecksEnabled = false): WebhookController
    {
        $config = $this->createMock(PluginConfigService::class);
        $config->method('getBool')
            ->with(ConfigValue::ENABLE_WEBHOOK_SIGNATURE_CHECKS)
            ->willReturn($signatureChecksEnabled);
        $config->method('getString')
            ->with(ConfigValue::WEBHOOK_SIGNING_SECRETS)
            ->willReturn('');

        $registry = new Registry();
        $registry->addEventType(OfferEvent::class);
        $registry->addEventType(OrderEvent::class);

        return new WebhookController(
            $registry,
            $this->processor,
            new WebhookVerificationService($config),
            new WebhookEventTypeResolver(),
            new NullLogger()
        );
    }

    /**
     * @param array<mixed> $payload
     */
    private function createRequest(array $payload, string $queryString = ''): Request
    {
        return Request::create(
            '/api/_action/topi-payment-integration/webhook'.$queryString,
            'POST',
            content: json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @return array<mixed>
     */
    private function offerPayload(): array
    {
        return [
            'id' => '9cebdcec-5eec-459f-82c2-2e105aab5b9f',
            'created_at' => '2026-06-08T09:31:35Z',
            'checkout_redirect_url' => 'https://dashboard.topi.eu/offer/x',
            'lines' => [],
            'order_id' => '8c9a3939-0125-4d24-a9e1-6727591a1ace',
            'seller_offer_reference' => 'C73335',
            'metadata' => [],
            'status' => 'accepted',
        ];
    }

    /**
     * @return array<mixed>
     */
    private function orderPayload(): array
    {
        return [
            'id' => '8c9a3939-0125-4d24-a9e1-6727591a1ace',
            'offer_id' => '9cebdcec-5eec-459f-82c2-2e105aab5b9f',
            'seller_offer_reference' => 'C73335',
            'assets' => [],
            'metadata' => null,
            'status' => 'created',
        ];
    }
}
