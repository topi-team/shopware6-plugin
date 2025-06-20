<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Controller;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use TopiPaymentIntegration\Event\Registry;
use TopiPaymentIntegration\Exception\WebhookVerificationFailedException;
use TopiPaymentIntegration\Service\EventProcessing\ProcessorInterface;
use TopiPaymentIntegration\Service\WebhookVerificationService;

#[Route(defaults: ['_routeScope' => ['api']])]
class WebhookController extends AbstractController
{
    public function __construct(
        private readonly Registry $eventRegistry,
        private readonly ProcessorInterface $processor,
        private readonly WebhookVerificationService $webhookVerificationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[OA\Post(
        path: '/api/_action/topi-payment-integration/webhook',
        operationId: 'executeWebhook',
        requestBody: new OA\RequestBody(content: new OA\JsonContent()),
        tags: ['Admin Api', 'SwagPayPalPosWebhook'],
        parameters: [new OA\Parameter(
            parameter: 'event',
            name: 'event',
            in: 'query',
            schema: new OA\Schema(type: 'string')
        )],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Webhook execution was successful'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Invalid input data'),
        ]
    )]
    #[Route(
        path: '/api/_action/topi-payment-integration/webhook',
        name: 'api.action.topi_payment_integration.webhook.execute',
        defaults: ['auth_required' => false],
        methods: ['POST']
    )]
    public function executeWebhook(Request $request, Context $context): Response
    {
        $event = $request->query->getString('event');
        if ('' === $event) {
            return $this->malformedRequestError();
        }

        $content = $request->getContent();

        $svixHeaders = [
            'svix-id',
            'svix-timestamp',
            'svix-signature',
        ];
        $svixHeaderData = [];
        foreach ($svixHeaders as $header) {
            $svixHeaderData[$header] = $request->headers->get($header);
        }

        try {
            $data = $this->webhookVerificationService->verify($content, $svixHeaderData);
        } catch (\JsonException|WebhookVerificationFailedException $e) {
            return $this->malformedRequestError();
        }

        $eventParentType = substr($event, 0, strpos($event, '.'));
        $allowedEventParents = ['offer', 'order'];
        if (!in_array($eventParentType, $allowedEventParents, true)) {
            return $this->malformedRequestError();
        }

        $eventObject = $this->eventRegistry->getEvent($event);
        if (is_null($eventObject)) {
            return $this->malformedRequestError();
        }
        $eventObject->applyData([
            $eventParentType => $data,
        ]);

        try {
            if ($this->processor->canProcess($event)) {
                $this->processor->process($eventObject, $context);
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'event' => $event,
                'data' => $data,
                'eventObject' => $eventObject,
                'exception' => $e,
            ]);

            return new JsonResponse(
                ['status' => 'error', 'message' => $e->getMessage()],
                status: 200
            );
        }

        return new Response(status: 201);
    }

    protected function malformedRequestError(): Response
    {
        return new JsonResponse(
            ['status' => 'error', 'message' => 'Request data is not formed correctly.'],
            status: 400
        );
    }
}
