import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import AuraTheme from '@primevue/themes/aura';
import 'modern-normalize/modern-normalize.css';
import 'primeicons/primeicons.css';

import ExceptionApp from './ExceptionApp.vue';

const parseDataAttributes = function (element) {
  const data = {};
  for (const attr of element.attributes) {
    if (attr.name.startsWith('data-')) {
      const key = attr.name.slice(5).replace(/-([a-z])/g, (_, char) => char.toUpperCase());
      try {
        data[key] = JSON.parse(attr.value);
      } catch (e) {
        console.warn(`Failed to parse data attribute "${attr.name}": ${e.message}`);
      }
    }
  }
  return data;
}

const mountPointID = 'exception-app';

createApp(ExceptionApp, parseDataAttributes(document.getElementById(mountPointID)))
  .use(PrimeVue, {
    theme: {
      preset: AuraTheme,
      options: {
        darkModeSelector: 'system',
        // cssLayer: false
      }
    },
    ripple: true
  })
  .mount(`#${mountPointID}`);