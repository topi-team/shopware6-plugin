const Plugin = window.PluginBaseClass;

export default class TopiListingPlugin extends Plugin {
  init() {
    this.bindListingItems();
  }

  /**
   * Keep topi.listingItems in sync with AJAX listing pagination/filtering.
   *
   * Shopware's Listing plugin replaces the listing's innerHTML from the AJAX
   * response (ElementReplaceHelper). Inline <script> tags in that markup never
   * execute, so the inline listingItems script does not re-run and the freshly
   * inserted topi widgets stay empty. We instead read the inert
   * `.topi-listing-items-data` JSON container(s) that ARE part of the replaced
   * markup, re-assign topi.listingItems and force the listing widgets to render.
   */
  bindListingItems() {
    const listings = document.querySelectorAll('.cms-element-product-listing');
    if (!listings.length) return;

    let timer = null;
    this._listingObserver = new MutationObserver(() => {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => this.refreshListingItems(), 50);
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
  collectListingItems() {
    const items = [];
    const seen = new Set();

    document.querySelectorAll('.topi-listing-items-data').forEach((el) => {
      const raw = el.getAttribute('data-topi-listing-items');
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

  refreshListingItems() {
    const listingItems = this.collectListingItems();
    if (!listingItems.length) return;

    const apply = () => {
      window.topi = window.topi || {};
      window.topi.listingItems = listingItems;
      this.rerenderListingWidgets();
    };

    if (typeof window.topi === 'undefined') {
      window.addEventListener('topi.widgets.loaded', apply, { once: true });
    } else {
      apply();
    }
  }

  /**
   * The widgets in AJAX-replaced markup connected while topi.listingItems still
   * held the previous page, so they rendered empty. Re-inserting a fresh clone
   * re-runs their connectedCallback against the now-updated topi.listingItems.
   * The observer is paused so our own DOM swaps do not retrigger a refresh.
   */
  rerenderListingWidgets() {
    if (this._listingObserver) this._listingObserver.disconnect();

    document
      .querySelectorAll('.cms-element-product-listing x-topi-listing-rental-summary-label')
      .forEach((el) => el.replaceWith(el.cloneNode(true)));

    if (this._observeListings) this._observeListings();
  }
}
