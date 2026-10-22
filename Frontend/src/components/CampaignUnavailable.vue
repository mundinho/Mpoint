<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  // 'not-found' | 'paused' | 'closed' | 'error'
  reason: {
    type: String,
    default: 'not-found'
  },
  campaignName: {
    type: String,
    default: ''
  }
})

const { t, locale } = useI18n()

const key = computed(() =>
  ['not-found', 'paused', 'closed', 'error'].includes(props.reason) ? props.reason : 'not-found'
)

function toggleLanguage() {
  const newLanguage = locale.value === 'pt' ? 'en' : 'pt'
  locale.value = newLanguage
  localStorage.setItem('language', newLanguage)
}

function reload() {
  window.location.reload()
}
</script>

<template>
  <div class="unavailable-page">
    <header class="top-bar">
      <div class="accent"></div>
      <span class="brand">MPOINTS</span>
      <button type="button" class="language" @click="toggleLanguage">
        {{ locale === 'pt' ? 'EN' : 'PT' }}
      </button>
    </header>

    <main class="content">
      <section class="card" role="status">
        <div class="card-top"></div>

        <span class="icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" /></svg>
        </span>

        <p v-if="campaignName && key !== 'not-found'" class="campaign">{{ campaignName }}</p>
        <h1>{{ t(`unavailable.${key}.title`) }}</h1>
        <p class="text">{{ t(`unavailable.${key}.text`) }}</p>

        <button v-if="key === 'error' || key === 'paused'" type="button" class="retry" @click="reload">
          {{ t('unavailable.retry') }}
        </button>
      </section>
    </main>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.unavailable-page {
  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  background: #faf7f8;
  font-family: Arial, Helvetica, sans-serif;
  color: #111827;
}

.top-bar {
  position: relative;
  height: 74px;
  padding: 0 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  overflow: hidden;
  background: #840435;
}

.accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 200px;
  height: 100%;
  background: #6a032a;
  clip-path: polygon(34% 0, 100% 0, 100% 100%, 0 100%);
}

.brand {
  position: relative;
  color: #ffffff;
  font-size: 19px;
  font-weight: 800;
  letter-spacing: 3px;
}

.language {
  position: relative;
  width: 44px;
  height: 34px;
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
  font-weight: 800;
  cursor: pointer;
}

.content {
  flex: 1;
  display: grid;
  place-items: center;
  padding: 32px 16px;
}

.card {
  width: 100%;
  max-width: 480px;
  padding: 0 32px 36px;
  overflow: hidden;
  border: 1px solid #ece4e7;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 20px 50px rgba(132, 4, 53, 0.08);
  text-align: center;
}

.card-top {
  height: 8px;
  margin: 0 -32px 32px;
  background: #840435;
}

.icon {
  width: 56px;
  height: 56px;
  padding: 13px;
  display: block;
  margin: 0 auto 18px;
  border-radius: 8px;
  background: #fbeef3;
  color: #840435;
}

.icon svg {
  width: 100%;
  height: 100%;
  fill: none;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.campaign {
  margin: 0 0 6px;
  color: #840435;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
}

h1 {
  margin: 0 0 10px;
  font-size: 26px;
}

.text {
  margin: 0;
  color: #4b5563;
  line-height: 1.55;
}

.retry {
  min-height: 46px;
  margin-top: 24px;
  padding: 0 24px;
  border: none;
  border-radius: 7px;
  background: #840435;
  color: #ffffff;
  font-family: inherit;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
}

.retry:hover {
  background: #6a032a;
}
</style>
