<template lang="pug">
  Tabs(value="handler")
    TabList
      Tab(value="handler")
        | Handler
      Tab(value="parameters")
        | Parameters
      Tab(value="request-middleware" :disabled="!requestMiddlewares")
        | Request Middleware
      Tab(value="response-middleware" :disabled="!responseMiddlewares")
        | Response Middleware
    TabPanels
      TabPanel(value="handler")
        Fieldset(v-if="route.code")
          template(#legend)
            Path(:value="route.file" v-if="route.file")
          FormattedValue(:value="route.code" type="code/php" :lines-offset="route.line" :show-lines="true" :prettify="true")
        EmptySection(v-else)
          template(#icon)
            span.pi.pi-directions
          | No matching route found
      TabPanel(value="parameters")
        VarDump(:value="route.parameters" v-if="route.parameters && route.parameters.length")
        EmptySection(v-else)
          | No route parameters found
      TabPanel(value="request-middleware")
        template(v-for="middleware in requestMiddlewares" v-if="requestMiddlewares?.length")
          Fieldset
            template(#legend)
              Path(:value="middleware.file")
            FormattedValue(:value="middleware.code" type="code/php" :lines-offset="middleware.line" :show-lines="true" :prettify="true")
        EmptySection(v-else)
          | No request middleware found
      TabPanel(value="response-middleware")
        template(v-for="middleware in responseMiddlewares" v-if="responseMiddlewares?.length")
          Fieldset
            template(#legend)
              Path(:value="middleware.file")
            FormattedValue(:value="middleware.code" type="code/php" :lines-offset="middleware.line" :show-lines="true" :prettify="true")
        EmptySection(v-else)
          | No response middleware found
</template>
<script>

import Fieldset from 'primevue/fieldset';
import Tab from 'primevue/tab';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';

import EmptySection from '../components/EmptySection.vue';
import FormattedValue from '../components/FormattedValue.vue';
import Path from '../components/Path.vue';
import VarDump from '../components/VarDump.vue';

export default {
  components: {
    Tab,
    Tabs,
    TabList,
    TabPanel,
    TabPanels,
    EmptySection,
    Fieldset,
    FormattedValue,
    Path,
    VarDump,
  },
  computed: {
    route() {
      return this.$root.route;
    },
    requestMiddlewares() {
      return [
        this.$root.route.requestMiddleware,
        this.$root.route.routeGroupRequestMiddleware,
        this.$root.route.routeRequestMiddleware
      ].filter(v=>v);
    },
    responseMiddlewares() {
      return [
        this.$root.route.routeResponseMiddleware,
        this.$root.route.routeGroupResponseMiddleware,
        this.$root.route.responseMiddleware
      ].filter(v=>v);
    }
  }
}
</script>
<style lang="stylus">
</style>