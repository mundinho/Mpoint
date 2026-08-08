<script setup>
import { ref } from 'vue'
import {
  requestAdminLogin,
  validateAdminLogin,
  getAdminMe
} from '../services/api'
import LoadingSpinner from './LoadingSpinner.vue'

const emit = defineEmits(['login'])

const step = ref('phone')

const phone = ref('')
const otpDigits = ref(['', '', '', '', '', ''])
const otpInputs = ref([])

const error = ref('')
const loading = ref(false)

const resendSeconds = ref(0)
let resendInterval = null

function normalizePhone(value) {
  return value.replace(/\D/g, '')
}

async function requestCode() {
  error.value = ''

  const normalizedPhone = normalizePhone(phone.value)

  const fullPhone = normalizedPhone.startsWith('258')
  ? `+${normalizedPhone}`
  : `+258${normalizedPhone}`

  if (normalizedPhone.length < 9) {
    error.value = 'Introduza um número de telefone válido.'
    return
  }

  try {
    loading.value = true

    await requestAdminLogin(fullPhone)

    phone.value = normalizedPhone.startsWith('258')
  ? normalizedPhone.substring(3)
  : normalizedPhone
    step.value = 'otp'

    startResendTimer()

    setTimeout(() => {
      otpInputs.value[0]?.focus()
    })
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

function handleOtpInput(index, event) {
  const value = event.target.value.replace(/\D/g, '').slice(-1)

  otpDigits.value[index] = value

  if (value && index < 5) {
    otpInputs.value[index + 1]?.focus()
  }
}

function handleOtpKeydown(index, event) {
  if (
    event.key === 'Backspace' &&
    !otpDigits.value[index] &&
    index > 0
  ) {
    otpInputs.value[index - 1]?.focus()
  }
}

async function validateCode() {
  error.value = ''

  const code = otpDigits.value.join('')

  if (code.length !== 6) {
    error.value = 'Introduza o código de 6 dígitos.'
    return
  }

  try {
    loading.value = true

    const response = await validateAdminLogin(
      phone.value,
      code
    )

    const token = response.token

    localStorage.setItem('adminToken', token)

    const admin = await getAdminMe(token)

    emit('login', {
      token,
      admin
    })
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

function startResendTimer() {
  clearInterval(resendInterval)

  resendSeconds.value = 60

  resendInterval = setInterval(() => {
    resendSeconds.value--

    if (resendSeconds.value <= 0) {
      clearInterval(resendInterval)
    }
  }, 1000)
}

async function resendCode() {
  if (resendSeconds.value > 0) return

  try {
    loading.value = true
    error.value = ''

    await requestAdminLogin(phone.value)

    otpDigits.value = ['', '', '', '', '', '']

    startResendTimer()

    setTimeout(() => {
      otpInputs.value[0]?.focus()
    })
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

function changePhone() {
  step.value = 'phone'
  otpDigits.value = ['', '', '', '', '', '']
  error.value = ''
  clearInterval(resendInterval)
}
</script>

<template>
  <div class="login-page">
    <main class="page-content">
      <section class="login-card">
        <div class="card-stripe">
          <div class="stripe-accent"></div>
        </div>

        <div class="card-content">
          <!-- ETAPA 1 -->
          <template v-if="step === 'phone'">
            <h1>Acesso Administrativo</h1>

            <p class="subtitle">
              Introduza o seu número de telefone para receber o código de acesso.
            </p>

            <div class="field-group">
              <label for="phone">
                Número de telefone
              </label>

              <div class="input-wrapper">
                <span class="input-icon">
                  <svg
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <rect
                      x="5"
                      y="2"
                      width="14"
                      height="20"
                      rx="2"
                      ry="2"
                    />
                    <line
                      x1="12"
                      y1="18"
                      x2="12.01"
                      y2="18"
                    />
                  </svg>
                </span>

                <input
                  id="phone"
                  v-model="phone"
                  type="tel"
                  placeholder="8XXXXXXXX"
                  autocomplete="off"
                  @keyup.enter="requestCode"
                  @input="error = ''"
                  
                />
              </div>
            </div>

            <div
              v-if="error"
              class="error-message"
            >
              {{ error }}
            </div>

            <button
              type="button"
              class="login-button"
              :disabled="loading"
              @click="requestCode"
            >
              <LoadingSpinner v-if="loading" />
              {{ loading ? 'A enviar...' : 'Enviar código' }}
            </button>
          </template>

          <!-- ETAPA 2 -->
          <template v-else>
            <h1>Verificação OTP</h1>

            <p class="subtitle">
              Introduza o código enviado para
              <strong>{{ phone }}</strong>.
            </p>

            <div class="otp-container">
              <input
                v-for="(_, index) in otpDigits"
                :key="index"
                :ref="el => otpInputs[index] = el"
                v-model="otpDigits[index]"
                type="text"
                inputmode="numeric"
                maxlength="1"
                class="otp-input"
                @input="handleOtpInput(index, $event)"
                @keydown="handleOtpKeydown(index, $event)"
              />
            </div>

            <div
              v-if="error"
              class="error-message"
            >
              {{ error }}
            </div>

            <button
              type="button"
              class="login-button"
              :disabled="loading"
              @click="validateCode"
            >
              <LoadingSpinner v-if="loading" />
              {{ loading ? 'A validar...' : 'Entrar' }}
            </button>

            <div class="otp-actions">
              <button
                type="button"
                class="link-button"
                :disabled="loading"
                @click="changePhone"
              >
                Alterar número
              </button>

              <button
                type="button"
                class="link-button"
                :disabled="resendSeconds > 0 || loading"
                @click="resendCode"
              >
                <LoadingSpinner
                  v-if="loading"
                  color="purple"
                  :size="11"
                />
                {{
                  resendSeconds > 0
                    ? `Reenviar em ${resendSeconds}s`
                    : 'Reenviar código'
                }}
              </button>
            </div>
          </template>

          <p class="access-note">
            Acesso reservado aos utilizadores autorizados.
          </p>
        </div>
      </section>

      <p class="footer-text">
        FACIM · Sistema de Gestão da Campanha
      </p>
    </main>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.login-page {
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f7f7fb;
  font-family: Arial, Helvetica, sans-serif;
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

.login-card {
  width: 100%;
  max-width: 390px;
  min-height: 560px;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #e0e0ef;
  border-radius: 10px;
  box-shadow: 0 2px 12px rgba(39, 34, 127, 0.08);
}

.card-stripe {
  position: relative;
  width: 100%;
  height: 30px;
  overflow: hidden;
  background: #27227f;
}

.stripe-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 115px;
  height: 100%;
  background: #0088cc;
  clip-path: polygon(35% 0, 100% 0, 100% 100%, 0 100%);
}

.card-content {
  padding: 62px 40px 45px;
}

h1 {
  margin: 0 0 10px;
  color: #111827;
  text-align: center;
  font-size: 28px;
}

.subtitle {
  margin: 0 0 34px;
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
  margin-bottom: 8px;
  color: #374151;
  font-size: 14px;
  font-weight: 600;
}

.input-wrapper {
  position: relative;
}

.input-icon {
  position: absolute;
  top: 50%;
  left: 13px;
  display: flex;
  color: #9ca3af;
  transform: translateY(-50%);
  pointer-events: none;
}

.input-wrapper input {
  width: 100%;
  min-height: 50px;
  padding: 12px 13px 12px 42px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #ffffff;
  color: #111827;
  font-size: 14px;
  outline: none;
}

.input-wrapper input:focus {
  border-color: #27227f;
  box-shadow: 0 0 0 2px rgba(39, 34, 127, 0.08);
}

.otp-container {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-bottom: 24px;
}

.otp-input {
  width: 44px;
  height: 52px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #ffffff;
  color: #111827;
  text-align: center;
  font-size: 21px;
  font-weight: 700;
  outline: none;
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
  background: #fef2f2;
  color: #dc2626;
  text-align: center;
  font-size: 13px;
}

.login-button {
  width: 100%;
  margin-top: 6px;
  padding: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  border: 0;
  border-radius: 8px;
  background: #27227f;
  color: #ffffff;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
}

.login-button:hover:not(:disabled) {
  background: #1c1860;
}

.login-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.otp-actions {
  margin-top: 20px;
  display: flex;
  justify-content: space-between;
  gap: 15px;
}

.link-button {
  padding: 0;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 0;
  background: transparent;
  color: #0088cc;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.link-button:disabled {
  color: #9ca3af;
  cursor: not-allowed;
}

.access-note {
  margin: 30px 0 0;
  color: #9ca3af;
  text-align: center;
  font-size: 12px;
}

.footer-text {
  margin: 18px 0 0;
  color: #9ca3af;
  text-align: center;
  font-size: 12px;
}

@media (max-width: 480px) {
  .page-content {
    padding: 16px;
  }

  .card-content {
    padding: 48px 24px 38px;
  }

  .login-card {
    min-height: auto;
  }

  .otp-input {
    width: 38px;
    height: 48px;
  }
}
</style>
