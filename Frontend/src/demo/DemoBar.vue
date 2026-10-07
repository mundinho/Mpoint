<script setup>
import { landingUrl } from './index'
</script>

<template>
  <div class="demo-bar" role="note">
    <span class="badge">Demonstração</span>
    <span class="text">SMS simulados · dados fictícios, reiniciados a cada 3 horas</span>
    <a class="link" :href="landingUrl" target="_blank" rel="noopener">Sobre o MPoints</a>
  </div>
</template>

<style scoped>
.demo-bar {
  position: fixed;
  z-index: 10002;
  top: 0;
  left: 50%;
  display: flex;
  align-items: center;
  gap: 10px;
  max-width: calc(100vw - 120px);
  padding: 4px 6px 4px 4px;
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-top: none;
  border-radius: 0 0 6px 6px;
  background: #0088cc;
  color: #ffffff;
  font-family: Arial, Helvetica, sans-serif;
  font-size: 12px;
  white-space: nowrap;
  transform: translateX(-50%);
  box-shadow: 0 4px 14px rgba(15, 12, 51, 0.2);
}

.badge {
  padding: 2px 7px;
  border-radius: 4px;
  background: #ffffff;
  color: #27227f;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.text {
  overflow: hidden;
  text-overflow: ellipsis;
}

.link {
  color: #ffffff;
  font-weight: 700;
  text-decoration: underline;
  text-underline-offset: 2px;
}

@media (max-width: 700px) {
  .text,
  .link {
    display: none;
  }

  .demo-bar {
    padding: 3px;
  }
}
</style>
