<script setup>
import { computed, onMounted, ref } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'

import {
  resetCampaign as resetCampaignApi,
  getCampaign,
  updateCampaign,
  activateCampaign as activateCampaignApi,
  pauseCampaign as pauseCampaignApi,
  closeCampaignApi,
  configureRandomDistribution,
  configureManualDistribution,
  getPrizeSummary
} from '../services/api'

const props = defineProps({
  campaignId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits([
  'back-dashboard',
  'switch-campaign',
  'campaign-reset',
  'toast',
   'confirm'
])

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
  maximumOtpAttempts: 5
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

const statusLabels = {
  ativa: 'Activa',
  pausada: 'Pausada',
  encerrada: 'Encerrada'
}

const campaignStatusLabel = computed(() =>
  statusLabels[campaign.value.status] || campaign.value.status
)

// Aplica a resposta genuína do servidor (nunca um "patch" local com valores
// adivinhados) — usado sempre que carregamos ou recarregamos a campanha.
function applyCampaignResponse(campaignResponse) {
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
  'Não foi possível identificar a campanha activa.',
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
        otp_validade_minutos: campaign.value.otpValidity
      },
      token
    )

    snapshotCampaign()

    showToast('Campanha actualizada com sucesso.', 'success')
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

  showToast('As alterações foram canceladas.', 'info')
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
      'Preencha os campos obrigatórios.',
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
      `O número deve estar entre 1 e ${campaign.value.totalNumbers}.`,
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
      'Este número já foi associado a outro prémio.',
      'warning'
    )
    return
  }

  // Se já existir um prémio com o mesmo nome noutra grafia ("Carro" vs "carro"),
  // usa a grafia já existente — evita que o mesmo prémio fique espalhado por
  // duas entradas diferentes no resumo por causa de maiúsculas/minúsculas.
  let trimmedName = prizeForm.value.name.trim()

  const existingSameName = prizes.value.find(
    prize =>
      prize.name.trim().toLowerCase() === trimmedName.toLowerCase() &&
      prize.id !== editingPrizeId.value
  )

  if (existingSameName && existingSameName.name !== trimmedName) {
    showToast(
      `Já existe "${existingSameName.name}" na lista — a usar essa grafia para contar como o mesmo prémio.`,
      'info'
    )

    trimmedName = existingSameName.name
  }

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
      'Adicione pelo menos um prémio.',
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
      'Preencha correctamente todos os prémios, quantidades e lógicas de aleatoriedade.',
      'warning'
    )
    return
  }

  const token = localStorage.getItem('adminToken')

  isSavingRandomDistribution.value = true

  try {
    // Normaliza nomes que só diferem em maiúsculas/minúsculas para a mesma
    // grafia (a primeira que aparecer na lista) — evita "Carro"/"carro"
    // ficarem como prémios diferentes no resumo.
    const nomesCanonicos = new Map()

    const premios = randomPrizeRows.value.map(item => {
      const nome = item.name.trim()
      const chave = nome.toLowerCase()

      if (!nomesCanonicos.has(chave)) {
        nomesCanonicos.set(chave, nome)
      }

      return {
        nome: nomesCanonicos.get(chave),
        quantidade: Number(item.quantity),
        logica_aleatoriedade: item.randomnessLogic,
        data_programada: item.scheduledDay
          ? `${item.scheduledDay} 00:00:00`
          : null
      }
    })

    const campaignResponse = await configureRandomDistribution(
      campaign.value.id,
      premios,
      token
    )

    applyCampaignResponse(campaignResponse)
    await loadPrizeSummary()

    showToast(
      'Distribuição aleatória guardada com sucesso.',
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
      'Adicione pelo menos um prémio.',
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
    `Linha ${invalidPrizeIndex + 1}: número="${prize.winningNumber}" | nome="${prize.name}"`,
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

    showToast(
      'Distribuição manual guardada com sucesso.',
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
    'Tem a certeza de que pretende remover este prémio?',
    () => executeRemovePrize(numero)
  )
}

function executeRemovePrize(numero) {
  prizes.value = prizes.value.filter(
    prize =>
      Number(prize.winningNumber) !== Number(numero)
  )

  showToast(
    'Prémio removido com sucesso.',
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

  } catch (error) {
   showToast(error.message, 'error')
  }
}


// ===============================
// RELATÓRIOS
// Ainda aguardam endpoints
// ===============================

function exportParticipants() {
  showToast('Funcionalidade em desenvolvimento.', 'info')
}

function exportWinners() {
  showToast('Funcionalidade em desenvolvimento.', 'info')
}

function exportAudit() {
  showToast('Funcionalidade em desenvolvimento.', 'info')
}

function downloadCampaignReport() {
  showToast('Funcionalidade em desenvolvimento.', 'info')
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

  } catch (error) {
    if (error.status === 401) {
      showToast('Sessão expirada, inicie sessão novamente.', 'error')
      localStorage.removeItem('adminToken')
      emit('back-dashboard')
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
          <button
            type="button"
            class="back-button"
            aria-label="Voltar ao Dashboard"
            title="Voltar ao Dashboard"
            @click="emit('back-dashboard')"
          >
            ←
          </button>

          <div>
            <h1>Gestão da Campanha</h1>

            <p>
              Configure e controle o funcionamento da campanha de sorteio
            </p>
          </div>
        </div>

        <div class="status-area">
          <span>Estado actual</span>

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
            <h2>Informações da Campanha</h2>

            <p>
              Defina as informações gerais e as regras de funcionamento.
            </p>
          </div>
        </div>

        <div class="form-grid">
          <div class="field-group field-wide">
            <label for="campaign-name">Nome da campanha</label>

            <input
              id="campaign-name"
              v-model="campaign.name"
              type="text"
            />
          </div>

          <div class="field-group">
            <label>Estado</label>

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

              <small>Altere em "Controlo da Campanha", abaixo</small>
            </div>
          </div>

          <div class="field-group">
            <label for="start-date">Data de início</label>

            <input
              id="start-date"
              v-model="campaign.startDate"
              type="date"
            />
          </div>

          <div class="field-group">
            <label for="end-date">Data de fim</label>

            <input
              id="end-date"
              v-model="campaign.endDate"
              type="date"
            />
          </div>

          <div class="field-group">
            <label for="total-numbers">Total de números</label>

            <input
              id="total-numbers"
              v-model.number="campaign.totalNumbers"
              type="number"
              min="1"
            />
          </div>

          <div class="field-group">
            <label for="total-prizes">Total de prémios</label>

            <input
              id="total-prizes"
              v-model.number="campaign.totalPrizes"
              type="number"
              min="1"
            />
          </div>

          <div class="field-group">
            <label for="otp-validity">Validade do OTP (minutos)</label>

            <input
              id="otp-validity"
              v-model.number="campaign.otpValidity"
              type="number"
              min="1"
            />
          </div>

          <div class="field-group">
            <label for="otp-attempts">
              Máximo de tentativas do OTP
            </label>

            <input
              id="otp-attempts"
              v-model.number="campaign.maximumOtpAttempts"
              type="number"
              min="1"
            />
          </div>
        </div>

        <div class="form-actions">
          <button
            type="button"
            class="outline-button"
            :disabled="isSavingCampaign || isLoadingCampaign"
            @click="cancelChanges"
          >
            Cancelar
          </button>

          <button
            type="button"
            class="primary-button"
            :disabled="isSavingCampaign || isLoadingCampaign"
            @click="saveCampaign"
          >
            <LoadingSpinner v-if="isSavingCampaign" />
            {{ isSavingCampaign ? 'A guardar...' : 'Guardar Alterações' }}
          </button>
        </div>
      </section>

      <!-- Configuração dos prémios -->
      <section class="management-card">
       <div class="section-heading">
  <div>
    <h2>Configuração dos Prémios</h2>

    <p>
      Defina como os prémios serão distribuídos durante a campanha.
    </p>
  </div>
</div>

<div class="distribution-settings">
  <div class="field-group">
    <label for="distribution-mode">
      Modo de distribuição
    </label>

    <select
      id="distribution-mode"
      v-model="distributionMode"
    >
      <option value="aleatorio">
        Aleatória
      </option>

      <option value="manual">
        Manual
      </option>
    </select>
  </div>

  <div class="distribution-info">
    <template v-if="distributionMode === 'aleatorio'">
  Os números premiados serão distribuídos automaticamente
  de acordo com as quantidades e lógicas definidas para cada prémio.
</template>

<template v-else>
  No modo manual, o administrador escolhe individualmente
  os números premiados e define o prémio correspondente.
</template>
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
        <th>Nome do Prémio</th>
        <th>Quantidade</th>
        <th>Lógica de Aleatoriedade</th>
        <th>Data de Disponibilidade</th>
        <th>Acções</th>
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
            placeholder="Ex.: Smartphone"
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
              Seleccione a lógica
            </option>

            <option value="aleatorio">
              Aleatório
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
              Remover
            </button>
          </div>
        </td>
      </tr>

      <tr v-if="randomPrizeRows.length === 0">
        <td
          colspan="5"
          class="empty-message"
        >
          Ainda não existem prémios configurados.
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
      + Adicionar Prémio
    </button>

    <button
      type="button"
      class="primary-button"
      :disabled="isSavingRandomDistribution"
      @click="saveRandomDistribution"
    >
      <LoadingSpinner v-if="isSavingRandomDistribution" />
      {{ isSavingRandomDistribution ? 'A guardar...' : 'Guardar Distribuição' }}
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
    <th>Número Premiado</th>
    <th>Nome do Prémio</th>
    <th>Data Programada</th>
    <th>Estado</th>
    <th>Acções</th>
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
            {{ prize.status }}
          </span>
        </td>

        <td>
          <div class="row-actions">
            <button
              type="button"
              class="edit-button"
              @click="editPrize(prize)"
            >
              Editar
            </button>

            <button
              type="button"
              class="remove-button"
              @click="removePrize(prize.winningNumber)"
            >
              Remover
            </button>
          </div>
        </td>
      </tr>

      <tr v-if="prizes.length === 0">
        <td
          colspan="5"
          class="empty-message"
        >
          Ainda não existem prémios configurados.
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
    + Adicionar Prémio
  </button>

  <button
    type="button"
    class="primary-button"
    :disabled="isSavingManualDistribution"
    @click="saveManualDistribution"
  >
    <LoadingSpinner v-if="isSavingManualDistribution" />
    {{ isSavingManualDistribution ? 'A guardar...' : 'Guardar Distribuição' }}
  </button>
</div>
</div>
      </section>

     <!-- Painel de controlo dos prémios -->
<section class="management-card prize-summary-card">
  <div class="section-heading">
    <div>
      <h2>Painel de Controlo dos Prémios</h2>

      <p>
        Consulte as quantidades totais, atribuídas e remanescentes de cada prémio.
      </p>
    </div>

   <input
  v-model="prizeSummarySearch"
  class="prize-summary-search"
  type="search"
  placeholder="Pesquisar prémio"
  autocomplete="off"
/>
  </div>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Nome do Prémio</th>
          <th>Quantidade Total</th>
          <th>Quantidade Atribuída</th>
          <th>Quantidade Remanescente</th>
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

        <tr v-if="isLoadingPrizeSummary && filteredPrizeSummary.length === 0">
          <td
            colspan="5"
            class="empty-message"
          >
            <LoadingSpinner color="purple" :size="16" /> A carregar...
          </td>
        </tr>

        <tr v-else-if="filteredPrizeSummary.length === 0">
          <td
            colspan="5"
            class="empty-message"
          >
            Nenhum prémio encontrado.
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
            <h2>Controlo da Campanha</h2>

            <p>
              Execute acções sobre o estado e funcionamento da campanha.
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
              <strong>Activar Campanha</strong>
              <small>Permitir novas participações</small>
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
              <strong>Pausar Campanha</strong>
              <small>Suspender temporariamente</small>
            </span>
          </button>

          <button
            type="button"
            class="control-button close-button"
            @click="closeCampaign"
          >
            <span class="control-icon">■</span>

            <span>
              <strong>Encerrar Campanha</strong>
              <small>Impedir novas participações</small>
            </span>
          </button>

          <button
            type="button"
            class="control-button reset-button"
            @click="resetCampaign"
          >
            <span class="control-icon">↻</span>

            <span>
              <strong>Reiniciar Campanha</strong>
              <small>Voltar ao estado inicial</small>
            </span>
          </button>
        </div>
      </section>

      <!-- Relatórios -->
      <section class="management-card">
        <div class="section-heading">
          <div>
            <h2>Relatórios</h2>

            <p>
              Exporte os dados registados durante a campanha.
            </p>
          </div>
        </div>

        <div class="reports-grid">
          <button
            type="button"
            class="report-button"
            @click="exportParticipants"
          >
            <span class="report-icon">⇩</span>
            Exportar Participantes
          </button>

          <button
            type="button"
            class="report-button"
            @click="exportWinners"
          >
            <span class="report-icon">★</span>
            Exportar Vencedores
          </button>

          <button
            type="button"
            class="report-button"
            @click="exportAudit"
          >
            <span class="report-icon">☰</span>
            Exportar Auditoria
          </button>

          <button
            type="button"
            class="report-button"
            @click="downloadCampaignReport"
          >
            <span class="report-icon">▣</span>
            Relatório da Campanha
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
            {{ editingPrizeId !== null ? 'Editar Prémio' : 'Adicionar Prémio' }}
          </h2>

          <div class="field-group">
            <label for="winning-number">Número premiado</label>

            <input
              id="winning-number"
              v-model.number="prizeForm.winningNumber"
              type="number"
              min="1"
              :max="campaign.totalNumbers"
            />
          </div>

          <div class="field-group">
            <label for="prize-name">Nome do prémio</label>

            <input
              id="prize-name"
              v-model="prizeForm.name"
              type="text"
              placeholder="Ex.: Smartphone"
            />
          </div>

          <div class="field-group">
            <label for="scheduled-day">Data programada</label>

            <input
              id="scheduled-day"
              v-model="prizeForm.scheduledDay"
              type="date"
            />
          </div>

          <div class="field-group">
            <label for="prize-status">Estado</label>

            <select
              id="prize-status"
              v-model="prizeForm.status"
            >
              <option>Disponível</option>
              <option>Atribuído</option>
              <option>Entregue</option>
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

.back-button {
  width: 46px;
  height: 46px;
  flex-shrink: 0;
  border: 1px solid rgba(255, 255, 255, 0.38);
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  font-size: 24px;
  cursor: pointer;
}

.back-button:hover {
  background: rgba(255, 255, 255, 0.22);
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

.reports-grid {
  padding: 24px;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
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
.report-button {
  min-height: 75px;
  padding: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: 1px solid #e0e0ef;
  border-radius: 8px;
  background: #ffffff;
  color: #27227f;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.report-button:hover {
  background: #f8f8fc;
  border-color: #27227f;
}

.report-icon {
  font-size: 21px;
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

@media (max-width: 650px) {
  .header-content {
    padding: 24px 20px;
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


</style>