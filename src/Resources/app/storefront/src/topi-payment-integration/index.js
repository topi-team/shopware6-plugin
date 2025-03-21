import Plugin from 'src/plugin-system/plugin.class';

export default class TopiPaymentIntegrationPlugin extends Plugin {
  init() {
    const plugin = window.PluginManager.getPluginInstanceFromElement(document.querySelector('[data-off-canvas-cart]'), 'OffCanvasCart');
    plugin.$emitter.subscribe('offCanvasOpened', this.onOffCanvasCartOpened);
  }

  onOffCanvasCartOpened () {
    eval(document.querySelector('script#topi-offcanvas-cart-items').innerHTML)
  }
}
