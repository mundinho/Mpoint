import { createApp } from 'vue'
import { createI18n } from 'vue-i18n'
import App from './App.vue'
import pt from './locales/pt.json'
import en from './locales/en.json'
import router from './router'
import './assets/scrollbar.css'


const savedLanguage =
  localStorage.getItem('language') || 'pt'


const i18n = createI18n({
  legacy: false,
  locale: savedLanguage,
  fallbackLocale: 'pt',
  messages: {
    pt,
    en
  }
})


const app = createApp(App)

app.use(router)
app.use(i18n)

app.mount('#app')