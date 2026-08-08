<script setup>
import LoadingSpinner from './LoadingSpinner.vue'

defineProps({
  number: {
    type: Number,
    required: true
  },

  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <Transition name="modal-fade">
    <div class="modal-overlay">
      <section class="modal-card">
        <div class="card-stripe">
          <div class="stripe-accent"></div>
        </div>

        <div class="modal-content">
          <div class="icon-circle">
            ?
          </div>

          <h2>Confirmar escolha</h2>

          <p class="message">
            Tem a certeza de que pretende abrir o número
            <strong>{{ number }}</strong>?
          </p>

          <p class="warning">
            Depois de confirmar, não poderá escolher outro número.
          </p>

          <div class="actions">
            <button
              type="button"
              class="cancel-button"
              :disabled="loading"
              @click="emit('cancel')"
            >
              Cancelar
            </button>

            <button
              type="button"
              class="confirm-button"
              :disabled="loading"
              @click="emit('confirm')"
            >
              <LoadingSpinner v-if="loading" color="white" />
              {{ loading ? 'A confirmar...' : 'Confirmar' }}
            </button>
          </div>
        </div>
      </section>
    </div>
  </Transition>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(17, 24, 39, 0.55);
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.18s ease;
}

.modal-fade-enter-active .modal-card,
.modal-fade-leave-active .modal-card {
  transition: transform 0.18s ease, opacity 0.18s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-from .modal-card,
.modal-fade-leave-to .modal-card {
  transform: scale(0.94) translateY(6px);
  opacity: 0;
}

.modal-card {
  width: 100%;
  max-width: 420px;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #e0e0ef;
  border-radius: 10px;
  box-shadow: 0 18px 50px rgba(17, 24, 39, 0.22);
}

.card-stripe {
  position: relative;
  height: 28px;
  overflow: hidden;
  background: #27227f;
}

.stripe-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 110px;
  height: 100%;
  background: #0088cc;
  clip-path: polygon(35% 0, 100% 0, 100% 100%, 0 100%);
}

.modal-content {
  padding: 38px 34px 34px;
  text-align: center;
}

.icon-circle {
  width: 58px;
  height: 58px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #27227f;
  border-radius: 50%;
  color: #27227f;
  font-size: 28px;
  font-weight: 800;
}

h2 {
  margin: 0 0 14px;
  color: #111827;
  font-size: 25px;
}

.message {
  margin: 0;
  color: #4b5563;
  font-size: 15px;
  line-height: 1.6;
}

.message strong {
  color: #27227f;
  font-size: 19px;
}

.warning {
  margin: 14px 0 28px;
  color: #9ca3af;
  font-size: 12px;
  line-height: 1.5;
}

.actions {
  display: flex;
  gap: 12px;
}

.actions button {
  flex: 1;
  padding: 13px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
}

.actions button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.cancel-button {
  border: 1px solid #d1d5db;
  background: #ffffff;
  color: #4b5563;
}

.cancel-button:hover:not(:disabled) {
  background: #f9fafb;
}

.confirm-button {
  border: 1px solid #27227f;
  background: #27227f;
  color: #ffffff;
}

.confirm-button:hover:not(:disabled) {
  background: #1c1860;
}
</style>