import { defineConfig } from 'astro/config';
import tailwind from '@astrojs/tailwind';

export default defineConfig({
  output: 'static',
  site: 'https://pithead.co.uk',
  integrations: [
    tailwind({
      applyBaseStyles: true,
    }),
  ],
});
