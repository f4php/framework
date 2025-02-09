<template lang="pug">
.exception
  h1.exception-message
    | {{ exception.class}}:
    | {{ exception.message}}
  h2(v-if="exception.code").exception-code
    | Status code: {{ exception.code}}
    |  
  .exception-file(v-if="exception.file")
    | {{ exception.file }}
    template(v-if="exception.line")
      | :{{ exception.line }}
  .exception-backtrace(v-if="exception.trace")
    Backtrace(:value="exception.trace")
</template>
<script>

import Backtrace from '../debugger/components/Backtrace.vue';

export default {
  components: {
    Backtrace
  },
  props: {
    exception: {
      type: Object,
      required: true
    }
  }
};
</script>
<style lang="stylus">
html
  font-size 16px
body
  font-family sans-serif
.exception
  padding 1rem 1.125rem
  max-width 100%
  &-message
  &-code
    font-family sans-serif
  &-file
    font-family monospace
    font-size (14/16)rem
  &-backtrace
    margin-top 1.125rem
</style>