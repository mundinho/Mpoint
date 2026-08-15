<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  stages: {
    // [{ label, value }] em ordem decrescente
    type: Array,
    required: true
  }
})

// Rampa sequencial azul, ordinal (degraus ≥250 no claro — ver palette.md)
const STEPS = ['#86b6ef', '#5598e7', '#2a78d6', '#1c5cab', '#104281']

const first = computed(() => props.stages[0]?.value || 0)
const hasData = computed(() => first.value > 0)

const rows = computed(() =>
  props.stages.map((stage, i) => ({
    ...stage,
    percent: first.value > 0 ? Math.round((stage.value / first.value) * 100) : 0,
    color: STEPS[Math.min(i, STEPS.length - 1)]
  }))
)
</script>

<template>
  <div class="funnel-chart">
    <div
      v-if="!hasData"
      class="empty-state"
    >
      {{ t('charts.common.noData') }}
    </div>

    <div
      v-for="row in rows"
      v-else
      :key="row.label"
      class="funnel-row"
    >
      <div class="funnel-heading">
        <span>{{ row.label }}</span>
        <span class="funnel-percent">{{ row.percent }}%</span>
      </div>

      <div class="funnel-track">
        <div
          class="funnel-fill"
          :style="{ width: `${Math.max(row.percent, row.value > 0 ? 4 : 0)}%`, background: row.color }"
        >
          <strong class="funnel-value">{{ row.value }}</strong>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.funnel-chart {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 14px;
  font-family: Arial, Helvetica, sans-serif;
}

.empty-state {
  padding: 40px 0;
  text-align: center;
  color: #898781;
  font-size: 13px;
}

.funnel-heading {
  margin-bottom: 6px;
  display: flex;
  justify-content: space-between;
  color: #52514e;
  font-size: 12px;
}

.funnel-percent {
  color: #898781;
}

.funnel-track {
  height: 28px;
  border-radius: 4px;
  background: #f0efec;
}

.funnel-fill {
  height: 100%;
  min-width: 34px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 10px;
  border-radius: 4px;
  transition: width 0.2s ease;
}

.funnel-value {
  color: #ffffff;
  font-size: 12px;
  font-weight: 700;
}
</style>
