<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { getCampaign, getCampaignReports } from '../services/api'
import LoadingSpinner from './LoadingSpinner.vue'
import StatTile from './charts/StatTile.vue'
import LineChart from './charts/LineChart.vue'
import DonutChart from './charts/DonutChart.vue'
import BarChart from './charts/BarChart.vue'
import Meter from './charts/Meter.vue'
import StackedBarChart from './charts/StackedBarChart.vue'
import FunnelChart from './charts/FunnelChart.vue'

const props = defineProps({
  campaignId: {
    type: [Number, String],
    default: null
  }
})

const { t } = useI18n()

const emit = defineEmits(['switch-campaign', 'logout', 'toast'])

function showToast(message, type = 'error') {
  emit('toast', { message, type })
}

const campaignName = ref('')
const report = ref(null)
const isLoading = ref(false)

// --- Resumo (KPIs) ---------------------------------------------------------
const statTiles = computed(() => {
  const r = report.value?.resumo

  if (!r) return []

  return [
  { label: t('charts.stats.campaignNumbers'), value: r.total_quadrados ?? 0 },
  { label: t('charts.stats.registered'), value: r.total_registados ?? 0 },
  { label: t('charts.stats.validated'), value: r.total_validados ?? 0 },
  { label: t('charts.stats.pendingValidation'), value: r.total_pendentes_validacao ?? 0 },
  { label: t('charts.stats.played'), value: r.total_jogaram ?? 0 },
  { label: t('charts.stats.winners'), value: r.total_venceram ?? 0 },
  { label: t('charts.stats.nonWinners'), value: r.total_nao_venceram ?? 0 },
  { label: t('charts.stats.pendingResult'), value: r.total_pendentes_resultado ?? 0 }
]
})

// --- Actividade por hora (linhas) ------------------------------------------
function alignHourly(seriesList) {
  const hours = [...new Set(seriesList.flatMap(s => s.map(p => p.hora)))].sort()

  return hours
}

const activitySeries = computed(() => {
  const jogadas = report.value?.jogadas_por_hora || []
  const vencedores = report.value?.vencedores_por_hora || []
  const premios = report.value?.premios_atribuidos_por_hora || []

  const hours = alignHourly([jogadas, vencedores, premios])

  function toPoints(list) {
    const byHour = new Map(list.map(p => [p.hora, p.quantidade]))
    return hours.map(h => ({ x: h, y: byHour.get(h) || 0 }))
  }

  return [
  { name: t('charts.series.plays'), color: '#2a78d6', points: toPoints(jogadas) },
  { name: t('charts.series.winners'), color: '#eb6834', points: toPoints(vencedores) },
  { name: t('charts.series.prizesAwarded'), color: '#1baf7a', points: toPoints(premios) }
]
})

const registosSeries = computed(() => {
  const registos = report.value?.registos_por_hora || []

  return [{
  name: t('charts.series.registrations'),
  color: '#2a78d6',
  points: registos.map(p => ({ x: p.hora, y: p.quantidade }))
}]
})

// --- Resultados (donut) -----------------------------------------------------
const resultadoMeta = computed(() => ({
  vencedor: {
    label: t('charts.results.winner'),
    color: '#2a78d6'
  },
  nao_vencedor: {
    label: t('charts.results.nonWinner'),
    color: '#eb6834'
  },
  pendente: {
    label: t('charts.results.pending'),
    color: '#1baf7a'
  }
}))

const resultadosData = computed(() =>
  (report.value?.resultados || []).map(r => ({
   label: resultadoMeta.value[r.resultado]?.label || r.resultado,
    value: r.quantidade,
   color: resultadoMeta.value[r.resultado]?.color || '#4a3aa7'
  }))
)

// --- Prémios por nome (barras) ----------------------------------------------
const premiosData = computed(() =>
  (report.value?.premios_por_nome || []).map(p => ({ label: p.nome, value: p.quantidade }))
)

// --- Números por estado (meter) ---------------------------------------------
const numerosAbertos = computed(() => {
  const linha = (report.value?.numeros_por_estado || []).find(n => n.estado === 'aberto')
  return linha?.quantidade || 0
})

const totalNumeros = computed(() => report.value?.resumo?.total_quadrados || 0)

// --- SMS por tipo e estado (barras empilhadas) ------------------------------
const estadoSmsMeta = computed(() => ({
  enviado: {
    label: t('charts.sms.sent'),
    color: '#0ca30c'
  },
  falhado: {
    label: t('charts.sms.failed'),
    color: '#d03b3b'
  }
}))

const smsChart = computed(() => {
  const rows = report.value?.sms_por_tipo_e_estado || []
  const categories = [...new Set(rows.map(r => r.tipo))]
  const estados = [...new Set(rows.map(r => r.estado))]

  const series = estados.map(estado => ({
   name: estadoSmsMeta.value[estado]?.label || estado,
color: estadoSmsMeta.value[estado]?.color || '#898781',
    values: categories.map(cat => rows.find(r => r.tipo === cat && r.estado === estado)?.quantidade || 0)
  }))

  return { categories, series }
})

// --- Funil -------------------------------------------------------------------
const funilStages = computed(() =>
  (report.value?.funil || []).map(f => ({ label: f.etapa, value: f.quantidade }))
)

// --- Carregamento --------------------------------------------------------
async function loadReport() {
  const token = localStorage.getItem('adminToken')

  if (!token) {
    emit('logout')
    return
  }

  if (!props.campaignId) {
    emit('switch-campaign')
    return
  }

  isLoading.value = true

  try {
    const [campaign, reportResponse] = await Promise.all([
      getCampaign(props.campaignId, token),
      getCampaignReports(props.campaignId, token)
    ])

    campaignName.value = campaign.nome || `Campanha #${campaign.id}`
    report.value = reportResponse
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

    showToast(error.message || 'Não foi possível carregar os gráficos.', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(loadReport)
</script>

<template>
  <div class="charts-page">
    <header class="top-header">
      <div class="header-accent"></div>

      <div class="header-content">
        <div>
          <h1>{{ t('charts.page.title') }}</h1>

<p>
  {{
    campaignName
      ? t('charts.page.viewing', { campaign: campaignName })
      : t('charts.page.analysis')
  }}
</p>
        </div>

        <button
          type="button"
          class="secondary-action"
          :disabled="isLoading"
          @click="loadReport"
        >
          <LoadingSpinner v-if="isLoading" :size="12" />
        {{ t('charts.page.refresh') }}
        </button>
      </div>
    </header>

    <main class="charts-content">
      <div
        v-if="isLoading && !report"
        class="state-message"
      >
       <LoadingSpinner color="purple" :size="20" />
{{ t('charts.page.loading') }}
      </div>

      <template v-else-if="report">
        <section class="stat-grid">
          <StatTile
            v-for="tile in statTiles"
            :key="tile.label"
            :label="tile.label"
            :value="tile.value"
          />
        </section>

        <div class="chart-grid">
          <article class="chart-card wide">
           <h2>{{ t('charts.cards.activityTitle') }}</h2>

<p>
  {{ t('charts.cards.activityDescription') }}
</p>
            <LineChart :series="activitySeries" />
          </article>

          <article class="chart-card wide">
            <h2>{{ t('charts.cards.registrationsTitle') }}</h2>
            <p>
              {{ t('charts.cards.registrationsDescription') }}
            </p>
            <LineChart :series="registosSeries" />
          </article>

          <article class="chart-card">
            <h2>{{ t('charts.cards.resultsTitle') }}</h2>
<p>{{ t('charts.cards.resultsDescription') }}</p>
            <DonutChart :data="resultadosData" />
          </article>

          <article class="chart-card">
  <h2>{{ t('charts.cards.openedNumbersTitle') }}</h2>

  <p>
    {{ t('charts.cards.openedNumbersDescription') }}
  </p>

  <Meter
    :value="numerosAbertos"
    :total="totalNumeros"
    :label="t('charts.cards.openedNumbersTitle')"
  />
</article>

<article class="chart-card wide">
  <h2>{{ t('charts.cards.prizesTitle') }}</h2>

  <p>
    {{ t('charts.cards.prizesDescription') }}
  </p>

  <BarChart :data="premiosData" />
</article>

<article class="chart-card wide">
  <h2>{{ t('charts.cards.smsTitle') }}</h2>

  <p>
    {{ t('charts.cards.smsDescription') }}
  </p>

  <StackedBarChart
    :categories="smsChart.categories"
    :series="smsChart.series"
  />
</article>

<article class="chart-card wide">
  <h2>{{ t('charts.cards.funnelTitle') }}</h2>

  <p>
    {{ t('charts.cards.funnelDescription') }}
  </p>

  <FunnelChart :stages="funilStages" />
</article>
        </div>
      </template>
    </main>

    <footer class="bottom-stripe">
      <div class="bottom-accent"></div>
    </footer>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.charts-page {
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f9f9f7;
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
  min-height: 110px;
  padding: 27px 42px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 30px;
}

.header-content h1 {
  margin: 0;
  color: #ffffff;
  font-size: 28px;
}

.header-content p {
  margin: 7px 0 0;
  color: rgba(255, 255, 255, 0.62);
  font-size: 14px;
}

.secondary-action {
  min-height: 40px;
  padding: 0 16px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid rgba(255, 255, 255, 0.38);
  border-radius: 7px;
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.secondary-action:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.charts-content {
  width: 100%;
  max-width: 1450px;
  flex: 1;
  margin: 0 auto;
  padding: 30px 38px 42px;
}

.state-message {
  padding: 80px 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #898781;
  font-size: 14px;
}

.stat-grid {
  margin-bottom: 24px;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

.chart-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 18px;
}

.chart-card {
  padding: 20px 22px;
  border: 1px solid #e3e3ef;
  border-radius: 9px;
  background: #fcfcfb;
  box-shadow: 0 2px 9px rgba(39, 34, 127, 0.05);
}

.chart-card.wide {
  grid-column: span 2;
}

.chart-card h2 {
  margin: 0;
  color: #111827;
  font-size: 16px;
}

.chart-card p {
  margin: 4px 0 16px;
  color: #898781;
  font-size: 12px;
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

@media (max-width: 1100px) {
  .stat-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .chart-grid {
    grid-template-columns: 1fr;
  }

  .chart-card.wide {
    grid-column: span 1;
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
    flex-direction: column;
    align-items: flex-start;
  }

  .charts-content {
    padding: 20px 15px 30px;
  }

  .stat-grid {
    grid-template-columns: 1fr;
  }
}
</style>
