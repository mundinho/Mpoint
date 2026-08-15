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

const navItems = computed(() => [
  {
    screen: 'dashboard',
    label: t('sidebar.dashboard'),
    icon: '▤'
  },
  {
    screen: 'charts',
    label: t('sidebar.charts'),
    icon: '◔'
  },
  {
    screen: 'campaign-management',
    label: t('sidebar.campaignManagement'),
    icon: '⚙'
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

const adminInitial = computed(() =>
  (props.admin?.nome || props.admin?.name || props.admin?.telefone || '?')
    .trim()
    .charAt(0)
    .toUpperCase()
)
</script>

<template>
  <button
    type="button"
    class="menu-toggle"
    :aria-label="t('sidebar.openMenu')"
    @click="isOpen = !isOpen"
  >
    ☰
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
   <div class="sidebar-brand">
  <span class="brand-avatar">{{ adminInitial }}</span>

  <div class="brand-text">
    <strong>MPoint</strong>
    <small>
      {{ admin?.nome || admin?.name || admin?.telefone || 'Admin' }}
    </small>
  </div>

  <button
    type="button"
    class="sidebar-language-button"
    @click="toggleLanguage"
  >
    {{ locale === 'pt' ? 'EN' : 'PT' }}
  </button>
</div>

    <nav class="sidebar-nav">
      <button
        v-for="item in navItems"
        :key="item.screen"
        type="button"
        class="nav-item"
        :class="{ active: active === item.screen }"
        @click="navigate(item.screen)"
      >
        <span class="nav-icon">{{ item.icon }}</span>
        {{ item.label }}
      </button>
    </nav>

    <div class="sidebar-footer">
      <button
        type="button"
        class="nav-item"
        @click="switchCampaign"
      >
       <span class="nav-icon">⇄</span>
{{ t('sidebar.switchCampaign') }}
      </button>

      <button
        type="button"
        class="nav-item logout"
        @click="logout"
      >
       <span class="nav-icon">⏻</span>
{{ t('sidebar.logout') }}
      </button>
    </div>
  </aside>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.menu-toggle {
  display: none;
  position: fixed;
  z-index: 60;
  top: 16px;
  left: 16px;
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 8px;
  background: #27227f;
  color: #ffffff;
  font-size: 18px;
  cursor: pointer;
}

.sidebar-backdrop {
  display: none;
}

.admin-sidebar {
  width: 230px;
  flex-shrink: 0;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #27227f 0%, #201c6b 100%);
  box-shadow: 3px 0 18px rgba(15, 12, 51, 0.18);
  font-family: Arial, Helvetica, sans-serif;
}

.sidebar-brand {
  padding: 22px 18px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.sidebar-language-button {
  margin-left: auto;
  width: 38px;
  height: 30px;
  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border: 1px solid rgba(255, 255, 255, 0.45);
  border-radius: 6px;

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

.brand-avatar {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, #0088cc, #00b4d8);
  box-shadow: 0 2px 10px rgba(0, 136, 204, 0.45);
  color: #ffffff;
  font-size: 16px;
  font-weight: 800;
}

.brand-text {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.brand-text strong {
  color: #ffffff;
  font-size: 15px;
}

.brand-text small {
  overflow: hidden;
  color: rgba(255, 255, 255, 0.6);
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sidebar-nav {
  padding: 16px 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-item {
  position: relative;
  width: 100%;
  min-height: 42px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: none;
  border-left: 3px solid transparent;
  border-radius: 8px;
  background: transparent;
  color: rgba(255, 255, 255, 0.75);
  font-size: 13px;
  font-weight: 600;
  text-align: left;
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

.nav-item.active {
  border-left-color: #00b4d8;
  background: #ffffff;
  color: #27227f;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
}

.nav-icon {
  width: 18px;
  flex-shrink: 0;
  text-align: center;
  font-size: 15px;
}

.sidebar-footer {
  margin-top: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}

.nav-item.logout:hover {
  background: rgba(220, 38, 38, 0.25);
  color: #ffffff;
}

@media (max-width: 900px) {
  .menu-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
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
    transform: translateX(-100%);
    transition: transform 0.2s ease;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.25);
  }

  .admin-sidebar.open {
    transform: translateX(0);
  }
}
</style>
