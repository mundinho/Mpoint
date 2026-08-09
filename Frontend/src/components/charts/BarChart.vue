<script setup>
import { computed } from 'vue'

const props = defineProps({
  data: {
    // [{ label, value }]
    type: Array,
    required: true
  },

  color: {
    type: String,
    default: '#2a78d6'
  }
})

const max = computed(() => Math.max(1, ...props.data.map(d => d.value)))
const hasData = computed(() => props.data.length > 0 && props.data.some(d => d.value > 0))
</script>

<template>
  <div class="bar-chart">
    <div
      v-if="!hasData"
      class="empty-state"
    >
      Sem dados ainda.
    </div>

    <div
      v-for="row in data"
      v-else
      :key="row.label"
      class="bar-row"
    >
      <span class="bar-label" :title="row.label">{{ row.label }}</span>

      <div class="bar-track">
        <div
          class="bar-fill"
          :style="{
            width: `${Math.max((row.value / max) * 100, row.value > 0 ? 3 : 0)}%`,
            background: color
          }"
        ></div>
      </div>

      <strong class="bar-value">{{ row.value }}</strong>
    </div>
  </div>
</template>

<style scoped>
.bar-chart {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 10px;
  font-family: Arial, Helvetica, sans-serif;
}

.empty-state {
  padding: 40px 0;
  text-align: center;
  color: #898781;
  font-size: 13px;
}

.bar-row {
  display: grid;
  grid-template-columns: minmax(0, 120px) 1fr 42px;
  align-items: center;
  gap: 10px;
  border-radius: 6px;
  padding: 3px 4px;
}

.bar-row:hover {
  background: #f9f9f7;
}

.bar-label {
  overflow: hidden;
  color: #52514e;
  font-size: 12px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bar-track {
  height: 18px;
  border-radius: 4px;
  background: #f0efec;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  border-radius: 0 4px 4px 0;
  transition: width 0.2s ease;
}

.bar-value {
  color: #0b0b0b;
  font-size: 12px;
  text-align: right;
}
</style>
