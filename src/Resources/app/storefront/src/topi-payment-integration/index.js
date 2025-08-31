import Plugin from 'src/plugin-system/plugin.class';

export default class TopiPaymentIntegrationPlugin extends Plugin {
  init() {
    this.bindOffCanvasIfAvailable();
    this.applyFromData();
  }

  bindOffCanvasIfAvailable() {
    try {
      const el = document.querySelector('[data-off-canvas-cart], [data-offcanvas-cart], #offcanvas-cart');
      if (!el) return;

      const offCanvasPlugin = window.PluginManager.getPluginInstanceFromElement(el, 'OffCanvasCart')
        || window.PluginManager.getPluginInstanceFromElement(el, 'OffCanvas');

      if (offCanvasPlugin && offCanvasPlugin.$emitter) {
        offCanvasPlugin.$emitter.subscribe('offCanvasOpened', this.onOffCanvasOpened.bind(this));
      }
    } catch (e) {
      // ignore
    }
  }

  onOffCanvasOpened() {
    this.applyFromData();
  }

  applyFromData() {
    const dataEl = document.querySelector('#topi-offcanvas-cart-items-data');
    const raw = dataEl?.getAttribute('data-topi-cart-items');
    if (!raw) return;

    let cartItems = [];
    try { cartItems = Object.values(JSON.parse(raw)); } catch (e) { /* ignore */ }

    console.log('topi.cartItems', cartItems);
    const assign = () => { window.topi = window.topi || {}; window.topi.cartItems = cartItems; console.log('window.topi.cartItems', cartItems); };

    if (typeof window.topi === 'undefined') {
      console.log('topi.widgets not loaded yet, waiting...');
      window.addEventListener('topi.widgets.loaded', assign, { once: true });
    } else {
      assign();
    }
  }
}
