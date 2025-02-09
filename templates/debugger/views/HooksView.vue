<template lang="pug">
  VarDump(:value="hooks")
</template>
<script>

import VarDump from '../components/VarDump.vue';

export default {
  components: {
    VarDump
  },
  computed: {
    hooks() {
      return this.$root.hooks;
    }
  },
  data() {
    return {};
  }
}
</script>
<style lang="stylus">
</style>