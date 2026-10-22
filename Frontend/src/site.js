// Onde estamos: painel de administração, jogo de uma campanha ou domínio antigo.
//   admin.mpoints.site            → painel (em qualquer caminho)
//   bci.c.mpoints.site            → jogo da campanha "bci"
//   mpoints.emedx.co.mz (antigo)  → jogo em "/", painel em "/admin"
// Configurado no build: VITE_ADMIN_HOSTS e VITE_GAME_HOST_SUFFIX (.env.production / .env.demo)

const SLUG_PATTERN = /^[a-z0-9](?:[a-z0-9-]{0,38}[a-z0-9])$/

const gameSuffix = (import.meta.env.VITE_GAME_HOST_SUFFIX || '').trim().toLowerCase()
const adminHosts = (import.meta.env.VITE_ADMIN_HOSTS || '')
  .split(',')
  .map(host => host.trim().toLowerCase())
  .filter(Boolean)

const hostname = window.location.hostname.toLowerCase()

function detect() {
  if (adminHosts.includes(hostname)) {
    return { mode: 'admin', slug: null }
  }

  if (gameSuffix && hostname.endsWith(`.${gameSuffix}`)) {
    const slug = hostname.slice(0, -(gameSuffix.length + 1))
    // Só um nível de subdomínio e só caracteres permitidos
    return { mode: 'game', slug: SLUG_PATTERN.test(slug) && !slug.includes('--') ? slug : null }
  }

  return { mode: 'legacy', slug: null }
}

export const site = detect()

export const campaignHostSuffix = gameSuffix

export function campaignLink(slug) {
  return slug && gameSuffix ? `https://${slug}.${gameSuffix}` : null
}

export function isValidCampaignSlug(slug) {
  return SLUG_PATTERN.test(slug || '') && !slug.includes('--')
}

// Sugere um link a partir do nome: "Campanha do BCI 2026" → "campanha-do-bci-2026"
export function slugify(text) {
  return String(text || '')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 40)
    .replace(/-+$/g, '')
}
