<template lang="pug">
span.path
  | {{ displayedValue }}
</template>
<script>
  
export default {
  props: {
    value: {
      type: String,
      required: true,
    },
    base: {
      type: String,
      required: false,
      default: null,
    }
  },
  computed: {
    displayedValue() {
      return this.value.startsWith(this.strippedBase) ? this.value.slice(this.strippedBase.length) : this.value;
    },
    strippedBase() {
      return this.base;
    }
  },
  data() {
    return {
    };
  },
};
</script>
<style lang="stylus" scoped>
.path
  //
</style>