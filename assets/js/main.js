// ExperientIA · Bootstrap del sitio público (Vue 3 SPA, importmap, sin build)
import { createApp, h } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { store, loadDict, setMeta } from './lib/core.js';
import { SiteBackdrop, SiteHeader, SiteFooter, Toasts, AlexIA } from './lib/layout.js';
import { reveal } from './lib/ui.js';
import { Home, Soluciones } from './views/pages1.js';
import { Tablero, Productos, Casos, Nosotros, FAQ } from './views/pages2.js';
import { Recursos, RecursoDetalle, Contacto } from './views/pages3.js';
import { SolucionLanding, ProductoLanding, CasoDetalle } from './views/pages5.js';
import { GBDemo, GBDiagnostico } from './views/growthboard.js';
import { Diagnostico, Agenda } from './views/pages4.js';

const NotFound = {
  template: `<section class="page-hero" style="min-height:60vh;display:grid;align-items:center"><div class="bg-atmos"><div class="halo halo-violet" style="width:480px;height:480px;top:-160px;right:-200px;opacity:.35"></div></div>
    <div class="container page-hero__in" style="justify-items:center;text-align:center;max-width:40rem"><h1 class="display">404</h1><h2 class="h3">{{ t('not_found.titulo') }}</h2><p class="lead">{{ t('not_found.sub') }}</p>
    <router-link :to="'/'+store.locale+'/'" class="btn btn-primary">{{ t('not_found.cta') }}</router-link></div></section>`,
  data(){ return { store }; }, computed:{ t(){ return (p)=>{ let v=store.dict; for(const k of p.split('.')){v=v&&v[k]; if(v===undefined)return p;} return v; }; } },
};

// Slugs por defecto (para construir rutas antes de cargar diccionarios)
const SLUGS = {
  es:{soluciones:'soluciones',tablero:'tablero-de-crecimiento',productos:'productos',casos:'casos',recursos:'recursos',nosotros:'nosotros',faq:'preguntas-frecuentes',contacto:'contacto',diagnostico:'diagnostico',agenda:'agendar'},
  en:{soluciones:'solutions',tablero:'growth-board',productos:'products',casos:'cases',recursos:'resources',nosotros:'about',faq:'faq',contacto:'contact',diagnostico:'diagnostic',agenda:'book'},
  pt:{soluciones:'solucoes',tablero:'painel-de-crescimento',productos:'produtos',casos:'casos',recursos:'recursos',nosotros:'sobre',faq:'perguntas-frequentes',contacto:'contato',diagnostico:'diagnostico',agenda:'agendar'},
};
const VIEW = { soluciones:Soluciones, tablero:Tablero, productos:Productos, casos:Casos, recursos:Recursos, nosotros:Nosotros, faq:FAQ, contacto:Contacto, diagnostico:Diagnostico, agenda:Agenda };

const routes = [{ path: '/', redirect: () => '/' + store.locale + '/' }];
for (const loc of ['es','en','pt']) {
  routes.push({ path: `/${loc}/`, component: Home });
  for (const key of Object.keys(VIEW)) {
    const slug = SLUGS[loc][key];
    if (key === 'recursos') {
      routes.push({ path: `/${loc}/${slug}`, component: Recursos });
      routes.push({ path: `/${loc}/${slug}/:slug`, component: RecursoDetalle });
    } else if (key === 'soluciones') {
      routes.push({ path: `/${loc}/${slug}`, component: Soluciones });
      routes.push({ path: `/${loc}/${slug}/:slug`, component: SolucionLanding });
    } else if (key === 'productos') {
      routes.push({ path: `/${loc}/${slug}`, component: Productos });
      routes.push({ path: `/${loc}/${slug}/:slug`, component: ProductoLanding });
    } else if (key === 'casos') {
      routes.push({ path: `/${loc}/${slug}`, component: Casos });
      routes.push({ path: `/${loc}/${slug}/:slug`, component: CasoDetalle });
    } else if (key === 'tablero') {
      routes.push({ path: `/${loc}/${slug}`, component: Tablero });
      routes.push({ path: `/${loc}/${slug}/demo`, component: GBDemo });
      routes.push({ path: `/${loc}/${slug}/diagnostico`, component: GBDiagnostico });
    } else {
      routes.push({ path: `/${loc}/${slug}`, component: VIEW[key] });
    }
  }
}
routes.push({ path: '/:pathMatch(.*)*', component: NotFound });

const router = createRouter({ history: createWebHistory(), routes, scrollBehavior(){ return { top: 0 }; } });

const App = {
  components: { SiteBackdrop, SiteHeader, SiteFooter, Toasts, AlexIA },
  template: `<div><SiteBackdrop/><SiteHeader/><main id="main"><router-view v-slot="{Component}"><component :is="Component" :key="$route.path"/></router-view></main><SiteFooter/><Toasts/><AlexIA/></div>`,
};

(async () => {
  const loc = location.pathname.split('/').filter(Boolean)[0];
  // Si la URL trae idioma, manda; si no, conserva el detectado (guardado/navegador).
  if (['es','en','pt'].includes(loc)) { store.locale = loc; }
  document.documentElement.lang = store.locale;
  await loadDict(store.locale);
  const app = createApp(App);
  app.directive('reveal', reveal);
  app.use(router);
  app.mount('#app');
})();
