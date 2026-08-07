<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { resendOtp } from '../services/api'

const props = defineProps({
  participantId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['validate'])

const otpDigits = ref(['', '', '', '', '', ''])
const otpInputs = ref([])
const error = ref('')

const resendSeconds = ref(30)
const canResend = computed(() => resendSeconds.value === 0)

let resendTimer = null

const otp = computed(() => otpDigits.value.join(''))

function startResendTimer() {
  resendSeconds.value = 30

  if (resendTimer) {
    clearInterval(resendTimer)
  }

  resendTimer = setInterval(() => {
    if (resendSeconds.value > 0) {
      resendSeconds.value--
    }

    if (resendSeconds.value === 0) {
      clearInterval(resendTimer)
      resendTimer = null
    }
  }, 1000)
}

function handleOtpInput(index) {
  otpDigits.value[index] = otpDigits.value[index]
    .replace(/\D/g, '')
    .slice(0, 1)

  error.value = ''

  if (otpDigits.value[index] && index < 5) {
    otpInputs.value[index + 1]?.focus()
  }
}

function handleBackspace(index) {
  if (!otpDigits.value[index] && index > 0) {
    otpInputs.value[index - 1]?.focus()
  }
}

function handlePaste(event) {
  event.preventDefault()

  const pastedCode = event.clipboardData
    .getData('text')
    .replace(/\D/g, '')
    .slice(0, 6)

  if (!pastedCode) return

  otpDigits.value = ['', '', '', '', '', '']

  pastedCode.split('').forEach((digit, index) => {
    otpDigits.value[index] = digit
  })

  const nextIndex = Math.min(pastedCode.length, 5)
  otpInputs.value[nextIndex]?.focus()
}

function validateOTP() {
  if (!/^\d{6}$/.test(otp.value)) {
    error.value = 'Introduza um código OTP válido de 6 dígitos.'
    return
  }

  error.value = ''
  emit('validate', otp.value)
}

async function resendOTP() {
  if (!canResend.value) return

  try {
    await resendOtp(props.participantId)

    otpDigits.value = ['', '', '', '', '', '']
    error.value = ''

    startResendTimer()

    setTimeout(() => {
      otpInputs.value[0]?.focus()
    })

    alert('Um novo código foi enviado.')
  } catch (error) {
    alert(error.message)
  }
}

onMounted(() => {
  startResendTimer()
})

onUnmounted(() => {
  if (resendTimer) {
    clearInterval(resendTimer)
  }
})
</script>

<template>
  <div class="otp-page">
    

    <main class="page-content">
      <section class="otp-card">
        <div class="card-stripe">
          <div class="stripe-accent"></div>
        </div>

        <div class="card-content">
          <h1>Validação por OTP</h1>

          <p class="subtitle">
            Introduza o código de verificação enviado para o seu número de
            telemóvel.
          </p>

          <form @submit.prevent="validateOTP">
            <div class="field-group">
              <label>Código OTP</label>

              <div class="otp-boxes">
                <input
                  v-for="(_, index) in otpDigits"
                  :key="index"
                  :ref="(element) => (otpInputs[index] = element)"
                  v-model="otpDigits[index]"
                  type="text"
                  inputmode="numeric"
                  maxlength="1"
                  class="otp-input"
                  autocomplete="one-time-code"
                  :aria-label="`Dígito ${index + 1} do código OTP`"
                  @input="handleOtpInput(index)"
                  @keydown.backspace="handleBackspace(index)"
                  @paste="handlePaste"
                />
              </div>
            </div>

            <div v-if="error" class="error-message">
              {{ error }}
            </div>

            <button class="validate-button" type="submit">
              Validar
            </button>
          </form>

          <p class="resend-text">
  <template v-if="!canResend">
    Pode solicitar um novo código em
    <strong>00:{{ String(resendSeconds).padStart(2, '0') }}</strong>
  </template>

  <template v-else>
    Não recebeu o código?
  </template>
</p>

<button
  class="resend-button"
  type="button"
  :disabled="!canResend"
  @click="resendOTP"
>
  Reenviar código
</button>
        </div>
      </section>

      <p class="page-footer">
        FACIM · Campanha de Sorteio
      </p>
    </main>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.otp-page {
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: #f7f7fb;
  font-family: Arial, Helvetica, sans-serif;
}

.page-header {
  position: relative;
  width: 100%;
  height: 30px;
  flex-shrink: 0;
  overflow: hidden;
  background-color: #27227f;
}

.header-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 130px;
  height: 100%;
  background-color: #0088cc;
  clip-path: polygon(35% 0, 100% 0, 100% 100%, 0 100%);
}

.page-content {
  width: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.otp-card {
  width: 100%;
  max-width: 430px;
  min-height: 520px;
  overflow: hidden;
  background-color: #ffffff;
  border: 1px solid #e0e0ef;
  border-radius: 10px;
  box-shadow: 0 2px 12px rgba(39, 34, 127, 0.08);
}

.card-stripe {
  position: relative;
  width: 100%;
  height: 28px;
  overflow: hidden;
  background-color: #27227f;
}

.stripe-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 110px;
  height: 100%;
  background-color: #0088cc;
  clip-path: polygon(35% 0, 100% 0, 100% 100%, 0 100%);
}

.card-content {
  padding: 60px 35px 45px;
}

h1 {
  margin: 0 0 10px;
  color: #111827;
  text-align: center;
  font-size: 27px;
  line-height: 1.2;
}

.subtitle {
  margin: 0 0 36px;
  color: #6b7280;
  text-align: center;
  font-size: 14px;
  line-height: 1.5;
}

.field-group {
  margin-bottom: 18px;
}

label {
  display: block;
  margin-bottom: 12px;
  color: #374151;
  font-size: 14px;
  font-weight: 600;
}

.otp-boxes {
  display: flex;
  justify-content: center;
  gap: 9px;
  width: 100%;
}

.otp-input {
  width: 48px;
  height: 56px;
  padding: 0;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background-color: #ffffff;
  color: #111827;
  outline: none;
  text-align: center;
  font-size: 22px;
  font-weight: 700;
}

.otp-input:focus {
  border-color: #27227f;
  box-shadow: 0 0 0 2px rgba(39, 34, 127, 0.08);
}

.error-message {
  margin-bottom: 14px;
  padding: 10px;
  border: 1px solid #fecaca;
  border-radius: 8px;
  background-color: #fef2f2;
  color: #dc2626;
  text-align: center;
  font-size: 13px;
}

.validate-button {
  width: 100%;
  padding: 14px;
  border: 0;
  border-radius: 8px;
  background-color: #27227f;
  color: #ffffff;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
}

.validate-button:hover {
  background-color: #1c1860;
}

.resend-text {
  margin: 28px 0 5px;
  color: #9ca3af;
  text-align: center;
  font-size: 12px;
}

.resend-button {
  display: block;
  margin: 0 auto;
  padding: 6px;
  border: 0;
  background: transparent;
  color: #0088cc;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.resend-button:hover {
  text-decoration: underline;
}

.resend-button:disabled {
  color: #9ca3af;
  cursor: not-allowed;
  text-decoration: none;
  opacity: 0.7;
}

.resend-text strong {
  color: #27227f;
}
.page-footer {
  margin: 18px 0 0;
  color: #9ca3af;
  font-size: 12px;
  text-align: center;
}

@media (max-width: 480px) {
  .page-content {
    padding: 16px;
  }

  .card-content {
    padding: 48px 20px 38px;
  }

  .otp-boxes {
    gap: 6px;
  }

  .otp-input {
    width: 42px;
    height: 52px;
  }

  .otp-card {
    min-height: auto;
  }
}
</style>