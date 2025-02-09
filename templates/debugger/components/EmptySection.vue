<template lang="pug">
  .empty-section
    .empty-section-icon
      slot(name="icon")
        span.pi.pi-info-circle
    .empty-section-text
      slot
        | Nothing found
</template>
<script>

export default {
  components: {
  },
  props: {
  },
  computed: {
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
.empty-section
  --f4-empty-section-color var(--p-text-muted-color)
  --f4-empty-section-icon-color var(--p-surface-600)
  padding 2rem 0 
  &-icon
    text-align center
    font-size 5rem
    margin-bottom 1rem
    .pi
      color var(--f4-empty-section-icon-color)
      font-size 5rem
  &-text
    text-align center
    color var(--f4-empty-section-color)
</style>