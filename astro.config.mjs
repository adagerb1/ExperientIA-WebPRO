import { defineConfig } from 'astro/config';

// TODO: reemplazar por el dominio de producción definitivo de ExperientIA.
const SITE_URL = 'https://experientia.example.com';

export default defineConfig({
  site: SITE_URL,
  trailingSlash: 'ignore',
  build: {
    format: 'directory',
  },
});
