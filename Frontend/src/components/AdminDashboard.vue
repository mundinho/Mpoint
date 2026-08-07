<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  adminLogout,
  getDashboardStatistics,
  getAdminParticipants,
  getAdminWinners,
  markPrizeDelivered
} from '../services/api'
const props = defineProps({
  admin: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['open-management', 'logout'])

const search = ref('')
const phoneSearch = ref('')

const participants = ref([
])

const statistics = ref({
  totalParticipants: 0,
  validatedParticipants: 0,
  pendingParticipants: 0,
  availableNumbers: 0,
  openedNumbers: 0,
  availablePrizes: 0,
  deliveredPrizes: 0
})

const filteredParticipants = computed(() => {
  const nameValue = search.value.trim().toLowerCase()
  const phoneValue = phoneSearch.value.trim()

  return participants.value.filter((participant) => {
    const matchesName =
      !nameValue ||
      participant.name.toLowerCase().includes(nameValue)

    const matchesPhone =
      !phoneValue ||
      participant.phone.includes(phoneValue)

    return matchesName && matchesPhone
  })
})

const winners = ref([])

async function refreshDashboard() {
  await loadDashboard()
}

function exportExcel() {
  alert('Exportação para Excel iniciada.')
}

function exportPDF() {
  alert('Exportação para PDF iniciada.')
}

async function handleLogout() {
  const token = localStorage.getItem('adminToken')

  try {
    if (token) {
      await adminLogout(token)
    }
  } catch (error) {
    console.error('Erro ao terminar sessão:', error)
  } finally {
    localStorage.removeItem('adminToken')
    emit('logout')
  }
}

onMounted(async () => {
  const token = localStorage.getItem('adminToken')

  try {
    const response = await getUsers(token)

    console.log('Utilizadores:', response)

    const data = Array.isArray(response)
      ? response
      : response.usuarios || response.data || []

    participants.value = data.map(user => ({
      id: user.id,
      name: user.nome || '',
      phone: user.telefone || '',

      // Ainda aguardamos endpoints/dados de participação
      status: '-',
      number: '-',
      result: '-',
      prize: '-',
      prizeStatus: '-',
      date: '-'
    }))
  } catch (error) {
    console.error(
      'Erro ao carregar utilizadores:',
      error
    )
  }
})
async function loadDashboard() {
  const token = localStorage.getItem('adminToken')

  if (!token) {
    return
  }

  try {
    const [
      statisticsResponse,
      participantsResponse,
      winnersResponse
    ] = await Promise.all([
      getDashboardStatistics(token),
      getAdminParticipants(token),
      getAdminWinners(token)
    ])

    statistics.value = {
      totalParticipants:
        statisticsResponse.total_participantes || 0,

      validatedParticipants:
        statisticsResponse.participantes_validados || 0,

      pendingParticipants:
        statisticsResponse.participantes_pendentes || 0,

      availableNumbers:
        statisticsResponse.numeros_disponiveis || 0,

      openedNumbers:
        statisticsResponse.numeros_abertos || 0,

      availablePrizes:
        statisticsResponse.premios_disponiveis || 0,

      deliveredPrizes:
        statisticsResponse.premios_entregues || 0
    }

    participants.value = participantsResponse.map(
      participant => ({
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
              : '-',

        prize:
          participant.premio || '-',

        date:
          participant.participou_em
            ? formatDateTime(participant.participou_em)
            : '-'
      })
    )

    winners.value = winnersResponse.map(
      winner => ({
        id: winner.participacao_id,
        userId: winner.usuario_id,
        name: winner.nome || '',
        phone: winner.telefone || '',
        number: winner.numero,
        prize: winner.premio || '-',

        prizeStatus:
          winner.entrega_estado === 'entregue'
            ? 'Entregue'
            : 'Pendente',

        date:
          winner.data_hora
            ? formatDateTime(winner.data_hora)
            : '-'
      })
    )

  } catch (error) {
    console.error(
      'Erro ao carregar Dashboard:',
      error
    )
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
async function deliverPrize(winner) {
  const confirmed = window.confirm(
    `Confirmar a entrega do prémio "${winner.prize}" a ${winner.name}?`
  )

  if (!confirmed) return

  const token = localStorage.getItem('adminToken')

  try {
    await markPrizeDelivered(winner.number, token)

    await loadDashboard()

    alert('Prémio marcado como entregue.')
  } catch (error) {
    console.error('Erro ao marcar prémio como entregue:', error)
    alert(error.message)
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
            <h1>Painel de Controlo</h1>
            <p>Monitorização em tempo real da campanha</p>
          </div>

          <button
            type="button"
            class="settings-button"
            title="Abrir Gestão da Campanha"
            aria-label="Abrir Gestão da Campanha"
            @click="emit('open-management')"
          >
            <svg
              width="22"
              height="22"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="12" cy="12" r="3" />
              <path
                d="M19.4 15a1.7 1.7 0 00.34 1.88l.06.06a2 2 0 01-2.83 2.83l-.06-.06A1.7 1.7 0 0015 19.4a1.7 1.7 0 00-1 .6 1.7 1.7 0 00-.4 1.1V21a2 2 0 01-4 0v-.09A1.7 1.7 0 008.6 19.4a1.7 1.7 0 00-1.88.34l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.7 1.7 0 004.6 15a1.7 1.7 0 00-.6-1 1.7 1.7 0 00-1.1-.4H3a2 2 0 010-4h.09A1.7 1.7 0 004.6 8.6a1.7 1.7 0 00-.34-1.88l-.06-.06a2 2 0 012.83-2.83l.06.06A1.7 1.7 0 009 4.6a1.7 1.7 0 001-.6 1.7 1.7 0 00.4-1.1V3a2 2 0 014 0v.09a1.7 1.7 0 001 1.51 1.7 1.7 0 001.88-.34l.06-.06a2 2 0 012.83 2.83l-.06.06A1.7 1.7 0 0019.4 9a1.7 1.7 0 00.6 1 1.7 1.7 0 001.1.4H21a2 2 0 010 4h-.09A1.7 1.7 0 0019.4 15z"
              />
            </svg>
          </button>
        </div>

        <div class="header-actions">
  <button
    type="button"
    class="secondary-action"
    @click="refreshDashboard"
  >
    Actualizar
  </button>

  <button
    type="button"
    class="secondary-action"
    @click="exportExcel"
  >
    Exportar Excel
  </button>

  <button
    type="button"
    class="primary-action"
    @click="exportPDF"
  >
    Exportar PDF
  </button>

  <span
    v-if="admin"
    class="admin-name"
  >
    {{ admin.nome || admin.name || admin.telefone }}
  </span>

  <button
    type="button"
    class="logout-button"
    @click="handleLogout"
  >
    Sair
  </button>
</div>
      </div>
    </header>

    <main class="dashboard-content">
      <section class="statistics-grid">
        <article class="stat-card">
          <span class="stat-label">Total de Participantes</span>
          <strong>{{ statistics.totalParticipants }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">Participantes Validados</span>
          <strong>{{ statistics.validatedParticipants }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">Participantes Pendentes</span>
          <strong>{{ statistics.pendingParticipants }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">Números Disponíveis</span>
          <strong>{{ statistics.availableNumbers }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">Números Abertos</span>
          <strong>{{ statistics.openedNumbers }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">Prémios Disponíveis</span>
          <strong>{{ statistics.availablePrizes }}</strong>
        </article>

        <article class="stat-card">
          <span class="stat-label">Prémios Entregues</span>
          <strong class="delivered-number">
            {{ statistics.deliveredPrizes }}
          </strong>
        </article>
      </section>

      <section class="table-card">
        <div class="table-header">
          <div>
            <h2>Participantes</h2>
            <p>Consulta dos registos e resultados da campanha</p>
          </div>

          <div class="search-area">
            <input
              v-model="search"
              type="search"
              placeholder="Pesquisar por nome"
            />

            <input
              v-model="phoneSearch"
              type="search"
              placeholder="Pesquisar por telemóvel"
            />
          </div>
        </div>

        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Participante</th>
                <th>Telemóvel</th>
                <th>Estado</th>
                <th>Número</th>
                <th>Resultado</th>
                <th>Prémio</th>
                <th>Data e Hora</th>
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
                    {{ participant.status }}
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
                    {{ participant.result }}
                  </span>

                  <span v-else>-</span>
                </td>

                <td>{{ participant.prize }}</td>
                <td>{{ participant.date }}</td>
              </tr>

              <tr v-if="filteredParticipants.length === 0">
                <td colspan="7" class="empty-message">
                  Nenhum participante encontrado.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="table-card winners-table">
        <div class="table-header">
          <div>
            <h2>Vencedores</h2>
            <p>Participantes premiados durante a campanha</p>
          </div>
        </div>

        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Participante</th>
                <th>Telemóvel</th>
                <th>Número Premiado</th>
                <th>Prémio</th>
                <th>Estado da Entrega</th>
                <th>Data e Hora</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="winnerItem in winners"
                :key="winnerItem.id"
              >
                <td class="participant-cell">
                  {{ winnerItem.name }}
                </td>

                <td>{{ winnerItem.phone }}</td>
                <td>{{ winnerItem.number }}</td>

                <td>
                  <span class="prize-name">
                    {{ winnerItem.prize }}
                  </span>
                </td>

                <td>
                  <span
                    class="delivery-badge"
                    :class="{
  delivered: winnerItem.prizeStatus === 'Entregue',
  'delivery-pending': winnerItem.prizeStatus === 'Pendente'
}"
                  >
                    {{ winnerItem.prizeStatus }}
                  </span>
                </td>

                <td>{{ winnerItem.date }}</td>
              </tr>

              <tr v-if="winners.length === 0">
                <td colspan="6" class="empty-message">
                  Ainda não existem vencedores.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="activity-card">
        <div class="activity-heading">
          <h2>Actividade Recente</h2>
          <span>Últimos registos da campanha</span>
        </div>

        <div class="activity-list">
          <div class="activity-item">
            <span class="activity-dot winner-dot"></span>

            <p>
              <strong>Ana Manuel</strong>
              venceu um Smartphone no número 17.
            </p>

            <time>09:15</time>
          </div>

          <div class="activity-item">
            <span class="activity-dot"></span>

            <p>
              <strong>Carlos João</strong>
              concluiu a sua participação.
            </p>

            <time>09:23</time>
          </div>

          <div class="activity-item">
            <span class="activity-dot pending-dot"></span>

            <p>
              <strong>Marta António</strong>
              aguarda validação por OTP.
            </p>

            <time>09:30</time>
          </div>
        </div>
      </section>
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

.admin-name {
  color: #ffffff;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
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

.settings-button {
  width: 46px;
  height: 46px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.38);
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  cursor: pointer;
  transition:
    background 0.15s ease,
    transform 0.15s ease;
}

.settings-button:hover {
  background: rgba(255, 255, 255, 0.22);
  transform: rotate(25deg);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 9px;
}

.header-actions button {
  min-height: 40px;
  padding: 0 16px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
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

.logout-button {
  border: 1px solid rgba(255, 255, 255, 0.38);
  background: transparent;
  color: #ffffff;
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

.table-wrapper {
  width: 100%;
  overflow-x: auto;
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

.empty-message {
  padding: 30px;
  text-align: center;
  color: #9ca3af;
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
  padding: 7px 24px;
}

.activity-item {
  min-height: 58px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid #f0f0f5;
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
</style>