<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  data: {
    // [{ label, value, color }]
    type: Array,
    required: true
  },

  size: {
    type: Number,
    default: 200
  }
})

const { t } = useI18n()
const total = computed(() => props.data.reduce((sum, d) => sum + d.value, 0))
const hasData = computed(() => total.value > 0)

const RADIUS = 70
const STROKE = 26
const CIRCUMFERENCE = 2 * Math.PI * RADIUS

const segments = computed(() => {
  let cumulative = 0

  return props.data.map(d => {
    const fraction = total.value > 0 ? d.value / total.value : 0
    const dash = fraction * CIRCUMFERENCE
    const offset = -(cumulative / (total.value || 1)) * CIRCUMFERENCE

    cumulative += d.value

    return {
      ...d,
      fraction,
      dash,
      offset
    }
  })
})

const hoverIndex = ref(null)

const activeSegment = computed(() =>
  hoverIndex.value === null ? null : segments.value[hoverIndex.value]
)
</script>

<template>
  <div class="donut-chart">
    <div
      v-if="!hasData"
      class="empty-state"
      :style="{ height: `${size}px` }"
    >
      {{ t('charts.common.noData') }}
    </div>

    <template v-else>
      <svg
        :viewBox="`0 0 ${size} ${size}`"
        class="chart-svg"
      >
        <circle
          :cx="size / 2"
          :cy="size / 2"
          :r="RADIUS"
          fill="none"
          stroke="#e1e0d9"
          :stroke-width="STROKE"
        />

        <g :transform="`rotate(-90 ${size / 2} ${size / 2})`">
          <circle
            v-for="(seg, i) in segments"
            :key="seg.label"
            :cx="size / 2"
            :cy="size / 2"
            :r="RADIUS"
            fill="none"
            :stroke="seg.color"
            :stroke-width="hoverIndex === i ? STROKE + 4 : STROKE"
            :stroke-dasharray="`${seg.dash} ${CIRCUMFERENCE}`"
            :stroke-dashoffset="seg.offset"
            class="donut-segment"
            @pointerenter="hoverIndex = i"
            @pointerleave="hoverIndex = null"
          />
        </g>

        <text
          :x="size / 2"
          :y="size / 2 - 4"
          text-anchor="middle"
          class="donut-center-value"
        >{{ activeSegment ? activeSegment.value : total }}</text>

        <text
          :x="size / 2"
          :y="size / 2 + 14"
          text-anchor="middle"
          class="donut-center-label"
        >{{ activeSegment ? activeSegment.label : 'Total' }}</text>
      </svg>

      <div class="legend">
        <span
          v-for="(seg, i) in segments"
          :key="`legend-${seg.label}`"
          class="legend-item"
          :class="{ active: hoverIndex === i }"
          @pointerenter="hoverIndex = i"
          @pointerleave="hoverIndex = null"
        >
          <span class="legend-swatch" :style="{ background: seg.color }"></span>
          {{ seg.label }}
          <strong>{{ seg.value }}</strong>
        </span>
      </div>
    </template>
  </div>
</template>

<style scoped>
.donut-chart {
  width: 100%;
  font-family: Arial, Helvetica, sans-serif;
}

.chart-svg {
  width: 100%;
  max-width: 200px;
  height: auto;
  display: block;
  margin: 0 auto;
}

.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #898781;
  font-size: 13px;
}

.donut-segment {
  cursor: pointer;
  transition: stroke-width 0.12s ease;
}

.donut-center-value {
  fill: #0b0b0b;
  font-size: 22px;
  font-weight: 700;
}

.donut-center-label {
  fill: #898781;
  font-size: 11px;
}

.legend {
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.legend-item {
  padding: 4px 6px;
  display: flex;
  align-items: center;
  gap: 8px;
  border-radius: 6px;
  color: #52514e;
  font-size: 12px;
  cursor: pointer;
}

.legend-item.active {
  background: #f9f9f7;
}

.legend-item strong {
  margin-left: auto;
  color: #0b0b0b;
  font-size: 12px;
}

.legend-swatch {
  width: 10px;
  height: 10px;
  flex-shrink: 0;
  border-radius: 3px;
}
</style>
