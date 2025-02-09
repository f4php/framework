import { createRouter, createWebHashHistory } from 'vue-router';
import Layout from '../layout/Layout.vue';

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: '/',
      component: Layout,
      redirect: to => {
        return 'response';
      },
      children: [
        {
          path: 'config',
          name: 'config',
          component: () => import('../views/ConfigView.vue')
        },
        {
          path: 'hooks',
          name: 'hooks',
          component: () => import('../views/HooksView.vue')
        },
        {
          path: 'queries',
          name: 'queries',
          component: () => import('../views/QueriesView.vue')
        },
        {
          path: 'request',
          name: 'request',
          component: () => import('../views/RequestView.vue')
        },
        {
          path: 'response',
          name: 'response',
          component: () => import('../views/ResponseView.vue')
        },
        {
          path: 'route',
          name: 'route',
          component: () => import('../views/RouteView.vue')
        },
        {
          path: 'profiler',
          name: 'profiler',
          component: () => import('../views/ProfilerView.vue')
        },
        {
          path: 'session',
          name: 'session',
          component: () => import('../views/SessionView.vue')
        },
        {
          path: 'system',
          name: 'system',
          component: () => import('../views/SystemView.vue')
        },
        {
          path: 'log',
          name: 'log',
          component: () => import('../views/LogView.vue')
        },
      ]
    }
  ]
});

export default router;