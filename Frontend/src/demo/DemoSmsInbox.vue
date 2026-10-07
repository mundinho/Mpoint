<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { fillOtp } from './index'

// Mostra como notificação os SMS que, na versão real, chegariam ao telemóvel
const messages = ref([])
let seq = 0

function formatPhone(phone) {
  const digits = String(phone).replace(/\D/g, '')
  const local = digits.startsWith('258') ? digits.slice(3) : digits
  return `+258 ${local.slice(0, 2)} ${local.slice(2, 5)} ${local.slice(5)}`
}

function dismiss(id) {
  messages.value = messages.value.filter(m => m.id !== id)
}

function useCode(message) {
  fillOtp(message.codigo)
  dismiss(message.id)
}

function receive(event) {
  for (const sms of event.detail) {
    const id = ++seq
    messages.value = [{ ...sms, id }, ...messages.value].slice(0, 3)

    // Mensagens sem código desaparecem sozinhas; as de código ficam até serem usadas
    if (!sms.codigo) setTimeout(() => dismiss(id), 12000)
  }
}

onMounted(() => window.addEventListener('demo-sms', receive))
onBeforeUnmount(() => window.removeEventListener('demo-sms', receive))
</script>

<template>
  <div class="demo-inbox" aria-live="polite">
    <TransitionGroup name="sms">
      <article
        v-for="message in messages"
        :key="message.id"
        class="sms"
      >
        <header class="sms-head">
          <span class="sms-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 5h16v11H8l-4 4V5Z" /></svg>
          </span>
          <div class="sms-meta">
            <strong>SMS simulado</strong>
            <small>para {{ formatPhone(message.telefone) }}</small>
          </div>
          <button
            type="button"
            class="sms-close"
            aria-label="Fechar notificação"
            @click="dismiss(message.id)"
          >
            ×
          </button>
        </header>

        <p class="sms-text">{{ message.mensagem }}</p>

        <div v-if="message.codigo" class="sms-code">
          <span class="code" :aria-label="`Código ${message.codigo.split('').join(' ')}`">{{ message.codigo }}</span>
          <button type="button" class="sms-fill" @click="useCode(message)">
            Preencher código
          </button>
        </div>
      </article>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.demo-inbox {
  position: fixed;
  z-index: 10001;
  top: 36px;
  right: 16px;
  width: min(340px, calc(100vw - 32px));
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
  font-family: Arial, Helvetica, sans-serif;
}

.sms {
  overflow: hidden;
  border: 1px solid #e3e3ef;
  border-left: 4px solid #0088cc;
  border-radius: 7px;
  background: #ffffff;
  box-shadow: 0 18px 40px rgba(15, 12, 51, 0.22);
  pointer-events: auto;
}

.sms-head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 10px 6px 12px;
}

.sms-icon {
  width: 30px;
  height: 30px;
  padding: 6px;
  flex-shrink: 0;
  border-radius: 4px;
  background: #27227f;
  color: #ffffff;
}

.sms-icon svg {
  width: 100%;
  height: 100%;
  fill: none;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linejoin: round;
}

.sms-meta {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.sms-meta strong {
  color: #111827;
  font-size: 13px;
}

.sms-meta small {
  color: #5f6675;
  font-size: 12px;
}

.sms-close {
  width: 28px;
  height: 28px;
  flex-shrink: 0;
  border: none;
  border-radius: 4px;
  background: transparent;
  color: #5f6675;
  font-size: 20px;
  line-height: 1;
  cursor: pointer;
}

.sms-close:hover {
  background: #f1f2f8;
}

.sms-text {
  margin: 0;
  padding: 0 12px 12px;
  color: #374151;
  font-size: 13px;
  line-height: 1.45;
}

.sms-code {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  border-top: 1px solid #e3e3ef;
  background: #f7f7fb;
}

.code {
  color: #27227f;
  font-size: 22px;
  font-weight: 800;
  letter-spacing: 4px;
}

.sms-fill {
  min-height: 34px;
  padding: 0 12px;
  border: none;
  border-radius: 6px;
  background: #27227f;
  color: #ffffff;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.sms-fill:hover {
  background: #0088cc;
}

.sms-enter-active,
.sms-leave-active {
  transition: opacity 0.25s ease, transform 0.3s ease;
}

.sms-enter-from {
  opacity: 0;
  transform: translateY(-12px);
}

.sms-leave-to {
  opacity: 0;
  transform: translateX(24px);
}

@media (prefers-reduced-motion: reduce) {
  .sms-enter-active,
  .sms-leave-active {
    transition: none;
  }
}
</style>
