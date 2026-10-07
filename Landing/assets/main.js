(() => {
  const doc = document.documentElement
  doc.classList.add('js')

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

  // Navegação: fundo ao rolar e menu móvel
  const nav = document.getElementById('nav')
  const toggle = document.getElementById('navToggle')

  const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 12)
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })

  toggle.addEventListener('click', () => {
    const open = nav.classList.toggle('open')
    toggle.setAttribute('aria-expanded', String(open))
  })

  document.querySelectorAll('#navLinks a').forEach(link =>
    link.addEventListener('click', () => {
      nav.classList.remove('open')
      toggle.setAttribute('aria-expanded', 'false')
    })
  )

  // Link activo conforme a secção visível
  const navLinks = [...document.querySelectorAll('#navLinks a')]
  const sections = navLinks
    .map(a => document.querySelector(a.getAttribute('href')))
    .filter(Boolean)

  const sectionObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return
      navLinks.forEach(a =>
        a.classList.toggle('active', a.getAttribute('href') === `#${entry.target.id}`)
      )
    })
  }, { rootMargin: '-45% 0px -50% 0px' })

  sections.forEach(s => sectionObserver.observe(s))

  // Revelação ao rolar
  const revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return
      entry.target.classList.add('in')
      revealObserver.unobserve(entry.target)
    })
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' })

  document.querySelectorAll('.reveal').forEach(el => {
    if (el.dataset.delay) el.style.setProperty('--d', `${el.dataset.delay}ms`)
    revealObserver.observe(el)
  })

  // Contadores
  const counterObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return
      const el = entry.target
      const target = Number(el.dataset.count)
      counterObserver.unobserve(el)

      if (reduceMotion) {
        el.textContent = target.toLocaleString('pt-PT')
        return
      }

      const duration = 1400
      const start = performance.now()
      const tick = now => {
        const p = Math.min((now - start) / duration, 1)
        const eased = 1 - Math.pow(1 - p, 3)
        el.textContent = Math.round(target * eased).toLocaleString('pt-PT')
        if (p < 1) requestAnimationFrame(tick)
      }
      requestAnimationFrame(tick)
    })
  }, { threshold: 0.6 })

  if (!reduceMotion) {
    document.querySelectorAll('[data-count]').forEach(el => {
      el.textContent = '0'
      counterObserver.observe(el)
    })
  }

  // Inclinação 3D do mockup do hero, segue o rato
  const tilt = document.querySelector('[data-tilt]')
  const finePointer = window.matchMedia('(hover: hover) and (min-width: 961px)').matches

  if (tilt && finePointer && !reduceMotion) {
    const hero = document.querySelector('.hero')
    hero.addEventListener('mousemove', e => {
      const r = hero.getBoundingClientRect()
      const x = (e.clientX - r.left) / r.width - 0.5
      const y = (e.clientY - r.top) / r.height - 0.5
      tilt.style.transform = `rotateY(${-9 + x * 8}deg) rotateX(${5 - y * 6}deg)`
    })
    hero.addEventListener('mouseleave', () => {
      tilt.style.transform = ''
    })
  }

  // Brilho que segue o rato nos cartões
  document.querySelectorAll('.feature').forEach(card => {
    card.addEventListener('mousemove', e => {
      const r = card.getBoundingClientRect()
      card.style.setProperty('--mx', `${e.clientX - r.left}px`)
      card.style.setProperty('--my', `${e.clientY - r.top}px`)
    })
  })

  // Showcase com separadores e rotação automática
  const tabs = [...document.querySelectorAll('.tab')]
  const screens = [...document.querySelectorAll('.screen')]
  const progress = document.getElementById('tabProgress')
  const INTERVAL = 6000
  let current = 0
  let timer = null
  let startedAt = 0
  let frame = null

  function show(index) {
    current = index
    tabs.forEach((t, i) => {
      t.classList.toggle('active', i === index)
      t.setAttribute('aria-selected', String(i === index))
    })
    screens.forEach((s, i) => s.classList.toggle('active', i === index))
  }

  function animateProgress(now) {
    const p = Math.min((now - startedAt) / INTERVAL, 1)
    if (progress) progress.style.width = `${p * 100}%`
    frame = requestAnimationFrame(animateProgress)
  }

  function startAuto() {
    if (reduceMotion) return
    stopAuto()
    startedAt = performance.now()
    frame = requestAnimationFrame(animateProgress)
    timer = setInterval(() => {
      show((current + 1) % tabs.length)
      startedAt = performance.now()
    }, INTERVAL)
  }

  function stopAuto() {
    clearInterval(timer)
    cancelAnimationFrame(frame)
  }

  tabs.forEach((tab, i) =>
    tab.addEventListener('click', () => {
      show(i)
      startAuto()
    })
  )

  const showcase = document.querySelector('.showcase')
  if (showcase) {
    new IntersectionObserver(([entry]) => {
      entry.isIntersecting ? startAuto() : stopAuto()
    }, { threshold: 0.3 }).observe(showcase)
  }

  document.getElementById('year').textContent = new Date().getFullYear()
})()
