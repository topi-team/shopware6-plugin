import Plugin from 'src/plugin-system/plugin.class';

export default class TopiPaymentIntegrationPlugin extends Plugin {
  init() {
    register({ shadow: true });
  }
}
