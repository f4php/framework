<template lang="pug">
.page-header
  .page-header-request
    .page-header-request-method
      | {{ method }}
    .page-header-request-path
      | {{ path }}
    .page-header-request-extension
      | {{ extension }}
    .page-header-request-debug-extension
      | {{ debugExtension }}
  .page-header-response
    .page-header-response-code
      | {{ code }}
    .page-header-response-status
      | {{ status }}
</template>
<script>
  
  export default {
    name: 'PageHeader',
    components: {
    },
    props: {
    },
    computed: {
      method() {
        return this.$root.request.method;
      },
      path() {
        return this.$root.request.path;
      },
      extension() {
        return this.$root.request.extension;
      },
      debugExtension() {
        return this.$root.request.debugExtension;
      },
      code() {
        return this.$root.response.code;
      },
      status() {
        return this.$root.response.status;
      },
    },
    data() {
      return {
      };
    },
  };
</script>
<style lang="stylus" scoped>
.page-header
  --f4-header-font-size (20/16)rem
  --f4-header-color var(--p-text-color)
  --f4-header-color-muted var(--p-text-muted-color)
  color var(--f4-header-color)
  font-size var(--f4-header-font-size)
  &-request
    display flex
    align-items center
    font-family monospace
    margin-bottom 0.5rem
    &-method
      margin-right 0.75rem
    &-debug-extension
      color var(--f4-header-color-muted)
  &-response
    display flex
    align-items center
    font-family monospace
    color var(--f4-header-color-muted)
    &-code
      margin-right 0.75rem
    &-status
      //-
</style>