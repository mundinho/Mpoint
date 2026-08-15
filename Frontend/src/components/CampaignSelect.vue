<script setup>
import { computed, onMounted, ref } from 'vue'
import { getCampaigns, resetCampaign } from '../services/api'
import LoadingSpinner from './LoadingSpinner.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()
const emit = defineEmits(['select', 'logout', 'toast', 'confirm'])

function showToast(message, type = 'error') {
  emit('toast', { message, type })
}

function requestConfirm(message, action) {
  emit('confirm', { message, action })
}

const campaigns = ref([])
const isLoading = ref(false)
const isCreating = ref(false)
const loadError = ref(false)

const activeCampaign = computed(() =>
  campaigns.value.find(campaign => campaign.estado === 'ativa') || null
)

function statusLabel(estado) {
  return {
    ativa: t('campaignSelect.status.active'),
    pausada: t('campaignSelect.status.paused'),
    encerrada: t('campaignSelect.status.closed')
  }[estado] || estado
}

function formatDate(value) {
  if (!value) return '-'

  return new Date(value).toLocaleString(locale.value === 'pt' ? 'pt-PT' : 'en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function loadCampaigns() {
  const token = localStorage.getItem('adminToken')

  if (!token) {
    emit('logout')
    return
  }

  isLoading.value = true
  loadError.value = false

  try {
    const response = await getCampaigns(token)

    campaigns.value = Array.isArray(response)
      ? response
      : response.data || []
  } catch (error) {
    if (error.status === 401) {
     showToast(
  t('campaignSelect.messages.sessionExpired'),
  'error'
)
      localStorage.removeItem('adminToken')
      emit('logout')
      return
    }

    loadError.value = true

    showToast(
  error.message || t('campaignSelect.messages.loadError'),
  'error'
)
  } finally {
    isLoading.value = false
  }
}

function selectCampaign(campaign) {
  emit('select', campaign.id)
}

function createCampaign() {
 const message = activeCampaign.value
  ? t('campaignSelect.messages.createWithActive', {
      campaign:
        activeCampaign.value.nome ||
        `#${activeCampaign.value.id}`
    })
  : t('campaignSelect.messages.createWithoutActive')

  requestConfirm(message, executeCreateCampaign)
}

async function executeCreateCampaign() {
  if (isCreating.value) return

  isCreating.value = true

  const token = localStorage.getItem('adminToken')

  try {
    const campaign = await resetCampaign(token)

    showToast(
  t('campaignSelect.messages.created'),
  'success'
)

    emit('select', campaign.id)
  } catch (error) {
    showToast(error.message, 'error')
  } finally {
    isCreating.value = false
  }
}

onMounted(loadCampaigns)
</script>

<template>
  <div class="select-page">
    <header class="top-header">
      <div class="header-accent"></div>

      <div class="header-content">
        <div>
        <h1>{{ t('campaignSelect.title') }}</h1>

<p>
  {{ t('campaignSelect.description') }}
</p>
        </div>

        <button
          type="button"
          class="logout-button"
          @click="emit('logout')"
        >
          {{ t('campaignSelect.logout') }}
        </button>
      </div>
    </header>

    <main class="select-content">
      <div class="toolbar">
        <button
          type="button"
          class="secondary-action"
          :disabled="isLoading"
          @click="loadCampaigns"
        >
          <LoadingSpinner
            v-if="isLoading"
            color="purple"
            :size="12"
          />
          {{ t('campaignSelect.refresh') }}
        </button>

        <button
          type="button"
          class="primary-action"
          :disabled="isCreating"
          @click="createCampaign"
        >
          <LoadingSpinner v-if="isCreating" :size="12" />
          {{
  isCreating
    ? t('campaignSelect.creating')
    : t('campaignSelect.createCampaign')
}}
        </button>
      </div>

      <div
        v-if="isLoading && campaigns.length === 0"
        class="state-message"
      >
      <LoadingSpinner color="purple" :size="20" />
{{ t('campaignSelect.loading') }}
      </div>

      <div
        v-else-if="loadError && campaigns.length === 0"
        class="state-message error"
      >
       {{ t('campaignSelect.loadError') }}
        <button
          type="button"
          class="retry-link"
          @click="loadCampaigns"
        >
          {{ t('campaignSelect.retry') }}
        </button>
      </div>

      <div
        v-else-if="campaigns.length === 0"
        class="state-message"
      >
        {{ t('campaignSelect.empty') }}
      </div>

      <div
        v-else
        class="campaign-grid"
      >
        <button
          v-for="campaign in campaigns"
          :key="campaign.id"
          type="button"
          class="campaign-card"
          @click="selectCampaign(campaign)"
        >
          <div class="campaign-card-header">
          <strong>
  {{ campaign.nome || t('campaignSelect.defaultCampaignName', { id: campaign.id }) }}
</strong>

            <span
              class="status-badge"
              :class="campaign.estado"
            >
              {{ statusLabel(campaign.estado) }}
            </span>
          </div>

          <div class="campaign-card-meta">
          <span>
  {{ t('campaignSelect.start') }}: {{ formatDate(campaign.data_inicio) }}
</span>

<span>
  {{ t('campaignSelect.mode') }}:
  {{
    campaign.modo_distribuicao === 'manual'
      ? t('campaignSelect.manual')
      : t('campaignSelect.random')
  }}
</span>
          </div>

          <div class="campaign-card-stats">
            <div>
              <strong>{{ campaign.quadrados_abertos ?? 0 }}/{{ campaign.total_quadrados ?? 0 }}</strong>
             <span>{{ t('campaignSelect.openedNumbers') }}</span>
            </div>

            <div>
              <strong>{{ campaign.premios_configurados ?? 0 }}</strong>
              <span>{{ t('campaignSelect.prizes') }}</span>
            </div>

            <div>
              <strong>{{ campaign.participantes ?? 0 }}</strong>
             <span>{{ t('campaignSelect.participants') }}</span>
            </div>
          </div>
        </button>
      </div>
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

.select-page {
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
  min-height: 120px;
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
  max-width: 520px;
  color: rgba(255, 255, 255, 0.65);
  font-size: 13px;
  line-height: 1.5;
}

.logout-button {
  min-height: 40px;
  padding: 0 16px;
  flex-shrink: 0;
  border: 1px solid rgba(255, 255, 255, 0.38);
  border-radius: 7px;
  background: transparent;
  color: #ffffff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.select-content {
  width: 100%;
  max-width: 1200px;
  flex: 1;
  margin: 0 auto;
  padding: 30px 38px 40px;
}

.toolbar {
  margin-bottom: 22px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.toolbar button {
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

.toolbar button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.secondary-action {
  border: 1px solid #d1d5db;
  background: #ffffff;
  color: #4b5563;
}

.secondary-action:hover:not(:disabled) {
  background: #f9fafb;
}

.primary-action {
  border: 1px solid #27227f;
  background: #27227f;
  color: #ffffff;
}

.primary-action:hover:not(:disabled) {
  background: #1c1860;
}

.state-message {
  padding: 60px 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: 1px solid #e3e3ef;
  border-radius: 9px;
  background: #ffffff;
  color: #9ca3af;
  font-size: 14px;
  text-align: center;
}

.state-message.error {
  color: #dc2626;
}

.retry-link {
  border: 0;
  background: transparent;
  color: #27227f;
  font-weight: 700;
  text-decoration: underline;
  cursor: pointer;
}

.campaign-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

.campaign-card {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  border: 1px solid #e3e3ef;
  border-radius: 9px;
  background: #ffffff;
  box-shadow: 0 2px 9px rgba(39, 34, 127, 0.05);
  text-align: left;
  cursor: pointer;
  transition: border-color 0.15s ease, transform 0.15s ease;
}

.campaign-card:hover {
  border-color: #27227f;
  transform: translateY(-2px);
}

.campaign-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.campaign-card-header strong {
  color: #111827;
  font-size: 16px;
}

.status-badge {
  flex-shrink: 0;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.status-badge.ativa {
  background: #dcfce7;
  color: #166534;
}

.status-badge.pausada {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.encerrada {
  background: #f3f4f6;
  color: #4b5563;
}

.campaign-card-meta {
  display: flex;
  flex-direction: column;
  gap: 3px;
  color: #9ca3af;
  font-size: 12px;
}

.campaign-card-stats {
  display: flex;
  gap: 18px;
  padding-top: 12px;
  border-top: 1px solid #ececf4;
}

.campaign-card-stats div {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.campaign-card-stats strong {
  color: #27227f;
  font-size: 16px;
}

.campaign-card-stats span {
  color: #9ca3af;
  font-size: 11px;
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

@media (max-width: 650px) {
  .header-content {
    padding: 24px 20px;
    flex-direction: column;
    align-items: flex-start;
  }

  .select-content {
    padding: 20px 15px 30px;
  }

  .campaign-grid {
    grid-template-columns: 1fr;
  }
}
</style>
