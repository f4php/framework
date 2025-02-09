<template lang="pug">
  VarDump(:value="queries" :show-types="false" :unwrap-quotes="true" v-if="queries.length")
  EmptySection(v-else)
    template(#icon)
      span.pi.pi-database
    | Query list is empty
</template>
<script>

import EmptySection from '../components/EmptySection.vue';
import VarDump from '../components/VarDump.vue';

export default {
  components: {
    EmptySection,
    VarDump,
  },
  computed: {
    queries() {
      return this.$root.queries;
    }
  },
  data() {
    return {};
  }
}
</script>
<style lang="stylus">
</style>