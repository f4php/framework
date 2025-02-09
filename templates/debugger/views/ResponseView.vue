<template lang="pug">
.response
  Tabs(:value="defaultTab")
    TabList
      Tab(value="data")
        | Data
      Tab(value="meta" :disabled="!meta.length")
        | Meta Data
      Tab(value="exception" :disabled="!exception")
        | Exception
      Tab(value="headers")
        | Headers
      Tab(value="template" :disabled="!template")
        | Template Context
      Tab(value="body")
        | Body
    TabPanels.response-tabpanels
      TabPanel(value="data")
        template(v-if="data")
          VarDump(:value="data")
        template(v-else)
          EmptySection
            | No Response Data found
      TabPanel(value="meta")
        VarDump(:value="meta" v-if="meta && meta.length")
        EmptySection(v-else)
          | No Response Meta Data found
      TabPanel(value="exception")
        HttpException(:value="exception" v-if="exception")
        EmptySection(v-else)
          | There's no exception
      TabPanel(value="headers")
        HttpHeaders(:value="headers" v-if="headers")
        EmptySection(v-else)
          | There are no response headers
      TabPanel(value="template")
        VarDump(:value="template.context" v-if="template && template.context")
        EmptySection(v-else)
          template(#icon)
            span.pi.pi-book
          | There's no template context
        //- Tabs(value="context")
          TabList
            Tab(value="context")
              | Context
            Tab(value="contents" :disabled="true")
              | Contents
          TabPanels
            TabPanel(value="context" v-if="template")
              VarDump(:value="template.context")
              //- todo: add template folders list and maybe template body
            TabPanel(value="contents")
              Fieldset()
                template(#legend)
                  Path(:value="template.path" v-if="template")
                | Template body debugging is not supported yet
      TabPanel(value="body")
        FormattedValue(:value="body" :type="responseFormat" :prettify="true")
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
import HttpException from '../components/HttpException.vue';
import HttpHeaders from '../components/HttpHeaders.vue';
import Path from '../components/Path.vue';
import VarDump from '../components/VarDump.vue';

export default {
  components: {
    EmptySection,
    Fieldset,
    FormattedValue,
    HttpException,
    Tab,
    Tabs,
    TabList,
    TabPanel,
    TabPanels,
    HttpHeaders,
    Path,
    VarDump,
  },
  computed: {
    defaultTab() {
      return this.exception ? 'exception' : 'data';
    },
    body() {
      return this.$root.response.body;
    },
    data() {
      return this.$root.response.data;
    },
    exception() {
      return this.$root.response.exception;
    },
    headers() {
      return this.$root.response.headers;
    },
    meta() {
      return this.$root.response.meta;
    },
    template() {
      return this.$root.response.template;
    },
    responseFormat() {
      return this.$root.response.format;
    }
  },
  data() {
    return {};
  }
}
</script>
<style lang="stylus">
// .response

.wrap-text
  white-space: normal; /* Ensure normal text wrapping */
  overflow-wrap: break-word; /* Break long words if necessary */
  word-wrap: break-word; /* For legacy browser support */
  word-break: break-word; /* Ensure breaks in long words */
</style>