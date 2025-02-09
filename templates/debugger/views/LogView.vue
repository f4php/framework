<template lang="pug">
  Log(:value="log")
</template>
<script>

import Log from '../components/Log.vue';

export default {
  components: {
    Log
  },
  computed: {
    log() {
      return this.$root.log;
    }
  },
  data() {
    return {};
  }
}
</script>
<style lang="stylus">
</style>