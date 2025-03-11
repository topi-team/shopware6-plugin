import Plugin from 'src/plugin-system/plugin.class';

export default class TopiPaymentIntegrationPlugin extends Plugin {
  init() {
    this.handlePdpElement();
  }

  handlePdpElement() {
    const topi = new TopiElements({
      locale: "de",
      widgetId: "pdpItem",
    });

    topi.pdpItem = {
      price: {
        currency: "EUR",
        gross: 119000,
        net: 100000,
        taxRate: 1900,
      },
      quantity: 1,
      sellerProductReference: {
        reference: "01954679c5d6717aa8452e416b4f9790",
        source: "shopware-ids",
      },
    };

    console.log('data');
  }
}
