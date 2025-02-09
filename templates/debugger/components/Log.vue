<template lang="pug">
.log
  .log-entry(v-for="entry in value" v-if="value.length")
    Fieldset(:legend = "entry.description || '(no description)'" :toggleable="true")
      VarDump(:value="entry.value")
  EmptySection(v-else)
    template(#icon)
      span.pi.pi-list
    | Log is empty
</template>
<script>
  
import Fieldset from 'primevue/fieldset';

import EmptySection from './EmptySection.vue';
import VarDump from './VarDump.vue';

export default {
  components: {
    EmptySection,
    Fieldset,
    VarDump,
  },
  props: {
    value: {
      type: Array,
      required: true,
    }
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
.log
  // display grid
  // grid-template-columns auto auto 1fr
  // gap 1rem 0
  // align-items center
  // &-entry
  //   display grid
  //   grid-column 1 / span 3
  //   grid-template-columns subgrid
  //   align-items start
</style>