<script setup>
defineProps({
  size: {
    type: Number,
    default: 14
  },

  color: {
    type: String,
    default: 'white'
  }
})
</script>

<template>
  <span
    class="loading-spinner"
    :class="color"
    :style="{ width: `${size}px`, height: `${size}px` }"
    aria-hidden="true"
  ></span>
</template>

<style scoped>
.loading-spinner {
  display: inline-block;
  flex-shrink: 0;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

.loading-spinner.white {
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #ffffff;
}

.loading-spinner.purple {
  border: 2px solid rgba(39, 34, 127, 0.25);
  border-top-color: #27227f;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
