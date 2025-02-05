<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use TopiPaymentIntegration\ApiClient\PreProcessOptionsTrait;

readonly class OfferClient
{
    use PreProcessOptionsTrait;

    public function __construct(
        private GuzzleClient $client,
        private LoggerInterface $logger,
    ) {
    }

    /** @param array<mixed> $options */
    public function createOffer(CreateOfferData $offer, array $options = []): CreatedOffer
    {
        $start = microtime(true);
        try {
            $response = $this->client->post('offers', $this->preProcessOptions(array_merge([
                'json' => $offer,
            ], $options)));
        } catch (RequestException $exception) {
            $this->logger->error('Response: '.$exception->getResponse()->getStatusCode().' Data: '.$exception->getResponse()->getBody());
            throw $exception;
        }

        $responseData = (string) $response->getBody();

        $time_elapsed_secs = microtime(true) - $start;
        $this->logger->debug('Topi API took: '.$time_elapsed_secs.'s');
        $this->logger->debug('Response: '.$response->getStatusCode().' Data: '.$responseData);

        $createdOffer = new CreatedOffer();
        $createdOffer->applyData(
            @json_decode(
                $responseData,
                true,
                512,
                JSON_THROW_ON_ERROR
            )
        );

        return $createdOffer;
    }

    /** @param array<mixed> $options */
    public function validateOffer(CreateOfferData $offer, array $options = []): PricingOverview
    {
        $start = microtime(true);
        try {
            $response = $this->client->post('offers/validate', $this->preProcessOptions(array_merge([
                'json' => $offer,
            ], $options)));
        } catch (RequestException $exception) {
            $this->logger->error('Response: '.$exception->getResponse()->getStatusCode().' Data: '.$exception->getResponse()->getBody());
            throw $exception;
        }

        $time_elapsed_secs = microtime(true) - $start;
        $this->logger->debug('Topi API took: '.$time_elapsed_secs.'s');
        $this->logger->debug('Response: '.$response->getStatusCode().' Data: '.$response->getBody());

        $responseData = (string) $response->getBody();

        $pricingOverview = new PricingOverview();

        $pricingOverview->applyData(
            @json_decode(
                $responseData,
                true,
                512,
                JSON_THROW_ON_ERROR
            )['pricing_overview']
        );

        return $pricingOverview;
    }
}
