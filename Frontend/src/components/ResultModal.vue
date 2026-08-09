<script setup>
const props = defineProps({
  resultType: {
    type: String,
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

const emit = defineEmits([
  'close',
  'retry'
])
</script>

<template>
  <Transition
    name="modal-fade"
    appear
  >
  <div class="modal-overlay">
    <section class="modal-card">
      <div class="card-stripe">
        <div class="stripe-accent"></div>
      </div>

      <div class="modal-content">

  <!-- GANHOU -->
  <template v-if="resultType === 'won'">
    <div class="result-icon winner-icon">
      ★
    </div>

    <h2>Parabéns!</h2>

    <p class="message">
      O número <strong>{{ number }}</strong> contém um prémio.
    </p>

    <div class="prize-box">
      <span class="prize-label">
        Prémio
      </span>

      <strong>
        {{ prize }}
      </strong>
    </div>

    <p class="information">
      Entraremos em contacto para efectuar a entrega do prémio.
    </p>

    <button
      type="button"
      class="finish-button"
      @click="emit('close')"
    >
      Concluir
    </button>
  </template>


  <!-- TENTAR NOVAMENTE -->
  <template v-else-if="resultType === 'retry'">
    <div class="result-icon retry-icon">
      ↻
    </div>

    <h2>Tente Novamente!</h2>

    <p class="message">
      O número <strong>{{ number }}</strong>
      deu-lhe uma nova oportunidade.
    </p>

    <div class="retry-box">
      <span>Nova tentativa disponível</span>

      <strong>
        Escolha outro número
      </strong>
    </div>

    <p class="information">
      Pode voltar ao jogo e seleccionar outro número.
    </p>

    <button
      type="button"
      class="retry-button"
      @click="emit('retry')"
    >
      Tentar Novamente
    </button>
  </template>


  <!-- NÃO GANHOU -->
  <template v-else>
    <div class="result-icon loser-icon">
      ☹
    </div>

    <h2>Não foi desta vez</h2>

    <p class="information">
      Obrigado por participar. Continue atento às próximas campanhas.
    </p>

    <button
      type="button"
      class="finish-button"
      @click="emit('close')"
    >
      Concluir
    </button>
  </template>

</div>
    </section>
  </div>
  </Transition>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 12, 51, 0.6);
  font-family: Arial, Helvetica, sans-serif;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.18s ease;
}

.modal-fade-enter-active .modal-card,
.modal-fade-leave-active .modal-card {
  transition: transform 0.18s ease, opacity 0.18s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-from .modal-card,
.modal-fade-leave-to .modal-card {
  transform: scale(0.94) translateY(6px);
  opacity: 0;
}

.modal-card {
  width: 100%;
  max-width: 380px;
  overflow: hidden;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 20px 50px rgba(15, 12, 51, 0.3);
}

.card-stripe {
  height: 6px;
  background: #e5e7eb;
}

.stripe-accent {
  height: 100%;
  width: 100%;
  background: linear-gradient(90deg, #27227f, #0088cc);
}

.modal-content {
  padding: 32px 28px;
  text-align: center;
}

.result-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 32px;
  line-height: 1;
}

.winner-icon {
  background: #fef3c7;
  color: #b45309;
}

.loser-icon {
  background: #f3f4f6;
  color: #6b7280;
}

.retry-icon {
  background: #eef2ff;
  color: #27227f;
}

.retry-box {
  margin: 22px 0;
  padding: 17px;
  border: 1px solid #c7d2fe;
  border-radius: 8px;
  background: #eef2ff;
  text-align: center;
}

.retry-box span {
  display: block;
  margin-bottom: 5px;
  color: #6b7280;
  font-size: 12px;
}

.retry-box strong {
  color: #27227f;
  font-size: 17px;
}

.retry-button {
  width: 100%;
  min-height: 46px;
  border: 0;
  border-radius: 7px;
  background: #27227f;
  color: #ffffff;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.retry-button:hover {
  background: #1c1860;
}

.modal-content h2 {
  margin: 0 0 10px;
  color: #1f2937;
  font-size: 22px;
}

.message {
  margin: 0;
  color: #4b5563;
  font-size: 15px;
  line-height: 1.5;
}

.prize-box {
  margin: 22px 0;
  padding: 17px;
  border: 1px solid #fde68a;
  border-radius: 8px;
  background: #fffbeb;
  text-align: center;
}

.prize-label {
  display: block;
  margin-bottom: 5px;
  color: #92400e;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.prize-box strong {
  color: #b45309;
  font-size: 18px;
}

.information {
  margin: 18px 0 0;
  color: #9ca3af;
  font-size: 13px;
  line-height: 1.5;
}

.finish-button {
  width: 100%;
  min-height: 46px;
  margin-top: 22px;
  border: 0;
  border-radius: 7px;
  background: #27227f;
  color: #ffffff;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.finish-button:hover {
  background: #1c1860;
}
</style>