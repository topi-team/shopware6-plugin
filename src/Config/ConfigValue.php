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
    public const WIDGET_ID = 'widgetId';
    public const ENABLE_WIDGETS = 'enableWidgets';

	public const SHOW_LEGAL_INFO_PRODUCT_BOX = 'showLegalInfoProductBox';

	public const SHOW_LEGAL_INFO_BUY_WIDGET = 'showLegalInfoBuyWidget';

    public const PRODUCT_BOX_WIDGET_LOCATION = 'productBoxWidgetLocation';
}
