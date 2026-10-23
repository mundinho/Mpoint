<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  getAdministrators,
  createAdministrator,
  updateAdministrator,
  deleteAdministrator
} from '../services/api'
import { isValidMozPhone, normalizeMozPhone } from '../utils/telefone'
import LoadingSpinner from './LoadingSpinner.vue'

// Gestão dos administradores do painel (só para o administrador master)
const props = defineProps({
  admin: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['back', 'logout', 'toast', 'confirm'])
const { t } = useI18n()

const administrators = ref([])
const campaigns = ref([])
const isLoading = ref(false)
const loadError = ref(false)

function showToast(message, type = 'error') {
  emit('toast', { message, type })
}

function token() {
  return localStorage.getItem('adminToken')
}

async function load() {
  if (!token()) {
    emit('logout')
    return
  }

  isLoading.value = true
  loadError.value = false

  try {
    const response = await getAdministrators(token())
    administrators.value = response.administradores || []
    campaigns.value = response.campanhas || []
  } catch (error) {
    if (error.status === 401) {
      emit('logout')
      return
    }

    loadError.value = true
    showToast(error.message)
  } finally {
    isLoading.value = false
  }
}

const campaignById = computed(() =>
  Object.fromEntries(campaigns.value.map(campaign => [campaign.id, campaign]))
)

function campaignLabel(campaign) {
  if (!campaign) return ''
  const name = (campaign.nome || t('campaignSelect.defaultCampaignName', { id: campaign.id })).replace(/_/g, ' ')
  return campaign.slug ? `${name} · ${campaign.slug}` : name
}

function accessOf(administrator) {
  if (administrator.master) return 'master'
  if (administrator.todas_campanhas) return 'all'
  return 'selected'
}

function maskPhone(phone) {
  const local = String(phone || '').replace(/^258/, '')
  return local.replace(/^(\d{2})(\d{3})(\d{4})$/, '$1 $2 $3')
}

function statusLabel(estado) {
  const key = { ativa: 'active', pausada: 'paused', encerrada: 'closed' }[estado]
  return key ? t(`campaignSelect.status.${key}`) : estado
}

const isSelf = administrator => administrator.id === props.admin?.id

// Formulário (criar ou editar)
const showForm = ref(false)
const isSaving = ref(false)
const formError = ref('')
const form = ref(emptyForm())

function emptyForm() {
  return { id: null, name: '', phone: '', access: 'selected', campaigns: [], active: true }
}

function openCreate() {
  form.value = emptyForm()
  formError.value = ''
  showForm.value = true
}

function openEdit(administrator) {
  form.value = {
    id: administrator.id,
    name: administrator.nome,
    phone: maskPhone(administrator.telefone),
    access: accessOf(administrator),
    campaigns: [...(administrator.campanhas || [])],
    active: administrator.ativo
  }
  formError.value = ''
  showForm.value = true
}

function closeForm() {
  if (!isSaving.value) showForm.value = false
}

const editingSelf = computed(() => form.value.id !== null && form.value.id === props.admin?.id)

async function submitForm() {
  if (isSaving.value) return

  const name = form.value.name.trim()

  if (!name) {
    formError.value = t('admins.errors.name')
    return
  }

  if (!isValidMozPhone(form.value.phone)) {
    formError.value = t('admins.errors.phone')
    return
  }

  if (form.value.access === 'selected' && form.value.campaigns.length === 0) {
    formError.value = t('admins.errors.campaigns')
    return
  }

  const payload = {
    nome: name,
    telefone: normalizeMozPhone(form.value.phone),
    master: form.value.access === 'master',
    todas_campanhas: form.value.access !== 'selected',
    campanhas: form.value.access === 'selected' ? form.value.campaigns : [],
    ativo: form.value.active
  }

  formError.value = ''
  isSaving.value = true

  try {
    if (form.value.id) {
      await updateAdministrator(form.value.id, payload, token())
      showToast(t('admins.messages.updated'), 'success')
    } else {
      await createAdministrator(payload, token())
      showToast(t('admins.messages.created'), 'success')
    }

    showForm.value = false
    await load()
  } catch (error) {
    formError.value = error.message
  } finally {
    isSaving.value = false
  }
}

function confirmRemove(administrator) {
  emit('confirm', {
    message: t('admins.messages.removeConfirm', { name: administrator.nome }),
    action: async () => {
      try {
        await deleteAdministrator(administrator.id, token())
        showToast(t('admins.messages.removed'), 'success')
        await load()
      } catch (error) {
        showToast(error.message)
      }
    }
  })
}

onMounted(load)
</script>

<template>
  <div class="admins-page">
    <header class="top-header">
      <div class="header-accent"></div>

      <div class="header-content">
        <div>
          <h1>{{ t('admins.title') }}</h1>
          <p>{{ t('admins.description') }}</p>
        </div>

        <button
          type="button"
          class="header-button"
          @click="emit('back')"
        >
          {{ t('admins.back') }}
        </button>
      </div>
    </header>

    <main class="admins-content">
      <div class="toolbar">
        <button
          type="button"
          class="secondary-action"
          :disabled="isLoading"
          @click="load"
        >
          <LoadingSpinner v-if="isLoading" color="purple" :size="12" />
          {{ t('campaignSelect.refresh') }}
        </button>

        <button
          type="button"
          class="primary-action"
          @click="openCreate"
        >
          {{ t('admins.add') }}
        </button>
      </div>

      <div
        v-if="isLoading && administrators.length === 0"
        class="state-message"
      >
        <LoadingSpinner color="purple" :size="20" />
        {{ t('admins.loading') }}
      </div>

      <div
        v-else-if="loadError && administrators.length === 0"
        class="state-message error"
      >
        {{ t('admins.loadError') }}
      </div>

      <ul
        v-else
        class="admin-list"
      >
        <li
          v-for="administrator in administrators"
          :key="administrator.id"
          class="admin-row"
          :class="{ inactive: !administrator.ativo }"
        >
          <div class="admin-identity">
            <strong>
              {{ administrator.nome }}
              <span v-if="isSelf(administrator)" class="self-tag">{{ t('admins.you') }}</span>
            </strong>
            <span class="phone">{{ maskPhone(administrator.telefone) }}</span>
          </div>

          <div class="admin-access">
            <span
              class="access-badge"
              :class="accessOf(administrator)"
            >
              {{ t(`admins.access.${accessOf(administrator)}`) }}
            </span>

            <span v-if="!administrator.ativo" class="access-badge inactive">
              {{ t('admins.inactive') }}
            </span>

            <ul
              v-if="accessOf(administrator) === 'selected'"
              class="campaign-tags"
            >
              <li
                v-for="id in administrator.campanhas"
                :key="id"
              >
                {{ campaignLabel(campaignById[id]) || `#${id}` }}
              </li>
              <li v-if="administrator.campanhas.length === 0" class="none">
                {{ t('admins.noCampaigns') }}
              </li>
            </ul>
          </div>

          <div class="admin-actions">
            <button type="button" class="link-button" @click="openEdit(administrator)">
              {{ t('admins.edit') }}
            </button>
            <button
              v-if="!isSelf(administrator)"
              type="button"
              class="link-button danger"
              @click="confirmRemove(administrator)"
            >
              {{ t('admins.remove') }}
            </button>
          </div>
        </li>
      </ul>
    </main>

    <div
      v-if="showForm"
      class="form-overlay"
      @click.self="closeForm"
    >
      <form
        class="form-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="admin-form-title"
        @submit.prevent="submitForm"
      >
        <h2 id="admin-form-title">
          {{ form.id ? t('admins.form.editTitle') : t('admins.form.createTitle') }}
        </h2>

        <label for="admin-name">{{ t('admins.form.name') }}</label>
        <input
          id="admin-name"
          v-model="form.name"
          type="text"
          maxlength="255"
          autocomplete="off"
        />

        <label for="admin-phone">{{ t('admins.form.phone') }}</label>
        <input
          id="admin-phone"
          v-model="form.phone"
          type="tel"
          inputmode="numeric"
          maxlength="16"
          placeholder="84 123 4567"
          autocomplete="off"
        />
        <small class="field-help">{{ t('admins.form.phoneHelp') }}</small>

        <fieldset class="access-options">
          <legend>{{ t('admins.form.access') }}</legend>

          <label
            v-for="option in ['selected', 'all', 'master']"
            :key="option"
            class="access-option"
            :class="{ disabled: editingSelf && option !== 'master' }"
          >
            <input
              v-model="form.access"
              type="radio"
              name="access"
              :value="option"
              :disabled="editingSelf && option !== 'master'"
            />
            <span>
              <strong>{{ t(`admins.access.${option}`) }}</strong>
              <small>{{ t(`admins.accessHelp.${option}`) }}</small>
            </span>
          </label>
        </fieldset>

        <fieldset
          v-if="form.access === 'selected'"
          class="campaign-options"
        >
          <legend>{{ t('admins.form.campaigns') }}</legend>

          <label
            v-for="campaign in campaigns"
            :key="campaign.id"
            class="campaign-option"
          >
            <input
              v-model="form.campaigns"
              type="checkbox"
              :value="campaign.id"
            />
            <span>{{ campaignLabel(campaign) }}</span>
            <small :class="['state', campaign.estado]">{{ statusLabel(campaign.estado) }}</small>
          </label>
        </fieldset>

        <label
          v-if="!editingSelf"
          class="active-toggle"
        >
          <input v-model="form.active" type="checkbox" />
          {{ t('admins.form.active') }}
        </label>

        <p v-if="formError" class="form-error" role="alert">{{ formError }}</p>

        <div class="form-actions">
          <button type="button" class="secondary-action" :disabled="isSaving" @click="closeForm">
            {{ t('admins.form.cancel') }}
          </button>
          <button type="submit" class="primary-action" :disabled="isSaving">
            <LoadingSpinner v-if="isSaving" :size="12" />
            {{ t('admins.form.save') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.admins-page {
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
  max-width: 560px;
  color: rgba(255, 255, 255, 0.7);
  font-size: 13px;
  line-height: 1.5;
}

.header-button {
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

.admins-content {
  width: 100%;
  max-width: 1000px;
  margin: 0 auto;
  padding: 30px 38px 40px;
}

.toolbar {
  margin-bottom: 22px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.toolbar button,
.form-actions button {
  min-height: 42px;
  padding: 0 17px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 7px;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.secondary-action {
  border: 1px solid #e9dfe3;
  background: #ffffff;
  color: #374151;
}

.primary-action {
  border: 1px solid #840435;
  background: #840435;
  color: #ffffff;
}

.primary-action:hover:not(:disabled) {
  background: #6a032a;
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
}

.state-message.error {
  color: #dc2626;
}

.admin-list {
  margin: 0;
  padding: 0;
  list-style: none;
  border: 1px solid #ece4e7;
  border-radius: 9px;
  background: #ffffff;
}

.admin-row {
  padding: 18px 20px;
  display: grid;
  grid-template-columns: minmax(180px, 1fr) minmax(0, 2fr) auto;
  align-items: center;
  gap: 18px;
}

.admin-row + .admin-row {
  border-top: 1px solid #f1eaed;
}

.admin-row.inactive {
  opacity: 0.6;
}

.admin-identity {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.admin-identity strong {
  font-size: 15px;
}

.phone {
  color: #6b7280;
  font-size: 13px;
}

.self-tag {
  margin-left: 6px;
  padding: 2px 6px;
  border-radius: 4px;
  background: #f6e8ee;
  color: #840435;
  font-size: 11px;
  font-weight: 700;
}

.admin-access {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.access-badge {
  padding: 4px 9px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 700;
  background: #f3f4f6;
  color: #374151;
}

.access-badge.master {
  background: #840435;
  color: #ffffff;
}

.access-badge.all {
  background: #f6e8ee;
  color: #840435;
}

.access-badge.inactive {
  background: #fef2f2;
  color: #991b1b;
}

.campaign-tags {
  margin: 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  list-style: none;
}

.campaign-tags li {
  padding: 4px 8px;
  border: 1px solid #ece4e7;
  border-radius: 4px;
  color: #4b5563;
  font-size: 12px;
}

.campaign-tags li.none {
  border-color: #fde68a;
  color: #b45309;
}

.admin-actions {
  display: flex;
  gap: 14px;
}

.link-button {
  padding: 0;
  border: none;
  background: none;
  color: #840435;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.link-button.danger {
  color: #b91c1c;
}

.form-overlay {
  position: fixed;
  inset: 0;
  z-index: 200;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(40, 2, 16, 0.55);
}

.form-modal {
  width: 100%;
  max-width: 540px;
  max-height: calc(100vh - 32px);
  overflow-y: auto;
  padding: 28px;
  display: flex;
  flex-direction: column;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 24px 60px rgba(40, 2, 16, 0.3);
}

.form-modal h2 {
  margin: 0 0 6px;
  font-size: 22px;
}

.form-modal > label {
  margin: 14px 0 6px;
  color: #374151;
  font-size: 13px;
  font-weight: 700;
}

.form-modal input[type='text'],
.form-modal input[type='tel'] {
  min-height: 44px;
  padding: 0 12px;
  border: 1px solid #ece4e7;
  border-radius: 7px;
  font-family: inherit;
  font-size: 15px;
}

.form-modal input:focus {
  outline: 2px solid #840435;
  outline-offset: 1px;
}

.field-help {
  margin-top: 6px;
  color: #6b7280;
  font-size: 12px;
}

fieldset {
  margin: 18px 0 0;
  padding: 0;
  border: none;
}

legend {
  margin-bottom: 8px;
  color: #374151;
  font-size: 13px;
  font-weight: 700;
}

.access-option {
  padding: 12px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  border: 1px solid #ece4e7;
  border-radius: 7px;
  cursor: pointer;
}

.access-option + .access-option {
  margin-top: 8px;
}

.access-option:has(input:checked) {
  border-color: #840435;
  background: #fbf4f7;
}

.access-option.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.access-option input {
  margin-top: 2px;
  accent-color: #840435;
}

.access-option span {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.access-option strong {
  font-size: 14px;
}

.access-option small {
  color: #6b7280;
  font-size: 12px;
  line-height: 1.4;
}

.campaign-options {
  max-height: 240px;
  overflow-y: auto;
  padding: 4px 0;
  border: 1px solid #ece4e7;
  border-radius: 7px;
}

.campaign-options legend {
  float: left;
  width: 100%;
  margin: 0;
  padding: 8px 12px;
}

.campaign-option {
  clear: both;
  padding: 9px 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  cursor: pointer;
}

.campaign-option:hover {
  background: #faf7f8;
}

.campaign-option input {
  accent-color: #840435;
}

.campaign-option span {
  flex: 1;
  word-break: break-all;
}

.state {
  color: #9ca3af;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.state.ativa {
  color: #15803d;
}

.active-toggle {
  margin-top: 18px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #374151;
  font-size: 14px;
}

.active-toggle input {
  accent-color: #840435;
}

.form-error {
  margin: 16px 0 0;
  padding: 10px 12px;
  border-left: 3px solid #b91c1c;
  border-radius: 4px;
  background: #fef2f2;
  color: #991b1b;
  font-size: 14px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 22px;
}

@media (max-width: 760px) {
  .header-content {
    padding: 24px 20px;
    flex-direction: column;
    align-items: flex-start;
  }

  .admins-content {
    padding: 20px 16px 30px;
  }

  .admin-row {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .form-actions {
    flex-direction: column-reverse;
  }
}
</style>
