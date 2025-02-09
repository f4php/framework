<template lang="pug">
  .gantt-chart(:style="`--guide-count: ${guides}; --item-count: ${values.length}`")
    .gantt-chart-timeline
      .gantt-chart-timeline-header
      .gantt-chart-timeline-cell(v-for="bar in bars")
        .gantt-chart-timeline-bar(
          :style="{left: `${bar.start * 100}%`, width: `${(bar.end - bar.start) * 100}%`}"
          :class="{'gantt-chart-timeline-bar-unidentified': bar.unidentified}"
        )
      .gantt-chart-timeline-guide(v-for="guide in guides" :style="`left: ${guide.start * 100}%`")
        .gantt-chart-timeline-guide-label
          | {{ guide.value.toFixed(precision) }}
        .gantt-chart-timeline-guide-line
    .gantt-chart-row-header
    .gantt-chart-row-duration.gantt-chart-row-duration-total
      | {{ range.toFixed(precision) }}
      | {{ unit }}
    template(v-for="header in headers")
      .gantt-chart-row-header(:class="{'gantt-chart-row-header-unidentified': !header.label}")
        template(v-if="header.label")
          | {{ header.label }}
        template(v-else)
          | Unidentified
      .gantt-chart-row-duration
        | {{ (header.duration).toFixed(precision) }}
        | {{ unit }}
</template>
<script>

export default {
  components: {
  },
  props: {
    values: {
      type: Array,
      required: true,
    },
    guidesTarget: {
      type: Number,
      required: false,
      default: 5
    },
    unit: {
      type: String,
      required: false,
      default: ''
    },
    precision: {
      type: Number,
      required: false,
      default: 2
    }
  },
  computed: {
    minValue() {
      return this.values.reduce((result, item) => Math.min(result, item.start), Infinity);
    },
    maxValue() {
      return this.values.reduce((result, item) => Math.max(result, item.end), -Infinity);
    },
    range() {
      return Math.abs(this.maxValue - this.minValue);
    },
    headers() {
      return [...this.values]
        .map((item, index, items) => ({
          label: item.label,
          start: item.start,
          end: item.end,
          duration: item.end - item.start
        }))
        .sort((itemA, itemB) => itemA.start - itemB.start);
    },
    bars() {
      return [...this.values]
        .map((item, index, items) => {
          const duration = item.end - item.start;
          const start = (this.range !== 0) ? (item.start - this.minValue) / this.range : 0;
          const end = (this.range !== 0) ? (item.end - this.minValue) / this.range : 0;
          return {
            start,
            end,
            duration,
            unidentified: item.label ? false : true
          };
        })
        .sort((itemA, itemB) => itemA.start - itemB.start);
    },
    guides() {
      if(!this.guidesTarget || !this.range) {
        return [];
      }
      const roughStep = this.range / (this.guidesTarget - 1);
      const magnitude = Math.pow(10, Math.floor(Math.log10(roughStep)));
      const stepOptions = [1, 2, 5, 10].map(x => x * magnitude);
      const step = stepOptions.reduce((prev, curr) => {
        const prevCount = Math.floor(this.range / prev);
        const currCount = Math.floor(this.range / curr);
        return Math.abs(currCount - this.guidesTarget) < Math.abs(prevCount - this.guidesTarget) ? curr : prev;
      });
      const count = Math.floor(this.range / step) + 1;
      return Array.from({length: count}, (v, i) => ({
        value: i * step,
        start: i * step / this.range
      }));
    }
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
.gantt-chart
  --f4-vardump-text-color var(--p-text-color)
  --f4-vardump-header-color var(--p-primary-color)
  --f4-vardump-header-unidentified-color var(--p-text-muted-color)
  --f4-vardump-bar-color var(--p-primary-color)
  --f4-vardump-bar-unidentified-color var(--p-primary-color)
  --f4-vardump-bar-label-color var(--p-primary-color)
  --f4-vardump-guide-color var(--p-surface-700)
  --f4-vardump-guide-label-color var(--p-surface-600)
  --f4-vardump-duration-color var(--p-text-muted-color)
  position relative
  display grid
  grid-template-columns auto auto 1fr
  grid-template-rows 1fr repeat(var(--item-count), 1fr)
  grid-gap (16/16)rem (16/16)rem
  z-index 0
  &-row
    &-header
      grid-column 1
      &-unidentified
        font-style italic
        color var(--f4-vardump-header-unidentified-color)
    &-duration
      grid-column 2
      text-align right
      color var(--f4-vardump-duration-color)
      &-total
        color var(--f4-vardump-text-color)
  &-timeline
    grid-column 3
    grid-row 1 / span calc(1 + var(--item-count))
    display grid
    grid-template-columns auto
    grid-template-rows subgrid
    position relative
    overflow hidden
    &-header
      //
    &-cell
      position relative
    &-bar
      min-width 1px
      position absolute
      height (16/16)rem
      background var(--f4-vardump-bar-color)
      &-unidentified
        background repeating-linear-gradient(
            -45deg,                         \
            transparent,                    \
            transparent 6px,                \
            var(--f4-vardump-header-unidentified-color) 6px,  \
            var(--f4-vardump-header-unidentified-color) 12px  \
        )
    &-guide
      grid-column 1
      grid-row 1
      position absolute
      top 0
      bottom 0
      z-index -1
      &-label
        position absolute
        top 0
        padding-left (8/16)rem
        white-space nowrap
        color var(--f4-vardump-guide-label-color)
      &-line
        position absolute
        left 0
        top 0
        bottom 0
        border-left 1px solid var(--f4-vardump-guide-color)

</style>