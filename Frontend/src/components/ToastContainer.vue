<script setup>
defineProps({
  toasts: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['dismiss'])

const icons = {
  success: '✓',
  error: '✕',
  warning: '⚠',
  info: 'ℹ'
}
</script>

<template>
  <div
    class="toast-container"
    aria-live="polite"
  >
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast"
        :class="toast.type"
        role="status"
        @click="emit('dismiss', toast.id)"
      >
        <span class="toast-icon">{{ icons[toast.type] || icons.info }}</span>
        <span class="toast-message">{{ toast.message }}</span>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-container {
  position: fixed;
  z-index: 10000;
  right: 20px;
  bottom: 20px;
  display: flex;
  flex-direction: column-reverse;
  gap: 10px;
  width: min(360px, calc(100vw - 40px));
  pointer-events: none;
}

.toast {
  padding: 14px 16px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 8px 24px rgba(17, 24, 39, 0.16);
  border-left: 4px solid #0088cc;
  color: #1f2937;
  font-family: Arial, Helvetica, sans-serif;
  font-size: 13px;
  line-height: 1.5;
  cursor: pointer;
  pointer-events: auto;
}

.toast-icon {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 800;
  color: #ffffff;
  background: #0088cc;
}

.toast-message {
  flex: 1;
  padding-top: 1px;
}

.toast.success {
  border-left-color: #16a34a;
}

.toast.success .toast-icon {
  background: #16a34a;
}

.toast.error {
  border-left-color: #dc2626;
}

.toast.error .toast-icon {
  background: #dc2626;
}

.toast.warning {
  border-left-color: #d97706;
}

.toast.warning .toast-icon {
  background: #d97706;
}

.toast.info {
  border-left-color: #0088cc;
}

.toast-enter-active,
.toast-leave-active {
  transition: transform 0.22s ease, opacity 0.22s ease;
}

.toast-enter-from {
  transform: translateX(24px);
  opacity: 0;
}

.toast-leave-to {
  transform: translateX(24px);
  opacity: 0;
}

.toast-leave-active {
  position: absolute;
}

@media (max-width: 480px) {
  .toast-container {
    right: 12px;
    bottom: 12px;
    left: 12px;
    width: auto;
  }
}
</style>
