<template lang="pug">
  VarDump(:value="config" :show-meta="false" :sort="true")
</template>
<script>

import VarDump from '../components/VarDump.vue';

export default {
  components: {
    VarDump
  },
  computed: {
    config() {
      return this.$root.config;
    }
  },
  data() {
    return {};
  }
}
</script>
<style lang="stylus">
</style>