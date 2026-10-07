(() => {
  const root = document.documentElement
  const desktop = window.matchMedia('(min-width: 960px)')

  /* ---------- Navegação ---------- */
  const nav = document.getElementById('nav')
  const toggle = document.getElementById('navToggle')
  const menu = document.getElementById('navMenu')

  // Sombra do cabeçalho: um sentinela no topo evita ouvir cada evento de scroll
  const sentinel = document.createElement('div')
  sentinel.setAttribute('aria-hidden', 'true')
  sentinel.style.cssText = 'position:absolute;top:0;left:0;width:1px;height:8px;pointer-events:none'
  document.body.prepend(sentinel)

  new IntersectionObserver(([entry]) => {
    nav.classList.toggle('scrolled', !entry.isIntersecting)
  }).observe(sentinel)

  function setMenu(open, { restoreFocus = true } = {}) {
    nav.classList.toggle('open', open)
    root.classList.toggle('menu-open', open)
    toggle.setAttribute('aria-expanded', String(open))
    toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu')

    if (open) {
      menu.querySelector('a')?.focus()
    } else if (restoreFocus) {
      toggle.focus()
    }
    updateMobileCta()
  }

  const isOpen = () => nav.classList.contains('open')

  toggle.addEventListener('click', () => setMenu(!isOpen()))

  menu.addEventListener('click', event => {
    if (event.target.closest('a') && isOpen()) setMenu(false, { restoreFocus: false })
  })

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && isOpen()) setMenu(false)
  })

  // Mantém o foco dentro do menu aberto (telemóvel)
  nav.addEventListener('keydown', event => {
    if (event.key !== 'Tab' || !isOpen()) return
    const focusables = [toggle, ...menu.querySelectorAll('a')]
    const first = focusables[0]
    const last = focusables[focusables.length - 1]

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault()
      first.focus()
    }
  })

  desktop.addEventListener('change', event => {
    if (event.matches && isOpen()) setMenu(false, { restoreFocus: false })
  })

  /* ---------- Link activo conforme a secção visível ---------- */
  const navLinks = [...menu.querySelectorAll('.nav-links a')]
  const sections = navLinks
    .map(link => document.querySelector(link.getAttribute('href')))
    .filter(Boolean)

  const sectionObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return
      navLinks.forEach(link => {
        const active = link.getAttribute('href') === `#${entry.target.id}`
        if (active) link.setAttribute('aria-current', 'true')
        else link.removeAttribute('aria-current')
      })
    })
  }, { rootMargin: '-45% 0px -50% 0px' })

  // Secções sem link (hero, porquê, segurança, contacto) limpam o destaque
  document.querySelectorAll('main > section[id]').forEach(section => sectionObserver.observe(section))

  /* ---------- Revelação ao rolar ---------- */
  document.querySelectorAll('[data-stagger]').forEach(group => {
    ;[...group.children].forEach((child, index) => child.style.setProperty('--i', index % 3))
  })

  const revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return
      entry.target.classList.add('in')
      revealObserver.unobserve(entry.target)
    })
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' })

  document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el))

  /* ---------- Separadores do painel (padrão WAI-ARIA Tabs) ---------- */
  const tabs = [...document.querySelectorAll('[role="tab"]')]
  const panel = document.getElementById('showcase-panel')
  const image = document.getElementById('showcaseImg')
  const notes = [...document.querySelectorAll('.notes')]

  function selectTab(tab, { focus = false } = {}) {
    if (tab.getAttribute('aria-selected') === 'true') {
      if (focus) tab.focus()
      return
    }

    tabs.forEach(t => {
      const selected = t === tab
      t.setAttribute('aria-selected', String(selected))
      t.tabIndex = selected ? 0 : -1
    })

    panel.setAttribute('aria-labelledby', tab.id)
    notes.forEach(list => { list.hidden = list.dataset.tab !== tab.id })
    if (focus) tab.focus()

    const swap = () => {
      image.srcset = `${tab.dataset.srcSm} 800w, ${tab.dataset.src} 1600w`
      image.src = tab.dataset.src
      image.alt = tab.dataset.alt
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      swap()
      return
    }

    image.classList.add('is-swapping')
    setTimeout(() => {
      swap()
      const show = () => image.classList.remove('is-swapping')
      image.complete ? show() : image.addEventListener('load', show, { once: true })
    }, 180)
  }

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => selectTab(tab))

    tab.addEventListener('keydown', event => {
      const keys = {
        ArrowDown: index + 1,
        ArrowRight: index + 1,
        ArrowUp: index - 1,
        ArrowLeft: index - 1,
        Home: 0,
        End: tabs.length - 1
      }

      if (!(event.key in keys)) return
      event.preventDefault()
      const next = tabs[(keys[event.key] + tabs.length) % tabs.length]
      selectTab(next, { focus: true })
    })
  })

  // Pré-carrega os outros ecrãs quando a secção se aproxima
  const showcase = document.querySelector('.showcase')
  if (showcase) {
    const preload = new IntersectionObserver(([entry]) => {
      if (!entry.isIntersecting) return
      // Mesmo srcset/sizes do ecrã principal: o navegador escolhe o tamanho certo
      tabs.forEach(tab => {
        const img = new Image()
        img.sizes = image.sizes
        img.srcset = `${tab.dataset.srcSm} 800w, ${tab.dataset.src} 1600w`
      })
      preload.disconnect()
    }, { rootMargin: '400px 0px' })
    preload.observe(showcase)
  }

  /* ---------- Copiar email ---------- */
  const copyStatus = document.getElementById('copyStatus')

  document.querySelectorAll('[data-copy]').forEach(button => {
    button.addEventListener('click', async () => {
      const text = button.dataset.copy
      let copied = false

      try {
        await navigator.clipboard.writeText(text)
        copied = true
      } catch {
        // Sem permissão de clipboard: selecciona o texto para cópia manual
        const range = document.createRange()
        range.selectNodeContents(button.previousElementSibling)
        const selection = window.getSelection()
        selection.removeAllRanges()
        selection.addRange(range)
      }

      button.textContent = copied ? 'Copiado' : 'Seleccionado'
      button.classList.add('is-done')
      copyStatus.textContent = copied ? `${text} copiado` : `${text} seleccionado`

      clearTimeout(button._reset)
      button._reset = setTimeout(() => {
        button.textContent = 'Copiar'
        button.classList.remove('is-done')
        copyStatus.textContent = ''
      }, 2200)
    })
  })

  /* ---------- Barra de contacto fixa (telemóvel) ---------- */
  // Aparece depois do hero e esconde-se quando a secção de contacto está à vista
  const mobileCta = document.getElementById('mobileCta')
  const hero = document.getElementById('topo')
  const contact = document.getElementById('contacto')
  const visible = { hero: true, contact: false }

  function updateMobileCta() {
    const show = !visible.hero && !visible.contact && !isOpen() && !desktop.matches
    mobileCta.classList.toggle('is-visible', show)
    mobileCta.setAttribute('aria-hidden', String(!show))
    mobileCta.querySelectorAll('a').forEach(a => { a.tabIndex = show ? 0 : -1 })
  }

  new IntersectionObserver(([entry]) => {
    visible.hero = entry.isIntersecting
    updateMobileCta()
  }).observe(hero)

  new IntersectionObserver(([entry]) => {
    visible.contact = entry.isIntersecting
    updateMobileCta()
  }).observe(contact)

  desktop.addEventListener('change', updateMobileCta)

  document.getElementById('year').textContent = new Date().getFullYear()
})()
