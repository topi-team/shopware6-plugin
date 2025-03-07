<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Config;

final readonly class ConfigValue
{
    public const CATALOG_SYNC_ACTIVE_IN_SALES_CHANNEL = 'catalogSyncActiveInSalesChannel';
    public const CATEGORIES = 'categories';
    public const ENVIRONMENT = 'environment';
    public const CLIENT_ID = 'clientId';
    public const CLIENT_SECRET = 'clientSecret';
    public const WEBHOOK_SIGNING_SECRETS = 'webhookSigningSecrets';
    public const ENABLE_WEBHOOK_SIGNATURE_CHECKS = 'enableWebhookSignatureChecks';
}
