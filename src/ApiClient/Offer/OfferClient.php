<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TopiPaymentIntegration\ApiClient\Exception\TopiApiException;
use TopiPaymentIntegration\ApiClient\PreProcessOptionsTrait;

readonly class OfferClient
{
    use PreProcessOptionsTrait;

    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
    ) {
    }

    /** @param array<mixed> $options */
    public function createOffer(CreateOfferData $offer, array $options = []): CreatedOffer
    {
        try {
            $response = $this->client->request('POST', 'offers', $this->preProcessOptions(array_merge([
                'json' => $offer,
            ], $options)));

            $createdOffer = new CreatedOffer();
            $createdOffer->applyData($response->toArray());

            return $createdOffer;
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            $this->logger->error($e);
            throw new TopiApiException($e->getMessage(), $e instanceof HttpExceptionInterface ? $e->getResponse()->getStatusCode() : $e->getCode(), $e);
        }
    }

    /** @param array<mixed> $options */
    public function validateOffer(CreateOfferData $offer, array $options = []): PricingOverview
    {
        try {
            $response = $this->client->request('POST', 'offers/validate', $this->preProcessOptions(array_merge([
                'json' => $offer,
            ], $options)));

            $pricingOverview = new PricingOverview();
            $pricingOverview->applyData($response->toArray()['pricing_overview']);

            return $pricingOverview;
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            $this->logger->error($e);
            throw new TopiApiException($e->getMessage(), $e instanceof HttpExceptionInterface ? $e->getResponse()->getStatusCode() : $e->getCode(), $e);
        }
    }
}
