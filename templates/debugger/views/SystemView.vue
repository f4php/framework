<template lang="pug">
  VarDump(:value="system" :show-types="false" :unwrap-quotes="true")
</template>
<script>

import VarDump from '../components/VarDump.vue';

export default {
  components: {
    VarDump
  },
  computed: {
    system() {
      return this.$root.system;
    }
  },
  data() {
    return {};
  }
}
</script>
<style lang="stylus">
</style>