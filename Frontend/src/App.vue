<script setup>
import { onMounted, ref } from 'vue'

import RegisterScreen from './components/RegisterScreen.vue'
import OtpScreen from './components/OtpScreen.vue'
import DrawScreen from './components/DrawScreen.vue'
import ConfirmModal from './components/ConfirmModal.vue'
import ResultModal from './components/ResultModal.vue'
import AdminLogin from './components/AdminLogin.vue'
import AdminDashboard from './components/AdminDashboard.vue'
import CampaignManagement from './components/CampaignManagement.vue'

import {
  registerParticipant,
  resendOtp,
  validateOtp,
  getSquares,
  openNumber,
  getResult,
  getPrizes,
  getActiveCampaign,
  resetCampaign
} from './services/api'

const currentScreen = ref('admin-login')
// Depois volta para:
// const currentScreen = ref('register')
const activeCampaign = ref(null)
const admin = ref(null)

const participant = ref({
  id: null,
  name: '',
  phone: ''
})

const selectedNumber = ref(null)
const squares = ref([])

const showConfirmModal = ref(false)
const showResultModal = ref(false)

const result = ref({
  won: false,
  prize: ''
})

async function handleRegister(data) {
  try {
    const response = await registerParticipant(data)

    participant.value = {
      id: response.usuario.id,
      name: response.usuario.nome,
      phone: response.usuario.telefone
    }

    currentScreen.value = 'otp'
  } catch (error) {
    alert(error.message)
  }
}

async function handleOTP(code) {
  try {
    await validateOtp(participant.value.id, code)

    const response = await getSquares()

    squares.value = Array.isArray(response)
      ? response
      : response.quadrados || response.data || []

    currentScreen.value = 'draw'
  } catch (error) {
    alert(error.message)
  }
}

function handleNumberSelection(number) {
  selectedNumber.value = number
  showConfirmModal.value = true
}

function cancelNumberSelection() {
  showConfirmModal.value = false
  selectedNumber.value = null
}

async function confirmNumberSelection() {
  showConfirmModal.value = false

  try {
    await openNumber(
      participant.value.id,
      selectedNumber.value
    )

    const participation = await getResult(
      participant.value.id
    )

    result.value = {
      won: participation.resultado === 'vencedor',
      prize:
        participation.premio?.nome ||
        participation.premio_nome ||
        ''
    }

    showResultModal.value = true
  } catch (error) {
    alert(error.message)
    selectedNumber.value = null
  }
}

function closeResultModal() {
  showResultModal.value = false

  participant.value = {
    name: '',
    phone: ''
  }

  selectedNumber.value = null

  currentScreen.value = 'register'
}

function handleAdminLogin(data) {
  admin.value = data.admin
  currentScreen.value = 'dashboard'
}

function handleAdminLogout() {
  admin.value = null
  currentScreen.value = 'admin-login'
}

onMounted(async () => {
  try {
    activeCampaign.value = await getActiveCampaign()
  } catch (error) {
    console.error('Não foi possível carregar a campanha activa:', error)
  }
})

</script>
<template>
  <RegisterScreen
    v-if="currentScreen === 'register'"
    @register="handleRegister"
  />

  <OtpScreen
    v-else-if="currentScreen === 'otp'"
    :participant-id="participant.id"
    @validate="handleOTP"
  />

  <DrawScreen
  v-else-if="currentScreen === 'draw'"
  :participant-name="participant.name"
  :squares="squares"
  @select-number="handleNumberSelection"
/>

  <AdminLogin
    v-else-if="currentScreen === 'admin-login'"
    @login="handleAdminLogin"
  />

  <AdminDashboard
  v-else-if="currentScreen === 'dashboard'"
  :admin="admin"
  @open-management="currentScreen = 'campaign-management'"
  @logout="handleAdminLogout"
/>

  <CampaignManagement
    v-else-if="currentScreen === 'campaign-management'"
    @back-dashboard="currentScreen = 'dashboard'"
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
</template>