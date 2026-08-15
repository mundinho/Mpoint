<script setup>
import { computed, onMounted, ref } from 'vue'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import { useI18n } from 'vue-i18n'
import {
  getCampaign,
  getDashboardStatistics,
  getAdminParticipants,
  markPrizeDelivered,
  grantExtraAttempt,
  getRecentActivity
} from '../services/api'
import LoadingSpinner from './LoadingSpinner.vue'

const props = defineProps({
  admin: {
    type: Object,
    default: null
  },

  campaignId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits([
  'logout',
  'toast',
  'confirm'
])

const { t, locale } = useI18n()

function translateStatus(status) {
  if (status === 'Validado') {
    return t('common.validated')
  }

  if (status === 'Pendente') {
    return t('common.pending')
  }

   if (status === 'Entregue') {
    return t('dashboard.winners.delivered')
  }

  return status
}

function translateResult(result) {
  if (result === 'Vencedor') {
    return t('dashboard.participants.winner')
  }

  if (result === 'Sem prémio') {
    return t('dashboard.participants.noPrize')
  }

  return result
}

function changeLanguage(language) {
  locale.value = language
  localStorage.setItem('language', language)
}


const campaignName = ref('')
const campaignStartDate = ref('')
const campaignEndDate = ref('')

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

const participantFilterType = ref('name')
const participantSearch = ref('')

const participants = ref([
])

const statistics = ref({
  totalParticipants: 0,
  validatedParticipants: 0,
  pendingParticipants: 0,
  totalNumbers: 0,
  availableNumbers: 0,
  openedNumbers: 0,
  availablePrizes: 0,
  deliveredPrizes: 0
})

const filteredParticipants = computed(() => {
  const value = participantSearch.value
    .trim()
    .toLowerCase()

  if (!value) {
    return participants.value
  }

  return participants.value.filter((participant) => {
    if (participantFilterType.value === 'name') {
      return participant.name
        .toLowerCase()
        .includes(value)
    }

    if (participantFilterType.value === 'phone') {
      return participant.phone
        .toLowerCase()
        .includes(value)
    }

    return true
  })
})

const filteredRecentActivity = computed(() => {
  const value = activitySearch.value.trim().toLowerCase()

  if (!value) {
    return recentActivity.value
  }

  const actionLabels = {
    registo: 'efectuou o registo',
    validacao: 'validou o número de telemóvel',
    participacao: 'participou',
    vencedor: 'venceu',
    tentar_novamente: 'ganhou uma nova tentativa',
    premio_entregue: 'recebeu o prémio'
  }

  return recentActivity.value.filter((activity) => {
    if (activityFilterType.value === 'user') {
      return (activity.name || '')
        .toLowerCase()
        .includes(value)
    }

    if (activityFilterType.value === 'action') {
      const action = actionLabels[activity.type] || ''

      return action
        .toLowerCase()
        .includes(value)
    }

    return true
  })
})









const recentActivity = ref([])
const activityFilterType = ref('user')
const activitySearch = ref('')

const isLoadingDashboard = ref(false)

const showExportModal = ref(false)
const exportFormat = ref('csv')

const exportFilters = ref({
  dataInicio: '',
  dataFim: '',
  estado: 'todos',
  resultado: 'todos'
})

const totalAttemptsUsed = computed(() =>
  participants.value.reduce(
    (total, participant) =>
      total + Number(participant.attemptsUsed || 0),
    0
  )
)

const totalAttemptsAvailable = computed(() =>
  participants.value.reduce(
    (total, participant) =>
      total + Number(participant.attemptsAvailable || 0),
    0
  )
)

const totalAttemptsRemaining = computed(() =>
  Math.max(
    totalAttemptsAvailable.value - totalAttemptsUsed.value,
    0
  )
)

const filteredExportParticipants = computed(() => {
  return participants.value.filter(participant => {
    const matchesStatus =
      exportFilters.value.estado === 'todos' ||
      participant.status.toLowerCase() === exportFilters.value.estado

    const resultMap = {
      vencedor: 'Vencedor',
      nao_vencedor: 'Sem prémio',
      pendente: '-'
    }

    const matchesResult =
      exportFilters.value.resultado === 'todos' ||
      participant.result === resultMap[exportFilters.value.resultado]

    let matchesDate = true

    if (
      participant.date &&
      participant.date !== '-' &&
      (
        exportFilters.value.dataInicio ||
        exportFilters.value.dataFim
      )
    ) {
      const [datePart] = participant.date.split(', ')
      const [day, month, year] = datePart.split('/')

      const participantDate = `${year}-${month}-${day}`

      if (
        exportFilters.value.dataInicio &&
        participantDate < exportFilters.value.dataInicio
      ) {
        matchesDate = false
      }

      if (
        exportFilters.value.dataFim &&
        participantDate > exportFilters.value.dataFim
      ) {
        matchesDate = false
      }
    }

    return matchesStatus && matchesResult && matchesDate
  })
})

function generateCSV() {
  const rows = filteredExportParticipants.value.map(participant => ({
  [t('dashboard.reportPdf.name')]:
    participant.name || '-',

  [t('dashboard.reportPdf.phone')]:
    participant.phone
      ? `="${participant.phone}"`
      : '-',

  [t('dashboard.reportPdf.campaign')]:
    campaignName.value || '-',

  [t('dashboard.reportPdf.number')]:
    participant.number ?? '-',

  [t('dashboard.reportPdf.result')]:
    translateResult(participant.result) || '-',

  [t('dashboard.reportPdf.prize')]:
    participant.prize || '-',

  [t('dashboard.reportPdf.dateTime')]:
    participant.date || '-',

  [t('dashboard.reportPdf.status')]:
    translateStatus(participant.status) || '-',

  [t('dashboard.reportPdf.attemptsUsed')]:
    participant.attemptsUsed ?? 0,

  [t('dashboard.reportPdf.attemptsAvailable')]:
    participant.attemptsAvailable ?? 0
}))

  if (rows.length === 0) {
    showToast(
      t('dashboard.messages.noExportData'),
      'warning'
    )
    return
  }

  const headers = Object.keys(rows[0])

  const csvContent = [
    headers.join(','),
    ...rows.map(row =>
      headers.map(header => {
        const value = String(row[header] ?? '')

        return `"${value.replace(/"/g, '""')}"`
      }).join(',')
    )
  ].join('\n')

  const blob = new Blob(
    ['\ufeff' + csvContent],
    {
      type: 'text/csv;charset=utf-8;'
    }
  )

  const url = URL.createObjectURL(blob)

  const link = document.createElement('a')
  link.href = url

  const fileName =
    (campaignName.value || 'campanha')
      .replace(/[^a-zA-Z0-9-_]/g, '_')

  link.download = `relatorio_${fileName}.csv`

  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)

  URL.revokeObjectURL(url)

  closeExportModal()

  showToast(
   t('dashboard.messages.csvSuccess'),
    'success'
  )
}

function generatePDF() {
  const data = filteredExportParticipants.value

  if (data.length === 0) {
    showToast(
      t('dashboard.messages.noExportData'),
      'warning'
    )
    return
  }

  const doc = new jsPDF({
    orientation: 'landscape',
    unit: 'mm',
    format: 'a4'
  })

  // Cores da identidade visual
const NAVY = [39, 34, 127]      // #27227F
const BLUE = [0, 136, 204]      // #0088CC
const WHITE = [255, 255, 255]

// Largura da página
const pageWidth = doc.internal.pageSize.getWidth()

// Barra azul-escura
doc.setFillColor(...NAVY)
doc.rect(0, 0, pageWidth, 30, 'F')

// Área azul-clara do lado direito com divisão diagonal
doc.setFillColor(...BLUE)

doc.triangle(
  pageWidth - 75, 0,
  pageWidth, 0,
  pageWidth, 30,
  'F'
)

doc.triangle(
  pageWidth - 75, 0,
  pageWidth - 55, 30,
  pageWidth, 30,
  'F'
)

// Título
doc.setTextColor(...WHITE)
doc.setFontSize(18)
doc.setFont('helvetica', 'bold')

doc.text(
 t('dashboard.reportPdf.title'),
  14,
  13
)

// Nome da campanha
doc.setFontSize(10)
doc.setFont('helvetica', 'normal')

doc.text(
campaignName.value || t('dashboard.reportPdf.campaign'),
  14,
  21
)

// Período no lado direito
doc.setFontSize(9)

doc.text(
  `${exportFilters.value.dataInicio || '-'}  -  ${exportFilters.value.dataFim || '-'}`,
  pageWidth - 14,
  18,
  { align: 'right' }
)

// Voltar à cor normal para o restante PDF
doc.setTextColor(30, 30, 30)

  doc.setFontSize(13)
 doc.text(
  t('dashboard.reportPdf.summary'),
  14,
  43
)

  doc.setFontSize(10)

  doc.text(
  `${t('dashboard.reportPdf.totalParticipants')}: ${statistics.value.totalParticipants}`,
  14,
  52
)

  doc.text(
  `${t('dashboard.reportPdf.openedNumbers')}: ${statistics.value.openedNumbers}`,
  14,
  58
)

  doc.text(
    `${t('dashboard.reportPdf.availableNumbers')}: ${statistics.value.availableNumbers}`,
    14,
    64
  )

  doc.text(
    `${t('dashboard.reportPdf.availablePrizes')}: ${statistics.value.availablePrizes}`,
    85,
    52
  )

 doc.text(
  `${t('dashboard.reportPdf.deliveredPrizes')}: ${statistics.value.deliveredPrizes}`,
  85,
  58
)

doc.text(
  `${t('dashboard.reportPdf.usedAttempts')}: ${totalAttemptsUsed.value}`,
  85,
  64
)

doc.text(
  `${t('dashboard.reportPdf.availableAttempts')}: ${totalAttemptsAvailable.value}`,
  160,
  52
)

doc.text(
  `${t('dashboard.reportPdf.remainingAttempts')}: ${totalAttemptsRemaining.value}`,
  160,
  58
)

  autoTable(doc, {
    startY: 75,

  head: [[
  t('dashboard.reportPdf.name'),
  t('dashboard.reportPdf.phone'),
  t('dashboard.reportPdf.campaign'),
  t('dashboard.reportPdf.number'),
  t('dashboard.reportPdf.result'),
  t('dashboard.reportPdf.prize'),
  t('dashboard.reportPdf.dateTime'),
  t('dashboard.reportPdf.status'),
  t('dashboard.reportPdf.attemptsUsed'),
  t('dashboard.reportPdf.attemptsAvailable')
]],

    body: data.map(participant => [
  participant.name || '-',
  participant.phone || '-',
  campaignName.value || '-',
  participant.number ?? '-',
  translateResult(participant.result) || '-',
  participant.prize || '-',
  participant.date || '-',
  translateStatus(participant.status) || '-',
  participant.attemptsUsed ?? 0,
  participant.attemptsAvailable ?? 0
]),

    styles: {
      fontSize: 7,
      cellPadding: 2
    },

    headStyles: {
      fontStyle: 'bold'
    }
  })

  const fileName =
    (campaignName.value || 'campanha')
      .replace(/[^a-zA-Z0-9-_]/g, '_')

  doc.save(`relatorio_${fileName}.pdf`)

  closeExportModal()

  showToast(
    t('dashboard.messages.pdfSuccess'),
    'success'
  )
}


function confirmExport() {
  if (exportFormat.value === 'csv') {
    generateCSV()
    return
  }

  if (exportFormat.value === 'pdf') {
    generatePDF()
  }
}

async function refreshDashboard() {
  await loadDashboard()
}

function exportExcel() {
  exportFormat.value = 'csv'

  exportFilters.value.dataInicio =
    campaignStartDate.value

  exportFilters.value.dataFim =
    campaignEndDate.value

  showExportModal.value = true
}

function exportPDF() {
  exportFormat.value = 'pdf'

  exportFilters.value.dataInicio =
    campaignStartDate.value

  exportFilters.value.dataFim =
    campaignEndDate.value

  showExportModal.value = true
}

function closeExportModal() {
  showExportModal.value = false
}

function giveExtraAttempt(participant) {
  requestConfirm(
   t('dashboard.messages.extraAttemptConfirm', {
  name: participant.name
}),
    () => executeGiveExtraAttempt(participant)
  )
}

async function executeGiveExtraAttempt(participant) {
  const token = localStorage.getItem('adminToken')

  try {
    await grantExtraAttempt(
      participant.id,
      token
    )

    await loadDashboard()

    showToast(
     t('dashboard.messages.extraAttemptSuccess'),
      'success'
    )

  } catch (error) {
    showToast(
      error.message,
      'error'
    )
  }
}


async function loadDashboard() {
  const token = localStorage.getItem('adminToken')

  if (!token) {
    return
  }

  if (!props.campaignId) {
    emit('switch-campaign')
    return
  }

  isLoadingDashboard.value = true

  try {
     // 1. aqui esta Buscar dados
   const [
  campaignResponse,
  statisticsResponse,
  participantsResponse,
  activityResponse
] = await Promise.all([
  getCampaign(props.campaignId, token),
  getDashboardStatistics(props.campaignId, token),
  getAdminParticipants(props.campaignId, token),
  getRecentActivity(props.campaignId, token)
])

    campaignName.value = campaignResponse.nome || `Campanha #${campaignResponse.id}`
    campaignStartDate.value = campaignResponse.data_inicio
  ? campaignResponse.data_inicio.substring(0, 10)
  : ''

campaignEndDate.value = campaignResponse.data_fim
  ? campaignResponse.data_fim.substring(0, 10)
  : new Date().toISOString().substring(0, 10)


    statistics.value = {
      totalParticipants:
        statisticsResponse.total_participantes || 0,

      validatedParticipants:
        statisticsResponse.participantes_validados || 0,

      pendingParticipants:
        statisticsResponse.participantes_pendentes || 0,

       totalNumbers:
        statisticsResponse.total_numeros || 0,

      availableNumbers:
        statisticsResponse.numeros_disponiveis || 0,

      openedNumbers:
        statisticsResponse.numeros_abertos || 0,

      availablePrizes:
        statisticsResponse.premios_disponiveis || 0,

      deliveredPrizes:
        statisticsResponse.premios_entregues || 0
    }

   const participantsData = Array.isArray(participantsResponse)
  ? participantsResponse
  : participantsResponse.data || []

participants.value = participantsData
  .map(participant => ({
    id: participant.id,
    name: participant.nome || '',
    phone: participant.telefone || '',

    status:
      participant.estado === 'validado'
        ? 'Validado'
        : 'Pendente',

    number:
      participant.numero ?? '-',

    result:
      participant.resultado === 'vencedor'
        ? 'Vencedor'
        : participant.resultado === 'nao_vencedor'
          ? 'Sem prémio'
          : participant.resultado === 'tentar_novamente'
            ? 'Tentar novamente'
            : '-',

    prize:
      participant.premio || '-',

    prizeStatus:
      participant.entrega_estado === 'entregue'
        ? 'Entregue'
        : participant.entrega_estado === 'pendente'
          ? 'Pendente'
          : 'Não aplicável',

    date:
      participant.participou_em
        ? formatDateTime(participant.participou_em)
        : '-',

    participatedAt:
      participant.participou_em || null,

    attemptsUsed:
      participant.tentativas_usadas || 0,

    attemptsAvailable:
      participant.tentativas_disponiveis || 0
  }))
  .sort((a, b) => {
    if (!a.participatedAt) return 1
    if (!b.participatedAt) return -1

    return new Date(b.participatedAt) - new Date(a.participatedAt)
  })

 
      const activityData = Array.isArray(activityResponse)
      ? activityResponse
      : activityResponse.data || []

    recentActivity.value = activityData.map(item => ({
      type: item.tipo,
      userId: item.usuario_id,
      name: item.nome || '',
      number: item.numero ?? null,
      prize: item.premio || '',
      date: item.data_hora
        ? formatDateTime(item.data_hora)
        : '-'
    }))

  } catch (error) {
    console.error(
      'Erro ao carregar Dashboard:',
      error
    )

    if (error.status === 401) {
      showToast(
  t('dashboard.messages.sessionExpired'),
  'error'
)
      localStorage.removeItem('adminToken')
      emit('logout')
      return
    }

    if (error.status === 404) {
      showToast(
  t('dashboard.messages.campaignNotFound'),
  'error'
)
      emit('switch-campaign')
      return
    }

    showToast(
  t('dashboard.messages.dashboardLoadError'),
  'error'
)
  } finally {
    isLoadingDashboard.value = false
  }
}

function formatDateTime(dateValue) {
  const date = new Date(dateValue)

  return date.toLocaleString('pt-PT', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
 onMounted(async () => {
  await loadDashboard()
})


function deliverPrize(winner) {
  requestConfirm(
   t('dashboard.messages.deliverPrizeConfirm', {
  prize: winner.prize,
  name: winner.name
}),
    () => executeDeliverPrize(winner)
  )
}

async function executeDeliverPrize(winner) {
  const token = localStorage.getItem('adminToken')

  try {
    await markPrizeDelivered(
      props.campaignId,
      winner.number,
      token
    )

    await loadDashboard()

    showToast(
     t('dashboard.messages.prizeDelivered'),
      'success'
    )

  } catch (error) {
    console.error(
      'Erro ao marcar prémio como entregue:',
      error
    )

    showToast(
      error.message,
      'error'
    )
  }
}
</script>

<template>
  <div class="dashboard-page">
    <header class="top-header">
      <div class="header-accent"></div>

      <div class="header-content">
        <div class="title-with-button">
          <div>
           <h1>{{ t('dashboard.title') }}</h1>

<p>
  {{
    campaignName
      ? t('dashboard.viewing', { campaign: campaignName })
      : t('dashboard.monitoring')
  }}
</p>
          </div>
        </div>

        <div class="header-actions">

        

  <button
    type="button"
    class="secondary-action"
    :disabled="isLoadingDashboard"
    @click="refreshDashboard"
  >
    <LoadingSpinner
      v-if="isLoadingDashboard"
      :size="12"
    />
   {{ t('common.update') }}
  </button>


  
  <button
    type="button"
    class="secondary-action"
    @click="exportExcel"
  >
    {{ t('dashboard.exportExcel') }}
  </button>

  <button
    type="button"
    class="primary-action"
    @click="exportPDF"
  >
    {{ t('dashboard.exportPdf') }}
  </button>
</div>
      </div>
    </header>

    <main class="dashboard-content">
      <section class="statistics-grid">
        <article class="stat-card">
          <span class="stat-label">{{ t('dashboard.stats.totalParticipants') }}</span>
          <strong>{{ statistics.totalParticipants }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">{{ t('dashboard.stats.validatedParticipants') }}</span>
          <strong>{{ statistics.validatedParticipants }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">{{ t('dashboard.stats.pendingParticipants') }}</span>
          <strong>{{ statistics.pendingParticipants }}</strong>
        </article>

        <article class="stat-card">
         <span class="stat-label">{{ t('dashboard.stats.totalNumbers') }}</span>
         <strong>{{ statistics.totalNumbers }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">{{ t('dashboard.stats.availableNumbers') }}</span>
          <strong>{{ statistics.availableNumbers }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">{{ t('dashboard.stats.openedNumbers') }}</span>
          <strong>{{ statistics.openedNumbers }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">{{ t('dashboard.stats.availablePrizes') }}</span>
          <strong>{{ statistics.availablePrizes }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">{{ t('dashboard.stats.deliveredPrizes') }}</span>
          <strong class="delivered-number">
            {{ statistics.deliveredPrizes }}
          </strong>
        </article>
      </section>

      <section class="table-card">
        <div class="table-header">
          <div>
            <h2>{{ t('dashboard.participants.title') }}</h2>
            <p>{{ t('dashboard.participants.description') }}</p>
          </div>

          <div class="search-area">
            <select v-model="participantFilterType">
  <option value="name">{{ t('dashboard.participants.name') }}</option>
  <option value="phone">{{ t('dashboard.participants.contact') }}</option>
</select>

<input
  v-model="participantSearch"
  type="search"
  :placeholder="
    participantFilterType === 'name'
      ? t('dashboard.participants.searchName')
      : t('dashboard.participants.searchContact')
  "
/>
          </div>
        </div>

     <div class="table-wrapper scroll-table">
          <table>
            <thead>
              <tr>
          <th>{{ t('dashboard.participants.participant') }}</th>
<th>{{ t('dashboard.participants.phone') }}</th>
<th>{{ t('dashboard.participants.status') }}</th>
<th>{{ t('dashboard.participants.number') }}</th>
<th>{{ t('dashboard.participants.result') }}</th>
<th>{{ t('dashboard.participants.prize') }}</th>
<th>{{ t('dashboard.participants.deliveryStatus') }}</th>
<th>{{ t('dashboard.participants.dateTime') }}</th>
<th>{{ t('dashboard.participants.attempts') }}</th>
<th>{{ t('common.actions') }}</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="participant in filteredParticipants"
                :key="participant.id"
              >
                <td class="participant-cell">
                  {{ participant.name }}
                </td>

                <td>{{ participant.phone }}</td>

                <td>
                  <span
                    class="status-badge"
                    :class="{
                      validated: participant.status === 'Validado',
                      pending: participant.status === 'Pendente'
                    }"
                  >
                  {{ translateStatus(participant.status) }}
                  </span>
                </td>

                <td>{{ participant.number }}</td>

                <td>
                  <span
                    v-if="participant.result !== '-'"
                    class="result-badge"
                  :class="{
  winner: participant.result === 'Vencedor',
  'no-prize': participant.result === 'Sem prémio'
}"
                  >
                    {{ translateResult(participant.result) }}
                  </span>

                  <span v-else>-</span>
                </td>

           <td>{{ participant.prize }}</td>

<!-- Estado da entrega -->
<td>
  <span
    v-if="participant.result === 'Vencedor'"
    class="delivery-badge"
    :class="{
      delivered: participant.prizeStatus === 'Entregue',
      'delivery-pending': participant.prizeStatus === 'Pendente'
    }"
  >
    {{ translateStatus(participant.prizeStatus) }}
  </span>

  <span v-else>
    {{ t('dashboard.participants.notApplicable') }}
  </span>
</td>

<td>{{ participant.date }}</td>

<td>
  {{ participant.attemptsUsed }}
  /
  {{ participant.attemptsAvailable }}
</td>

<td>
  <div class="row-actions">

    <!-- Dar nova tentativa -->
    <button
      type="button"
      class="edit-button"
      @click="giveExtraAttempt(participant)"
    >
      {{ t('dashboard.participants.newAttempt') }}
    </button>

    <!-- Marcar prémio como entregue -->
    <button
      v-if="
        participant.result === 'Vencedor' &&
        participant.prizeStatus === 'Pendente'
      "
      type="button"
      class="edit-button"
      @click="deliverPrize(participant)"
    >
      {{ t('dashboard.participants.markDelivered') }}
    </button>

  </div>
</td>

</tr>

<tr v-if="isLoadingDashboard && filteredParticipants.length === 0">
  <td colspan="9" class="empty-message">
    <LoadingSpinner color="purple" :size="16" /> {{ t('common.loading') }}
  </td>
</tr>

<tr v-else-if="filteredParticipants.length === 0">
  <td colspan="9" class="empty-message">
    {{ t('dashboard.participants.notFound') }}
  </td>
</tr>

</tbody>
</table>
</div>
</section>


      <section class="activity-card">
        <div class="activity-heading">
          <h2>{{ t('dashboard.activity.title') }}</h2>
          <span>{{ t('dashboard.activity.description') }}</span>
        </div>
<div class="activity-filters">
  <select v-model="activityFilterType">
    <option value="user">{{ t('dashboard.activity.user') }}</option>
    <option value="action">{{ t('dashboard.activity.action') }}</option>
  </select>

   <input
    v-model="activitySearch"
    type="search"
    :placeholder="
  activityFilterType === 'user'
    ? t('dashboard.activity.searchUser')
    : t('dashboard.activity.searchAction')
"
  />
</div>

        <div class="activity-list">
         <div
 v-for="(activity, index) in filteredRecentActivity"
  :key="`${activity.userId}-${activity.date}-${index}`"
  class="activity-item"
>
  <span
    class="activity-dot"
    :class="{
      'winner-dot': activity.type === 'vencedor',
      'pending-dot': activity.type === 'validacao',
      'retry-dot': activity.type === 'tentar_novamente'
    }"
  ></span>

  <p>
  <strong class="activity-name">{{ activity.name }}</strong>

   <template v-if="activity.type === 'registo'">
  {{ t('dashboard.activity.registered') }}
</template>

    <template v-else-if="activity.type === 'validacao'">
  {{ t('dashboard.activity.validatedPhone') }}
</template>

    <template v-else-if="activity.type === 'participacao'">
      {{ t('dashboard.activity.participated') }}
      <strong>{{ activity.number }}</strong>.
    </template>

    <template v-else-if="activity.type === 'vencedor'">
      {{ t('dashboard.activity.wonPrize') }}
      <strong>{{ activity.prize }}</strong>
      no número
      <strong>{{ activity.number }}</strong>.
    </template>

    <template v-else-if="activity.type === 'tentar_novamente'">
      {{ t('dashboard.activity.wonRetry') }}
      <strong>{{ activity.number }}</strong>.
    </template>

    <template v-else-if="activity.type === 'premio_entregue'">
      {{ t('dashboard.activity.receivedPrize') }}
      <strong>{{ activity.prize }}</strong>.
    </template>

    <template v-else-if="activity.number !== null">
  {{ t('dashboard.activity.playedNumber') }}
  <strong>{{ activity.number }}</strong>.
</template>
  </p>

  <time>{{ activity.date }}</time>
</div>

<div
 v-if="isLoadingDashboard && filteredRecentActivity.length === 0"
  class="empty-message"
>
  <LoadingSpinner color="purple" :size="16" /> {{ t('common.loading') }}
</div>

<div
 v-else-if="filteredRecentActivity.length === 0"
  class="empty-message"
>
  {{ t('dashboard.activity.empty') }}
</div>
  
        </div>
      </section>
    </main>

    <footer class="bottom-stripe">
      <div class="bottom-accent"></div>
    </footer>

<div
  v-if="showExportModal"
  class="export-modal-overlay"
  @click.self="closeExportModal"
>
  <div class="export-modal">

    <div class="export-modal-header">
      <div>
      <h2>{{ t('dashboard.export.title') }}</h2>
        <p>
  {{ t('dashboard.export.description') }}
</p>
      </div>

      <button
        type="button"
        class="export-close-button"
        @click="closeExportModal"
      >
        ×
      </button>
    </div>

    <div class="export-modal-body">

      <div class="export-field export-field-full">
        <label>{{ t('dashboard.export.campaign') }}</label>

        <div class="export-campaign-name">
          {{ campaignName || t('dashboard.export.noCampaignSelected') }}
        </div>
      </div>

      <div class="export-field">
        <label>{{ t('dashboard.export.startDate') }}</label>

        <input
          v-model="exportFilters.dataInicio"
          type="date"
        />
      </div>

      <div class="export-field">
       <label>{{ t('dashboard.export.endDate') }}</label>

        <input
          v-model="exportFilters.dataFim"
          type="date"
        />
      </div>

      <div class="export-field">
     <label>{{ t('dashboard.export.status') }}</label>

        <select v-model="exportFilters.estado">
         <option value="todos">{{ t('common.all') }}</option>
         <option value="validado">{{ t('common.validated') }}</option>
          <option value="pendente">{{ t('common.pending') }}</option>
        </select>
      </div>

      <div class="export-field">
     <label>{{ t('dashboard.export.result') }}</label>

<select v-model="exportFilters.resultado">
  <option value="todos">
    {{ t('common.all') }}
  </option>

  <option value="vencedor">
    {{ t('dashboard.export.winner') }}
  </option>

  <option value="nao_vencedor">
    {{ t('dashboard.export.noPrize') }}
  </option>

  <option value="pendente">
    {{ t('common.pending') }}
  </option>
</select>
      </div>

    </div>

    <div class="export-modal-actions">
      <button
        type="button"
        class="secondary-action-modal"
        @click="closeExportModal"
      >
        {{ t('common.cancel') }}
      </button>

    <button
  type="button"
  class="primary-action-modal"
  @click="confirmExport"
>
  {{ t('dashboard.export.export') }}
  {{ exportFormat.toUpperCase() }}
</button>
    </div>

  </div>
</div>


  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.dashboard-page {
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
  padding: 27px 42px 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 30px;
}


.title-with-button {
  display: flex;
  align-items: center;
  gap: 17px;
}

.title-with-button h1 {
  margin: 0;
  color: #ffffff;
  font-size: 30px;
}

.title-with-button p {
  margin: 7px 0 0;
  color: rgba(255, 255, 255, 0.62);
  font-size: 14px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 9px;
}

.header-actions button {
  min-height: 40px;
  padding: 0 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.header-actions button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.secondary-action {
  border: 1px solid rgba(255, 255, 255, 0.38);
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

.primary-action {
  border: 1px solid #ffffff;
  background: #ffffff;
  color: #27227f;
}

.dashboard-content {
  width: 100%;
  max-width: 1500px;
  flex: 1;
  margin: 0 auto;
  padding: 30px 38px 40px;
}

.statistics-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(160px, 1fr));
  gap: 15px;
  margin-bottom: 24px;
}

.stat-card {
  min-height: 112px;
  padding: 20px;
  border: 1px solid #e3e3ef;
  border-radius: 9px;
  background: #ffffff;
  box-shadow: 0 2px 9px rgba(39, 34, 127, 0.05);
}

.stat-label {
  display: block;
  min-height: 34px;
  color: #6b7280;
  font-size: 13px;
  line-height: 1.4;
}

.stat-card strong {
  display: block;
  margin-top: 8px;
  color: #27227f;
  font-size: 28px;
}

.delivered-number {
  color: #047857 !important;
}

.table-card,
.activity-card {
  overflow: hidden;
  border: 1px solid #e3e3ef;
  border-radius: 9px;
  background: #ffffff;
  box-shadow: 0 2px 9px rgba(39, 34, 127, 0.05);
}

.table-card {
  margin-bottom: 24px;
}

.table-header {
  padding: 21px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 25px;
  border-bottom: 1px solid #ececf4;
}

.table-header h2,
.activity-heading h2 {
  margin: 0;
  color: #111827;
  font-size: 20px;
}

.table-header p {
  margin: 5px 0 0;
  color: #9ca3af;
  font-size: 13px;
}

.search-area {
  display: flex;
  gap: 10px;
}

.search-area input {
  width: 210px;
  height: 42px;
  padding: 0 13px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  outline: none;
  font-size: 13px;
}

.search-area input:focus {
  border-color: #27227f;
}

.search-area select {
  width: 120px;
  height: 42px;
  padding: 0 13px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  outline: none;
  font-size: 13px;
  background: white;
  cursor: pointer;
}

.search-area select:focus {
  border-color: #27227f;
}

.scroll-table {
  max-height: 420px;
  overflow-y: auto;
  overflow-x: auto;
  scrollbar-gutter: stable;
}

.scroll-table thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: #ffffff;
}

.table-wrapper {
  overflow-x: auto;
  overflow-y: auto;
  max-height: 320px;
  scrollbar-gutter: stable;
}

.table-wrapper thead th {
  position: sticky;
  top: 0;
  background: white;
  z-index: 2;
}

table {
  width: 100%;
  min-width: 1000px;
  border-collapse: collapse;
}

th {
  padding: 14px 16px;
  background: #f8f8fc;
  color: #6b7280;
  text-align: left;
  font-size: 11px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

td {
  padding: 15px 16px;
  border-top: 1px solid #f0f0f5;
  color: #4b5563;
  font-size: 13px;
}

tbody tr:hover {
  background: #fafafe;
}

.participant-cell {
  color: #111827;
  font-weight: 700;
}

.status-badge,
.result-badge,
.delivery-badge {
  display: inline-block;
  padding: 5px 9px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
}

.validated {
  background: #ecfdf5;
  color: #047857;
}

.pending {
  background: #fff7ed;
  color: #c2410c;
}

.winner {
  background: #eff6ff;
  color: #1d4ed8;
}

.no-prize {
  background: #f3f4f6;
  color: #6b7280;
}

.delivered {
  background: #ecfdf5;
  color: #047857;
}

.delivery-pending {
  background: #fff7ed;
  color: #c2410c;
}

.prize-name {
  color: #27227f;
  font-weight: 700;
}

.edit-button {
  min-height: 33px;
  padding: 0 11px;
  border: 1px solid #27227f;
  border-radius: 6px;
  background: #ffffff;
  color: #27227f;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.edit-button:hover {
  background: #f5f5fb;
}

.empty-message {
  padding: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  text-align: center;
  color: #9ca3af;
}

.activity-filters {
  display: flex;
  gap: 10px;
  margin-left: 16px;
  margin-top: 12px;
  margin-bottom: 16px;
}

.activity-filters select {
  width: 120px;
  height: 42px;
  padding: 0 13px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  outline: none;
  background: white;
  font-size: 13px;
  cursor: pointer;
}

.activity-filters input {
  width: 210px;
  height: 42px;
  padding: 0 13px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  outline: none;
  font-size: 13px;
}

.activity-filters select:focus,
.activity-filters input:focus {
  border-color: #27227f;
}

.activity-heading {
  padding: 20px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #ececf4;
}

.activity-heading span {
  color: #9ca3af;
  font-size: 12px;
}

.activity-list {
  max-height: 420px;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 0 16px;
  box-sizing: border-box;
  scrollbar-gutter: stable;
}

.activity-item {
  min-height: 58px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid #f0f0f5;

  width: 100%;
  box-sizing: border-box;
}

.activity-item:last-child {
  border-bottom: 0;
}

.activity-item p {
  flex: 1;
  margin: 0;
  color: #4b5563;
  font-size: 13px;
}

.activity-item time {
  color: #9ca3af;
  font-size: 12px;
}

.activity-dot {
  width: 9px;
  height: 9px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #0088cc;
}

.activity-name {
  margin-right: 4px;
}

.winner-dot {
  background: #f59e0b;
}

.pending-dot {
  background: #9ca3af;
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

.export-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(0, 0, 0, 0.45);
}

.export-modal {
  width: 100%;
  max-width: 560px;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);
}

.export-modal-header {
  padding: 20px 22px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  border-bottom: 1px solid #ececf4;
}

.export-modal-header h2 {
  margin: 0;
  color: #111827;
  font-size: 20px;
}

.export-modal-header p {
  margin: 5px 0 0;
  color: #9ca3af;
  font-size: 13px;
}

.export-close-button {
  border: 0;
  background: transparent;
  color: #6b7280;
  font-size: 26px;
  cursor: pointer;
}

.export-modal-body {
  padding: 22px;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.export-field {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.export-field-full {
  grid-column: 1 / -1;
}

.export-field label {
  color: #4b5563;
  font-size: 13px;
  font-weight: 600;
}

.export-field input,
.export-field select {
  height: 42px;
  padding: 0 12px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  background: #ffffff;
  outline: none;
}

.export-field input:focus,
.export-field select:focus {
  border-color: #27227f;
}

.export-campaign-name {
  min-height: 42px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  background: #f9fafb;
  color: #27227f;
  font-size: 13px;
  font-weight: 700;
}

.export-modal-actions {
  padding: 16px 22px 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid #ececf4;
}

.secondary-action-modal,
.primary-action-modal {
  min-height: 40px;
  padding: 0 17px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.secondary-action-modal {
  border: 1px solid #d1d5db;
  background: #ffffff;
  color: #4b5563;
}

.primary-action-modal {
  border: 1px solid #27227f;
  background: #27227f;
  color: #ffffff;
}

@media (max-width: 1100px) {
  .header-content {
    align-items: flex-start;
    flex-direction: column;
  }

  .statistics-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .table-header {
    align-items: flex-start;
    flex-direction: column;
  }
}

@media (max-width: 900px) {
  .header-content {
    padding-left: 64px;
  }
}

@media (max-width: 650px) {
  .dashboard-content {
    padding: 20px 15px 30px;
  }

  .header-content {
    padding: 24px 20px;
  }

  .header-actions,
  .search-area {
    width: 100%;
    flex-wrap: wrap;
  }

  .statistics-grid {
    grid-template-columns: 1fr;
  }

  .search-area input {
    width: 100%;
  }

  .title-with-button h1 {
    font-size: 25px;
  }
}

.row-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

</style>