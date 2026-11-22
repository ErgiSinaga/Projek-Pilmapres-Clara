/**
 * Hash router sederhana untuk SPA murid tanpa framework.
 */
export class Router {
  constructor(routes = {}, defaultRoute = 'login') {
    this.routes = routes;
    this.defaultRoute = defaultRoute;
    this.currentScreen = null;
    this.container = null;
    this.beforeHooks = [];

    window.addEventListener('hashchange', () => this.handleRoute());
  }

  init(container) {
    this.container = container;
    this.handleRoute();
  }

  beforeEach(hook) {
    this.beforeHooks.push(hook);
  }

  navigate(path) {
    window.location.hash = '#' + path.replace(/^#/, '');
  }

  async handleRoute() {
    const rawHash = window.location.hash.slice(1) || this.defaultRoute;
    const [path, queryString] = rawHash.split('?');
    const params = new URLSearchParams(queryString || '');

    for (const hook of this.beforeHooks) {
      const allowed = await hook(path, params);
      if (allowed === false) return;
      if (typeof allowed === 'string') {
        this.navigate(allowed);
        return;
      }
    }

    const handler = this.routes[path] || this.routes[this.defaultRoute];
    if (handler && this.container) {
      if (this.currentScreen && typeof this.currentScreen.destroy === 'function') {
        this.currentScreen.destroy();
      }
      this.currentScreen = await handler(this.container, params);
    }
  }
}
