<script setup>
import { ref } from 'vue'

import RegisterScreen from './components/RegisterScreen.vue'
import OtpScreen from './components/OtpScreen.vue'
import DrawScreen from './components/DrawScreen.vue'
import ConfirmModal from './components/ConfirmModal.vue'
import ResultModal from './components/ResultModal.vue'
import AdminLogin from './components/AdminLogin.vue'
import AdminDashboard from './components/AdminDashboard.vue'
import CampaignManagement from './components/CampaignManagement.vue'

const currentScreen = ref('admin-login')
// Depois volta para:
// const currentScreen = ref('register')

const participant = ref({
  name: '',
  phone: ''
})

const selectedNumber = ref(null)

const showConfirmModal = ref(false)
const showResultModal = ref(false)

const result = ref({
  won: false,
  prize: ''
})

function handleRegister(data) {
  participant.value = data
  currentScreen.value = 'otp'
}

function handleOTP(code) {
  console.log('OTP:', code)
  currentScreen.value = 'draw'
}

function handleNumberSelection(number) {
  selectedNumber.value = number
  showConfirmModal.value = true
}

function cancelNumberSelection() {
  showConfirmModal.value = false
  selectedNumber.value = null
}

function confirmNumberSelection() {
  showConfirmModal.value = false

  const winningNumbers = {
    17: 'Smartphone',
    58: 'Smart TV',
    420: 'Voucher',
    998: 'Mochila'
  }

  const prize = winningNumbers[selectedNumber.value]

  result.value = {
    won: Boolean(prize),
    prize: prize || ''
  }

  showResultModal.value = true
}

function closeResultModal() {
  showResultModal.value = false

  participant.value = {
    name: '',
    phone: ''
  }

  selectedNumber.value = null

  currentScreen.value = 'admin-login'
}

function handleAdminLogin(data) {
  console.log('Login administrativo:', data)
  currentScreen.value = 'dashboard'
}
</script>

<template>



  <RegisterScreen
    v-if="currentScreen === 'register'"
    @register="handleRegister"
  />

  <OtpScreen
    v-else-if="currentScreen === 'otp'"
    @validate="handleOTP"
  />

  <DrawScreen
    v-else-if="currentScreen === 'draw'"
    :participant-name="participant.name"
    @select-number="handleNumberSelection"
  />

  <ConfirmModal
    v-if="showConfirmModal && selectedNumber !== null"
    :number="selectedNumber"
    @confirm="confirmNumberSelection"
    @cancel="cancelNumberSelection"
  />

  <ResultModal
    v-if="showResultModal && selectedNumber !== null"
    :won="result.won"
    :number="selectedNumber"
    :prize="result.prize"
    @close="closeResultModal"
  />

<AdminLogin
    v-else-if="currentScreen === 'admin-login'"
    @login="handleAdminLogin"
  />

 <AdminDashboard
  v-else-if="currentScreen === 'dashboard'"
  @open-management="currentScreen = 'campaign-management'"
  @logout="currentScreen = 'admin-login'"
/>

<CampaignManagement
  v-else-if="currentScreen === 'campaign-management'"
  @back-dashboard="currentScreen = 'dashboard'"
/>
</template>