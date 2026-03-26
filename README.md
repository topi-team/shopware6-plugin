# Topi Payment Integration for Shopware 6

Rent with topi - easily offer electronics rental in your Shopware 6 store. This plugin integrates the [topi](https://topi.eu) rental payment platform, supporting mixed shopping baskets (rental + regular products) and secure payment without additional customer costs.

## Requirements

- **PHP:** >= 8.2
- **Shopware:** >6.5.8.0 <6.8.0.0 (Core & Storefront)

### Credentials from topi

| Feature                  | Required credentials                                  |
|--------------------------|-------------------------------------------------------|
| Catalog synchronization  | Client-ID & Client-Secret (Seller API)                |
| Order processing         | Client-ID & Client-Secret + Webhook Secrets (optional)|
| Storefront widgets       | Widget-ID                                             |

All credentials are provided by your topi contact.

## Installation

### Via Composer (recommended)

Automatic updates independent of topi.

```bash
composer require topi-team/shopware6-plugin
php bin/console plugin:install --activate TopiPaymentIntegrationPlugin
```

To update:

```bash
composer update topi-team/shopware6-plugin
php bin/console plugin:update TopiPaymentIntegrationPlugin
```

### Via ZIP file

1. Download the latest release from GitHub ([topi-team/shopware6-plugin](https://github.com/topi-team/shopware6-plugin))
2. Upload the ZIP file in **Administration > Extensions > Upload extension**
3. Install and activate the plugin

## Configuration

Navigate to **Administration > Extensions > My Extensions > Topi Payment Integration > Configure**.

Configuration is **per sales channel** - select the desired sales channel in the dropdown at the top before configuring.

> It is recommended to configure catalog synchronization and topi Elements first.

### API Connection

| Setting        | Description                                                                 |
|----------------|-----------------------------------------------------------------------------|
| Environment    | `sandbox`, `staging`, or `production` - must match your credentials         |
| Client-ID      | OAuth client ID from topi                                                   |
| Client-Secret  | OAuth client secret from topi                                               |

> Credentials are only valid for one environment. Make sure to select the correct one.

### Catalog Synchronization

| Setting                                                      | Description                                           |
|--------------------------------------------------------------|-------------------------------------------------------|
| Activate catalog-synchronization in this sales-channel       | Enable/disable sync for the current sales channel     |
| Categories                                                   | Select which product categories to sync to topi       |

At least one category must be selected when catalog sync is active.

### Webhooks

| Setting                    | Description                                                                 |
|----------------------------|-----------------------------------------------------------------------------|
| Webhook signing secrets    | Comma-separated signing secrets from topi                                   |
| Verify webhook signatures  | Enable Svix signature verification on incoming webhooks                     |

### topi Elements

| Setting                                                | Description                                            |
|--------------------------------------------------------|--------------------------------------------------------|
| Activate topi elements                                 | Enable/disable widget embedding in the storefront      |
| Widget-ID                                              | Your widget ID from topi                               |
| Placement of the topi Widget in the product box        | `left`, `center`, or `right`                           |
| Show business legal info below widget on product box   | Display B2B info on product listing cards               |
| Show business legal info below widget on product detail page | Display B2B info on the product detail page        |

## Features

### Payment Flow

1. Customer selects "Rent with topi" at checkout
2. Plugin creates a rental offer via the topi API
3. Customer is redirected to topi's checkout page
4. After completion, topi sends a webhook to update the order status
5. When the merchant ships the order and adds a tracking code, the plugin sends shipment data to topi

### Catalog Synchronization

Products are synced to topi based on the configured categories per sales channel. Synchronization can be triggered:

- **Automatically** via a registered Shopware scheduled task
- **Manually** via CLI commands (see below)

Products are processed in batches of 250 items via the Symfony Messenger queue.

#### Product Inactivity Override

A custom field **"topi inactive"** (`topi_is_inactive`) is available on products. When enabled, the product is marked as inactive in the topi catalog regardless of its Shopware active status.

### Webhook Events

The plugin receives webhooks at:

```
https://{shop-url}/api/_action/topi-payment-integration/webhook?event={event}
```

Supported events:

| Event              | Effect                                          |
|--------------------|--------------------------------------------------|
| `offer.accepted`   | Marks the order transaction as **paid**          |
| `offer.declined`   | Marks the order transaction as **failed**        |
| `offer.expired`    | Marks the order transaction as **cancelled**     |
| `offer.voided`     | Marks the order transaction as **cancelled**     |
| `order.created`    | Stores the topi order ID on the Shopware order   |

### Custom Fields

| Custom Field Set       | Field              | Type   | Description                                      |
|------------------------|--------------------|--------|--------------------------------------------------|
| `topi_order_details`   | `topi_order_id`    | Text   | Topi order ID linked to the Shopware order       |
| `topi_product_details` | `topi_is_inactive` | Switch | Override to mark product inactive in topi catalog|

## CLI Commands

| Command                              | Alias      | Description                                     |
|--------------------------------------|------------|-------------------------------------------------|
| `topi:catalog-sync:start`            | `t:cs:s`   | Queue catalog synchronization via messenger      |
| `topi:catalog-sync:complete`         |            | Finalize pending catalog sync processes          |
| `topi:shipping-methods:sync`         |            | Synchronize topi shipping methods to Shopware    |
| `topi:catalog-sync:replay-batch`     |            | Replay failed catalog sync batches               |

Example - full manual catalog sync:

```bash
php bin/console topi:catalog-sync:start
# Process the messenger queue, then:
php bin/console topi:catalog-sync:complete
php bin/console topi:shipping-methods:sync
```

## Development

### Code Quality

```bash
# Static analysis
composer phpstan

# Code style check / fix
composer style-check
composer style-fix

# License compliance
composer check-licenses
```

### Running Tests

```bash
vendor/bin/phpunit
```

## License

Proprietary - (c) [topi GmbH](https://topi.eu)
