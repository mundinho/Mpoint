<script setup>
defineProps({
  won: {
    type: Boolean,
    required: true
  },

  number: {
    type: Number,
    required: true
  },

  prize: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['close'])
</script>

<template>
  <div class="modal-overlay">
    <section class="modal-card">
      <div class="card-stripe">
        <div class="stripe-accent"></div>
      </div>

      <div class="modal-content">
        <template v-if="won">
          <div class="result-icon winner-icon">
            ★
          </div>

          <h2>Parabéns!</h2>

          <p class="message">
            O número <strong>{{ number }}</strong> contém um prémio.
          </p>

          <div class="prize-box">
            <span class="prize-label">Prémio</span>
            <strong>{{ prize }}</strong>
          </div>

          <p class="information">
            Entraremos em contacto para efectuar a entrega do prémio.
          </p>
        </template>

        <template v-else>
          <div class="result-icon loser-icon">
            ☹
          </div>

          <h2>Não foi desta vez</h2>

          <p class="message">
            O número <strong>{{ number }}</strong> não contém um prémio.
          </p>

          <p class="information">
            Obrigado por participar. Continue atento às próximas campanhas.
          </p>
        </template>

        <button
          type="button"
          class="finish-button"
          @click="emit('close')"
        >
          Concluir
        </button>
      </div>
    </section>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: rgba(17, 24, 39, 0.55);
}

.modal-card {
  width: 100%;
  max-width: 420px;
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #e0e0ef;
  border-radius: 10px;
  box-shadow: 0 18px 50px rgba(17, 24, 39, 0.22);
}

.card-stripe {
  position: relative;
  height: 28px;
  overflow: hidden;
  background: #27227f;
}

.stripe-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 110px;
  height: 100%;
  background: #0088cc;
  clip-path: polygon(35% 0, 100% 0, 100% 100%, 0 100%);
}

.modal-content {
  padding: 38px 34px 34px;
  text-align: center;
}

.result-icon {
  width: 66px;
  height: 66px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 31px;
  font-weight: 800;
}

.winner-icon {
  border: 2px solid #f59e0b;
  background: #fffbeb;
  color: #f59e0b;
}

.loser-icon {
  border: 2px solid #9ca3af;
  background: #f9fafb;
  color: #6b7280;
}

h2 {
  margin: 0 0 14px;
  color: #111827;
  font-size: 25px;
}

.message {
  margin: 0;
  color: #4b5563;
  font-size: 15px;
  line-height: 1.6;
}

.message strong {
  color: #27227f;
  font-size: 18px;
}

.prize-box {
  margin: 24px 0;
  padding: 18px;
  border: 1px solid #fde68a;
  border-radius: 8px;
  background: #fffbeb;
  color: #92400e;
}

.prize-label {
  display: block;
  margin-bottom: 5px;
  color: #b45309;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
}

.prize-box strong {
  display: block;
  font-size: 21px;
}

.information {
  margin: 22px 0 28px;
  color: #9ca3af;
  font-size: 13px;
  line-height: 1.6;
}

.finish-button {
  width: 100%;
  padding: 13px;
  border: 0;
  border-radius: 8px;
  background: #27227f;
  color: #ffffff;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
}

.finish-button:hover {
  background: #1c1860;
}
</style>