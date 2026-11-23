import '../css/app.css';
import { Router } from './router.js';
import { store } from './store.js';
import { api } from './api.js';

let routerInstance;

const routes = {
  login: async (container) => {
    const { createLoginScreen } = await import('./screens/login.js');
    return createLoginScreen(container, routerInstance);
  },
  onboarding: async (container) => {
    const { createOnboardingScreen } = await import('./screens/onboarding.js');
    return createOnboardingScreen(container, routerInstance);
  },
  test: async (container, params) => {
    const { createTestScreen } = await import('./screens/test.js');
    return createTestScreen(container, routerInstance, params);
  },
  survey: async (container) => {
    const { createSurveyScreen } = await import('./screens/survey.js');
    return createSurveyScreen(container, routerInstance);
  },
  home: async (container) => {
    const { createHomeScreen } = await import('./screens/home.js');
    return createHomeScreen(container, routerInstance);
  },
  shop: async (container) => {
    const { createShopScreen } = await import('./screens/shop.js');
    return createShopScreen(container, routerInstance);
  },
  daily: async (container) => {
    const { createDailyScreen } = await import('./screens/daily.js');
    return createDailyScreen(container, routerInstance);
  },
  'game/pilah-makanan': async (container) => {
    const { createPilahMakananGame } = await import('./games/pilah-makanan.js');
    return createPilahMakananGame(container, routerInstance);
  },
  'game/urutan-sikat': async (container) => {
    const { createUrutanSikatGame } = await import('./games/urutan-sikat.js');
    return createUrutanSikatGame(container, routerInstance);
  },
  'game/sikat-bersih': async (container) => {
    const { createSikatBersihGame } = await import('./games/sikat-bersih.js');
    return createSikatBersihGame(container, routerInstance);
  },
  'game/memory-fakta': async (container) => {
    const { createMemoryFaktaGame } = await import('./games/memory-fakta.js');
    return createMemoryFaktaGame(container, routerInstance);
  },
  'game/kuis-kilat': async (container) => {
    const { createKuisKilatGame } = await import('./games/kuis-kilat.js');
    return createKuisKilatGame(container, routerInstance);
  },
  videos: async (container, params) => {
    const { createVideosScreen } = await import('./screens/videos.js');
    return createVideosScreen(container, routerInstance, params);
  },
  jurnal: async (container) => {
    const { createJurnalScreen } = await import('./screens/jurnal.js');
    return createJurnalScreen(container, routerInstance);
  },
  'jurnal-ringkasan': async (container) => {
    const { createJurnalRingkasanScreen } = await import('./screens/jurnal-ringkasan.js');
    return createJurnalRingkasanScreen(container, routerInstance);
  },
  certificate: async (container) => {
    const { createCertificateScreen } = await import('./screens/certificate.js');
    return createCertificateScreen(container, routerInstance);
  },
  'dev/mascot': async (container) => {
    const { initMascotGallery } = await import('./dev/mascot-gallery.js');
    return initMascotGallery(container);
  },
};

routerInstance = new Router(routes, 'login');

document.addEventListener('DOMContentLoaded', () => {
  const appContainer = document.getElementById('app');
  if (appContainer) {
    if (window.location.pathname === '/dev/mascot' && !window.location.hash) {
      window.location.hash = '#dev/mascot';
    }
    routerInstance.init(appContainer);
  }
});

export { routerInstance as router, store, api };
