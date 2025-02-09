<template lang="pug">
.exception
  .exception-message
    | Message: {{ exception.message}}
  .exception-code(v-if="exception.code")
    | Code: {{ exception.code}}
  .exception-backtrace
    Backtrace(:value="exception.trace")
</template>
<script>

import Backtrace from './Backtrace.vue';

export default {
  components: {
    Backtrace
  },
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  computed: {
    exception() {
      return this.$root.response.exception;
    }
  },
  methods: {
    
  },
  data() {
    return {
      
    };
  }
}
</script>
<style lang="stylus">
.exception
  &-message
    //
    margin-bottom 0.5rem
  &-code
    font-size 0.875rem
  &-backtrace
    margin-top 1rem
</style>