import Plugin from 'src/plugin-system/plugin.class';

export default class TopiPaymentIntegrationPlugin extends Plugin {
  init() {
    this.bindOffCanvasIfAvailable();
    this.applyFromData();
    this.bindSwpProductOptionPriceChange();
    this.bindListingCartItems();
  }

  /**
   * Keep topi.cartItems in sync with AJAX listing pagination/filtering.
   *
   * Shopware's Listing plugin replaces the listing's innerHTML from the AJAX
   * response (ElementReplaceHelper). Inline <script> tags in that markup never
   * execute, so the inline cartItems script does not re-run and the freshly
   * inserted topi widgets stay empty. We instead read the inert
   * `.topi-cart-items-data` JSON container(s) that ARE part of the replaced
   * markup, re-assign topi.cartItems and force the listing widgets to render.
   */
  bindListingCartItems() {
    const listings = document.querySelectorAll('.cms-element-product-listing');
    if (!listings.length) return;

    let timer = null;
    this._listingObserver = new MutationObserver(() => {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => this.refreshListingCartItems(), 50);
    });

    this._observeListings = () => listings.forEach((el) => {
      this._listingObserver.observe(el, { childList: true, subtree: true });
    });
    this._observeListings();
  }

  /**
   * Collect product metadata from every topi data container currently in the
   * DOM (listing + sliders), de-duplicated by product reference.
   */
  collectCartItems() {
    const items = [];
    const seen = new Set();

    document.querySelectorAll('.topi-cart-items-data').forEach((el) => {
      const raw = el.getAttribute('data-topi-cart-items');
      if (!raw) return;

      let parsed;
      try { parsed = JSON.parse(raw); } catch (e) { return; }

      Object.values(parsed).forEach((item) => {
        const ref = item && item.sellerProductReference && item.sellerProductReference.reference;
        if (!ref || seen.has(ref)) return;
        seen.add(ref);
        items.push(item);
      });
    });

    return items;
  }

  refreshListingCartItems() {
    const cartItems = this.collectCartItems();
    // Never wipe cartItems that other contexts (offcanvas, pdp, checkout) own.
    if (!cartItems.length) return;

    const apply = () => {
      window.topi = window.topi || {};
      window.topi.cartItems = cartItems;
      this.rerenderListingWidgets();
    };

    if (typeof window.topi === 'undefined') {
      window.addEventListener('topi.widgets.loaded', apply, { once: true });
    } else {
      apply();
    }
  }

  /**
   * The widgets in AJAX-replaced markup connected while topi.cartItems still
   * held the previous page, so they rendered empty. Re-inserting a fresh clone
   * re-runs their connectedCallback against the now-updated topi.cartItems.
   * The observer is paused so our own DOM swaps do not retrigger a refresh.
   */
  rerenderListingWidgets() {
    if (this._listingObserver) this._listingObserver.disconnect();

    document
      .querySelectorAll('.cms-element-product-listing x-topi-cart-rental-summary-label')
      .forEach((el) => el.replaceWith(el.cloneNode(true)));

    if (this._observeListings) this._observeListings();
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
