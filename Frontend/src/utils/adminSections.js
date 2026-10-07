// Navegação por secções dentro das páginas do painel de administração.
// Cada página é o seu próprio contentor de scroll, com o cabeçalho fixo (sticky) no topo.

const GAP = 20

function getPage() {
  return document.querySelector('.admin-shell-content > *')
}

function headerHeight(page) {
  return page?.querySelector('.top-header')?.offsetHeight || 0
}

export function scrollToSection(id, behavior = 'smooth') {
  const page = getPage()
  if (!page) return false

  if (!id) {
    page.scrollTo({ top: 0, behavior })
    return true
  }

  const target = document.getElementById(id)
  if (!target) return false

  const top =
    target.getBoundingClientRect().top -
    page.getBoundingClientRect().top +
    page.scrollTop -
    headerHeight(page) -
    GAP

  page.scrollTo({ top: Math.max(top, 0), behavior })
  return true
}

// Tenta durante alguns frames: a secção pode ainda não estar renderizada
export function scrollToSectionWhenReady(id, attempts = 180) {
  if (scrollToSection(id) || attempts <= 0) return
  requestAnimationFrame(() => scrollToSectionWhenReady(id, attempts - 1))
}

export function currentSection(ids) {
  const page = getPage()
  if (!page || !ids.length) return null

  // No fundo da página, a última secção é a activa
  if (page.scrollTop + page.clientHeight >= page.scrollHeight - 4) {
    const last = [...ids].reverse().find(id => document.getElementById(id))
    if (last) return last
  }

  const limit = page.getBoundingClientRect().top + headerHeight(page) + GAP + 40
  let active = null

  for (const id of ids) {
    const el = document.getElementById(id)
    if (el && el.getBoundingClientRect().top <= limit) active = id
  }

  return active || ids.find(id => document.getElementById(id)) || null
}
