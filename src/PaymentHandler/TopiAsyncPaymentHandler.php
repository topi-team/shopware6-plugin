<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\PaymentHandler;

/**
 * Marker class used as a stable handlerIdentifier string.
 * The actual runtime handler instance is provided by a factory-constructed service
 * with this service id.
 */
class TopiAsyncPaymentHandler
{
}
