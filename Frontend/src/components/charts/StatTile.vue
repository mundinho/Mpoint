<script setup>
defineProps({
  label: {
    type: String,
    required: true
  },

  value: {
    type: [Number, String],
    required: true
  }
})
</script>

<template>
  <article class="stat-tile">
    <span class="stat-label">{{ label }}</span>
    <strong class="stat-value">{{ value }}</strong>
  </article>
</template>

<style scoped>
.stat-tile {
  min-height: 96px;
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
  border: 1px solid #e3e3ef;
  border-radius: 9px;
  background: #fcfcfb;
  box-shadow: 0 2px 9px rgba(39, 34, 127, 0.05);
}

.stat-label {
  color: #52514e;
  font-size: 12px;
  line-height: 1.4;
}

.stat-value {
  color: #0b0b0b;
  font-size: 26px;
  font-weight: 700;
  font-variant-numeric: normal;
}
</style>
