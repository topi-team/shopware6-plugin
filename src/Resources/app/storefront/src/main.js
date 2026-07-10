// Import all necessary Storefront plugins
import TopiListingPlugin from './topi-payment-integration/listing.plugin';
import TopiCartPlugin from './topi-payment-integration/cart.plugin';

// Register your plugins via the existing PluginManager
const PluginManager = window.PluginManager;
// Initialize on every page to catch dynamic offcanvas injections
PluginManager.register('TopiPaymentIntegrationListing', TopiListingPlugin, 'body');
PluginManager.register('TopiPaymentIntegrationCart', TopiCartPlugin, 'body');
