<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

function toggleLanguage() {
  const newLanguage =
    locale.value === 'pt' ? 'en' : 'pt'

  locale.value = newLanguage
  localStorage.setItem('language', newLanguage)
}

const props = defineProps({
  active: {
    type: String,
    default: 'dashboard'
  },

  admin: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['navigate', 'switch-campaign', 'logout'])

const isOpen = ref(false)

// Caminhos SVG (24x24, traço) para cada ícone do menu
const ICONS = {
  dashboard: 'M3 13h8V3H3v10Zm0 8h8v-6H3v6Zm10 0h8V11h-8v10Zm0-18v6h8V3h-8Z',
  charts: 'M4 20V10M10 20V4M16 20v-8M22 20H2',
  'campaign-management': 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.4-3a7.4 7.4 0 0 0-.1-1.3l2-1.6-2-3.4-2.4 1a7.6 7.6 0 0 0-2.2-1.3L14.4 3h-4l-.4 2.4a7.6 7.6 0 0 0-2.2 1.3l-2.4-1-2 3.4 2 1.6a7.4 7.4 0 0 0 0 2.6l-2 1.6 2 3.4 2.4-1a7.6 7.6 0 0 0 2.2 1.3l.4 2.4h4l.4-2.4a7.6 7.6 0 0 0 2.2-1.3l2.4 1 2-3.4-2-1.6c.1-.4.1-.9.1-1.3Z',
  switch: 'M7 7h13l-4-4M17 17H4l4 4',
  logout: 'M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3'
}

const navItems = computed(() => [
  {
    screen: 'dashboard',
    label: t('sidebar.dashboard'),
    hint: t('sidebar.dashboardHint')
  },
  {
    screen: 'charts',
    label: t('sidebar.charts'),
    hint: t('sidebar.chartsHint')
  },
  {
    screen: 'campaign-management',
    label: t('sidebar.campaignManagement'),
    hint: t('sidebar.campaignManagementHint')
  }
])

function navigate(screen) {
  isOpen.value = false
  emit('navigate', screen)
}

function switchCampaign() {
  isOpen.value = false
  emit('switch-campaign')
}

function logout() {
  isOpen.value = false
  emit('logout')
}

const adminName = computed(() =>
  props.admin?.nome || props.admin?.name || props.admin?.telefone || 'Admin'
)

const adminInitial = computed(() =>
  adminName.value.trim().charAt(0).toUpperCase()
)
</script>

<template>
  <button
    type="button"
    class="menu-toggle"
    :aria-label="t('sidebar.openMenu')"
    @click="isOpen = !isOpen"
  >
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M4 7h16M4 12h16M4 17h16" />
    </svg>
  </button>

  <div
    v-if="isOpen"
    class="sidebar-backdrop"
    @click="isOpen = false"
  ></div>

  <aside
    class="admin-sidebar"
    :class="{ open: isOpen }"
  >
    <div class="sidebar-logo">
      <span class="logo-mark">M</span>

      <div class="logo-text">
        <strong>MPoints</strong>
        <small>{{ t('sidebar.tagline') }}</small>
      </div>

      <button
        type="button"
        class="sidebar-language-button"
        :title="t('sidebar.language')"
        @click="toggleLanguage"
      >
        {{ locale === 'pt' ? 'EN' : 'PT' }}
      </button>
    </div>

    <nav class="sidebar-nav">
      <span class="nav-section">{{ t('sidebar.sectionMain') }}</span>

      <button
        v-for="item in navItems"
        :key="item.screen"
        type="button"
        class="nav-item"
        :class="{ active: active === item.screen }"
        :aria-current="active === item.screen ? 'page' : undefined"
        @click="navigate(item.screen)"
      >
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path :d="ICONS[item.screen]" />
          </svg>
        </span>

        <span class="nav-text">
          <span class="nav-label">{{ item.label }}</span>
          <span class="nav-hint">{{ item.hint }}</span>
        </span>
      </button>

      <span class="nav-section">{{ t('sidebar.sectionSession') }}</span>

      <button
        type="button"
        class="nav-item"
        @click="switchCampaign"
      >
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path :d="ICONS.switch" />
          </svg>
        </span>

        <span class="nav-text">
          <span class="nav-label">{{ t('sidebar.switchCampaign') }}</span>
          <span class="nav-hint">{{ t('sidebar.switchCampaignHint') }}</span>
        </span>
      </button>
    </nav>

    <div class="sidebar-footer">
      <div class="admin-card">
        <span class="admin-avatar">{{ adminInitial }}</span>

        <div class="admin-text">
          <strong>{{ adminName }}</strong>
          <small>{{ t('sidebar.administrator') }}</small>
        </div>

        <button
          type="button"
          class="logout-button"
          :title="t('sidebar.logout')"
          :aria-label="t('sidebar.logout')"
          @click="logout"
        >
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path :d="ICONS.logout" />
          </svg>
        </button>
      </div>
    </div>
  </aside>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

svg {
  width: 100%;
  height: 100%;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.menu-toggle {
  display: none;
  position: fixed;
  z-index: 60;
  top: 16px;
  left: 16px;
  width: 40px;
  height: 40px;
  padding: 9px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 10px;
  background: #27227f;
  color: #ffffff;
  cursor: pointer;
}

.sidebar-backdrop {
  display: none;
}

.admin-sidebar {
  width: 280px;
  height: 100vh;
  height: 100dvh;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  background:
    radial-gradient(circle at 0 0, rgba(0, 180, 216, 0.18), transparent 45%),
    linear-gradient(180deg, #27227f 0%, #1c1862 100%);
  box-shadow: 3px 0 18px rgba(15, 12, 51, 0.18);
  font-family: Arial, Helvetica, sans-serif;
}

.sidebar-logo {
  min-height: 82px;
  padding: 20px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo-mark {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: linear-gradient(135deg, #0088cc, #00b4d8);
  box-shadow: 0 4px 14px rgba(0, 136, 204, 0.45);
  color: #ffffff;
  font-size: 19px;
  font-weight: 900;
}

.logo-text {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.logo-text strong {
  color: #ffffff;
  font-size: 17px;
  letter-spacing: 0.2px;
}

.logo-text small {
  color: rgba(255, 255, 255, 0.55);
  font-size: 11px;
}

.sidebar-language-button {
  margin-left: auto;
  width: 38px;
  height: 30px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-radius: 7px;
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  transition: background 0.15s ease;
}

.sidebar-language-button:hover {
  background: rgba(255, 255, 255, 0.18);
}

.sidebar-nav {
  flex: 1;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
  padding: 10px 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-section {
  margin: 16px 10px 6px;
  color: rgba(255, 255, 255, 0.4);
  font-size: 10.5px;
  font-weight: 800;
  letter-spacing: 1.2px;
  text-transform: uppercase;
}

.nav-item {
  position: relative;
  width: 100%;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  gap: 13px;
  border: none;
  border-radius: 11px;
  background: transparent;
  color: rgba(255, 255, 255, 0.78);
  text-align: left;
  cursor: pointer;
  transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
  transform: translateX(2px);
}

.nav-item.active {
  background: #ffffff;
  color: #27227f;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
  transform: none;
}

.nav-item.active::before {
  content: '';
  position: absolute;
  top: 12px;
  bottom: 12px;
  left: -14px;
  width: 4px;
  border-radius: 0 4px 4px 0;
  background: #00b4d8;
}

.nav-icon {
  width: 36px;
  height: 36px;
  padding: 8px;
  flex-shrink: 0;
  border-radius: 9px;
  background: rgba(255, 255, 255, 0.08);
}

.nav-item.active .nav-icon {
  background: linear-gradient(135deg, #0088cc, #00b4d8);
  color: #ffffff;
}

.nav-text {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.nav-label {
  font-size: 13.5px;
  font-weight: 700;
}

.nav-hint {
  color: rgba(255, 255, 255, 0.5);
  font-size: 11px;
  line-height: 1.35;
}

.nav-item.active .nav-hint {
  color: #6b7280;
}

.sidebar-footer {
  padding: 14px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.admin-card {
  padding: 10px;
  display: flex;
  align-items: center;
  gap: 11px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.06);
}

.admin-avatar {
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.16);
  color: #ffffff;
  font-size: 15px;
  font-weight: 800;
}

.admin-text {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.admin-text strong {
  overflow: hidden;
  color: #ffffff;
  font-size: 13px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.admin-text small {
  color: rgba(255, 255, 255, 0.5);
  font-size: 11px;
}

.logout-button {
  width: 34px;
  height: 34px;
  padding: 8px;
  flex-shrink: 0;
  border: none;
  border-radius: 9px;
  background: transparent;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;
}

.logout-button:hover {
  background: rgba(220, 38, 38, 0.3);
  color: #ffffff;
}

@media (max-width: 900px) {
  .menu-toggle {
    display: block;
  }

  .sidebar-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 55;
    background: rgba(17, 24, 39, 0.5);
  }

  .admin-sidebar {
    position: fixed;
    z-index: 56;
    top: 0;
    bottom: 0;
    left: 0;
    width: min(300px, 86vw);
    transform: translateX(-100%);
    transition: transform 0.22s ease;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.25);
  }

  .admin-sidebar.open {
    transform: translateX(0);
  }
}
</style>
