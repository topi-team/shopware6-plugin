// Import all necessary Storefront plugins
import TopiPaymentIntegrationPlugin from './topi-payment-integration';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
// Initialize on every page to catch dynamic offcanvas injections
PluginManager.register('TopiPaymentIntegration', TopiPaymentIntegrationPlugin, 'body');
