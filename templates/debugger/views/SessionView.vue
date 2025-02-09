<template lang="pug">
  VarDump(:value="session" v-if="session.length")
  EmptySection(v-else)
    template(#icon)
      span.pi.pi-user
    | Session data is empty
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
    session() {
      return this.$root.session;
    }
  },
  data() {
    return {};
  }
}
</script>
<style lang="stylus">
</style>