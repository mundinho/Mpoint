(() => {
  // Navegação: sombra ao rolar e menu móvel
  const nav = document.getElementById('nav')
  const toggle = document.getElementById('navToggle')

  const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 8)
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })

  function closeMenu() {
    nav.classList.remove('open')
    toggle.setAttribute('aria-expanded', 'false')
  }

  toggle.addEventListener('click', () => {
    const open = nav.classList.toggle('open')
    toggle.setAttribute('aria-expanded', String(open))
  })

  const navLinks = [...document.querySelectorAll('#navLinks a')]
  navLinks.forEach(link => link.addEventListener('click', closeMenu))

  // Link activo conforme a secção visível
  const sectionObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return
      navLinks.forEach(a =>
        a.classList.toggle('active', a.getAttribute('href') === `#${entry.target.id}`)
      )
    })
  }, { rootMargin: '-45% 0px -50% 0px' })

  navLinks
    .map(a => document.querySelector(a.getAttribute('href')))
    .filter(Boolean)
    .forEach(section => sectionObserver.observe(section))

  // Revelação suave ao rolar
  const revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return
      entry.target.classList.add('in')
      revealObserver.unobserve(entry.target)
    })
  }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' })

  document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el))

  // Separadores do painel
  const tabs = [...document.querySelectorAll('.tab')]
  const screens = [...document.querySelectorAll('.screen')]

  tabs.forEach((tab, index) =>
    tab.addEventListener('click', () => {
      tabs.forEach((t, i) => {
        t.classList.toggle('active', i === index)
        t.setAttribute('aria-selected', String(i === index))
      })
      screens.forEach((s, i) => s.classList.toggle('active', i === index))
    })
  )

  document.getElementById('year').textContent = new Date().getFullYear()
})()
