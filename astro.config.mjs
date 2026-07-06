import { defineConfig } from 'astro/config';

const SITE_URL = 'https://experientia.pro';

export default defineConfig({
  site: SITE_URL,
  trailingSlash: 'ignore',
  build: {
    format: 'directory',
  },
});
