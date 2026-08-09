<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  categories: {
    type: Array,
    required: true
  },

  series: {
    // [{ name, color, values: [n, n, ...] aligned to categories }]
    type: Array,
    required: true
  },

  height: {
    type: Number,
    default: 220
  }
})

const WIDTH = 600
const PAD_LEFT = 34
const PAD_RIGHT = 14
const PAD_TOP = 14
const PAD_BOTTOM = 30
const GAP = 2

const plotWidth = WIDTH - PAD_LEFT - PAD_RIGHT
const plotHeight = computed(() => props.height - PAD_TOP - PAD_BOTTOM)

const hasData = computed(() =>
  props.series.some(s => s.values.some(v => v > 0))
)

const totals = computed(() =>
  props.categories.map((_, i) => props.series.reduce((sum, s) => sum + (s.values[i] || 0), 0))
)

const maxTotal = computed(() => Math.max(1, ...totals.value))

const bandWidth = computed(() => plotWidth / Math.max(props.categories.length, 1))
const barWidth = computed(() => Math.min(24, bandWidth.value * 0.55))

function barX(index) {
  return PAD_LEFT + bandWidth.value * index + (bandWidth.value - barWidth.value) / 2
}

const columns = computed(() =>
  props.categories.map((category, i) => {
    let cursorY = PAD_TOP + plotHeight.value

    const segments = props.series.map(s => {
      const value = s.values[i] || 0
      const segHeight = (value / maxTotal.value) * plotHeight.value
      const y = cursorY - segHeight

      cursorY = y - (segHeight > 0 ? GAP : 0)

      return {
        name: s.name,
        color: s.color,
        value,
        y,
        height: Math.max(segHeight, 0)
      }
    })

    return { category, segments, total: totals.value[i] }
  })
)

const hover = ref(null)
</script>

<template>
  <div class="stacked-chart">
    <div
      v-if="!hasData"
      class="empty-state"
      :style="{ height: `${height}px` }"
    >
      Sem dados ainda.
    </div>

    <svg
      v-else
      :viewBox="`0 0 ${WIDTH} ${height}`"
      class="chart-svg"
    >
      <line
        :x1="PAD_LEFT"
        :x2="WIDTH - PAD_RIGHT"
        :y1="PAD_TOP + plotHeight"
        :y2="PAD_TOP + plotHeight"
        class="baseline"
      />

      <g
        v-for="(col, ci) in columns"
        :key="col.category"
      >
        <rect
          v-for="seg in col.segments"
          :key="`${col.category}-${seg.name}`"
          :x="barX(ci)"
          :y="seg.y"
          :width="barWidth"
          :height="seg.height"
          :fill="seg.color"
          rx="2"
          class="segment"
          :class="{ dim: hover && (hover.category !== col.category || hover.name !== seg.name) }"
          @pointerenter="hover = { category: col.category, name: seg.name, value: seg.value }"
          @pointerleave="hover = null"
        />

        <text
          :x="barX(ci) + barWidth / 2"
          :y="PAD_TOP + plotHeight - (col.total > 0 ? ((col.total / maxTotal) * plotHeight) : 0) - 6"
          text-anchor="middle"
          class="total-label"
        >{{ col.total }}</text>

        <text
          :x="barX(ci) + barWidth / 2"
          :y="height - 8"
          text-anchor="middle"
          class="axis-label"
        >{{ col.category }}</text>
      </g>

      <g v-if="hover">
        <rect
          x="10"
          y="10"
          width="150"
          height="40"
          rx="6"
          class="tooltip-box"
        />

        <text x="20" y="27" class="tooltip-series">{{ hover.category }} · {{ hover.name }}</text>
        <text x="20" y="43" class="tooltip-value">{{ hover.value }}</text>
      </g>
    </svg>

    <div class="legend">
      <span
        v-for="s in series"
        :key="`legend-${s.name}`"
        class="legend-item"
      >
        <span class="legend-swatch" :style="{ background: s.color }"></span>
        {{ s.name }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.stacked-chart {
  width: 100%;
  font-family: Arial, Helvetica, sans-serif;
}

.chart-svg {
  width: 100%;
  height: auto;
  display: block;
}

.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #898781;
  font-size: 13px;
}

.baseline {
  stroke: #c3c2b7;
  stroke-width: 1;
}

.segment {
  cursor: pointer;
  transition: opacity 0.12s ease;
}

.segment.dim {
  opacity: 0.35;
}

.total-label {
  fill: #52514e;
  font-size: 11px;
  font-weight: 700;
}

.axis-label {
  fill: #898781;
  font-size: 10px;
}

.tooltip-box {
  fill: #ffffff;
  stroke: rgba(11, 11, 11, 0.1);
  filter: drop-shadow(0 4px 10px rgba(17, 24, 39, 0.16));
}

.tooltip-series {
  fill: #52514e;
  font-size: 11px;
}

.tooltip-value {
  fill: #0b0b0b;
  font-size: 13px;
  font-weight: 700;
}

.legend {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
}

.legend-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #52514e;
  font-size: 12px;
}

.legend-swatch {
  width: 10px;
  height: 10px;
  border-radius: 3px;
}
</style>
