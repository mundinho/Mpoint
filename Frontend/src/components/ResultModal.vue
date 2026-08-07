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

    <p class="message">
      O número <strong>{{ number }}</strong>
      não contém um prémio.
    </p>

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
</template>
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