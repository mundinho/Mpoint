<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import RegisterScreen from './components/RegisterScreen.vue'
import OtpScreen from './components/OtpScreen.vue'
import DrawScreen from './components/DrawScreen.vue'
import ConfirmModal from './components/ConfirmModal.vue'
import ResultModal from './components/ResultModal.vue'
import AdminLogin from './components/AdminLogin.vue'
import CampaignSelect from './components/CampaignSelect.vue'
import AdminSidebar from './components/AdminSidebar.vue'
import AdminDashboard from './components/AdminDashboard.vue'
import ChartsView from './components/ChartsView.vue'
import CampaignManagement from './components/CampaignManagement.vue'
import AppDialog from './components/AppDialog.vue'
import ToastContainer from './components/ToastContainer.vue'

const ADMIN_PANEL_SCREENS = ['dashboard', 'charts', 'campaign-management']
import {
  registerParticipant,
  validateOtp,
  getSquares,
  openNumber,
  getActiveCampaign,
  getAdminMe,
  adminLogout
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
const selectedCampaignId = ref(null)

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
const isOpeningNumber = ref(false)

const pendingDialogAction = ref(null)
const isConfirmActionLoading = ref(false)

const dialog = ref({
  visible: false,
  title: '',
  message: '',
  type: 'warning'
})

const toasts = ref([])
let toastSeq = 0

function showToast(message, type = 'info') {
  const id = ++toastSeq

  toasts.value.push({ id, message, type })

  setTimeout(() => dismissToast(id), 4000)
}

function dismissToast(id) {
  toasts.value = toasts.value.filter(toast => toast.id !== id)
}

function handleConfirmRequest(data) {
  pendingDialogAction.value = data.action

  dialog.value = {
    visible: true,
    title: 'Confirmar acção',
    message: data.message,
    type: 'warning'
  }
}

async function confirmDialogAction() {
  const action = pendingDialogAction.value

  if (!action) {
    closeDialog()
    return
  }

  isConfirmActionLoading.value = true

  try {
    await action()
  } finally {
    isConfirmActionLoading.value = false
    pendingDialogAction.value = null
    closeDialog()
  }
}


function handleToast(data) {
  showToast(data.message, data.type)
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
  showToast(error.message, 'error')
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
  showToast(error.message, 'error')
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
  if (isOpeningNumber.value) return

  isOpeningNumber.value = true

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

      showConfirmModal.value = false
      showResultModal.value = true

      return
    }

    // VENCEDOR OU NÃO VENCEDOR
    result.value = {
      type: participation.resultado === 'vencedor' ? 'won' : 'lost',
      prize: participation.premio?.nome || ''
    }

    showConfirmModal.value = false
    showResultModal.value = true

 } catch (error) {
  showConfirmModal.value = false
 showToast(error.message, 'error')
  selectedNumber.value = null
} finally {
    isOpeningNumber.value = false
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
    showToast(error.message, 'error')
  }
}

function handleAdminLogin(data) {
  admin.value = data.admin
  currentScreen.value = 'campaign-select'
}

let isLoggingOut = false

async function handleAdminLogout() {
  if (isLoggingOut) return

  isLoggingOut = true

  const token = localStorage.getItem('adminToken')

  try {
    if (token) {
      await adminLogout(token)
    }
  } catch (error) {
    console.error('Erro ao terminar sessão:', error)
  } finally {
    isLoggingOut = false
    localStorage.removeItem('adminToken')
    admin.value = null
    selectedCampaignId.value = null
    currentScreen.value = 'admin-login'
  }
}

function handleCampaignSelected(campaignId) {
  selectedCampaignId.value = campaignId
  currentScreen.value = 'dashboard'
}

function switchCampaign() {
  selectedCampaignId.value = null
  currentScreen.value = 'campaign-select'
}

onMounted(async () => {
  try {
    activeCampaign.value = await getActiveCampaign()
  } catch (error) {
    console.error('Não foi possível carregar a campanha activa:', error)
  }

  const token = localStorage.getItem('adminToken')

  if (token) {
    try {
      admin.value = await getAdminMe(token)
      currentScreen.value = 'campaign-select'
    } catch (error) {
      localStorage.removeItem('adminToken')
      admin.value = null
    }
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
  @toast="handleToast"
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

  <CampaignSelect
    v-else-if="currentScreen === 'campaign-select'"
    @select="handleCampaignSelected"
    @logout="handleAdminLogout"
    @toast="handleToast"
    @confirm="handleConfirmRequest"
  />

  <div
    v-else-if="ADMIN_PANEL_SCREENS.includes(currentScreen)"
    class="admin-shell"
  >
    <AdminSidebar
      :active="currentScreen"
      :admin="admin"
      @navigate="currentScreen = $event"
      @switch-campaign="switchCampaign"
      @logout="handleAdminLogout"
    />

    <div class="admin-shell-content">
      <AdminDashboard
        v-if="currentScreen === 'dashboard'"
        :admin="admin"
        :campaign-id="selectedCampaignId"
        @toast="handleToast"
        @confirm="handleConfirmRequest"
      />

      <ChartsView
        v-else-if="currentScreen === 'charts'"
        :campaign-id="selectedCampaignId"
        @switch-campaign="switchCampaign"
        @logout="handleAdminLogout"
        @toast="handleToast"
      />

      <CampaignManagement
        v-else-if="currentScreen === 'campaign-management'"
        :campaign-id="selectedCampaignId"
        @switch-campaign="switchCampaign"
        @campaign-reset="selectedCampaignId = $event"
        @logout="handleAdminLogout"
        @toast="handleToast"
        @confirm="handleConfirmRequest"
      />
    </div>
  </div>

  <ConfirmModal
    v-if="showConfirmModal && selectedNumber !== null"
    :number="selectedNumber"
    :loading="isOpeningNumber"
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
  mode="confirm"
  :title="dialog.title"
  :message="dialog.message"
  :type="dialog.type"
  :loading="isConfirmActionLoading"
  @cancel="closeDialog"
  @confirm="confirmDialogAction"
/>

<ToastContainer
  :toasts="toasts"
  @dismiss="dismissToast"
/>

</template>

<style scoped>
.admin-shell {
  width: 100%;
  min-height: 100vh;
  display: flex;
  align-items: stretch;
}

.admin-shell-content {
  min-width: 0;
  flex: 1;
}
</style>
