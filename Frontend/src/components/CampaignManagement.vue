<script setup>
import { computed, onMounted, ref } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'
import { useI18n } from 'vue-i18n'


import {
  resetCampaign as resetCampaignApi,
  getCampaign,
  updateCampaign,
  activateCampaign as activateCampaignApi,
  pauseCampaign as pauseCampaignApi,
  closeCampaignApi,
  configureRandomDistribution,
  configureManualDistribution,
  getPrizeSummary,
  getPrizeBank,
  createPrizeBankItem,
  deletePrizeBankItem,
  updatePrizeBankItem,
} from '../services/api'

const { t } = useI18n()

const props = defineProps({
  campaignId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits([
  'switch-campaign',
  'campaign-reset',
  'logout',
  'toast',
   'confirm'
])

function translatePrizeStatus(status) {
  if (status === 'Disponível') {
    return t('campaignManagement.prizes.manualTable.available')
  }

  if (status === 'Atribuído') {
    return t('campaignManagement.prizes.manualTable.assigned')
  }

  if (status === 'Entregue') {
    return t('campaignManagement.prizes.manualTable.delivered')
  }

  return status
}

function showToast(message, type = 'error') {
  emit('toast', {
    message,
    type
  })
}

function requestConfirm(message, action) {
  emit('confirm', {
    message,
    action
  })
}

const campaign = ref({
  id: null,
  name: '',
  status: '',
  startDate: '',
  endDate: '',
  totalNumbers: 1000,
  totalPrizes: 10,
  otpValidity: 5,
  maximumOtpAttempts: 5,
  smsResultEnabled: true,
resultSmsText: ''
})

const originalCampaign = ref(null)

function snapshotCampaign() {
  originalCampaign.value = { ...campaign.value }
}

const isLoadingCampaign = ref(false)
const isSavingCampaign = ref(false)
const isSavingRandomDistribution = ref(false)
const isSavingManualDistribution = ref(false)
const isActivatingCampaign = ref(false)
const isPausingCampaign = ref(false)
const isLoadingPrizeSummary = ref(false)

const prizes = ref([])
const distributionMode = ref('aleatorio')
const prizeSummary = ref([])
const prizeSummarySearch = ref('')

const filteredPrizeSummary = computed(() => {
  const value = prizeSummarySearch.value
    .trim()
    .toLowerCase()

  if (!value) {
    return prizeSummary.value
  }

  return prizeSummary.value.filter(prize =>
    String(prize.nome || '')
      .toLowerCase()
      .includes(value)
  )
})

const totalPrizeQuantity = computed(() =>
  prizeSummary.value.reduce(
    (total, prize) =>
      total + Number(prize.quantidade_total || 0),
    0
  )
)

const totalAssignedQuantity = computed(() =>
  prizeSummary.value.reduce(
    (total, prize) =>
      total + Number(prize.quantidade_atribuida || 0),
    0
  )
)

const totalRemainingQuantity = computed(() =>
  prizeSummary.value.reduce(
    (total, prize) =>
      total + Number(prize.quantidade_remanescente || 0),
    0
  )
)

async function loadPrizeSummary() {
  const token = localStorage.getItem('adminToken')

  isLoadingPrizeSummary.value = true

  try {
    const response = await getPrizeSummary(props.campaignId, token)

    prizeSummary.value = Array.isArray(response)
      ? response
      : response.data || response.premios || []
  } catch (error) {
    console.error(
      'Erro ao carregar resumo dos prémios:',
      error
    )

    showToast(error.message, 'error')
  } finally {
    isLoadingPrizeSummary.value = false
  }
}

const randomPrizeRows = ref([])

const showPrizeForm = ref(false)
const editingPrizeId = ref(null)

const prizeForm = ref({
  winningNumber: '',
  name: '',
  scheduledDay: '',
  status: 'Disponível'
})

const campaignStatusLabel = computed(() => {
  const labels = {
    ativa: t('campaignManagement.status.active'),
    pausada: t('campaignManagement.status.paused'),
    encerrada: t('campaignManagement.status.closed')
  }

  return labels[campaign.value.status] || campaign.value.status
})

// Aplica a resposta genuína do servidor (nunca um "patch" local com valores
// adivinhados) — usado sempre que carregamos ou recarregamos a campanha.
function applyCampaignResponse(campaignResponse) {
   console.log(
    'sms_resultado_ativo recebido:',
    campaignResponse.sms_resultado_ativo
  )
  campaign.value = {
    id: campaignResponse.id,
    name: campaignResponse.nome || '',
    status: campaignResponse.estado || '',
    startDate: campaignResponse.data_inicio
      ? campaignResponse.data_inicio.substring(0, 10)
      : '',
    endDate: campaignResponse.data_fim
      ? campaignResponse.data_fim.substring(0, 10)
      : '',
    totalNumbers: campaignResponse.total_quadrados || 1000,
    totalPrizes: campaignResponse.total_premios || 10,
    otpValidity: campaignResponse.otp_validade_minutos || 5,
    smsResultEnabled:campaignResponse.sms_resultado_ativo ?? true,
    resultSmsText: campaignResponse.texto_sms_resultado || '',
    maximumOtpAttempts: campaign.value.maximumOtpAttempts
  }

  snapshotCampaign()

  distributionMode.value = campaignResponse.modo_distribuicao || 'aleatorio'

  randomPrizeRows.value = []
  prizes.value = []

  if (
    campaignResponse.modo_distribuicao === 'aleatorio' &&
    Array.isArray(campaignResponse.distribuicao_aleatoria)
  ) {
    randomPrizeRows.value = campaignResponse.distribuicao_aleatoria.map(item => ({
      name: item.nome || '',
      quantity: item.quantidade || 1,
      randomnessLogic: item.logica_aleatoriedade || '',
      scheduledDay: item.data_programada
        ? item.data_programada.substring(0, 10)
        : ''
    }))
  }

  if (
    campaignResponse.modo_distribuicao === 'manual' &&
    Array.isArray(campaignResponse.premios)
  ) {
    prizes.value = campaignResponse.premios.map((prize, index) => ({
      id: prize.id || `manual-${index}`,
      winningNumber: prize.numero,
      name: prize.nome || '',
      scheduledDay: prize.data_programada
        ? prize.data_programada.substring(0, 10)
        : '',
      status: prize.entregue ? 'Entregue' : 'Disponível'
    }))
  }
}


// ===============================
// CAMPANHA
// ===============================

async function saveCampaign() {
  if (isSavingCampaign.value) return

  const token = localStorage.getItem('adminToken')

  if (!campaign.value.id) {
  showToast(
  t('campaignManagement.messages.campaignNotIdentified'),
  'warning'
)
    return
  }

  isSavingCampaign.value = true

  try {
    await updateCampaign(
      campaign.value.id,
      {
  nome: campaign.value.name,
  data_inicio: campaign.value.startDate,
  data_fim: campaign.value.endDate,
  total_quadrados: campaign.value.totalNumbers,
  total_premios: campaign.value.totalPrizes,
  otp_validade_minutos: campaign.value.otpValidity,

  sms_resultado_ativo:
    campaign.value.smsResultEnabled,

  texto_sms_resultado:
    campaign.value.resultSmsText.trim() || null
},
      token
    )

    snapshotCampaign()

   showToast(
  t('campaignManagement.messages.campaignUpdated'),
  'success'
)
  } catch (error) {
    showToast(error.message, 'error')
  } finally {
    isSavingCampaign.value = false
  }
}

function cancelChanges() {
  if (originalCampaign.value) {
    campaign.value = { ...originalCampaign.value }
  }

showToast(
  t('campaignManagement.messages.changesCancelled'),
  'info'
)
}

function addRandomPrizeRow() {
  randomPrizeRows.value.push({
    name: '',
    quantity: 1,
    randomnessLogic: '',
    scheduledDay: campaign.value.startDate || ''
  })
}

function removeRandomPrizeRow(index) {
  randomPrizeRows.value.splice(index, 1)
}

// ===============================
// BANCO DE PRÉMIOS
// ===============================

const prizeBank = ref([])
const newBankItemName = ref('')
const newBankItemDescription = ref('')
const newBankItemQuantity = ref(1)
const editingBankItemId = ref(null)
const isSavingBankItem = ref(false)

async function loadPrizeBank() {
  const token = localStorage.getItem('adminToken')

  try {
    const response = await getPrizeBank(token)
    prizeBank.value = Array.isArray(response) ? response : response.data || []
  } catch (error) {
    showToast(error.message, 'error')
  }
}

async function saveBankItem() {
  const name = newBankItemName.value.trim()

  if (!name) {
 showToast(
  t('campaignManagement.messages.enterPrizeName'),
  'warning'
)
    return
  }

  const token = localStorage.getItem('adminToken')
  isSavingBankItem.value = true

  try {
  const data = {
    nome: name,
    descricao: newBankItemDescription.value.trim() || null,
    quantidade_padrao: newBankItemQuantity.value || 1
  }

  if (editingBankItemId.value !== null) {
    await updatePrizeBankItem(
      editingBankItemId.value,
      data,
      token
    )
  } else {
    await createPrizeBankItem(
      data,
      token
    )
  }

  newBankItemName.value = ''
  newBankItemDescription.value = ''
  newBankItemQuantity.value = 1
  editingBankItemId.value = null

  await loadPrizeBank()

  showToast(
    t('campaignManagement.messages.prizeAddedToBank'),
    'success'
  )
} catch (error) {
    showToast(error.message, 'error')
  } finally {
    isSavingBankItem.value = false
  }
}

async function removeBankItem(item) {
  const token = localStorage.getItem('adminToken')

  try {
    await deletePrizeBankItem(item.id, token)
    await loadPrizeBank()
  } catch (error) {
    showToast(error.message, 'error')
  }
}

function useBankItemInRandom(item) {
  randomPrizeRows.value.push({
    name: item.nome,
    quantity: item.quantidade_padrao || 1,
    randomnessLogic: '',
    scheduledDay: campaign.value.startDate || ''
  })
}

function useBankItemInManual(item) {
  prizeForm.value.name = item.nome
}

function editBankItem(item) {
  editingBankItemId.value = item.id
  newBankItemName.value = item.nome || ''
  newBankItemDescription.value = item.descricao || ''
  newBankItemQuantity.value = item.quantidade_padrao || 1
}












// ===============================
// FORMULÁRIO DE PRÉMIO
// ===============================

function openPrizeForm() {
  editingPrizeId.value = null

  prizeForm.value = {
    winningNumber: '',
    name: '',
    scheduledDay: campaign.value.startDate || '',
    status: 'Disponível'
  }

  showPrizeForm.value = true
}

function editPrize(prize) {
  editingPrizeId.value = prize.id

  prizeForm.value = {
    winningNumber: prize.winningNumber,
    name: prize.name || '',
    scheduledDay: prize.scheduledDay || '',
    status: prize.status || 'Disponível'
  }

  showPrizeForm.value = true
}

function closePrizeForm() {
  showPrizeForm.value = false
  editingPrizeId.value = null
}


// ===============================
// ADICIONAR / EDITAR PRÉMIO
// ===============================

function savePrize() {
  if (
    !prizeForm.value.winningNumber ||
    !prizeForm.value.name.trim()
  ) {
    showToast(
     t('campaignManagement.messages.requiredFields'),
      'warning'
    )
    return
  }

  const number = Number(
    prizeForm.value.winningNumber
  )

  if (
    number < 1 ||
    number > campaign.value.totalNumbers
  ) {
    showToast(
     t('campaignManagement.messages.numberRange', {
  max: campaign.value.totalNumbers
}),
      'warning'
    )
    return
  }

  const repeatedNumber = prizes.value.some(
    prize =>
      Number(prize.winningNumber) === number &&
      prize.id !== editingPrizeId.value
  )

  if (repeatedNumber) {
    showToast(
    t('campaignManagement.messages.duplicateNumber'),
      'warning'
    )
    return
  }

  const trimmedName = prizeForm.value.name.trim()

  const prizeData = {
    id:
      editingPrizeId.value !== null
        ? editingPrizeId.value
        : Date.now(),

    winningNumber: number,

    name: trimmedName,

    scheduledDay:
      prizeForm.value.scheduledDay ||
      campaign.value.startDate ||
      '',

    status: prizeForm.value.status
  }

  if (editingPrizeId.value !== null) {
    const index = prizes.value.findIndex(
      prize => prize.id === editingPrizeId.value
    )

    if (index !== -1) {
      prizes.value[index] = prizeData
    }
  } else {
    prizes.value.push(prizeData)
  }

  closePrizeForm()
}

async function saveRandomDistribution() {
  if (isSavingRandomDistribution.value) return

  if (randomPrizeRows.value.length === 0) {
    showToast(
   t('campaignManagement.messages.addAtLeastOnePrize'),
      'warning'
    )
    return
  }

  const invalidRow = randomPrizeRows.value.some(
    item =>
      !item.name.trim() ||
      !item.quantity ||
      Number(item.quantity) < 1 ||
      !item.randomnessLogic
  )

  if (invalidRow) {
    showToast(
     t('campaignManagement.messages.invalidRandomDistribution'),
      'warning'
    )
    return
  }

  const token = localStorage.getItem('adminToken')

  isSavingRandomDistribution.value = true

  try {
    const premios = randomPrizeRows.value.map(item => ({
      nome: item.name.trim(),
      quantidade: Number(item.quantity),
      logica_aleatoriedade: item.randomnessLogic,
      data_programada: item.scheduledDay
        ? `${item.scheduledDay} 00:00:00`
        : null
    }))

    const campaignResponse = await configureRandomDistribution(
      campaign.value.id,
      premios,
      token
    )

    applyCampaignResponse(campaignResponse)
    await loadPrizeSummary()
    await loadPrizeBank()

    showToast(
   t('campaignManagement.messages.randomDistributionSaved'),
      'success'
    )

  } catch (error) {
    console.error(
      'Erro ao guardar distribuição aleatória:',
      error
    )

    showToast(error.message, 'error')
  } finally {
    isSavingRandomDistribution.value = false
  }
}

async function saveManualDistribution() {
  if (isSavingManualDistribution.value) return

  if (prizes.value.length === 0) {
    showToast(
    t('campaignManagement.messages.addAtLeastOnePrize'),
      'warning'
    )
    return
  }

  
 const invalidPrizeIndex = prizes.value.findIndex(
  prize =>
    !prize.winningNumber ||
    !prize.name?.trim()
)
if (invalidPrizeIndex !== -1) {
  const prize = prizes.value[invalidPrizeIndex]

  showToast(
   t('campaignManagement.messages.invalidManualRow', {
  line: invalidPrizeIndex + 1,
  number: prize.winningNumber,
  name: prize.name
}),
    'warning'
  )

  return
}

  const token = localStorage.getItem('adminToken')

  isSavingManualDistribution.value = true

  try {
    const premios = prizes.value.map(prize => ({
      numero: Number(prize.winningNumber),
      nome: prize.name,
      data_programada: prize.scheduledDay
        ? `${prize.scheduledDay} 00:00:00`
        : null
    }))

    const campaignResponse = await configureManualDistribution(
      campaign.value.id,
      premios,
      token
    )

    applyCampaignResponse(campaignResponse)
    await loadPrizeSummary()
    await loadPrizeBank()

    showToast(
     t('campaignManagement.messages.manualDistributionSaved'),
      'success'
    )

  } catch (error) {
    console.error(
      'Erro ao guardar distribuição manual:',
      error
    )

    showToast(error.message, 'error')
  } finally {
    isSavingManualDistribution.value = false
  }
}

// ===============================
// REMOVER PRÉMIO
// ===============================

function removePrize(numero) {
  requestConfirm(
  t('campaignManagement.messages.removePrizeConfirm'),
    () => executeRemovePrize(numero)
  )
}

function executeRemovePrize(numero) {
  prizes.value = prizes.value.filter(
    prize =>
      Number(prize.winningNumber) !== Number(numero)
  )

  showToast(
   t('campaignManagement.messages.prizeRemoved'),
    'success'
  )
}


// ===============================
// ACTIVAR CAMPANHA
// ===============================

function activateCampaign() {
  requestConfirm(
    'Activar esta campanha vai pausar automaticamente qualquer outra campanha que esteja activa neste momento — só uma pode estar activa de cada vez. Pretende continuar?',
    executeActivateCampaign
  )
}

async function executeActivateCampaign() {
  if (isActivatingCampaign.value) return

  const token = localStorage.getItem('adminToken')

  isActivatingCampaign.value = true

  try {
    await activateCampaignApi(
      campaign.value.id,
      token
    )

    applyCampaignResponse(await getCampaign(campaign.value.id, token))

    showToast('A campanha foi activada.', 'success')
  } catch (error) {
    showToast(error.message, 'error')
  } finally {
    isActivatingCampaign.value = false
  }
}


// ===============================
// PAUSAR CAMPANHA
// ===============================

async function pauseCampaign() {
  if (isPausingCampaign.value) return

  const token = localStorage.getItem('adminToken')

  isPausingCampaign.value = true

  try {
    await pauseCampaignApi(
      campaign.value.id,
      token
    )

    applyCampaignResponse(await getCampaign(campaign.value.id, token))

    showToast('A campanha foi pausada.', 'info')
  } catch (error) {
    showToast(error.message, 'error')
  } finally {
    isPausingCampaign.value = false
  }
}


// ===============================
// ENCERRAR CAMPANHA
// ===============================

function closeCampaign() {
  requestConfirm(
    'Tem a certeza de que pretende encerrar a campanha?',
    executeCloseCampaign
  )
}

async function executeCloseCampaign() {
  const token = localStorage.getItem('adminToken')

  try {
    await closeCampaignApi(
      campaign.value.id,
      token
    )

    applyCampaignResponse(await getCampaign(campaign.value.id, token))

    showToast(
      'A campanha foi encerrada.',
      'info'
    )

  } catch (error) {
    showToast(error.message, 'error')
  }
}


// ===============================
// RESET DA CAMPANHA
// ===============================
function resetCampaign() {
  requestConfirm(
    'Esta acção irá encerrar o ciclo actual, criar um novo ciclo e manter o histórico. Pretende continuar?',
    executeResetCampaign
  )
}

async function executeResetCampaign() {
  const token = localStorage.getItem('adminToken')

  try {
    const resetResponse = await resetCampaignApi(token)

    showToast('A campanha foi reiniciada com sucesso.', 'success')

    const campaignResponse = await getCampaign(resetResponse.id, token)

    emit('campaign-reset', campaignResponse.id)

    applyCampaignResponse(campaignResponse)
    await loadPrizeSummary()
    await loadPrizeBank()

  } catch (error) {
   showToast(error.message, 'error')
  }
}





// ===============================
// AO ABRIR A TELA
// ===============================

onMounted(async () => {
  const token = localStorage.getItem('adminToken')

  if (!token) {
   showToast(
  'Sessão administrativa não encontrada.',
  'warning'
)
    return
  }

  if (!props.campaignId) {
    emit('switch-campaign')
    return
  }

  isLoadingCampaign.value = true

  try {
    // CARREGAR A CAMPANHA SELECCIONADA
    const campaignResponse = await getCampaign(props.campaignId, token)

    applyCampaignResponse(campaignResponse)
    await loadPrizeSummary()
    await loadPrizeBank()

  } catch (error) {
    if (error.status === 401) {
      showToast('Sessão expirada, inicie sessão novamente.', 'error')
      localStorage.removeItem('adminToken')
      emit('logout')
      return
    }

    if (error.status === 404) {
      showToast('Esta campanha já não existe. Escolha outra.', 'error')
      emit('switch-campaign')
      return
    }

    showToast(error.message, 'error')
  } finally {
    isLoadingCampaign.value = false
  }
})
</script>

<template>
  <div class="management-page">
    <header class="top-header">
      <div class="header-accent"></div>

      <div class="header-content">
        <div class="title-area">
          <div>
           <h1>{{ t('campaignManagement.title') }}</h1>

           <p>
  {{ t('campaignManagement.description') }}
</p>
          </div>
        </div>

        <div class="status-area">
        <span>{{ t('campaignManagement.currentStatus') }}</span>

          <strong
            class="campaign-status"
            :class="{
              active: campaign.status === 'ativa',
              paused: campaign.status === 'pausada',
              closed: campaign.status === 'encerrada'
            }"
          >
            {{ campaignStatusLabel }}
          </strong>
        </div>
      </div>
    </header>

    <main class="management-content">
      <!-- Informações da campanha -->
      <section class="management-card">
        <div class="section-heading">
          <div>
           <h2>{{ t('campaignManagement.information.title') }}</h2>

<p>
  {{ t('campaignManagement.information.description') }}
</p>
          </div>
        </div>

        <div class="form-grid">
          <div class="field-group field-wide">
            <label for="campaign-name">
  {{ t('campaignManagement.information.name') }}
</label>

            <input
              id="campaign-name"
              v-model="campaign.name"
              type="text"
            />
          </div>

          <div class="field-group">
           <label>
  {{ t('campaignManagement.information.status') }}
</label>

            <div class="status-readonly">
              <strong
                class="campaign-status"
                :class="{
                  active: campaign.status === 'ativa',
                  paused: campaign.status === 'pausada',
                  closed: campaign.status === 'encerrada'
                }"
              >
                {{ campaignStatusLabel }}
              </strong>

             <small>
  {{ t('campaignManagement.information.statusHint') }}
</small>
            </div>
          </div>

          <div class="field-group">
            <label for="start-date">
  {{ t('campaignManagement.information.startDate') }}
</label>

            <input
              id="start-date"
              v-model="campaign.startDate"
              type="date"
            />
          </div>

          <div class="field-group">
            <label for="end-date">
  {{ t('campaignManagement.information.endDate') }}
</label>

            <input
              id="end-date"
              v-model="campaign.endDate"
              type="date"
            />
          </div>

          <div class="field-group">
            <label for="total-numbers">
  {{ t('campaignManagement.information.totalNumbers') }}
</label>

            <input
              id="total-numbers"
              v-model.number="campaign.totalNumbers"
              type="number"
              min="1"
            />
          </div>

          <div class="field-group">
          <label for="total-prizes">
  {{ t('campaignManagement.information.totalPrizes') }}
</label>

            <input
              id="total-prizes"
              v-model.number="campaign.totalPrizes"
              type="number"
              min="1"
            />
          </div>

          <div class="field-group">
          <label for="otp-validity">
  {{ t('campaignManagement.information.otpValidity') }}
</label>

            <input
              id="otp-validity"
              v-model.number="campaign.otpValidity"
              type="number"
              min="1"
            />
          </div>

          <div class="field-group">
          <label for="otp-attempts">
  {{ t('campaignManagement.information.maximumOtpAttempts') }}
</label>

            <input
              id="otp-attempts"
              v-model.number="campaign.maximumOtpAttempts"
              type="number"
              min="1"
            />
          </div>
        </div>

<div class="field-group">
  <label for="sms-result-enabled">
    {{ t('campaignManagement.information.smsResultEnabled') }}
  </label>

  <select
    id="sms-result-enabled"
    v-model="campaign.smsResultEnabled"
  >
    <option :value="true">
      {{ t('common.enabled') }}
    </option>

    <option :value="false">
      {{ t('common.disabled') }}
    </option>
  </select>
</div>

<div class="field-group field-wide">
  <label for="result-sms-text">
    {{ t('campaignManagement.information.resultSmsText') }}
  </label>

  <textarea
    id="result-sms-text"
    v-model="campaign.resultSmsText"
    rows="4"
    :placeholder="t('campaignManagement.information.resultSmsPlaceholder')"
  ></textarea>

  <small>
    {{ t('campaignManagement.information.resultSmsHint') }}
  </small>
</div>




        <div class="form-actions">
          <button
            type="button"
            class="outline-button"
            :disabled="isSavingCampaign || isLoadingCampaign"
            @click="cancelChanges"
          >
           {{ t('common.cancel') }}
          </button>

          <button
            type="button"
            class="primary-button"
            :disabled="isSavingCampaign || isLoadingCampaign"
            @click="saveCampaign"
          >
            <LoadingSpinner v-if="isSavingCampaign" />

{{
  isSavingCampaign
    ? t('campaignManagement.information.saving')
    : t('campaignManagement.information.saveChanges')
}}
          </button>
        </div>
      </section>

      <!-- Configuração dos prémios -->
      <section class="management-card">
       <div class="section-heading">
  <div>
    <h2>{{ t('campaignManagement.prizes.title') }}</h2>

    <p>
      {{ t('campaignManagement.prizes.description') }}
    </p>
  </div>
</div>

<div class="distribution-settings">
  <div class="field-group">
   <label for="distribution-mode">
  {{ t('campaignManagement.prizes.distributionMode') }}
</label>

    <select
      id="distribution-mode"
      v-model="distributionMode"
    >
      <option value="aleatorio">
        {{ t('campaignManagement.prizes.random') }}
      </option>

      <option value="manual">
        {{ t('campaignManagement.prizes.manual') }}
      </option>
    </select>
  </div>

  <div class="distribution-info">
    
    <template v-if="distributionMode === 'aleatorio'">
  {{ t('campaignManagement.prizes.randomInfo') }}
</template>

<template v-else>
  {{ t('campaignManagement.prizes.manualInfo') }}
</template>
  </div>
</div>


        <!-- BANCO DE PRÉMIOS -->
        <div class="prize-bank">
          <div class="prize-bank-header">
            <h3>{{ t('campaignManagement.prizes.bankTitle') }}</h3>
            <span>{{ t('campaignManagement.prizes.bankDescription') }}</span>
          </div>

          <div class="prize-bank-form">
            <input
              v-model="newBankItemName"
              type="text"
             :placeholder="t('campaignManagement.prizes.bankPlaceholder')"
              @keyup.enter="saveBankItem"
            />

            <input
             v-model="newBankItemDescription"
             type="text"
             :placeholder="t('campaignManagement.prizes.bankDescriptionPlaceholder')"
             />

            <input
              v-model.number="newBankItemQuantity"
              type="number"
              min="1"
              class="prize-bank-quantity"
            />

            <button
              type="button"
              class="outline-button"
              :disabled="isSavingBankItem"
              @click="saveBankItem"
            >
             <LoadingSpinner v-if="isSavingBankItem" />

{{
  isSavingBankItem
    ? t('campaignManagement.information.saving')
    : editingBankItemId !== null
      ? t('common.saveChanges')
      : t('campaignManagement.prizes.addToBank')
}}
            </button>
          </div>

          <div class="prize-bank-list">
            <span
              v-if="prizeBank.length === 0"
              class="empty-message"
            >
           {{ t('campaignManagement.prizes.bankEmpty') }}
            </span>

            <div
  v-for="item in prizeBank"
  :key="item.id"
  class="prize-bank-chip"
>
  <div class="chip-info">
    <span class="chip-name">
      {{ item.nome }}
    </span>

    <span
      v-if="item.descricao"
      class="chip-description"
    >
      {{ item.descricao }}
    </span>
  </div>

  <span class="chip-quantity">
    ×{{ item.quantidade_padrao }}
  </span>

             <button
  type="button"
  class="chip-use"
  :title="t('campaignManagement.prizes.useRandomTitle')"
  @click="useBankItemInRandom(item)"
>
  {{ t('campaignManagement.prizes.useRandom') }}
</button>

              <button
                type="button"
                class="chip-use"
                :title="t('campaignManagement.prizes.useManualTitle')"
                @click="useBankItemInManual(item)"
              >
                {{ t('campaignManagement.prizes.useManual') }}
              </button>

              <button
  type="button"
  class="chip-use"
  @click="editBankItem(item)"
>
  {{ t('common.edit') }}
</button>
            </div>
          </div>
        </div>

        <!-- MODO ALEATÓRIO -->
<div
  v-if="distributionMode === 'aleatorio'"
 class="table-wrapper"
>
  <table>
    <thead>
      <tr>
        <th>{{ t('campaignManagement.prizes.randomTable.prizeName') }}</th>
<th>{{ t('campaignManagement.prizes.randomTable.quantity') }}</th>
<th>{{ t('campaignManagement.prizes.randomTable.randomnessLogic') }}</th>
<th>{{ t('campaignManagement.prizes.randomTable.availabilityDate') }}</th>
<th>{{ t('common.actions') }}</th>
      </tr>
    </thead>

    <tbody>
      <tr
        v-for="(item, index) in randomPrizeRows"
        :key="index"
      >
        <td>
          <input
            v-model="item.name"
            type="text"
            :placeholder="t('campaignManagement.prizes.randomTable.prizePlaceholder')"
          />
        </td>

        <td>
          <input
            v-model.number="item.quantity"
            type="number"
            min="1"
          />
        </td>

        <td>
          <select v-model="item.randomnessLogic">
            <option value="" disabled>
  {{ t('campaignManagement.prizes.randomTable.selectLogic') }}
</option>

            <option value="uniforme">
  {{ t('campaignManagement.prizes.randomTable.uniform') }}
</option>

            <option value="aritmetica">
  {{ t('campaignManagement.prizes.randomTable.arithmetic') }}
</option>

           <option value="geometrica">
  {{ t('campaignManagement.prizes.randomTable.geometric') }}
</option>

          </select>
        </td>

        <td>
          <input
            v-model="item.scheduledDay"
            type="date"
          />
        </td>

        <td>
          <div class="row-actions">
          <button
  type="button"
  class="remove-button"
  @click="removeRandomPrizeRow(index)"
>
  {{ t('campaignManagement.prizes.randomTable.remove') }}
</button>
          </div>
        </td>
      </tr>

      <tr v-if="randomPrizeRows.length === 0">
        <td
          colspan="5"
          class="empty-message"
        >
    {{ t('campaignManagement.prizes.randomTable.empty') }}
        </td>
      </tr>
    </tbody>
  </table>

  <div class="prize-table-actions">
    <button
      type="button"
      class="outline-button"
      @click="addRandomPrizeRow"
    >
     {{ t('campaignManagement.prizes.randomTable.addPrize') }}
    </button>

    <button
      type="button"
      class="primary-button"
      :disabled="isSavingRandomDistribution"
      @click="saveRandomDistribution"
    >
     <LoadingSpinner v-if="isSavingRandomDistribution" />

{{
  isSavingRandomDistribution
    ? t('campaignManagement.information.saving')
    : t('campaignManagement.prizes.randomTable.saveDistribution')
}}
    </button>
  </div>
</div>


<!-- MODO MANUAL -->
<div
  v-else
 class="table-wrapper"
>
  <table>
   <thead>
  <tr>
    <th>{{ t('campaignManagement.prizes.manualTable.winningNumber') }}</th>
<th>{{ t('campaignManagement.prizes.manualTable.prizeName') }}</th>
<th>{{ t('campaignManagement.prizes.manualTable.scheduledDate') }}</th>
<th>{{ t('campaignManagement.prizes.manualTable.status') }}</th>
<th>{{ t('common.actions') }}</th>
  </tr>
</thead>

    <tbody>
      <tr
        v-for="prize in prizes"
        :key="prize.id"
      >
        <td class="winning-number">
          {{ prize.winningNumber }}
        </td>


        <td class="prize-name">
          {{ prize.name }}
        </td>

        <td>{{ prize.scheduledDay }}</td>

        <td>
          <span
            class="prize-status"
            :class="{
              available: prize.status === 'Disponível',
              assigned: prize.status === 'Atribuído',
              delivered: prize.status === 'Entregue'
            }"
          >
         {{ translatePrizeStatus(prize.status) }}
          </span>
        </td>

        <td>
          <div class="row-actions">
            <button
              type="button"
              class="edit-button"
              @click="editPrize(prize)"
            >
             {{ t('campaignManagement.prizes.manualTable.edit') }}
            </button>

            <button
              type="button"
              class="remove-button"
              @click="removePrize(prize.winningNumber)"
            >
              {{ t('campaignManagement.prizes.manualTable.remove') }}
            </button>
          </div>
        </td>
      </tr>

      <tr v-if="prizes.length === 0">
        <td
          colspan="5"
          class="empty-message"
        >
          {{ t('campaignManagement.prizes.manualTable.empty') }}
        </td>
      </tr>
    </tbody>
  </table>

  <div class="prize-table-actions">
  <button
    type="button"
    class="outline-button"
    @click="openPrizeForm"
  >
    {{ t('campaignManagement.prizes.manualTable.addPrize') }}
  </button>

  <button
    type="button"
    class="primary-button"
    :disabled="isSavingManualDistribution"
    @click="saveManualDistribution"
  >
  <LoadingSpinner v-if="isSavingManualDistribution" />

{{
  isSavingManualDistribution
    ? t('campaignManagement.information.saving')
    : t('campaignManagement.prizes.manualTable.saveDistribution')
}}
  </button>
</div>
</div>
      </section>

     <!-- Painel de controlo dos prémios -->
<section class="management-card prize-summary-card">
  <div class="section-heading">
    <div>
      <h2>
  {{ t('campaignManagement.prizeSummary.title') }}
</h2>

<p>
  {{ t('campaignManagement.prizeSummary.description') }}
</p>
    </div>

   <input
  v-model="prizeSummarySearch"
  class="prize-summary-search"
  type="search"
 :placeholder="t('campaignManagement.prizeSummary.search')"
  autocomplete="off"
/>
  </div>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>{{ t('campaignManagement.prizeSummary.prizeName') }}</th>
<th>{{ t('campaignManagement.prizeSummary.totalQuantity') }}</th>
<th>{{ t('campaignManagement.prizeSummary.assignedQuantity') }}</th>
<th>{{ t('campaignManagement.prizeSummary.remainingQuantity') }}</th>
<th>ID</th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="prize in filteredPrizeSummary"
          :key="prize.id"
        >
          <td class="prize-name">
            {{ prize.nome || '-' }}
          </td>

          <td>{{ prize.quantidade_total ?? 0 }}</td>
          <td>{{ prize.quantidade_atribuida ?? 0 }}</td>
          <td>{{ prize.quantidade_remanescente ?? 0 }}</td>
          <td>{{ prize.id ?? '-' }}</td>
        </tr>

        <tr
  v-if="prizeSummary.length > 0"
  class="totals-row"
>
  <td>
    <strong>
      {{ t('campaignManagement.prizeSummary.total') }}
    </strong>
  </td>

  <td>
    <strong>{{ totalPrizeQuantity }}</strong>
  </td>

  <td>
    <strong>{{ totalAssignedQuantity }}</strong>
  </td>

  <td>
    <strong>{{ totalRemainingQuantity }}</strong>
  </td>

  <td>-</td>
</tr>

        <tr v-if="isLoadingPrizeSummary && filteredPrizeSummary.length === 0">
          <td
            colspan="5"
            class="empty-message"
          >
         <LoadingSpinner color="purple" :size="16" />
{{ t('common.loading') }}
          </td>
        </tr>

        <tr v-else-if="filteredPrizeSummary.length === 0">
          <td
            colspan="5"
            class="empty-message"
          >
            {{ t('campaignManagement.prizeSummary.empty') }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</section> 

      <!-- Controlo da campanha -->
      <section class="management-card">
        <div class="section-heading">
          <div>
           <h2>{{ t('campaignManagement.control.title') }}</h2>

            <p>
              {{ t('campaignManagement.control.description') }}
            </p>
          </div>
        </div>

        <div class="control-grid">
          <button
            type="button"
            class="control-button activate-button"
            :disabled="isActivatingCampaign"
            @click="activateCampaign"
          >
            <span class="control-icon">
              <LoadingSpinner v-if="isActivatingCampaign" color="purple" />
              <template v-else>▶</template>
            </span>

            <span>
              <strong>{{ t('campaignManagement.control.activate') }}</strong>
            <small>
  {{ t('campaignManagement.control.activateHint') }}
</small>
            </span>
          </button>

          <button
            type="button"
            class="control-button pause-button"
            :disabled="isPausingCampaign"
            @click="pauseCampaign"
          >
            <span class="control-icon">
              <LoadingSpinner v-if="isPausingCampaign" color="purple" />
              <template v-else>Ⅱ</template>
            </span>

            <span>
             <strong>
  {{ t('campaignManagement.control.pause') }}
</strong>

<small>
  {{ t('campaignManagement.control.pauseHint') }}
</small>
            </span>
          </button>

          <button
            type="button"
            class="control-button close-button"
            @click="closeCampaign"
          >
            <span class="control-icon">■</span>

            <span>
              <strong>{{ t('campaignManagement.control.close') }}</strong>
             <small>
  {{ t('campaignManagement.control.preventNewEntries') }}
</small>
            </span>
          </button>

          <button
            type="button"
            class="control-button reset-button"
            @click="resetCampaign"
          >
            <span class="control-icon">↻</span>

            <span>
              <strong>{{ t('campaignManagement.control.restart') }}</strong>
             <small>
  {{ t('campaignManagement.control.restartHint') }}
</small>
            </span>
          </button>
        </div>
      </section>

    </main>

    <!-- Modal de prémio -->
    <div
      v-if="showPrizeForm"
      class="modal-overlay"
    >
      <section class="prize-modal">
        <div class="modal-stripe">
          <div class="modal-accent"></div>
        </div>

        <div class="modal-content">
          <h2>
  {{
    editingPrizeId !== null
      ? t('campaignManagement.prizeModal.editTitle')
      : t('campaignManagement.prizeModal.addTitle')
  }}
</h2>
          <div class="field-group">
           <label for="winning-number">
  {{ t('campaignManagement.prizeModal.winningNumber') }}
</label>

            <input
              id="winning-number"
              v-model.number="prizeForm.winningNumber"
              type="number"
              min="1"
              :max="campaign.totalNumbers"
            />
          </div>

          <div class="field-group">
           <label for="prize-name">
  {{ t('campaignManagement.prizeModal.prizeName') }}
</label>

            <input
              id="prize-name"
              v-model="prizeForm.name"
              type="text"
              :placeholder="t('campaignManagement.prizeModal.prizePlaceholder')"
            />
          </div>

          <div class="field-group">
            <label for="scheduled-day">
  {{ t('campaignManagement.prizeModal.scheduledDate') }}
</label>

            <input
              id="scheduled-day"
              v-model="prizeForm.scheduledDay"
              type="date"
            />
          </div>

          <div class="field-group">
            <label for="prize-status">
  {{ t('campaignManagement.prizeModal.status') }}
</label>

            <select
              id="prize-status"
              v-model="prizeForm.status"
            >
              <option value="Disponível">
  {{ t('campaignManagement.prizeModal.available') }}
</option>

<option value="Atribuído">
  {{ t('campaignManagement.prizeModal.assigned') }}
</option>

<option value="Entregue">
  {{ t('campaignManagement.prizeModal.delivered') }}
</option>
            </select>
          </div>

          <div class="modal-actions">
            <button
              type="button"
              class="outline-button"
              @click="closePrizeForm"
            >
              Cancelar
            </button>

            <button
              type="button"
              class="primary-button"
              @click="savePrize"
            >
              Guardar
            </button>
          </div>
        </div>
      </section>
    </div>

   

    <footer class="bottom-stripe">
      <div class="bottom-accent"></div>
    </footer>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.management-page {
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f7f7fb;
  color: #111827;
  font-family: Arial, Helvetica, sans-serif;
}

.top-header {
  position: relative;
  overflow: hidden;
  flex-shrink: 0;
  background: #27227f;
}

.header-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 230px;
  height: 100%;
  background: #0088cc;
  clip-path: polygon(34% 0, 100% 0, 100% 100%, 0 100%);
}

.header-content {
  position: relative;
  z-index: 1;
  width: 100%;
  min-height: 130px;
  padding: 26px 42px 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 30px;
}

.title-area {
  display: flex;
  align-items: center;
  gap: 17px;
}

.title-area h1 {
  margin: 0;
  color: #ffffff;
  font-size: 30px;
}

.title-area p {
  margin: 7px 0 0;
  color: rgba(255, 255, 255, 0.62);
  font-size: 14px;
}

.status-area {
  position: relative;
  z-index: 2;
  text-align: right;
}

.status-area > span {
  display: block;
  margin-bottom: 7px;
  color: rgba(255, 255, 255, 0.65);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.8px;
  text-transform: uppercase;
}

.campaign-status {
  display: inline-block;
  padding: 8px 14px;
  border-radius: 20px;
  font-size: 13px;
}

.campaign-status.active {
  background: #dcfce7;
  color: #166534;
}

.campaign-status.paused {
  background: #fef3c7;
  color: #92400e;
}

.campaign-status.closed {
  background: #fee2e2;
  color: #991b1b;
}

.management-content {
  width: 100%;
  max-width: 1450px;
  flex: 1;
  margin: 0 auto;
  padding: 30px 38px 42px;
}

.management-card {
  margin-bottom: 24px;
  overflow: hidden;
  border: 1px solid #e3e3ef;
  border-radius: 9px;
  background: #ffffff;
  box-shadow: 0 2px 9px rgba(39, 34, 127, 0.05);
}

.section-heading {
  padding: 21px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  border-bottom: 1px solid #ececf4;
}

.section-heading h2 {
  margin: 0;
  color: #111827;
  font-size: 20px;
}

.section-heading p {
  margin: 5px 0 0;
  color: #9ca3af;
  font-size: 13px;
}

.form-grid {
  padding: 24px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 19px;
}

.field-wide {
  grid-column: span 2;
}

.field-group label {
  display: block;
  margin-bottom: 7px;
  color: #374151;
  font-size: 13px;
  font-weight: 700;
}

.field-group input,
.field-group select {
  width: 100%;
  min-height: 45px;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  background: #ffffff;
  color: #111827;
  outline: none;
  font-size: 14px;
}

.field-group input:focus,
.field-group select:focus {
  border-color: #27227f;
  box-shadow: 0 0 0 2px rgba(39, 34, 127, 0.07);
}

.status-readonly {
  min-height: 45px;
  padding: 6px 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 4px;
}

.status-readonly small {
  color: #9ca3af;
  font-size: 11px;
}

.form-actions {
  padding: 0 24px 24px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.prize-bank {
  margin: 0 24px 22px;
  padding: 18px 20px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fafaff;
}

.prize-bank-header {
  margin-bottom: 14px;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.prize-bank-header h3 {
  margin: 0;
  color: #27227f;
  font-size: 15px;
}

.prize-bank-header span {
  color: #9ca3af;
  font-size: 12px;
}

.prize-bank-form {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
}

.prize-bank-form input[type='text'] {
  flex: 1;
}

.prize-bank-quantity {
  width: 90px;
}

.prize-bank-form input {
  min-height: 42px;
  padding: 0 12px;
  border: 1px solid #e0e0ef;
  border-radius: 7px;
  font-size: 13px;
}

.prize-bank-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.prize-bank-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 8px 7px 14px;
  border: 1px solid #e0e0ef;
  border-radius: 999px;
  background: #ffffff;
  transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

.prize-bank-chip:hover {
  border-color: #c7d2fe;
  box-shadow: 0 2px 8px rgba(39, 34, 127, 0.08);
}

.chip-name {
  color: #27227f;
  font-size: 13px;
  font-weight: 700;
}

.chip-quantity {
  color: #9ca3af;
  font-size: 12px;
}

.chip-use {
  padding: 4px 10px;
  border: 1px solid #c7d2fe;
  border-radius: 999px;
  background: #eef2ff;
  color: #27227f;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.chip-use:hover {
  background: #dfe4ff;
}

.chip-remove {
  width: 20px;
  height: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 50%;
  background: #fee2e2;
  color: #b91c1c;
  font-size: 14px;
  line-height: 1;
  cursor: pointer;
}

.chip-remove:hover {
  background: #fecaca;
}

.primary-button,
.outline-button {
  min-height: 42px;
  padding: 0 17px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.primary-button:disabled,
.outline-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.primary-button {
  border: 1px solid #27227f;
  background: #27227f;
  color: #ffffff;
}

.primary-button:hover:not(:disabled) {
  background: #1c1860;
}

.outline-button {
  border: 1px solid #d1d5db;
  background: #ffffff;
  color: #4b5563;
}

.outline-button:hover:not(:disabled) {
  background: #f9fafb;
}

.table-wrapper {
  width: 100%;
  overflow-x: auto;
}

table {
  width: 100%;
  min-width: 900px;
  border-collapse: collapse;
}

th {
  padding: 14px 17px;
  background: #f8f8fc;
  color: #6b7280;
  text-align: left;
  font-size: 11px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

td {
  padding: 15px 17px;
  border-top: 1px solid #f0f0f5;
  color: #4b5563;
  font-size: 13px;
}

tbody tr:hover {
  background: #fafafe;
}

.winning-number,
.prize-name {
  color: #27227f;
  font-weight: 700;
}

.prize-status {
  display: inline-block;
  padding: 5px 9px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
}

.prize-status.available {
  background: #eff6ff;
  color: #1d4ed8;
}

.prize-status.assigned {
  background: #fff7ed;
  color: #c2410c;
}

.prize-status.delivered {
  background: #ecfdf5;
  color: #047857;
}

.row-actions {
  display: flex;
  gap: 7px;
}

.edit-button,
.remove-button {
  min-height: 33px;
  padding: 0 11px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.edit-button {
  border: 1px solid #27227f;
  background: #ffffff;
  color: #27227f;
}

.remove-button {
  border: 1px solid #fecaca;
  background: #ffffff;
  color: #dc2626;
}

.empty-message {
  padding: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  color: #9ca3af;
  text-align: center;
}

.control-grid {
  padding: 24px;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

.control-button {
  min-height: 100px;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  border-radius: 8px;
  background: #ffffff;
  text-align: left;
  cursor: pointer;
}

.control-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.control-icon {
  width: 42px;
  height: 42px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 18px;
  font-weight: 700;
}

.control-button strong {
  display: block;
  margin-bottom: 5px;
  font-size: 14px;
}

.control-button small {
  color: #9ca3af;
  font-size: 11px;
  line-height: 1.4;
}

.activate-button {
  border: 1px solid #bbf7d0;
  color: #166534;
}

.activate-button .control-icon {
  background: #dcfce7;
}

.pause-button {
  border: 1px solid #fde68a;
  color: #92400e;
}

.pause-button .control-icon {
  background: #fef3c7;
}

.close-button {
  border: 1px solid #fecaca;
  color: #991b1b;
}

.close-button .control-icon {
  background: #fee2e2;
}

.reset-button {
  border: 1px solid #d1d5db;
  color: #4b5563;
}

.reset-button .control-icon {
  background: #f3f4f6;
}


.distribution-settings {
  padding: 22px 24px;
  display: grid;
  grid-template-columns: 300px 1fr;
  align-items: end;
  gap: 24px;
  border-bottom: 1px solid #ececf4;
}

.distribution-info {
  min-height: 45px;
  padding: 11px 15px;
  display: flex;
  align-items: center;
  border: 1px solid #bae6fd;
  border-radius: 7px;
  background: #f0f9ff;
  color: #1e40af;
  font-size: 13px;
  line-height: 1.5;
}


.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  padding: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(17, 24, 39, 0.55);
}

.prize-modal {
  width: 100%;
  max-width: 450px;
  overflow: hidden;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 18px 50px rgba(17, 24, 39, 0.22);
}

.modal-stripe {
  position: relative;
  height: 25px;
  overflow: hidden;
  background: #27227f;
}

.modal-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 100px;
  height: 100%;
  background: #0088cc;
  clip-path: polygon(35% 0, 100% 0, 100% 100%, 0 100%);
}

.modal-content {
  padding: 30px;
}

.modal-content h2 {
  margin: 0 0 25px;
  color: #111827;
  text-align: center;
  font-size: 23px;
}

.modal-content .field-group {
  margin-bottom: 16px;
}

.modal-actions {
  margin-top: 25px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.bottom-stripe {
  position: relative;
  width: 100%;
  height: 11px;
  flex-shrink: 0;
  overflow: hidden;
  background: #27227f;
}

.bottom-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 180px;
  height: 100%;
  background: #0088cc;
  clip-path: polygon(22% 0, 100% 0, 100% 100%, 0 100%);
}

.table-wrapper select,
.table-wrapper input {
  width: 100%;
  min-height: 38px;
  padding: 7px 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: #ffffff;
  color: #111827;
  font-size: 13px;
}

.prize-table-actions {
  padding: 18px 24px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid #ececf4;
}


@media (max-width: 1050px) {
  .form-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .control-grid,
  .reports-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 900px) {
  .header-content {
    padding-left: 64px;
  }
}

@media (max-width: 650px) {
  .header-content {
    padding: 24px 20px 24px 64px;
    align-items: flex-start;
    flex-direction: column;
  }
.distribution-settings {
  grid-template-columns: 1fr;
}
  .status-area {
    text-align: left;
  }

  .management-content {
    padding: 20px 15px 30px;
  }

  .section-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .form-grid,
  .control-grid,
  .reports-grid {
    grid-template-columns: 1fr;
  }

  .field-wide {
    grid-column: span 1;
  }

  .title-area h1 {
    font-size: 25px;
  }
}

.prize-summary-search {
  width: 210px;
  height: 42px;
  padding: 0 13px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  outline: none;
  font-size: 13px;
  background: #ffffff;
}

.prize-summary-search:focus {
  border-color: #27227f;
}

.totals-row {
  background: #f8f8fc;
}

.totals-row td {
  border-top: 2px solid #dcdceb;
  color: #27227f;
  font-weight: 700;
}

.field-group textarea {
  width: 100%;
  min-height: 100px;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  background: #ffffff;
  color: #111827;
  outline: none;
  font-size: 14px;
  resize: vertical;
}

.field-group textarea:focus {
  border-color: #27227f;
  box-shadow: 0 0 0 2px rgba(39, 34, 127, 0.07);
}

.field-group small {
  display: block;
  margin-top: 6px;
  color: #9ca3af;
  font-size: 11px;
}

.chip-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.chip-description {
  max-width: 220px;
  overflow: hidden;
  color: #9ca3af;
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}


</style>