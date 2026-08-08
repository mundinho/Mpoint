<script setup>
import { ref } from 'vue'

const emit = defineEmits(['register'])

const name = ref('')
const phone = ref('')
const error = ref('')

function submitForm() {
  if (!name.value.trim()) {
    error.value = 'Introduza o seu nome completo.'
    return
  }

  const phonePattern = /^\+?[\d\s\-()]{8,}$/

  if (!phonePattern.test(phone.value)) {
    error.value = 'Introduza um número de telemóvel válido.'
    return
  }

  error.value = ''

  emit('register', {
    name: name.value.trim(),
    phone: phone.value.trim(),
  })
}

defineProps({
  loading: {
    type: Boolean,
    default: false
  }
})

</script>

<template>
  <div class="register-page">
    <!-- <header class="page-header">
      <div class="header-accent"></div>
    </header> -->

    <main class="page-content">
      <section class="register-card">
        <div class="card-stripe">
          <div class="stripe-accent"></div>
        </div>

        <div class="card-content">
          <h1>Registo</h1>

          <p class="subtitle">
            Introduza os seus dados para participar no sorteio
          </p>

          <form @submit.prevent="submitForm">
            <div class="field-group">
              <label for="name">Nome completo</label>

             <input
  id="name"
  v-model="name"
  type="text"
  autocomplete="off"
/>
            </div>

            <div class="field-group">
              <label for="phone">Número de telemóvel</label>

              <input
                id="phone"
                v-model="phone"
                type="tel"
                placeholder="8XXXXXXXX"
                autocomplete="off"
              />
            </div>

            <p v-if="error" class="error-message">
              {{ error }}
            </p>

           <button
  type="submit"
  :disabled="loading"
>
  {{ loading ? 'A processar...' : 'Registar' }}
</button>
          </form>

          <p class="terms">
            Ao registar-se, concorda com os termos e condições da campanha.
          </p>
        </div>
      </section>

      <p class="footer-text">
        FACIM · Campanha de Sorteio
      </p>
    </main>
  </div>
</template>

<style scoped>
.register-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f7f7fb;
  font-family: Arial, sans-serif;
}

.page-header {
  position: relative;
  height: 28px;
  overflow: hidden;
  background: #27227f;
}

.header-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 120px;
  height: 100%;
  background: #0088cc;
  clip-path: polygon(35% 0, 100% 0, 100% 100%, 0 100%);
}

.page-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.register-card {
  width: 100%;
  max-width: 400px;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #e0e0ef;
  border-radius: 10px;
  box-shadow: 0 2px 12px rgba(39, 34, 127, 0.08);
}

.card-stripe {
  position: relative;
  height: 30px;
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

.card-content {
  padding:55px 40px;
}

h1 {
  margin: 0 0 6px;
  text-align: center;
  font-size: 28px;
  color: #111827;
}

.subtitle {
  margin: 0 0 32px;
  text-align: center;
  color: #6b7280;
  font-size: 14px;
}

.field-group {
  margin-bottom: 18px;
}

label {
  display: block;
  margin-bottom: 7px;
  color: #374151;
  font-size: 14px;
  font-weight: 600;
}

input {
  width: 100%;
  box-sizing: border-box;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #ffffff;
  color: #111827;
  font-size: 14px;
  outline: none;
}

input:focus {
  border-color: #27227f;
}

button {
  width: 100%;
  margin-top: 8px;
  padding: 13px;
  border: none;
  border-radius: 8px;
  background: #27227f;
  color: #ffffff;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
}

button:hover {
  background: #1c1860;
}

.error-message {
  margin: 0 0 12px;
  padding: 10px;
  border: 1px solid #fecaca;
  border-radius: 8px;
  background: #fef2f2;
  color: #dc2626;
  font-size: 14px;
}

.terms {
  margin-top: 24px;
  text-align: center;
  color: #9ca3af;
  font-size: 12px;
}

.footer-text {
  margin-top: 20px;
  color: #9ca3af;
  font-size: 12px;
}
</style>