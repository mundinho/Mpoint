<script setup>
import { useI18n } from 'vue-i18n'

const emit = defineEmits(['participate'])

const { t, locale } = useI18n()

function toggleLanguage() {
  const newLanguage =
    locale.value === 'pt' ? 'en' : 'pt'

  locale.value = newLanguage
  localStorage.setItem('language', newLanguage)
}

function startParticipation() {
  emit('participate')
}
</script>


<template>
  <div class="advertisement-page">

    <!-- Barra superior -->
    <header class="advertisement-header">
      <div class="header-accent"></div>

      <div class="header-content">
        <span class="brand">
          MPOINTS
        </span>

        <button
          type="button"
          class="language-button"
          @click="toggleLanguage"
        >
          {{ locale === 'pt' ? 'EN' : 'PT' }}
        </button>
      </div>
    </header>


    <!-- Conteúdo principal -->
    <main class="advertisement-content">

      <section class="advertisement-card">

        <div class="card-decoration">
          <div class="decoration-circle circle-one"></div>
          <div class="decoration-circle circle-two"></div>
        </div>


        <div class="advertisement-copy">

          <span class="eyebrow">
            {{ t('advertisement.eyebrow') }}
          </span>

          <h1>
            {{ t('advertisement.title') }}
          </h1>

          <button
            type="button"
            class="participate-button"
            @click="startParticipation"
          >
            {{ t('advertisement.participate') }}

            <span class="button-arrow">
              →
            </span>
          </button>

        </div>


        <!-- Parte visual -->
        <div class="advertisement-visual">

       

        </div>

      </section>


      <p class="footer-text">
        {{ t('advertisement.footer') }}
      </p>

    </main>


    <!-- Barra inferior -->
    <footer class="bottom-stripe">
      <div class="bottom-accent"></div>
    </footer>

  </div>
</template>


<style scoped>
* {
  box-sizing: border-box;
}


.advertisement-page {
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f7f7fb;
  font-family: Arial, Helvetica, sans-serif;
}


/* HEADER */

.advertisement-header {
  position: relative;
  height: 74px;
  flex-shrink: 0;
  overflow: hidden;
  background: #27227f;
}


.header-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 240px;
  height: 100%;
  background: #0088cc;
  clip-path: polygon(
    32% 0,
    100% 0,
    100% 100%,
    0 100%
  );
}


.header-content {
  position: relative;
  z-index: 2;
  width: 100%;
  height: 100%;
  padding: 0 38px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}


.brand {
  color: #ffffff;
  font-size: 17px;
  font-weight: 800;
  letter-spacing: 1.4px;
}


.language-button {
  width: 46px;
  height: 34px;
  border: 1px solid rgba(255, 255, 255, 0.55);
  border-radius: 7px;
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}


.language-button:hover {
  background: rgba(255, 255, 255, 0.18);
}


/* CONTEÚDO */

.advertisement-content {
  width: 100%;
  max-width: 1280px;
  flex: 1;
  margin: 0 auto;
  padding: 55px 40px 35px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}


.advertisement-card {
  position: relative;
  width: 100%;
  min-height: 500px;
  overflow: hidden;
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  border: 1px solid #e0e0ef;
  border-radius: 16px;
  background: #ffffff;
  box-shadow:
    0 18px 45px
    rgba(39, 34, 127, 0.09);
}


.advertisement-copy {
  position: relative;
  z-index: 2;
  padding: 75px 65px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}


.eyebrow {
  margin-bottom: 15px;
  color: #0088cc;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 1.2px;
  text-transform: uppercase;
}


h1 {
  max-width: 600px;
  margin: 0;
  color: #111827;
  font-size: 46px;
  line-height: 1.08;
  letter-spacing: -1px;
}


.description {
  max-width: 570px;
  margin: 22px 0 0;
  color: #6b7280;
  font-size: 17px;
  line-height: 1.7;
}


.campaign-note {
  width: fit-content;
  margin-top: 28px;
  padding: 11px 15px;
  display: flex;
  align-items: center;
  gap: 9px;
  border-radius: 8px;
  background: #f4f4fb;
  color: #4b5563;
  font-size: 13px;
}


.note-icon {
  color: #0088cc;
}


.participate-button {
  width: fit-content;
  min-width: 180px;
  min-height: 48px;
  margin-top: 32px;
  padding: 0 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 13px;
  border: 0;
  border-radius: 8px;
  background: #27227f;
  color: #ffffff;
  font-size: 15px;
  font-weight: 800;
  cursor: pointer;
  transition:
    background 0.15s ease,
    transform 0.15s ease;
}


.participate-button:hover {
  background: #1c1860;
  transform: translateY(-1px);
}


.button-arrow {
  font-size: 20px;
}


/* VISUAL */

.advertisement-visual {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    linear-gradient(
      145deg,
      #27227f 0%,
      #27227f 58%,
      #0088cc 58%,
      #0088cc 100%
    );
}


.visual-circle {
  position: relative;
  z-index: 2;
  width: 260px;
  height: 260px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 8px solid rgba(255, 255, 255, 0.13);
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.09);
  box-shadow:
    0 20px 45px
    rgba(15, 12, 51, 0.25);
  color: #ffffff;
}


.prize-symbol {
  width: 76px;
  height: 76px;
  margin-bottom: 17px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #ffffff;
  color: #27227f;
  font-size: 36px;
}


.visual-circle strong {
  font-size: 27px;
}


.visual-circle span {
  margin-top: 7px;
  color: rgba(255, 255, 255, 0.67);
  font-size: 13px;
}


.card-decoration {
  position: absolute;
  inset: 0;
  pointer-events: none;
}


.decoration-circle {
  position: absolute;
  border-radius: 50%;
  border: 1px solid rgba(39, 34, 127, 0.06);
}


.circle-one {
  width: 230px;
  height: 230px;
  top: -110px;
  left: -70px;
}


.circle-two {
  width: 150px;
  height: 150px;
  bottom: -70px;
  left: 42%;
}


.footer-text {
  margin: 22px 0 0;
  color: #9ca3af;
  font-size: 12px;
}


/* BARRA INFERIOR */

.bottom-stripe {
  position: relative;
  height: 11px;
  overflow: hidden;
  flex-shrink: 0;
  background: #27227f;
}


.bottom-accent {
  position: absolute;
  top: 0;
  right: 0;
  width: 190px;
  height: 100%;
  background: #0088cc;
  clip-path: polygon(
    22% 0,
    100% 0,
    100% 100%,
    0 100%
  );
}


/* RESPONSIVO */

@media (max-width: 850px) {
  .advertisement-card {
    grid-template-columns: 1fr;
  }


  .advertisement-copy {
    padding: 50px 38px;
  }


  .advertisement-visual {
    min-height: 300px;
  }


  h1 {
    font-size: 38px;
  }
}


@media (max-width: 500px) {
  .header-content {
    padding: 0 20px;
  }


  .advertisement-content {
    padding: 30px 18px 25px;
  }


  .advertisement-copy {
    padding: 42px 25px;
  }


  h1 {
    font-size: 32px;
  }


  .description {
    font-size: 15px;
  }


  .visual-circle {
    width: 215px;
    height: 215px;
  }
}
</style>