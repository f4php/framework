<template lang="pug">
  GanttChart(:values="profilerTimeData" :guides-target="4" unit="ms")
  //- GanttChart(:values="profilerMemoryData" :guides-target="4" unit="Kb")
</template>
<script>

import GanttChart from '../components/GanttChart.vue';

export default {
  components: {
    GanttChart
  },
  computed: {
    profilerTimeData() {
      return this.$root.profiler.map((item, index, items) => ({
        label: item.label,
        start: (index > 0 ? items[index-1].time : item.time) / 1000000,
        end: item.time / 1000000,
      }));
    },
    // profilerMemoryData() {
    //   return this.$root.profiler.map((item, index, items) => ({
    //     label: item.label,
    //     start: (index > 0 ? items[index-1].memory : item.memory) / 1024,
    //     end: item.memory / 1024,
    //   }));
    // },
  },
  data() {
    return {};
  }
}
</script>
<style lang="stylus">
</style>