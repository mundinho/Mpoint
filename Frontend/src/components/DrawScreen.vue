<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  participantName: {
    type: String,
    default: 'Participante'
  },

  squares: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['select-number'])

const selectedNumber = ref(null)
const scrollContainer = ref(null)

function moveNumbers(direction) {
  const distance = 650

  scrollContainer.value?.scrollBy({
    left: direction * distance,
    behavior: 'smooth'
  })
}


const numbers = computed(() =>
  props.squares.map(square => ({
    id: square.id,
    number: square.numero,
    status: square.estado === 'aberto'
      ? 'opened'
      : 'available'
  }))
)

const availableCount = computed(() =>
  numbers.value.filter(item => item.status === 'available').length
)

const openedCount = computed(() =>
  numbers.value.filter(item => item.status === 'opened').length
)
/*
  esta faltar endpoint de vencedores, por isso o valor e fixo 0
*/
const winnersCount = computed(() => 0)

/*
  Divide os 1000 números em:
  Linha 1: 1–200
  Linha 2: 201–400
  Linha 3: 401–600
  Linha 4: 601–800
  Linha 5: 801–1000
*/
const numberRows = computed(() => {
  const rows = []

  for (let row = 0; row < 5; row++) {
    const start = row * 200
    const end = start + 200

    rows.push(numbers.value.slice(start, end))
  }

  return rows
})

function selectNumber(item) {
  if (item.status !== 'available') {
    return
  }

  selectedNumber.value = item.number
  emit('select-number', item.number)
}
</script>

<template>
  <div class="draw-page">
    <header class="draw-header">
      <div class="header-accent"></div>

      <div class="header-content">
        <div class="header-spacer"></div>

        <div class="title-area">
          <h1>Escolha um Número</h1>

          <p>
            Cada participante pode abrir apenas um número
          </p>
        </div>

        <div class="participant-area">
          <div class="participant-name">
            <span>Participante</span>
            <strong>{{ participantName }}</strong>
          </div>

          <div class="statistics">
            <div class="stat-card">
              <strong>{{ availableCount }}</strong>
              <span>Disponíveis</span>
            </div>

            <div class="stat-card">
              <strong class="opened-value">
                {{ openedCount }}
              </strong>
              <span>Abertos</span>
            </div>

            <div class="stat-card">
              <strong class="winner-value">
                {{ winnersCount }}
              </strong>
              <span>Vencedores</span>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="game-area">
      <div class="section-title">
        <div class="divider"></div>

        <span>
          1000 números · Escolha o seu
        </span>

        <div class="divider"></div>
      </div>

     <div class="numbers-area">
  <button
    type="button"
    class="navigation-arrow navigation-arrow-left"
    aria-label="Ver números anteriores"
    @click="moveNumbers(-1)"
  >
    ‹
  </button>

  <div
    ref="scrollContainer"
    class="shared-scroll"
  >
    <div class="numbers-content">
      <div
        v-for="(row, rowIndex) in numberRows"
        :key="rowIndex"
        class="number-row"
      >
        <button
          v-for="item in row"
          :key="item.number"
          type="button"
          class="number-button"
          :class="{
            available: item.status === 'available',
            opened: item.status === 'opened',
            won: item.status === 'won',
            selected: selectedNumber === item.number
          }"
          :disabled="item.status !== 'available'"
          @click="selectNumber(item)"
        >
          {{ item.number }}

          <span
            v-if="item.status === 'won'"
            class="winner-dot"
          ></span>
        </button>
      </div>
    </div>
  </div>

  <button
    type="button"
    class="navigation-arrow navigation-arrow-right"
    aria-label="Ver números seguintes"
    @click="moveNumbers(1)"
  >
    ›
  </button>
</div>

    <div class="legend">
  <div class="legend-item">
    <span class="legend-box available"></span>
    <span>Disponível</span>
  </div>

  <div class="legend-item">
    <span class="legend-box opened"></span>
    <span>Aberto</span>
  </div>

  <div class="legend-item">
    <span class="legend-box winner"></span>
    <span>Premiado</span>
  </div>
</div>
    </main>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.draw-page {
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #27227f;
  font-family: Arial, Helvetica, sans-serif;
}

.draw-header {
  position: relative;
  flex-shrink: 0;
  overflow: hidden;
  background: #1c1860;
}

.header-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 150px;
  height: 100%;
  background: #0088cc;
  clip-path: polygon(32% 0, 100% 0, 100% 100%, 0 100%);
}

.header-content {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 1500px;
  min-height: 138px;
  margin: 0 auto;
  padding: 30px 32px 22px;;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.header-spacer {
  width: 270px;
}

.title-area {
  text-align: center;
}

.title-area h1 {
  margin: 0;
  color: #ffffff;
  font-size: 29px;
}

.title-area p {
  margin: 7px 0 0;
  color: rgba(255, 255, 255, 0.6);
  font-size: 15px;
}

.participant-area {
  width: 360px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 18px;
}

.participant-name {
  text-align: right;
}

.participant-name span {
  display: block;
  margin-bottom: 4px;
  color: rgba(255, 255, 255, 0.5);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
}

.participant-name strong {
  color: #ffffff;
  font-size: 15px;
}

.statistics {
  display: flex;
  gap: 8px;
}

.stat-card {
  min-width: 76px;
  padding: 12px 9px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.1);
  text-align: center;
}

.stat-card strong {
  display: block;
  color: #ffffff;
  font-size: 20px;
}

.stat-card span {
  display: block;
  margin-top: 3px;
  color: rgba(255, 255, 255, 0.5);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.opened-value {
  color: #9ca3af !important;
}

.winner-value {
  color: #fbbf24 !important;
}

.game-area {
  min-height: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #ffffff;
}

.section-title {
  padding: 25px 48px 0;
  display: flex;
  align-items: center;
  gap: 16px;
}

.section-title span {
  color: #9ca3af;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  white-space: nowrap;
}

.divider {
  flex: 1;
  height: 1px;
  background: #e5e7eb;
}

.numbers-area {
  position: relative;
  min-height: 0;
  flex: 1;
  padding: 0 92px;
  display: flex;
  align-items: center;
}

.shared-scroll {
  width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: none;
  scroll-behavior: smooth;
}
.shared-scroll::-webkit-scrollbar {
  display: none;
}
 
.navigation-arrow {
  position: absolute;
  top: 50%;
  z-index: 10;
  width: 52px;
  height: 74px;
  border: 1px solid #e0e0ef;
  border-radius: 8px;
  background: #ffffff;
  color: #27227f;
  box-shadow: 0 4px 15px rgba(39, 34, 127, 0.12);
  font-size: 42px;
  font-weight: 400;
  line-height: 1;
  cursor: pointer;
  transform: translateY(-50%);
  transition:
    background 0.15s ease,
    transform 0.15s ease;
}

.navigation-arrow:hover {
  background: #f7f7fb;
  transform: translateY(-50%) scale(1.04);
}

.navigation-arrow-left {
  left: 24px;
}

.navigation-arrow-right {
  right: 24px;
}


.numbers-content {
  width: max-content;
  padding: 18px 42px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.number-row {
  display: flex;
  align-items: center;
  gap: 26px;
}

.number-button {
  position: relative;
  min-width: 68px;
  height: 44px;
  flex-shrink: 0;
  padding: 0 8px;
  border: 0;
  background: transparent;
  font-size: 21px;
  font-variant-numeric: tabular-nums;
  transition:
    transform 0.15s ease,
    color 0.15s ease;
}

.number-button.available {
  color: #27227f;
  font-weight: 700;
  cursor: pointer;
}

.number-button.available:hover {
  transform: scale(1.12);
}

.number-button.opened,
.number-button.won {
  color: #d1d5db;
  font-weight: 400;
  cursor: default;
}

.number-button.selected {
  color: #27227f;
  font-size: 25px;
  font-weight: 800;
  transform: scale(1.1);
}

.number-button.selected::before,
.number-button.selected::after {
  content: '';
  position: absolute;
  left: 50%;
  width: 1px;
  height: 8px;
  background: #27227f;
  transform: translateX(-50%);
}

.number-button.selected::before {
  top: 0;
}

.number-button.selected::after {
  bottom: 0;
}

.winner-dot {
  position: absolute;
  left: 50%;
  bottom: 2px;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #f59e0b;
  transform: translateX(-50%);
}

.legend {
  flex-shrink: 0;
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 42px;
  border-top: 1px solid #f3f4f6;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #6b7280;
  font-size: 14px;
}

.legend-number {
  position: relative;
  font-size: 15px;
  font-weight: 700;
}

.available-example {
  color: #27227f;
}

.opened-example,
.winner-example {
  color: #d1d5db;
}

.winner-example .winner-dot {
  bottom: -7px;
}

@media (max-width: 900px) {
  .header-content {
    flex-direction: column;
    gap: 18px;
  }

  .header-spacer {
    display: none;
  }

  .participant-area {
    width: auto;
  }
}
</style>