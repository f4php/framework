import { createApp } from 'vue';
import router from './router';
import PrimeVue from 'primevue/config';
import AuraTheme from '@primevue/themes/aura';
import 'modern-normalize/modern-normalize.css';
import 'primeicons/primeicons.css';
import DebuggerApp from './DebuggerApp.vue';
import Tooltip from 'primevue/tooltip';
import ToastService from 'primevue/toastservice';

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

const mountPointID = 'debugger-app';

createApp(DebuggerApp, parseDataAttributes(document.getElementById(mountPointID)))
  .use(router)
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
  .use(ToastService)
  .directive('tooltip', Tooltip)
  .mount(`#${mountPointID}`);