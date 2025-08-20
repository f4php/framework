<template lang="pug">
.localizer
  .localizer-table(v-if="value")
    .localizer-row-name
      | Negotiated Locale:
    .localizer-row-value
      | {{ value.locale }}
    .localizer-row-name
      | Loaded Resources:
    .localizer-row-value
        template(v-for="resource, index in value.resources")
          | {{ resource }}
          template(v-if="index < value.resources.length-1")
            |, 
    .localizer-row-name
      | Available Locales:
    .localizer-row-value
        template(v-for="locale, index in value.availableLocales")
          | {{ locale }}
          template(v-if="index < value.availableLocales.length-1")
            |, 
  EmptySection(v-else)
    template(#icon)
      span.pi.pi-language
    | No localizer was found
</template>
<script>

import EmptySection from './EmptySection.vue';

export default {
  components: {
    EmptySection,
  },
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  computed: {
  },
  methods: {
  },
  data() {
    return {
      filter: ''
    };
  }
}
</script>
<style lang="stylus">
.localizer
  --f4-headers-name-color var(--p-text-color)
  --f4-headers-value-color var(--p-text-muted-color)
  --f4-headers-border-color var(--p-surface-700)
  --f4-headers-padding 0.75rem
  font-family var(--font-family)
  &-table
    display grid
    align-items stretch
    grid-template-columns auto 1fr
  &-row
    &-empty
      grid-column 1 / span 2
      text-align center
      padding 1.5rem 0
      font-style italic
    &-name
      display flex
      align-items center
      font-family monospace
      white-space nowrap
      color var(--f4-headers-name-color)
      border-bottom 1px solid var(--f4-headers-border-color)
      padding var(--f4-headers-padding)
      padding-left 0
    &-value
      display flex
      align-items center
      white-space nowrap
      font-family monospace
      overflow scroll
      text-overflow ellipsis
      padding var(--f4-headers-padding)
      color var(--f4-headers-value-color)
      border-bottom 1px solid var(--f4-headers-border-color)
  & .p-datatable-header
    padding-left 0
    padding-right 0
  & .p-datatable-tbody > tr > td:first-child
    padding-left 0
  & .p-datatable-tbody > tr > td:last-child
    padding-right 0
</style>