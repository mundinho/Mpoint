<script setup>
import { computed, onMounted, ref } from 'vue'
import { getCampaigns, createCampaign as createCampaignApi } from '../services/api'
import { campaignHostSuffix, campaignLink, isValidCampaignSlug, slugify } from '../site'
import LoadingSpinner from './LoadingSpinner.vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()
const emit = defineEmits(['select', 'logout', 'toast', 'confirm'])

function showToast(message, type = 'error') {
  emit('toast', { message, type })
}


const campaigns = ref([])
const isLoading = ref(false)
const isCreating = ref(false)
const loadError = ref(false)


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

// Formulário de nova campanha (nome, link e total de números)
const showCreateForm = ref(false)
const createForm = ref({ name: '', slug: '', totalNumbers: 1000 })
const slugEdited = ref(false)
const createError = ref('')

function createCampaign() {
  createForm.value = { name: '', slug: '', totalNumbers: 1000 }
  slugEdited.value = false
  createError.value = ''
  showCreateForm.value = true
}

function closeCreateForm() {
  if (!isCreating.value) showCreateForm.value = false
}

// Enquanto o link não for editado à mão, acompanha o nome
function onNameInput() {
  if (!slugEdited.value) createForm.value.slug = slugify(createForm.value.name)
}

function onSlugInput() {
  slugEdited.value = true
  createForm.value.slug = createForm.value.slug.toLowerCase().replace(/[^a-z0-9-]/g, '')
}

const slugPreview = computed(() =>
  campaignLink(createForm.value.slug || 'link') || ''
)

async function submitCreate() {
  if (isCreating.value) return

  const name = createForm.value.name.trim()
  const slug = createForm.value.slug.trim()
  const totalNumbers = Number(createForm.value.totalNumbers)

  if (!name) {
    createError.value = t('campaignSelect.form.errors.name')
    return
  }

  if (!isValidCampaignSlug(slug)) {
    createError.value = t('campaignSelect.form.errors.slug')
    return
  }

  if (!Number.isInteger(totalNumbers) || totalNumbers < 1 || totalNumbers > 10000) {
    createError.value = t('campaignSelect.form.errors.total')
    return
  }

  createError.value = ''
  isCreating.value = true

  const token = localStorage.getItem('adminToken')

  try {
    const campaign = await createCampaignApi({ nome: name, slug, total_quadrados: totalNumbers }, token)

    showToast(t('campaignSelect.messages.created'), 'success')
    showCreateForm.value = false
    emit('select', campaign.id)
  } catch (error) {
    createError.value = error.message
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
          <span v-if="campaign.link" class="campaign-link">
  {{ campaign.link.replace('https://', '') }}
</span>
          <span v-else class="campaign-link missing">
  {{ t('campaignSelect.noLink') }}
</span>
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

    <div
      v-if="showCreateForm"
      class="create-overlay"
      @click.self="closeCreateForm"
    >
      <form
        class="create-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-title"
        @submit.prevent="submitCreate"
      >
        <h2 id="create-title">{{ t('campaignSelect.form.title') }}</h2>
        <p class="create-hint">{{ t('campaignSelect.form.hint') }}</p>

        <label for="create-name">{{ t('campaignSelect.form.name') }}</label>
        <input
          id="create-name"
          v-model="createForm.name"
          type="text"
          maxlength="255"
          :placeholder="t('campaignSelect.form.namePlaceholder')"
          autofocus
          @input="onNameInput"
        />

        <label for="create-slug">{{ t('campaignSelect.form.slug') }}</label>
        <div class="slug-field">
          <span class="slug-prefix">https://</span>
          <input
            id="create-slug"
            v-model="createForm.slug"
            type="text"
            maxlength="40"
            autocomplete="off"
            spellcheck="false"
            placeholder="bci"
            @input="onSlugInput"
          />
          <span class="slug-suffix">.{{ campaignHostSuffix }}</span>
        </div>
        <small class="slug-help">{{ t('campaignSelect.form.slugHelp') }}</small>
        <small class="slug-preview">{{ slugPreview }}</small>

        <label for="create-total">{{ t('campaignSelect.form.total') }}</label>
        <input
          id="create-total"
          v-model.number="createForm.totalNumbers"
          type="number"
          min="1"
          max="10000"
        />

        <p v-if="createError" class="create-error" role="alert">{{ createError }}</p>

        <div class="create-actions">
          <button type="button" class="secondary-action dark" :disabled="isCreating" @click="closeCreateForm">
            {{ t('campaignSelect.form.cancel') }}
          </button>
          <button type="submit" class="primary-action" :disabled="isCreating">
            <LoadingSpinner v-if="isCreating" :size="12" />
            {{ isCreating ? t('campaignSelect.creating') : t('campaignSelect.form.submit') }}
          </button>
        </div>
      </form>
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

.select-page {
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #faf7f8;
  color: #111827;
  font-family: Arial, Helvetica, sans-serif;
}

.top-header {
  position: relative;
  overflow: hidden;
  flex-shrink: 0;
  background: #840435;
}

.header-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 230px;
  height: 100%;
  background: #6a032a;
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
  border: 1px solid #840435;
  background: #840435;
  color: #ffffff;
}

.primary-action:hover:not(:disabled) {
  background: #4a021d;
}

.state-message {
  padding: 60px 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: 1px solid #ece4e7;
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
  color: #840435;
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
  border: 1px solid #ece4e7;
  border-radius: 9px;
  background: #ffffff;
  box-shadow: 0 2px 9px rgba(132, 4, 53, 0.05);
  text-align: left;
  cursor: pointer;
  transition: border-color 0.15s ease, transform 0.15s ease;
}

.campaign-card:hover {
  border-color: #840435;
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
  border-top: 1px solid #f1eaed;
}

.campaign-card-stats div {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.campaign-card-stats strong {
  color: #840435;
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
  background: #840435;
}

.bottom-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 180px;
  height: 100%;
  background: #6a032a;
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
.campaign-link {
  color: #840435;
  font-weight: 700;
  word-break: break-all;
}

.campaign-link.missing {
  color: #b45309;
}

.create-overlay {
  position: fixed;
  inset: 0;
  z-index: 200;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(40, 2, 16, 0.55);
}

.create-modal {
  width: 100%;
  max-width: 520px;
  max-height: calc(100vh - 32px);
  overflow-y: auto;
  padding: 28px;
  display: flex;
  flex-direction: column;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 24px 60px rgba(40, 2, 16, 0.3);
}

.create-modal h2 {
  margin: 0 0 6px;
  font-size: 22px;
}

.create-hint {
  margin: 0 0 18px;
  color: #6b7280;
  font-size: 14px;
  line-height: 1.5;
}

.create-modal label {
  margin: 14px 0 6px;
  color: #374151;
  font-size: 13px;
  font-weight: 700;
}

.create-modal input {
  min-height: 44px;
  padding: 0 12px;
  border: 1px solid #ece4e7;
  border-radius: 7px;
  font-family: inherit;
  font-size: 15px;
}

.create-modal input:focus {
  outline: 2px solid #840435;
  outline-offset: 1px;
}

.slug-field {
  display: flex;
  align-items: stretch;
  overflow: hidden;
  border: 1px solid #ece4e7;
  border-radius: 7px;
}

.slug-field:focus-within {
  outline: 2px solid #840435;
  outline-offset: 1px;
}

.slug-field input {
  min-width: 0;
  flex: 1;
  border: none;
  border-radius: 0;
}

.slug-field input:focus {
  outline: none;
}

.slug-prefix,
.slug-suffix {
  display: flex;
  align-items: center;
  padding: 0 10px;
  background: #faf7f8;
  color: #6b7280;
  font-size: 13px;
  white-space: nowrap;
}

.slug-help,
.slug-preview {
  margin-top: 6px;
  color: #6b7280;
  font-size: 12px;
}

.slug-preview {
  color: #840435;
  font-weight: 700;
  word-break: break-all;
}

.create-error {
  margin: 16px 0 0;
  padding: 10px 12px;
  border-left: 3px solid #b91c1c;
  border-radius: 4px;
  background: #fef2f2;
  color: #991b1b;
  font-size: 14px;
}

.create-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 22px;
}

.create-actions button {
  min-height: 44px;
  padding: 0 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid #840435;
  border-radius: 7px;
  background: #840435;
  color: #ffffff;
  font-family: inherit;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.create-actions button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.create-actions button.secondary-action.dark {
  border-color: #e9dfe3;
  background: #ffffff;
  color: #374151;
}

.secondary-action.dark {
  border-color: #ece4e7;
  background: #ffffff;
  color: #374151;
}

@media (max-width: 520px) {
  .slug-prefix {
    display: none;
  }

  .create-actions {
    flex-direction: column-reverse;
  }
}
</style>
