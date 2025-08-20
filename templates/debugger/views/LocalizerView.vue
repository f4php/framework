<template lang="pug">
  Localizer(:value="localizer")
</template>
<script>

import Localizer from '../components/Localizer.vue';

export default {
  components: {
    Localizer
  },
  computed: {
    localizer() {
      return {
        ...this.$root.localizer,
        availableLocales: this.availableLocales,
      };
    },
    availableLocales() {
      return this.$root.config
        .find(section => section.name==='LOCALES')
        .value
        .map(locale => locale.name);
    },
  },
  data() {
    return {};
  }
}
</script>
<style lang="stylus">
</style>