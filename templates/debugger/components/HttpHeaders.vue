<template lang="pug">
.headers
  .headers-table(v-if="items.length")
    .headers-row-search
      IconField
        InputIcon
          i.pi.pi-search
        InputText(v-model="filter" placeholder="Search")/
    template(v-for="header in displayedItems" v-if="displayedItems.length")
      .headers-row-name
        | {{ capitalize(header.name) }}:
      .headers-row-value
        | {{ header.value.join(' ') }}
      .headers-row-controls
        Toast
        Button(
          icon="pi pi-copy" 
          severity="secondary" 
          v-tooltip="{ value: 'Copy header to clipboard', showDelay: 700 }"
          @click.prevent="copyHeaderToClipoboard(header)")/
    EmptySection(v-else).headers-row-empty
      | No matching headers were found
  EmptySection(v-else)
    | No headers were found
</template>
<script>

import Button from 'primevue/button';
import EmptySection from './EmptySection.vue';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Toast from 'primevue/toast';

export default {
  components: {
    Button,
    EmptySection,
    IconField,
    InputIcon,
    InputText,
    Toast,
  },
  props: {
    value: {
      type: Object,
      required: true,
    }
  },
  computed: {
    items() {
      return this
        .mapObjectToArray(this.value);
    },
    displayedItems() {
      return this
        .items
        .filter((item) => {
          return !this.filter
            || (item.name.toLowerCase().indexOf(this.filter.toLowerCase()) !== -1)
            || (item.value.findIndex((valueItem) => valueItem.toLowerCase().indexOf(this.filter.toLowerCase()) !== -1) !== -1);
        })
        .sort((headerA, headerB) => headerA.name.localeCompare(headerB.name));
    }
  },
  methods: {
    isObject(value) {
      return value !== null && typeof value === "object" && !Array.isArray(value);
    },
    capitalize(name) {
      return name.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join('-');
    },
    mapObjectToArray(items) {
      return Object.entries(items).map(([name, value]) => ({
        name,
        value: [...new Set(value)] // only keep unique values
      }))
    },
    copyHeaderToClipoboard(header) {
      window.navigator.clipboard.writeText(this.capitalize(header.name) + ': '+header.value.join(' '));
      this.$toast.add({ severity: 'secondary', summary: 'Copied!',  detail: 'Header data was copied to clipboard', life: 3000 });
    }
  },
  data() {
    return {
      filter: ''
    };
  }
}
</script>
<style lang="stylus">
.headers
  --f4-headers-name-color var(--p-text-color)
  --f4-headers-value-color var(--p-text-muted-color)
  --f4-headers-border-color var(--p-surface-700)
  --f4-headers-padding 0.75rem
  font-family var(--font-family)
  &-table
    display grid
    align-items stretch
    grid-template-columns auto 1fr auto
  &-row
    &-empty
      grid-column 1 / span 3
      text-align center
    &-search
      display flex
      justify-content flex-end
      grid-column 1 / span 3
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
    &-controls
      border-bottom 1px solid var(--f4-headers-border-color)
      padding var(--f4-headers-padding)
      padding-right 0
  & .p-datatable-header
    padding-left 0
    padding-right 0
  & .p-datatable-tbody > tr > td:first-child
    padding-left 0
  & .p-datatable-tbody > tr > td:last-child
    padding-right 0
</style>