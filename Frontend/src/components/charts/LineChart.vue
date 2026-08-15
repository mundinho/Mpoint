<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  series: {
    // [{ name, color, points: [{ x: 'label', y: number }] }]
    type: Array,
    required: true
  },

  height: {
    type: Number,
    default: 220
  }
})

const WIDTH = 600
const PAD_LEFT = 42
const PAD_RIGHT = 14
const PAD_TOP = 14
const PAD_BOTTOM = 30

const plotWidth = WIDTH - PAD_LEFT - PAD_RIGHT
const plotHeight = computed(() => props.height - PAD_TOP - PAD_BOTTOM)

const labels = computed(() => props.series[0]?.points.map(p => p.x) || [])

const hasData = computed(() =>
  props.series.some(s => s.points.some(p => p.y > 0))
)

const maxY = computed(() => {
  const max = Math.max(
    1,
    ...props.series.flatMap(s => s.points.map(p => p.y))
  )

  // Arredonda para cima para um número "redondo" (1, 2, 5, 10, 20, 50...)
  const magnitude = Math.pow(10, Math.floor(Math.log10(max)))
  const normalized = max / magnitude

  let niceMax
  if (normalized <= 1) niceMax = 1
  else if (normalized <= 2) niceMax = 2
  else if (normalized <= 5) niceMax = 5
  else niceMax = 10

  return niceMax * magnitude
})

function xScale(index) {
  const count = labels.value.length

  if (count <= 1) return PAD_LEFT + plotWidth / 2

  return PAD_LEFT + (index / (count - 1)) * plotWidth
}

function yScale(value) {
  return PAD_TOP + plotHeight.value - (value / maxY.value) * plotHeight.value
}

const gridLines = computed(() => {
  const steps = 4

  return Array.from({ length: steps + 1 }, (_, i) => {
    const value = Math.round((maxY.value / steps) * i)

    return { value, y: yScale(value) }
  })
})

function buildPath(points) {
  return points
    .map((p, i) => `${i === 0 ? 'M' : 'L'}${xScale(i).toFixed(1)},${yScale(p.y).toFixed(1)}`)
    .join(' ')
}

function shortLabel(label) {
  // "2026-08-09 14:00:00" -> "14h"
  const match = /(\d{2}):00:00$/.exec(label)
  return match ? `${match[1]}h` : label
}

const svgEl = ref(null)
const hoverIndex = ref(null)

function handlePointerMove(event) {
  if (!svgEl.value || labels.value.length === 0) return

  const rect = svgEl.value.getBoundingClientRect()
  const svgX = ((event.clientX - rect.left) / rect.width) * WIDTH
  const ratio = (svgX - PAD_LEFT) / plotWidth
  const index = Math.round(ratio * (labels.value.length - 1))

  hoverIndex.value = Math.min(Math.max(index, 0), labels.value.length - 1)
}

function handlePointerLeave() {
  hoverIndex.value = null
}

const tooltipX = computed(() => hoverIndex.value === null ? 0 : xScale(hoverIndex.value))
const tooltipAlignRight = computed(() => tooltipX.value > WIDTH - 150)
</script>

<template>
  <div class="line-chart">
    <div
      v-if="!hasData"
      class="empty-state"
      :style="{ height: `${height}px` }"
    >
     {{ t('charts.common.noData') }}
    </div>

    <svg
      v-else
      ref="svgEl"
      :viewBox="`0 0 ${WIDTH} ${height}`"
      class="chart-svg"
      @pointermove="handlePointerMove"
      @pointerleave="handlePointerLeave"
    >
      <!-- gridlines -->
      <g>
        <line
          v-for="grid in gridLines"
          :key="grid.value"
          :x1="PAD_LEFT"
          :x2="WIDTH - PAD_RIGHT"
          :y1="grid.y"
          :y2="grid.y"
          class="gridline"
        />

        <text
          v-for="grid in gridLines"
          :key="`label-${grid.value}`"
          :x="PAD_LEFT - 8"
          :y="grid.y + 3"
          class="axis-label"
          text-anchor="end"
        >{{ grid.value }}</text>
      </g>

      <!-- x-axis hour labels (sparse) -->
      <text
        v-for="(label, i) in labels"
        v-show="labels.length <= 8 || i % Math.ceil(labels.length / 8) === 0"
        :key="`x-${i}`"
        :x="xScale(i)"
        :y="height - 8"
        class="axis-label"
        text-anchor="middle"
      >{{ shortLabel(label) }}</text>

      <!-- area fill for a single series -->
      <path
        v-if="series.length === 1"
        :d="`${buildPath(series[0].points)} L${xScale(labels.length - 1)},${PAD_TOP + plotHeight} L${xScale(0)},${PAD_TOP + plotHeight} Z`"
        :fill="series[0].color"
        opacity="0.1"
        stroke="none"
      />

      <!-- series lines -->
      <path
        v-for="s in series"
        :key="s.name"
        :d="buildPath(s.points)"
        fill="none"
        :stroke="s.color"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      />

      <!-- end markers -->
      <circle
        v-for="s in series"
        :key="`end-${s.name}`"
        :cx="xScale(s.points.length - 1)"
        :cy="yScale(s.points[s.points.length - 1]?.y || 0)"
        r="5"
        :fill="s.color"
        stroke="#fcfcfb"
        stroke-width="2"
      />

      <!-- crosshair + tooltip -->
      <g v-if="hoverIndex !== null">
        <line
          :x1="tooltipX"
          :x2="tooltipX"
          :y1="PAD_TOP"
          :y2="PAD_TOP + plotHeight"
          class="crosshair"
        />

        <circle
          v-for="s in series"
          :key="`hover-${s.name}`"
          :cx="tooltipX"
          :cy="yScale(s.points[hoverIndex]?.y || 0)"
          r="4"
          :fill="s.color"
          stroke="#fcfcfb"
          stroke-width="2"
        />

        <g :transform="`translate(${tooltipAlignRight ? tooltipX - 148 : tooltipX + 10}, ${PAD_TOP})`">
          <rect
            width="140"
            :height="24 + series.length * 16"
            rx="6"
            class="tooltip-box"
          />

          <text x="10" y="17" class="tooltip-title">{{ shortLabel(labels[hoverIndex]) }}</text>

          <g
            v-for="(s, si) in series"
            :key="`tt-${s.name}`"
            :transform="`translate(10, ${34 + si * 16})`"
          >
            <line x1="0" x2="10" y1="-4" y2="-4" :stroke="s.color" stroke-width="2" />
            <text x="16" y="0" class="tooltip-series">{{ s.name }}</text>
            <text x="130" y="0" text-anchor="end" class="tooltip-value">{{ s.points[hoverIndex]?.y ?? 0 }}</text>
          </g>
        </g>
      </g>
    </svg>

    <div
      v-if="series.length > 1"
      class="legend"
    >
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
.line-chart {
  width: 100%;
}

.chart-svg {
  width: 100%;
  height: auto;
  display: block;
  font-family: Arial, Helvetica, sans-serif;
}

.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #898781;
  font-size: 13px;
}

.gridline {
  stroke: #e1e0d9;
  stroke-width: 1;
}

.axis-label {
  fill: #898781;
  font-size: 10px;
}

.crosshair {
  stroke: #c3c2b7;
  stroke-width: 1;
}

.tooltip-box {
  fill: #ffffff;
  stroke: rgba(11, 11, 11, 0.1);
  filter: drop-shadow(0 4px 10px rgba(17, 24, 39, 0.16));
}

.tooltip-title {
  fill: #52514e;
  font-size: 10px;
  font-weight: 700;
}

.tooltip-series {
  fill: #52514e;
  font-size: 11px;
}

.tooltip-value {
  fill: #0b0b0b;
  font-size: 11px;
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
