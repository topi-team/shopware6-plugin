// Import all necessary Storefront plugins
import TopiPaymentIntegrationPlugin from './topi-payment-integration';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
// PluginManager.register('TopiPaymentIntegration', TopiPaymentIntegrationPlugin, '.is-ctl-product x-topi-product-rental-summary-label');
PluginManager.register('TopiPaymentIntegration', TopiPaymentIntegrationPlugin, '[data-off-canvas-cart]');
