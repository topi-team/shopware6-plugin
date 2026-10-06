const Plugin = window.PluginBaseClass;

export default class TopiCartPlugin extends Plugin {
  init() {
    this.bindOffCanvasIfAvailable();
    this.applyFromData();
    this.bindSwpProductOptionPriceChange();
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

    const assign = () => { window.topi = window.topi || {}; window.topi.cartItems = cartItems; };

    if (typeof window.topi === 'undefined') {
      window.addEventListener('topi.widgets.loaded', assign, { once: true });
    } else {
      assign();
    }
  }

  bindSwpProductOptionPriceChange() {
    document.$emitter.subscribe('ProductOptions/onBeforeInsertOptionsResult', (event) => {
      const content = document.createElement('div');
      content.insertAdjacentHTML('beforeend', event.detail);

      const priceElement = content.querySelector('.swp-productoptions--result-totalrow .price');
      if (!priceElement) return;

      const price = parseInt(priceElement.innerText
        .replace(/[^\d,.-]/g, '')
        .replace(',', '')
        .replace('.', '')
        .trim()
      );

      if (Number.isNaN(price)) return;

      const isGross = this.isGross();
      const taxFactor = this.getTaxFactor();
      const item = {...window.pdpItem,
        price: {
          ...window.pdpItem?.price,
          currency: window.pdpItem?.price?.currency ?? 'EUR',
          net: isGross ? Math.round(price / taxFactor) : price,
          gross: isGross ? price : Math.round(price * taxFactor),
        }
      };

      if (typeof topi === 'undefined') {
        window.addEventListener("topi.widgets.loaded", function () {
          topi.pdpItem = item;
        });
      } else {
        topi.pdpItem = item;
      }
    });
  }

  /**
   * Gross/net ratio of the server-rendered pdpItem, so non-19% tax rates are
   * converted correctly. Falls back to 19% if the item carries no usable price.
   */
  getTaxFactor() {
    const { net, gross } = window.pdpItem?.price ?? {};

    return net > 0 && gross > 0 ? gross / net : 1.19;
  }

  isGross() {
    // Rendered server-side next to pdpItem. The DOM heuristic below is only a
    // fallback for overridden templates: SwpProductOptions adds the "*" price
    // marker only after its first result was inserted, so on the initial
    // calculation the heuristic cannot tell and the gross price was sent as net.
    if (typeof window.topiDisplayGross === 'boolean') return window.topiDisplayGross;

    const priceElement = document.querySelector('.product-detail-price, .price');
    if (priceElement?.textContent.includes('*')) {
      // Suche nach dem Sternchen-Hinweis
      const footnote = document.querySelector('.product-detail-price-container small, .product-detail-tax');
      if (footnote) {
        const text = footnote.textContent.toLowerCase();
        if (text.includes('inkl.') || text.includes('incl.')) return true;
        if (text.includes('exkl.') || text.includes('excl.')) return false;
      }
    }
  }
}
