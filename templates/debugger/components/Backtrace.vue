<template lang="pug">
.backtrace
  .backtrace-navigation
    .backtrace-navigation-item(v-for="entry, index in displayedEntries" :class="{'backtrace-navigation-item-selected': index === selectedIndex}" @click="selectedIndex = index")
      .backtrace-navigation-item-class(v-if="entry?.class")
        span
          | {{ entry?.class || '' }}
      .backtrace-navigation-item-method(v-if="entry?.method")
        span
          template(v-if="!entry.method.includes('()')")
            | {{ entry.method }}()
          template(v-else)
            | {{ entry.method }}
      .backtrace-navigation-item-file
        span
          template(v-if="entry?.base")
            | {{ entry.base }}/
          | {{ entry?.file || '(unknown path)' }}
          template(v-if="entry?.line")
            | :{{ entry.line }}
  .backtrace-source
    .backtrace-source-code(v-if="selectedEntry?.file")
      Fieldset
        template(#legend)
          Path(:value="selectedEntry?.file")
        FormattedValue(
          :value="selectedSource"
          :lines-offset="selectedEntry?.line + selectedSourceLineOffset"
          :show-lines="true"
          :highlighted-line="selectedEntry?.line"
          type="code/php"
          :prettify="true"
        )
    .backtrace-source-arguments
      Fieldset(v-if="selectedEntry.arguments?.value?.length")
        template(#legend)
          | Arguments
        VarDump(:value="selectedEntry.arguments.value" :compact="true")
</template>
<script>
  
import Fieldset from 'primevue/fieldset';
import FormattedValue from '../components/FormattedValue.vue';
import Path from '../components/Path.vue';
import VarDump from '../components/VarDump.vue';

export default {
  components: {
    Path,
    Fieldset,
    FormattedValue,
    Path,
    VarDump,
  },
  props: {
    value: {
      type: Array,
      required: true,
    }
  },
  computed: {
    displayedEntries() {
      return this
        .value
        .filter(entry => !entry.vendor)
        .map(entry => {
          const [base, file] = [(entry.file && entry.file.match(/\//)) ? entry.file.slice(0, entry.file.lastIndexOf('/')) : null, entry.file ? entry.file.split('/').pop() : ''];
          return {
            ...entry,
            base,
            file
          };
        })
    },
    selectedEntry() {
      return this.displayedEntries[this.selectedIndex];
    },
    selectedSource() {
      return this.selectedEntry.source;
    },
    selectedSourceLines() {
      return this.selectedEntry.source.split("\n");
    },
    selectedSourceStartLine() {
      return this.selectedEntry.line;
    },
    selectedSourceLineOffset() {
      return this.selectedEntry.offset;
    }
  },
  methods: {

  },
  data() {
    return {
      selectedIndex: 0
    };
  }
}
</script>
<style lang="stylus">
.backtrace
  --f4-backtrace-transition-duration var(--p-transition-duration)
  --f4-backtrace-border-color var(--p-surface-700)
  --f4-backtrace-class-color var(--p-text-muted-color)
  --f4-backtrace-method-color var(--p-text-color)
  --f4-backtrace-file-color var(--p-surface-500)
  --f4-backtrace-line-number-color var(--p-surface-600)
  --f4-backtrace-line-source-color var(--p-text-muted-color)
  --f4-backtrace-line-source-active-color var(--p-text-color)
  --f4-backtrace-line-source-active-background-color var(--p-text-color)
  --f4-backtrace-hover-item-background-color var(--p-text-muted-color)
  --f4-backtrace-selected-item-background-color var(--p-text-muted-color)
  font-family monospace
  display grid
  grid-template-columns 25% auto
  gap 0 1.125rem
  font-size 0.875rem
  max-width 100%
  &-navigation
    padding-top 1rem
    &-item
      cursor pointer
      border-bottom 1px solid var(--f4-backtrace-border-color)
      padding 0.875rem 1.125rem 1.125rem 1.125rem
      transition-duration var(--f4-backtrace-transition-duration)
      border-right 1px solid var(--f4-backtrace-border-color)
      &:last-of-type
        border-bottom none
      &:hover
        background-color "color-mix(in srgb, %s 5%, transparent)" % var(--f4-backtrace-hover-item-background-color)
      &-selected
      &-selected:hover
        background-color "color-mix(in srgb, %s 15%, transparent)" % var(--f4-backtrace-hover-item-background-color)
        border-right 1px solid transparent
      &-class
        direction rtl
        text-align left
        white-space nowrap
        text-overflow ellipsis
        overflow hidden
        color var(--f4-backtrace-class-color)
        line-height 1.125rem
        font-size (14/16)rem
        margin-bottom 0.5rem
        span
          direction ltr
          unicode-bidi bidi-override
      &-method
        direction rtl
        text-align left
        white-space nowrap
        text-overflow ellipsis
        overflow hidden
        font-size (15/16)rem
        color var(--f4-backtrace-method-color)
        line-height 1.125rem
        margin-bottom 0.5rem
        unicode-bidi bidi-override
        span
          direction ltr
          unicode-bidi bidi-override
      &-file
        direction rtl
        text-align left
        white-space nowrap
        text-overflow ellipsis
        overflow hidden
        font-size (12/16)rem
        color var(--f4-backtrace-file-color)
        line-height 1.125rem
        span
          direction ltr
          unicode-bidi bidi-override
  &-source
    overflow-x scroll
    &-code
      margin-bottom 1.125rem
</style>