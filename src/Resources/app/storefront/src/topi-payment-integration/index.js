import Plugin from 'src/plugin-system/plugin.class';

export default class TopiPaymentIntegrationPlugin extends Plugin {
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

    const assign = () => { window.topi = window.topi || {}; window.topi.cartItems = cartItems; console.log('window.topi.cartItems', cartItems); };

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

      const price = parseInt(priceElement.innerText
        .replace(/[^\d,.-]/g, '')
        .replace(',', '')
        .replace('.', '')
        .trim()
      );

      const isGross = this.isGross();
      const item = {...window.pdpItem,
        price: {
          currency: 'EUR',
          net: isGross ? Math.round((price / 119) * 100) : price,
          gross: isGross ? price : Math.round((price / 100) * 119),
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

  isGross() {
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
