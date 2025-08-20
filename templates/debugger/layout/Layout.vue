<template lang="pug">
.layout
  PageHeader.layout-header
  Tabs(:value="currentRouteName").layout-menu
    TabList
      Tab(v-for="route in routes" :key="route.key" :value="route.to.name" :to="route.to" as="router-link" :disabled="route.disabled").layout-menu-item
        i(:class="route.icon")
        span
          | {{ route.label}}
    TabPanels.layout-tabpanels
      router-view()/
</template>
<script>

import PageHeader from '../components/PageHeader.vue';
import Tab from 'primevue/tab';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import TabPanels from 'primevue/tabpanels';

export default {
  name: 'debug-app',
  components: {
    PageHeader,
    Tab,
    Tabs,
    TabList,
    TabPanels,
  },
  computed: {
    currentRouteName() {
      return this.$route.name;
    },
  },
  data() {
    return {
      routes: [
        {
          label: 'Request',
          icon: 'pi pi-sign-in',
          // shortcut: '⌘+1',
          key: 'request',
          to: { name: "request" },
          disabled: false,
        },
        {
          label: 'Response',
          icon: 'pi pi-sign-out',
          // shortcut: '⌘+2',
          key: 'response',
          to: { name: "response" },
          disabled: false,
        },
        {
          label: 'Route',
          icon: 'pi pi-directions',
          // shortcut: '⌘+3',
          key: 'route',
          to: { name: "route" },
          disabled: false,
        },
        {
          label: 'Queries',
          icon: 'pi pi-database',
          // shortcut: '⌘+4',
          key: 'queries',
          to: { name: "queries" },
          disabled: !(this.$root.queries.length || 0),
        },
        {
          label: 'Session',
          icon: 'pi pi-user',
          // shortcut: '⌘+5',
          key: 'session',
          to: { name: "session" },
          disabled: false,
        },
        {
          label: 'Localizer',
          icon: 'pi pi-language',
          // shortcut: '⌘+6',
          key: 'localizer',
          to: { name: "localizer" },
          disabled: false,
        },
        {
          label: 'Log',
          icon: 'pi pi-list',
          // shortcut: '⌘+7',
          key: 'log',
          to: { name: "log" },
          disabled: false,
        },
        // {
        //   label: 'Hooks',
        //   icon: 'pi pi-code',
        //   // shortcut: '⌘+2',
        //   key: 'hooks',
        //   to: { name: "hooks" },
        // disabled: false,
        // },
        {
          label: 'Profiler',
          icon: 'pi pi-chart-line',
          // shortcut: '⌘+8',
          key: 'profiler',
          to: { name: "profiler" },
          disabled: false,
        },
        {
          label: 'Config',
          icon: 'pi pi-cog',
          key: 'config',
          // shortcut: '⌘+9',
          to: { name: "config" },
          disabled: false,
        },
        {
          label: 'System',
          icon: 'pi pi-info-circle',
          // shortcut: '⌘+0',
          key: 'system',
          to: { name: "system" },
          disabled: false,
        },
      ]
    }
  }
}
</script>
<style lang="stylus">
.layout
  font-size (14/16)rem
  &-header
    padding 1rem 1.125rem
  &-menu
    &-item
      display flex
      gap 0.5rem
      justify-content center
      align-items center
      text-decoration none !important
      i
        min-width 1rem
  &-tabpanels .p-tabpanels
    padding-left 0
    padding-right 0

</style>