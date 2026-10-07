<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { currentSection } from '../utils/adminSections'

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
const expanded = ref(props.active)
const activeSection = ref(null)

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
    hint: t('sidebar.dashboardHint'),
    children: [
      { id: 'dash-stats', label: t('sidebar.sub.statistics') },
      { id: 'dash-participants', label: t('dashboard.participants.title') },
      { id: 'dash-activity', label: t('dashboard.activity.title') }
    ]
  },
  {
    screen: 'charts',
    label: t('sidebar.charts'),
    hint: t('sidebar.chartsHint'),
    children: [
      { id: 'charts-summary', label: t('sidebar.sub.summary') },
      { id: 'charts-activity', label: t('charts.cards.activityTitle') },
      { id: 'charts-registrations', label: t('charts.cards.registrationsTitle') },
      { id: 'charts-results', label: t('charts.cards.resultsTitle') },
      { id: 'charts-opened', label: t('charts.cards.openedNumbersTitle') },
      { id: 'charts-prizes', label: t('charts.cards.prizesTitle') },
      { id: 'charts-sms', label: t('charts.cards.smsTitle') },
      { id: 'charts-funnel', label: t('charts.cards.funnelTitle') }
    ]
  },
  {
    screen: 'campaign-management',
    label: t('sidebar.campaignManagement'),
    hint: t('sidebar.campaignManagementHint'),
    children: [
      { id: 'mgmt-info', label: t('campaignManagement.information.title') },
      { id: 'mgmt-prizes', label: t('campaignManagement.prizes.title') },
      { id: 'mgmt-prize-summary', label: t('campaignManagement.prizeSummary.title') },
      { id: 'mgmt-control', label: t('campaignManagement.control.title') }
    ]
  }
])

const activeChildren = computed(() =>
  navItems.value.find(item => item.screen === props.active)?.children || []
)

watch(
  () => props.active,
  screen => {
    expanded.value = screen
    activeSection.value = null
  }
)

function toggleGroup(item) {
  if (item.screen !== props.active) {
    navigateTo(item.screen)
    return
  }

  expanded.value = expanded.value === item.screen ? null : item.screen
}

function navigateTo(screen, section = null) {
  isOpen.value = false
  expanded.value = screen
  activeSection.value = section
  emit('navigate', { screen, section })
}

function switchCampaign() {
  isOpen.value = false
  emit('switch-campaign')
}

function logout() {
  isOpen.value = false
  emit('logout')
}

// Destaca o submenu da secção visível enquanto o conteúdo rola
let frame = null

function updateActiveSection() {
  cancelAnimationFrame(frame)
  frame = requestAnimationFrame(() => {
    activeSection.value = currentSection(activeChildren.value.map(child => child.id))
  })
}

onMounted(() => {
  document.addEventListener('scroll', updateActiveSection, { capture: true, passive: true })
})

onBeforeUnmount(() => {
  document.removeEventListener('scroll', updateActiveSection, { capture: true })
  cancelAnimationFrame(frame)
})

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
      <div class="logo-text">
        <strong>MPOINTS</strong>
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

      <div
        v-for="item in navItems"
        :key="item.screen"
        class="nav-group"
        :class="{ expanded: expanded === item.screen }"
      >
        <button
          type="button"
          class="nav-item"
          :class="{ active: active === item.screen }"
          :aria-current="active === item.screen ? 'page' : undefined"
          :aria-expanded="expanded === item.screen"
          @click="toggleGroup(item)"
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

          <svg class="nav-chevron" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 6l6 6-6 6" />
          </svg>
        </button>

        <div class="submenu-wrap">
          <ul class="submenu">
            <li
              v-for="child in item.children"
              :key="child.id"
            >
              <button
                type="button"
                class="submenu-item"
                :class="{ active: active === item.screen && activeSection === child.id }"
                @click="navigateTo(item.screen, child.id)"
              >
                {{ child.label }}
              </button>
            </li>
          </ul>
        </div>
      </div>

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
  border: 1px solid rgba(255, 255, 255, 0.38);
  border-radius: 6px;
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
  background: #27227f;
  box-shadow: 3px 0 18px rgba(15, 12, 51, 0.18);
  font-family: Arial, Helvetica, sans-serif;
}

.sidebar-logo {
  position: relative;
  min-height: 76px;
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  overflow: hidden;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.sidebar-logo::after {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 92px;
  height: 100%;
  background: #0088cc;
  clip-path: polygon(40% 0, 100% 0, 100% 100%, 0 100%);
}

.logo-text {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.logo-text strong {
  color: #ffffff;
  font-size: 17px;
  font-weight: 900;
  letter-spacing: 2px;
}

.logo-text small {
  color: rgba(255, 255, 255, 0.6);
  font-size: 11px;
}

.sidebar-language-button {
  position: relative;
  z-index: 1;
  margin-left: auto;
  width: 38px;
  height: 30px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.45);
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  transition: background 0.15s ease;
}

.sidebar-language-button:hover {
  background: rgba(255, 255, 255, 0.2);
}

.sidebar-nav {
  flex: 1;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
  padding: 8px 12px 16px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  scrollbar-color: rgba(255, 255, 255, 0.25) transparent;
}

.nav-section {
  margin: 16px 10px 6px;
  color: rgba(255, 255, 255, 0.45);
  font-size: 10.5px;
  font-weight: 800;
  letter-spacing: 1.2px;
  text-transform: uppercase;
}

.nav-item {
  position: relative;
  width: 100%;
  padding: 9px 10px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: none;
  border-left: 3px solid transparent;
  border-radius: 6px;
  background: transparent;
  color: rgba(255, 255, 255, 0.78);
  font-family: inherit;
  text-align: left;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

.nav-item.active {
  border-left-color: #0088cc;
  background: #ffffff;
  color: #27227f;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
}

.nav-icon {
  width: 32px;
  height: 32px;
  padding: 7px;
  flex-shrink: 0;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.08);
}

.nav-item.active .nav-icon {
  background: #0088cc;
  color: #ffffff;
}

.nav-text {
  min-width: 0;
  flex: 1;
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

.nav-chevron {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  opacity: 0.6;
  transition: transform 0.2s ease;
}

.nav-group.expanded .nav-chevron {
  transform: rotate(90deg);
}

/* Submenu com abertura animada (grid 0fr -> 1fr) */
.submenu-wrap {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 0.22s ease;
}

.nav-group.expanded .submenu-wrap {
  grid-template-rows: 1fr;
}

.submenu {
  min-height: 0;
  overflow: hidden;
  margin: 0;
  padding: 0;
  list-style: none;
}

.nav-group.expanded .submenu {
  padding: 4px 0 8px;
}

.submenu li {
  margin-left: 28px;
  border-left: 1px solid rgba(255, 255, 255, 0.16);
}

.submenu-item {
  position: relative;
  width: 100%;
  padding: 7px 10px 7px 16px;
  border: none;
  background: transparent;
  color: rgba(255, 255, 255, 0.65);
  font-family: inherit;
  font-size: 12.5px;
  text-align: left;
  cursor: pointer;
  transition: color 0.15s ease, background 0.15s ease;
}

.submenu-item:hover {
  background: rgba(255, 255, 255, 0.06);
  color: #ffffff;
}

.submenu-item.active {
  color: #ffffff;
  font-weight: 700;
}

.submenu-item.active::before {
  content: '';
  position: absolute;
  top: 6px;
  bottom: 6px;
  left: -2px;
  width: 3px;
  background: #00b4d8;
}

.sidebar-footer {
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 11px;
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}

.admin-avatar {
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #0088cc;
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
  color: rgba(255, 255, 255, 0.55);
  font-size: 11px;
}

.logout-button {
  width: 34px;
  height: 34px;
  padding: 8px;
  flex-shrink: 0;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  background: transparent;
  color: rgba(255, 255, 255, 0.75);
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;
}

.logout-button:hover {
  background: rgba(220, 38, 38, 0.35);
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
