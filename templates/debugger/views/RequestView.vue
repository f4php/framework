<template lang="pug">
  Tabs(value="headers")
    TabList
      Tab(value="headers")
        | Headers
      Tab(value="parameters")
        | Parameters
      Tab(value="body" :disabled="!body")
        | Unparsed Body
    TabPanels
      TabPanel(value="headers")
        HttpHeaders(:value="headers" v-if="headers")
        EmptySection(v-else)
          | There are no request headers
      TabPanel(value="parameters")
        VarDump(:value="parameters" v-if="parameters && parameters.length")
        EmptySection(v-else)
          | There are no request parameters
      TabPanel(value="body")
        FormattedValue(:value="body" type="code/form-data" v-if="body")
        EmptySection(v-else)
          | There's no request body
</template>
<script>

import Tab from 'primevue/tab';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';

import EmptySection from '../components/EmptySection.vue';
import FormattedValue from '../components/FormattedValue.vue';
import HttpHeaders from '../components/HttpHeaders.vue';
import VarDump from '../components/VarDump.vue';

export default {
  components: {
    EmptySection,
    FormattedValue,
    HttpHeaders,
    Tab,
    Tabs,
    TabList,
    TabPanel,
    TabPanels,
    VarDump,
  },
  computed: {
    body() {
      return this.$root.request.body;
    },
    headers() {
      return this.$root.request.headers;
    },
    parameters() {
      return this.$root.request.parameters;
    },
  },
  methods: {
  },
  data() {
    return {};
  }

}
</script>
<style lang="stylus">
</style>