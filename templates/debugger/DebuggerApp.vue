<template lang="pug">
  router-view()/
</template>
<script>
export default {
  props: {
    config: {
      type: Array,
      required: true
    },
    log: {
      type: Array,
      required: true
    },
    profiler: {
      type: Array,
      required: true
    },
    project: {
      type: Object,
      required: true
    },
    queries: {
      type: Array,
      required: true
    },
    request: {
      type: Object,
      required: true
    },
    response: {
      type: Object,
      required: true
    },
    route: {
      type: Object,
      required: true
    },
    session: {
      type: Array,
      required: true
    },
    system: {
      type: Array,
      required: true
    },
  }
};
</script>
<style lang="stylus">
html
  font-size 16px
body
  font-family sans-serif
</style>