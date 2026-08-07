<script setup>
import { computed, onMounted, ref } from 'vue'

import {
  resetCampaign as resetCampaignApi,
  getPrizes,
  getActiveCampaignAdmin,
  updateCampaign,
  activateCampaign as activateCampaignApi,
  pauseCampaign as pauseCampaignApi,
  closeCampaignApi,
  createPrize,
  updatePrize,
  deletePrize
} from '../services/api'

const emit = defineEmits(['back-dashboard'])

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

const prizes = ref([])

const showPrizeForm = ref(false)
const editingPrizeId = ref(null)

const prizeForm = ref({
  winningNumber: '',
  name: '',
  scheduledDay: '',
  status: 'Disponível'
})

const campaignStatusLabel = computed(() => campaign.value.status)


// ===============================
// CAMPANHA
// ===============================

async function saveCampaign() {
  const token = localStorage.getItem('adminToken')

  if (!campaign.value.id) {
    alert('Não foi possível identificar a campanha activa.')
    return
  }

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

    alert('Campanha actualizada com sucesso.')
  } catch (error) {
    alert(error.message)
  }
}

function cancelChanges() {
  alert('As alterações foram canceladas.')
}


// ===============================
// CARREGAR PRÉMIOS
// ===============================

async function loadPrizes() {
  const response = await getPrizes()

  const data = Array.isArray(response)
    ? response
    : response.premios || response.data || []

  prizes.value = data.map(prize => ({
    id: prize.premio_id || prize.id,
    winningNumber: prize.numero,
    name: prize.descricao || '',
    scheduledDay: prize.data_programada || '',
    status: prize.entregue
      ? 'Entregue'
      : (prize.estado || 'Disponível')
  }))
}


// ===============================
// FORMULÁRIO DE PRÉMIO
// ===============================

function openPrizeForm() {
  editingPrizeId.value = null

  prizeForm.value = {
    winningNumber: '',
    name: '',
    scheduledDay: '',
    status: 'Disponível'
  }

  showPrizeForm.value = true
}

function editPrize(prize) {
  editingPrizeId.value = prize.id

  prizeForm.value = {
    winningNumber: prize.winningNumber,
    name: prize.name,
    scheduledDay: prize.scheduledDay || '',
    status: prize.status
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

async function savePrize() {
  if (
    !prizeForm.value.winningNumber ||
    !prizeForm.value.name.trim()
  ) {
    alert('Preencha os campos obrigatórios.')
    return
  }

  const token = localStorage.getItem('adminToken')

  try {
    if (editingPrizeId.value !== null) {
      await updatePrize(
        prizeForm.value.winningNumber,
        {
          descricao: prizeForm.value.name.trim(),
          data_programada:
            prizeForm.value.scheduledDay || null,
          entregue:
            prizeForm.value.status === 'Entregue'
        },
        token
      )

      alert('Prémio actualizado com sucesso.')
    } else {
      await createPrize(
        {
          numero: Number(
            prizeForm.value.winningNumber
          ),
          descricao:
            prizeForm.value.name.trim(),
          data_programada:
            prizeForm.value.scheduledDay || null
        },
        token
      )

      alert('Prémio adicionado com sucesso.')
    }

    await loadPrizes()
    closePrizeForm()

  } catch (error) {
    alert(error.message)
  }
}


// ===============================
// REMOVER PRÉMIO
// ===============================

async function removePrize(numero) {
  const confirmed = window.confirm(
    'Tem a certeza de que pretende remover este prémio?'
  )

  if (!confirmed) return

  const token = localStorage.getItem('adminToken')

  try {
    await deletePrize(numero, token)

    await loadPrizes()

    alert('Prémio removido com sucesso.')
  } catch (error) {
    alert(error.message)
  }
}


// ===============================
// ACTIVAR CAMPANHA
// ===============================

async function activateCampaign() {
  const token = localStorage.getItem('adminToken')

  try {
    await activateCampaignApi(
      campaign.value.id,
      token
    )

    campaign.value.status = 'Activa'

    alert('A campanha foi activada.')
  } catch (error) {
    alert(error.message)
  }
}


// ===============================
// PAUSAR CAMPANHA
// ===============================

async function pauseCampaign() {
  const token = localStorage.getItem('adminToken')

  try {
    await pauseCampaignApi(
      campaign.value.id,
      token
    )

    campaign.value.status = 'Pausada'

    alert('A campanha foi pausada.')
  } catch (error) {
    alert(error.message)
  }
}


// ===============================
// ENCERRAR CAMPANHA
// ===============================

async function closeCampaign() {
  const confirmed = window.confirm(
    'Tem a certeza de que pretende encerrar a campanha?'
  )

  if (!confirmed) return

  const token = localStorage.getItem('adminToken')

  try {
    await closeCampaignApi(
      campaign.value.id,
      token
    )

    campaign.value.status = 'Encerrada'

    alert('A campanha foi encerrada.')
  } catch (error) {
    alert(error.message)
  }
}


// ===============================
// RESET DA CAMPANHA
// ===============================

async function resetCampaign() {
  const confirmed = window.confirm(
    'Esta acção irá encerrar o ciclo actual, criar um novo ciclo com 1000 números e manter o histórico. Pretende continuar?'
  )

  if (!confirmed) return

  const token = localStorage.getItem('adminToken')

  try {
    await resetCampaignApi(token)

    alert('A campanha foi reiniciada com sucesso.')

    // Recarrega os dados da nova campanha
    const campaignResponse =
      await getActiveCampaignAdmin(token)

    campaign.value = {
      id: campaignResponse.id,
      name: campaignResponse.nome || '',
      status: campaignResponse.estado || '',
      startDate:
        campaignResponse.data_inicio || '',
      endDate:
        campaignResponse.data_fim || '',
      totalNumbers:
        campaignResponse.total_quadrados || 1000,
      totalPrizes:
        campaignResponse.total_premios || 10,
      otpValidity:
        campaignResponse.otp_validade_minutos || 5,
      maximumOtpAttempts:
        campaign.value.maximumOtpAttempts
    }

    await loadPrizes()

  } catch (error) {
    alert(error.message)
  }
}


// ===============================
// RELATÓRIOS
// Ainda aguardam endpoints
// ===============================

function exportParticipants() {
  alert('Exportação dos participantes iniciada.')
}

function exportWinners() {
  alert('Exportação dos vencedores iniciada.')
}

function exportAudit() {
  alert('Exportação dos registos de auditoria iniciada.')
}

function downloadCampaignReport() {
  alert('Relatório da campanha em preparação.')
}


// ===============================
// AO ABRIR A TELA
// ===============================

onMounted(async () => {
  const token = localStorage.getItem('adminToken')

  if (!token) {
    alert('Sessão administrativa não encontrada.')
    return
  }

  try {
    // CARREGAR CAMPANHA ACTIVA
    const campaignResponse =
      await getActiveCampaignAdmin(token)

    console.log(
      'Campanha activa:',
      campaignResponse
    )

    campaign.value = {
      id: campaignResponse.id,
      name: campaignResponse.nome || '',
      status: campaignResponse.estado || '',
      startDate:
        campaignResponse.data_inicio || '',
      endDate:
        campaignResponse.data_fim || '',
      totalNumbers:
        campaignResponse.total_quadrados || 1000,
      totalPrizes:
        campaignResponse.total_premios || 10,
      otpValidity:
        campaignResponse.otp_validade_minutos || 5,
      maximumOtpAttempts:
        campaign.value.maximumOtpAttempts
    }

    // CARREGAR PRÉMIOS
    await loadPrizes()

  } catch (error) {
    console.error(
      'Erro ao carregar gestão da campanha:',
      error
    )

    alert(error.message)
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
              active: campaign.status === 'Activa',
              paused: campaign.status === 'Pausada',
              draft: campaign.status === 'Rascunho',
              closed: campaign.status === 'Encerrada'
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
            <label for="campaign-status">Estado</label>

            <select
              id="campaign-status"
              v-model="campaign.status"
            >
              <option>Rascunho</option>
              <option>Activa</option>
              <option>Pausada</option>
              <option>Encerrada</option>
            </select>
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
            @click="cancelChanges"
          >
            Cancelar
          </button>

          <button
            type="button"
            class="primary-button"
            @click="saveCampaign"
          >
            Guardar Alterações
          </button>
        </div>
      </section>

      <!-- Configuração dos prémios -->
      <section class="management-card">
        <div class="section-heading">
          <div>
            <h2>Configuração dos Prémios</h2>

            <p>
              Associe cada prémio a um número e a um dia da campanha.
            </p>
          </div>

          <button
            type="button"
            class="primary-button"
            @click="openPrizeForm"
          >
            Adicionar Prémio
          </button>
        </div>

        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Número Premiado</th>
                <th>Prémio</th>
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
            @click="activateCampaign"
          >
            <span class="control-icon">▶</span>

            <span>
              <strong>Activar Campanha</strong>
              <small>Permitir novas participações</small>
            </span>
          </button>

          <button
            type="button"
            class="control-button pause-button"
            @click="pauseCampaign"
          >
            <span class="control-icon">Ⅱ</span>

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

.campaign-status.draft {
  background: #f3f4f6;
  color: #4b5563;
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
  border-radius: 7px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.primary-button {
  border: 1px solid #27227f;
  background: #27227f;
  color: #ffffff;
}

.primary-button:hover {
  background: #1c1860;
}

.outline-button {
  border: 1px solid #d1d5db;
  background: #ffffff;
  color: #4b5563;
}

.outline-button:hover {
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
</style>