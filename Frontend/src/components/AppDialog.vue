<template>
  <Transition name="dialog-fade">
    <div
      v-if="visible"
      class="dialog-overlay"
    >
      <div
        class="app-dialog"
        :class="type"
      >
        <div class="icon-circle">
          {{ icon }}
        </div>

        <h3 v-if="title">
          {{ title }}
        </h3>

        <p>
          {{ message }}
        </p>

        <div
          v-if="mode === 'confirm'"
          class="dialog-actions"
        >
          <button
            class="cancel-button"
            :disabled="loading"
            @click="$emit('cancel')"
          >
            Cancelar
          </button>

          <button
            class="confirm-button"
            :disabled="loading"
            @click="$emit('confirm')"
          >
            <LoadingSpinner v-if="loading" />
            {{ loading ? 'A processar...' : 'Confirmar' }}
          </button>
        </div>
      </div>

 <div
  v-if="mode === 'alert'"
  class="dialog-actions"
>
  <button
    class="confirm-button"
    @click="$emit('confirm')"
  >
    OK
  </button>
</div>

    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },

  mode: {
    type: String,
    default: 'notification'
  },

  title: {
    type: String,
    default: ''
  },

  message: {
    type: String,
    default: ''
  },

  type: {
    type: String,
    default: 'info'
  },

  loading: {
    type: Boolean,
    default: false
  }
})

defineEmits(['confirm', 'cancel'])

const icons = {
  success: '✓',
  error: '✕',
  warning: '⚠',
  info: 'ℹ'
}

const icon = computed(() => {
  if (props.mode === 'confirm') return '?'

  return icons[props.type] || icons.info
})
</script>

<style scoped>
.dialog-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;

  display: flex;
  align-items: center;
  justify-content: center;

  background: rgba(0, 0, 0, 0.25);
}

.dialog-fade-enter-active,
.dialog-fade-leave-active {
  transition: opacity 0.18s ease;
}

.dialog-fade-enter-active .app-dialog,
.dialog-fade-leave-active .app-dialog {
  transition: transform 0.18s ease, opacity 0.18s ease;
}

.dialog-fade-enter-from,
.dialog-fade-leave-to {
  opacity: 0;
}

.dialog-fade-enter-from .app-dialog,
.dialog-fade-leave-to .app-dialog {
  transform: scale(0.94) translateY(6px);
  opacity: 0;
}

.app-dialog {
  width: min(420px, calc(100% - 32px));
  padding: 24px;

  background: #ffffff;
  border-radius: 12px;

  font-family: inherit;
  text-align: center;

  box-shadow: 0 12px 35px rgba(0, 0, 0, 0.18);

  border-top: 5px solid #840435;
}

.icon-circle {
  width: 44px;
  height: 44px;
  margin: 0 auto 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #840435;
  color: #840435;
  font-size: 20px;
  font-weight: 800;
}

.app-dialog h3 {
  margin: 0 0 12px;
  color: #840435;
  font-size: 18px;
}

.app-dialog p {
  margin: 0;
  color: #4b5563;
  font-size: 14px;
  line-height: 1.5;
}

.app-dialog.success {
  border-top-color: #16a34a;
}

.app-dialog.success .icon-circle {
  border-color: #16a34a;
  color: #16a34a;
}

.app-dialog.error {
  border-top-color: #dc2626;
}

.app-dialog.error .icon-circle {
  border-color: #dc2626;
  color: #dc2626;
}

.app-dialog.warning {
  border-top-color: #d97706;
}

.app-dialog.warning .icon-circle {
  border-color: #d97706;
  color: #d97706;
}

.app-dialog.info {
  border-top-color: #6a032a;
}

.app-dialog.info .icon-circle {
  border-color: #6a032a;
  color: #6a032a;
}

.dialog-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-top: 24px;
}

.dialog-actions button {
  min-width: 110px;
  height: 40px;

  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;

  border-radius: 8px;

  font-family: inherit;
  font-size: 13px;
  font-weight: 600;

  cursor: pointer;
}

.dialog-actions button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.cancel-button {
  background: #ffffff;
  color: #840435;
  border: 1px solid #840435;
}

.confirm-button {
  background: #840435;
  color: #ffffff;
  border: 1px solid #840435;
}

.confirm-button:hover:not(:disabled) {
  background: #6a032a;
  border-color: #6a032a;
}
</style>
