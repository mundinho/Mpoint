import { onBeforeUnmount, onMounted } from 'vue'

// Activo apenas no build de demonstração (npm run build:demo)
export const isDemo = import.meta.env.VITE_DEMO === 'true'
export const demoAdminPhone = import.meta.env.VITE_DEMO_ADMIN_PHONE || ''
export const landingUrl = import.meta.env.VITE_LANDING_URL || '/'

// Chamado pelo services/api.js: entrega ao ecrã os SMS que o backend simulou
export function publishDemoSms(data) {
  if (!isDemo || !Array.isArray(data?._demo_sms)) return
  window.dispatchEvent(new CustomEvent('demo-sms', { detail: data._demo_sms }))
}

export function fillOtp(code) {
  window.dispatchEvent(new CustomEvent('demo-fill-otp', { detail: code }))
}

// Os ecrãs com campos OTP usam isto para aceitar o "Preencher código" da notificação
export function useDemoOtpFill(digitsRef, onFilled) {
  if (!isDemo) return

  function handle(event) {
    const code = String(event.detail || '').replace(/\D/g, '').slice(0, 6)
    if (code.length !== 6) return
    digitsRef.value = code.split('')
    onFilled?.()
  }

  onMounted(() => window.addEventListener('demo-fill-otp', handle))
  onBeforeUnmount(() => window.removeEventListener('demo-fill-otp', handle))
}
