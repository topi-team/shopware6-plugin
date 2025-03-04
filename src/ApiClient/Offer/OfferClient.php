<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
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
        $response = $this->client->request('POST', 'offers', $this->preProcessOptions(array_merge([
            'json' => $offer,
        ], $options)));

        $createdOffer = new CreatedOffer();
        $createdOffer->applyData($response->toArray());

        return $createdOffer;
    }

    /** @param array<mixed> $options */
    public function validateOffer(CreateOfferData $offer, array $options = []): PricingOverview
    {
        $response = $this->client->request('POST', 'offers/validate', $this->preProcessOptions(array_merge([
            'json' => $offer,
        ], $options)));

        $pricingOverview = new PricingOverview();
        $pricingOverview->applyData($response->toArray()['pricing_overview']);

        return $pricingOverview;
    }
}
