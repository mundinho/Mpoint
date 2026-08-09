<script setup>
import { computed } from 'vue'

const props = defineProps({
  value: {
    type: Number,
    required: true
  },

  total: {
    type: Number,
    required: true
  },

  label: {
    type: String,
    default: ''
  }
})

const percent = computed(() =>
  props.total > 0 ? Math.min(100, Math.round((props.value / props.total) * 100)) : 0
)
</script>

<template>
  <div class="meter">
    <div class="meter-heading">
      <span>{{ label }}</span>
      <strong>{{ percent }}%</strong>
    </div>

    <div class="meter-track">
      <div
        class="meter-fill"
        :style="{ width: `${percent}%` }"
      ></div>
    </div>

    <span class="meter-caption">{{ value }} de {{ total }}</span>
  </div>
</template>

<style scoped>
.meter {
  width: 100%;
  font-family: Arial, Helvetica, sans-serif;
}

.meter-heading {
  margin-bottom: 8px;
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  color: #52514e;
  font-size: 12px;
}

.meter-heading strong {
  color: #0b0b0b;
  font-size: 18px;
}

.meter-track {
  height: 14px;
  border-radius: 7px;
  background: #cde2fb;
  overflow: hidden;
}

.meter-fill {
  height: 100%;
  border-radius: 7px;
  background: #2a78d6;
  transition: width 0.2s ease;
}

.meter-caption {
  display: block;
  margin-top: 8px;
  color: #898781;
  font-size: 11px;
}
</style>
