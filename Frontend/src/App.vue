<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import RegisterScreen from './components/RegisterScreen.vue'
import OtpScreen from './components/OtpScreen.vue'
import DrawScreen from './components/DrawScreen.vue'
import ConfirmModal from './components/ConfirmModal.vue'
import ResultModal from './components/ResultModal.vue'
import AdminLogin from './components/AdminLogin.vue'
import AdminDashboard from './components/AdminDashboard.vue'
import CampaignManagement from './components/CampaignManagement.vue'
import AppDialog from './components/AppDialog.vue'
import {
  registerParticipant,
  validateOtp,
  getSquares,
  openNumber,
  getActiveCampaign,
  
} from './services/api'

const route = useRoute()

const currentScreen = ref('register')

watch(
  () => route.path,
  (path) => {
    if (path.startsWith('/admin')) {
      currentScreen.value = 'admin-login'
    } else {
      currentScreen.value = 'register'
    }
  },
  { immediate: true }
)


//const currentScreen = ref('admin-login')
// Depois volta para:
 //const currentScreen = ref('register')
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
  type: 'lost',
  prize: ''
})

const isRegistering = ref(false)
const isValidatingOtp = ref(false)

const pendingDialogAction = ref(null)

const dialog = ref({
  visible: false,
  mode: 'notification',
  title: '',
  message: '',
  type: 'info'
})

let dialogTimer = null

function showDialog(
  message,
  type = 'info',
  title = '',
  mode = 'notification'
) {
  dialog.value = {
    visible: true,
    mode,
    title,
    message,
    type
  }

  clearTimeout(dialogTimer)

  if (mode === 'notification') {
    dialogTimer = setTimeout(() => {
      dialog.value.visible = false
    }, 3500)
  }
}

function handleConfirmRequest(data) {
  pendingDialogAction.value = data.action

  showDialog(
    data.message,
    'warning',
    'Confirmar acção',
    'confirm'
  )
}

async function confirmDialogAction() {
  const action = pendingDialogAction.value

  closeDialog()
  pendingDialogAction.value = null

  if (action) {
    await action()
  }
}


function handleToast(data) {
  showDialog(
    data.message,
    data.type,
    '',
    'notification'
  )
}

function closeDialog() {
  dialog.value.visible = false
}

async function handleRegister(data) {
  if (isRegistering.value) return

  isRegistering.value = true

  try {
    const response = await registerParticipant(data)

    participant.value = {
      id: response.usuario.id,
      name: response.usuario.nome,
      phone: response.usuario.telefone
    }

    currentScreen.value = 'otp'

  } catch (error) {
  showDialog(error.message, 'error')
}finally {
    isRegistering.value = false
  }
}

async function handleOTP(code) {
  if (isValidatingOtp.value) return

  isValidatingOtp.value = true

  try {
    await validateOtp(participant.value.id, code)

    const response = await getSquares()

    squares.value = Array.isArray(response)
      ? response
      : response.quadrados || response.data || []

    currentScreen.value = 'draw'

  } catch (error) {
  showDialog(error.message, 'error')
} finally {
    isValidatingOtp.value = false
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
    const participation = await openNumber(
      participant.value.id,
      selectedNumber.value
    )

    // TENTAR NOVAMENTE
    if (participation.resultado === 'tentar_novamente') {
      result.value = {
        type: 'retry',
        prize: ''
      }

      showResultModal.value = true

      return
    }

    // VENCEDOR OU NÃO VENCEDOR
    result.value = {
      type: participation.resultado === 'vencedor' ? 'won' : 'lost',
      prize:
        participation.premio?.descricao ||
        participation.premio_descricao ||
        ''
    }

    showResultModal.value = true

 } catch (error) {
 showDialog(error.message, 'error')
  selectedNumber.value = null
}
}

function closeResultModal() {
  showResultModal.value = false

  participant.value = {
     id: null,
    name: '',
    phone: ''
  }

  selectedNumber.value = null

  currentScreen.value = 'register'
}

async function retryGame() {
  showResultModal.value = false
  selectedNumber.value = null

  try {
    const response = await getSquares()

    squares.value = Array.isArray(response)
      ? response
      : response.quadrados || response.data || []

    currentScreen.value = 'draw'
  } catch (error) {
    alert(error.message)
  }
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
  :loading="isRegistering"
  @register="handleRegister"
/>

 <OtpScreen
  v-else-if="currentScreen === 'otp'"
  :participant-id="participant.id"
  :loading="isValidatingOtp"
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
  @toast="handleToast"
  @confirm="handleConfirmRequest"
/>

 <CampaignManagement
  v-else-if="currentScreen === 'campaign-management'"
  @back-dashboard="currentScreen = 'dashboard'"
  @toast="handleToast"
  @confirm="handleConfirmRequest"
/>

  <ConfirmModal
    v-if="showConfirmModal && selectedNumber !== null"
    :number="selectedNumber"
    @confirm="confirmNumberSelection"
    @cancel="cancelNumberSelection"
  />

 <ResultModal
  v-if="showResultModal && selectedNumber !== null"
  :result-type="result.type"
  :number="selectedNumber"
  :prize="result.prize"
  @close="closeResultModal"
  @retry="retryGame"
/>

<AppDialog
  :visible="dialog.visible"
  :mode="dialog.mode"
  :title="dialog.title"
  :message="dialog.message"
  :type="dialog.type"
  @cancel="closeDialog"
  @confirm="confirmDialogAction"
/>

</template>