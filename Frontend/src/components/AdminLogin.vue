<script setup>
import { ref } from 'vue'

const emit = defineEmits(['login'])

const username = ref('')
const password = ref('')
const error = ref('')
const showPassword = ref(false)

function submitLogin() {
  if (!username.value.trim()) {
    error.value = 'Introduza o nome de utilizador.'
    return
  }

  if (!password.value.trim()) {
    error.value = 'Introduza a palavra-passe.'
    return
  }

  error.value = ''

  emit('login', {
    username: username.value.trim(),
    password: password.value
  })
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
          <h1>Iniciar Sessão</h1>

          <p class="subtitle">
            Introduza as suas credenciais para aceder ao painel administrativo.
          </p>

          <form @submit.prevent="submitLogin">
            <div class="field-group">
              <label for="username">Nome de utilizador</label>

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
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                  </svg>
                </span>

                <input
                  id="username"
                  v-model="username"
                  type="text"
                  placeholder="Introduza o nome de utilizador"
                  autocomplete="username"
                  @input="error = ''"
                />
              </div>
            </div>

            <div class="field-group">
              <label for="password">Palavra-passe</label>

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
                    <rect x="4" y="10" width="16" height="10" rx="2" />
                    <path d="M8 10V7a4 4 0 018 0v3" />
                  </svg>
                </span>

                <input
                  id="password"
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Introduza a palavra-passe"
                  autocomplete="current-password"
                  @input="error = ''"
                />

                <button
                  type="button"
                  class="show-password-button"
                  :aria-label="showPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'"
                  @click="showPassword = !showPassword"
                >
                  {{ showPassword ? 'Ocultar' : 'Mostrar' }}
                </button>
              </div>
            </div>

            <div v-if="error" class="error-message">
              {{ error }}
            </div>

            <button type="submit" class="login-button">
              Entrar
            </button>
          </form>

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

input {
  width: 100%;
  min-height: 50px;
  padding: 12px 82px 12px 42px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #ffffff;
  color: #111827;
  font-size: 14px;
  outline: none;
}

input:focus {
  border-color: #27227f;
  box-shadow: 0 0 0 2px rgba(39, 34, 127, 0.08);
}

.show-password-button {
  position: absolute;
  top: 50%;
  right: 10px;
  padding: 5px;
  border: 0;
  background: transparent;
  color: #0088cc;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transform: translateY(-50%);
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
  border: 0;
  border-radius: 8px;
  background: #27227f;
  color: #ffffff;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
}

.login-button:hover {
  background: #1c1860;
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
    padding: 48px 26px 38px;
  }

  .login-card {
    min-height: auto;
  }
}
</style>