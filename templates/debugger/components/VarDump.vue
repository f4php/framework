<template lang="pug">
  .var-dump
    //- .var-dump-filter(v-if="showFilter")
      IconField
        InputIcon
          i.pi.pi-search
        InputText(v-model="filter" placeholder="Search")/
    template(v-for="row in displayedRows")
      .var-dump-name
        .var-dump-name-level(:style="`--level: ${level}`")
        button.var-dump-name-control(
          @click.prevent="row.expanded = !row.expanded" 
          v-if="row.complex && row.value.length"
          :class="{'var-dump-name-control-expanded': row.expanded}"
        ).pi.pi-chevron-right
        .var-dump-name-control-empty(v-else)
        .var-dump-name-label
          span.var-dump-name-label-meta(v-if="row.meta && showMeta")
            | {{ row.meta.modifier || '' }}
            |  
            | {{ row.meta.type || '' }}
          |  
          | {{ row.name }}
      FormattedValue(
        :value="unwrapQuotes ? row.preview.replace(/^\"|\"$/g, '') : row.preview" 
        :type="showTypes ? row.type : ''"
        :class="{'var-dump-value-preview': row.complex, 'var-dump-value-compact': compact}"
      ).var-dump-value
      template(v-if="row.complex && row.expanded")
        VarDump(
          :value="row.value"
          :show-filter="false"
          :show-meta="showMeta"
          :show-types="showTypes"
          :unwrap-quotes="unwrapQuotes"
          :level="level + 1"
          :sort="sort"
          :compact="compact"
        )
</template>
<script>
  import IconField from 'primevue/iconfield';
  import InputIcon from 'primevue/inputicon';
  import InputText from 'primevue/inputtext';
  import FormattedValue from './FormattedValue.vue';
  
  export default {
    name: 'VarDump',
    components: {
      FormattedValue,
      IconField,
      InputIcon,
      InputText,
    },
    props: {
      showFilter: {
        type: Boolean,
        required: false,
        default: true,
      },
      showMeta: {
        type: Boolean,
        required: false,
        default: true,
      },
      showTypes: {
        type: Boolean,
        required: false,
        default: true,
      },
      unwrapQuotes: {
        type: Boolean,
        required: false,
        default: false,
      },
      level: {
        type: Number,
        required: false,
        default: 0,
      },
      sort: {
        type: Boolean,
        required: false,
        default: false,
      },
      compact: {
        type: Boolean,
        required: false,
        default: false,
      },
      value: {
        type: [Object, Array, String, Number],
        required: true,
      }
    },
    computed: {
      displayedRows() {
        return this.rows;
      },
    },
    data() {
      return {
        filter: '',
        rows: this.convertToRows(this.value)
      };
    },
    watch: {
      value() {
        this.rows = this.convertToRows(this.value);
      }
    },
    methods: {
      convertToRows(data) {
        const rows = [];
        if(data.map) {
            data
              .map(entry => rows.push(Object.assign(
                entry,
                {
                  expanded: false
                }
              )));
        }
        else {
          rows[0] = data;
        }
        return this.sort ? rows.sort((r1, r2) => r1.name.localeCompare(r2.name)): rows;
      }
    }
  };
</script>
<style lang="stylus" scoped>
.var-dump
  --f4-vardump-border-color var(--p-surface-800)
  --f4-vardump-header-cell-padding 0.75rem 1rem
  --f4-vardump-toggle-button-size 1.75rem
  --f4-vardump-toggle-button-color var(--p-text-muted-color)
  --f4-vardump-toggle-button-border-radius 50%
  --f4-vardump-transition-duration var(--p-transition-duration)
  --f4-vardump-toggle-button-hover-color var(--p-text-color)
  --f4-vardump-toggle-button-hover-background var(--p-content-hover-background)
  width 100%
  display grid
  grid-template-columns auto 1fr
  gap 0 0
  border-bottom 1px solid var(--f4-vardump-border-color)
  &-filter
    padding var(--f4-vardump-header-cell-padding)
    grid-column 1 / span 2
    justify-items end
  &-header
    &-name
      grid-column 1 / span 1
    &-value
      //
  &-name
    display flex
    align-items center
    justify-content flex-start
    white-space nowrap
    font-family monospace
    padding var(--f4-vardump-header-cell-padding)
    padding-left 0
    border-top 1px solid var(--f4-vardump-border-color)
    &-level
      height 100%
      // background "radial-gradient(circle, %s 25%, transparent 25%) 0 0 / %s %s" % var(--p-content-border-color) calc(var(--f4-vardump-toggle-button-size) / 3.5) calc(var(--f4-vardump-toggle-button-size) / 3.5)
      // background-repeat repeat-x
      // background-position left center
      padding-left "calc(var(--level) * %s)" % (14/14)rem
    &-control
      display: inline-flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
      width var(--f4-vardump-toggle-button-size)
      height var(--f4-vardump-toggle-button-size)
      color var(--f4-vardump-toggle-button-color)
      border 0 none
      background transparent
      cursor pointer
      border-radius var(--f4-vardump-toggle-button-border-radius)
      transition \
        background var(--f4-vardump-transition-duration), \
        color var(--f4-vardump-transition-duration), \
        border-color var(--f4-vardump-transition-duration), \
        outline-color var(--f4-vardump-transition-duration), \
        box-shadow var(--f4-vardump-transition-duration), \
        transform var(--f4-vardump-transition-duration)
      outline-color transparent
      user-select none
      &:hover
        color var(--f4-vardump-toggle-button-hover-color)
        background var(--f4-vardump-toggle-button-hover-background)
      &-empty
        // background "radial-gradient(circle, %s 25%, transparent 25%) 0 0 / %s %s" % var(--p-content-border-color) calc(var(--f4-vardump-toggle-button-size) / 3.5) calc(var(--f4-vardump-toggle-button-size) / 3.5)
        // background-repeat repeat-x
        // background-position left center
        width var(--f4-vardump-toggle-button-size)
        height 100%
      &-expanded
        transform rotate(90deg)
    &-label
      margin-left (8/16)rem
      &-meta
        display inline-block
        color var(--p-text-muted-color)
  &-value
    padding var(--f4-vardump-header-cell-padding)
    padding-right 0
    border-top 1px solid var(--f4-vardump-border-color)
    &-compact
      white-space nowrap
      overflow-x scroll
      text-overflow ellipsis
  & &
    grid-column 1 / span 2
    grid-template-columns subgrid
    border-bottom none
</style>