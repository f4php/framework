<template lang="pug">
span.formatted-value
  template(v-if="prettify")
    .formatted-value-content
      .formatted-value-content-code
        .formatted-value-content-code-line(v-for="line in formattedValueLines" :class="{'formatted-value-content-code-line-highlighted': highlightedLine === line.number}")
          .formatted-value-content-code-line-number(v-if="showLines")
            | {{ line.number }}
          .formatted-value-content-code-line-code
            | {{ line.code }}
  template(v-else)
    .formatted-value-content-simple
      | {{ formattedValueSimple }}
      |  
      Tag(v-if="type" severity="secondary" :value="type").formatted-value-content-type
      //- SplitButton(:model="formatMenu" severity="secondary" size="small" :text="true").formatted-value-button
        template(v-slot:dropdownicon)
          i.pi.pi-ellipsis-h
</template>
<script>

import SplitButton from 'primevue/splitbutton';
import Tag from 'primevue/tag';

export default {
  components: {
    SplitButton,
    Tag,
  },
  props: {
    value: {
      type: String,
      required: true
    },
    type: {
      type: String,
      required: false,
      default: 'text',
    },
    linesOffset: {
      type: Number,
      required: false,
      default: 1
    },
    highlightedLine: {
      type: Number,
      required: false,
      default: null
    },
    showLines: {
      type: Boolean,
      required: false,
      default: false
    },
    prettify: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  computed: {
    formatMenu() {
      return [
        {
          label: 'PHP',
          command: () => { }
        },
        {
          label: 'SQL',
          command: () => { }
        },
        {
          label: 'JSON',
          command: () => { }
        },
        {
          label: 'HTML',
          command: () => { }
        },
        {
          label: 'Plain text',
          command: () => { }
        },
      ];
    },
    commonLeadingWhitespaceLength() {
      return this.formattedValueSimple ? this.formattedValueSimple.split(/\n/)
        .filter(v => v) // ignore empty lines
        .map(str => str.match(/^\s*/)[0].length)
        .reduce((min, current) => Math.min(min, current), Infinity)
        : 0;
    },
    formattedValueSimple() {
      return (this.type === 'text/html') 
        ? this.prettyPrintHTML(this.value) 
        : (this.type === 'application/json') 
          ? this.prettyPrintJSON(this.value)
          : this.value;
    },
    formattedValueLines() {
      const leadingWhitespacelength = this.commonLeadingWhitespaceLength;
      return this.formattedValueSimple ? this
        .formattedValueSimple
        .split(/\n/)
        .map((line, index) => ({
          number: index + this.linesOffset,
          code: line.slice(leadingWhitespacelength)
        }))
        : [];
    },
  },
  data() {
    return {
      format: 'text',
      expanded: false
    }
  },
  methods: {
    prettyPrintHTML(htmlString) {
      const parser = new DOMParser();
      const doc = parser.parseFromString(htmlString, 'text/html');
      function formatNode(node, level = 0) {
        const indent = '  '.repeat(level);
        let result = '';
        switch (node.nodeType) {
          case Node.ELEMENT_NODE:
            result += `${indent}<${node.tagName.toLowerCase()}`;
            Array.from(node.attributes).forEach(attr => {
              result += ` ${attr.name}="${attr.value}"`;
            });
            if (node.childNodes.length === 0) {
              result += '/>\n';
            } else {
              result += '>\n';
              Array.from(node.childNodes).forEach(child => {
                result += formatNode(child, level + 1);
              });
              result += `${indent}</${node.tagName.toLowerCase()}>\n`;
            }
            break;
          case Node.TEXT_NODE:
            const text = node.textContent.trim();
            if (text) {
              result += `${indent}${text}\n`;
            }
            break;
        }
        return result;
      }
      return formatNode(doc.documentElement);
    },
    prettyPrintJSON(jsonString) {
      try {
          const obj = JSON.parse(jsonString);
          return JSON.stringify(obj, null, 2);
      } catch (e) {
          throw new Error('Invalid JSON string: ' + e.message);
      }
    }
  }
}
</script>
<style lang="stylus">
.formatted-value
  --f4-formatted-value-color var(--p-text-muted-color)
  --f4-formatted-value-highlighted-color var(--p-text-color)
  --f4-formatted-value-button-border-radius var(--p-button-border-radius)
  --f4-formatted-value-line-number-color var(--p-surface-700)
  --f4-formatted-value-highlighted-line-number-color var(--p-surface-500)
  width 100%
  max-width 100vw
  display flex
  align-items center
  justify-content space-between
  &-content
    flex 1
    line-height 1.5rem
    overflow scroll
    &-type
      display inline-block
    &-simple
      display inline-block
      color var(--f4-formatted-value-color)
      font-family monospace
    &-code
      display grid
      color var(--f4-formatted-value-color)
      font-family monospace
      &-line
        display grid
        grid-template-columns auto 1fr
        grid-template-rows subgrid;
        gap 0 0.25rem
        &:hover
          background-color "color-mix(in srgb, %s 5%, transparent)" % var(--f4-formatted-value-color)
        &-highlighted
        &-highlighted:hover
          background-color "color-mix(in srgb, %s 15%, transparent)" % var(--f4-formatted-value-color)
        &-number
          padding 0rem 0.5rem 
          color var(--f4-formatted-value-line-number-color)
          ../-highlighted &
            color var(--f4-formatted-value-highlighted-line-number-color)
        &-code
          white-space pre
          ../-highlighted &
            color var(--f4-formatted-value-highlighted-color)
  &-button
    .p-button
      border-radius var(--f4-formatted-value-button-border-radius) !important
</style>