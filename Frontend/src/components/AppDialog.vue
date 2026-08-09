<template>
  <div
    v-if="visible"
    class="dialog-overlay"
  >
    <div
      class="app-dialog"
      :class="type"
    >
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
          @click="$emit('cancel')"
        >
          Cancelar
        </button>

        <button
          class="confirm-button"
          @click="$emit('confirm')"
        >
          Confirmar
        </button>
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
  </div>
</template>

<script setup>
defineProps({
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
  }
})

defineEmits(['confirm', 'cancel'])
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

.app-dialog {
  width: min(420px, calc(100% - 32px));
  padding: 24px;

  background: #ffffff;
  border-radius: 12px;

  font-family: inherit;
  text-align: center;

  box-shadow: 0 12px 35px rgba(0, 0, 0, 0.18);

  border-top: 5px solid #27227f;
}

.app-dialog h3 {
  margin: 0 0 12px;
  color: #27227f;
  font-size: 18px;
}

.app-dialog p {
  margin: 0;
  color: #4b5563;
  font-size: 14px;
  line-height: 1.5;
}

.app-dialog.success {
  border-top-color: #27227f;
}

.app-dialog.error {
  border-top-color: #0088cc;
}

.app-dialog.warning {
  border-top-color: #27227f;
}

.app-dialog.info {
  border-top-color: #0088cc;
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

  border-radius: 8px;

  font-family: inherit;
  font-size: 13px;
  font-weight: 600;

  cursor: pointer;
}

.cancel-button {
  background: #ffffff;
  color: #27227f;
  border: 1px solid #27227f;
}

.confirm-button {
  background: #27227f;
  color: #ffffff;
  border: 1px solid #27227f;
}

.confirm-button:hover {
  background: #0088cc;
  border-color: #0088cc;
}
</style>



