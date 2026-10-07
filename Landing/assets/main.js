/* ==========================================================================
   MPoints — landing
   1. Navegação          5. Jogo de demonstração
   2. Barra móvel        6. Motion graphics (hero, OTP)
   3. Separadores        7. FAQ
   4. Utilitários        8. Coreografia de scroll (GSAP + ScrollTrigger)
   O site funciona sem GSAP e com movimento reduzido: só perde a animação.
   ========================================================================== */
(() => {
  const root = document.documentElement
  const desktop = window.matchMedia('(min-width: 960px)')
  const reduceQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
  const hasGsap = typeof window.gsap !== 'undefined' && typeof window.ScrollTrigger !== 'undefined'
  const motion = hasGsap && !reduceQuery.matches

  if (!motion) root.classList.add('no-motion')
  if (hasGsap) gsap.registerPlugin(ScrollTrigger)

  const $ = (selector, scope = document) => scope.querySelector(selector)
  const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)]
  const random = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min
  const shuffle = list => list.map(v => [Math.random(), v]).sort((a, b) => a[0] - b[0]).map(v => v[1])
  const wait = ms => new Promise(resolve => setTimeout(resolve, ms))

  // Corre fn enquanto el estiver visível (pára fora do ecrã e com o separador oculto)
  function whileVisible(el, { onShow, onHide }) {
    let visible = false
    const sync = () => {
      const active = visible && !document.hidden
      active ? onShow() : onHide()
    }
    new IntersectionObserver(([entry]) => {
      visible = entry.isIntersecting
      sync()
    }, { threshold: 0.2 }).observe(el)
    document.addEventListener('visibilitychange', sync)
  }

  /* ------------------------------------------------------------------------
     1. Navegação
     ------------------------------------------------------------------------ */
  const nav = $('#nav')
  const toggle = $('#navToggle')
  const menu = $('#navMenu')

  const sentinel = document.createElement('div')
  sentinel.setAttribute('aria-hidden', 'true')
  sentinel.style.cssText = 'position:absolute;top:0;left:0;width:1px;height:8px;pointer-events:none'
  document.body.prepend(sentinel)

  new IntersectionObserver(([entry]) => {
    nav.classList.toggle('scrolled', !entry.isIntersecting)
  }).observe(sentinel)

  const isMenuOpen = () => nav.classList.contains('open')

  function setMenu(open, { restoreFocus = true } = {}) {
    nav.classList.toggle('open', open)
    root.classList.toggle('menu-open', open)
    toggle.setAttribute('aria-expanded', String(open))
    toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu')

    if (open) $('a', menu)?.focus()
    else if (restoreFocus) toggle.focus()

    updateMobileCta()
  }

  toggle.addEventListener('click', () => setMenu(!isMenuOpen()))

  menu.addEventListener('click', event => {
    if (event.target.closest('a') && isMenuOpen()) setMenu(false, { restoreFocus: false })
  })

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && isMenuOpen()) setMenu(false)
  })

  nav.addEventListener('keydown', event => {
    if (event.key !== 'Tab' || !isMenuOpen()) return
    const focusables = [toggle, ...$$('a', menu)]
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
    if (event.matches && isMenuOpen()) setMenu(false, { restoreFocus: false })
  })

  // Link activo conforme a secção visível; secções sem link limpam o destaque
  const navLinks = $$('.nav-links a', menu)
  const sectionObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return
      navLinks.forEach(link => {
        if (link.getAttribute('href') === `#${entry.target.id}`) link.setAttribute('aria-current', 'true')
        else link.removeAttribute('aria-current')
      })
    })
  }, { rootMargin: '-45% 0px -50% 0px' })

  $$('main > section[id]').forEach(section => sectionObserver.observe(section))

  /* ------------------------------------------------------------------------
     2. Barra fixa no telemóvel: depois do hero, escondida na chamada final
     ------------------------------------------------------------------------ */
  const mobileCta = $('#mobileCta')
  const ctaVisibility = { hero: true, end: false }

  function updateMobileCta() {
    const show = !ctaVisibility.hero && !ctaVisibility.end && !isMenuOpen() && !desktop.matches
    mobileCta.classList.toggle('is-visible', show)
    mobileCta.setAttribute('aria-hidden', String(!show))
    $$('a', mobileCta).forEach(a => { a.tabIndex = show ? 0 : -1 })
  }

  new IntersectionObserver(([entry]) => {
    ctaVisibility.hero = entry.isIntersecting
    updateMobileCta()
  }).observe($('#topo'))

  new IntersectionObserver(([entry]) => {
    ctaVisibility.end = entry.isIntersecting
    updateMobileCta()
  }).observe($('#comecar'))

  desktop.addEventListener('change', updateMobileCta)

  /* ------------------------------------------------------------------------
     3. Separadores do painel (WAI-ARIA Tabs) com rotação automática
     ------------------------------------------------------------------------ */
  const tabs = $$('[role="tab"]')
  const panel = $('#showcase-panel')
  const screens = $('#screens')
  const notes = $$('.notes')
  const showcase = $('#showcase')
  const ROTATE_SECONDS = 6
  let current = 0
  let autoplay = motion
  let progressTween = null
  let swapping = Promise.resolve()

  function swapImage(tab) {
    const previous = $('.screen.is-current', screens)
    const next = document.createElement('img')
    next.className = 'screen'
    next.width = 1600
    next.height = 1000
    next.decoding = 'async'
    next.sizes = previous.sizes
    next.srcset = `${tab.dataset.srcSm} 800w, ${tab.dataset.src} 1600w`
    next.src = tab.dataset.src
    next.alt = tab.dataset.alt

    if (!motion) {
      previous.replaceWith(next)
      next.classList.add('is-current')
      return Promise.resolve()
    }

    // Entrada em diagonal, o mesmo corte do cabeçalho da plataforma
    return new Promise(resolve => {
      const reveal = () => {
        screens.append(next)
        gsap.fromTo(next,
          { clipPath: 'polygon(115% 0%, 115% 0%, 100% 100%, 100% 100%)' },
          {
            clipPath: 'polygon(-15% 0%, 115% 0%, 100% 100%, 0% 100%)',
            duration: 0.9,
            ease: 'power3.inOut',
            onComplete: () => {
              previous.remove()
              next.classList.add('is-current')
              gsap.set(next, { clearProps: 'clipPath' })
              resolve()
            }
          })
      }
      next.complete ? reveal() : next.addEventListener('load', reveal, { once: true })
      next.addEventListener('error', () => { previous.replaceWith(next); resolve() }, { once: true })
    })
  }

  function selectTab(index, { focus = false, fromUser = false } = {}) {
    const tab = tabs[index]
    if (fromUser) stopAutoplay()
    if (focus) tab.focus()
    if (index === current) return

    current = index
    tabs.forEach((t, i) => {
      t.setAttribute('aria-selected', String(i === index))
      t.tabIndex = i === index ? 0 : -1
    })
    panel.setAttribute('aria-labelledby', tab.id)
    notes.forEach(list => { list.hidden = list.dataset.tab !== tab.id })

    if (motion) {
      const visibleNotes = $$(`.notes[data-tab="${tab.id}"] li`)
      gsap.fromTo(visibleNotes, { autoAlpha: 0, y: 8 }, { autoAlpha: 1, y: 0, stagger: 0.06, duration: 0.4 })
    }

    swapping = swapping.then(() => swapImage(tab))
  }

  function runProgress() {
    if (!autoplay) return
    progressTween?.kill()
    $$('.tab-progress').forEach(bar => gsap.set(bar, { scaleX: 0 }))
    progressTween = gsap.to($('.tab-progress', tabs[current]), {
      scaleX: 1,
      duration: ROTATE_SECONDS,
      ease: 'none',
      onComplete: () => {
        selectTab((current + 1) % tabs.length)
        runProgress()
      }
    })
  }

  function stopAutoplay() {
    autoplay = false
    progressTween?.kill()
    if (hasGsap) gsap.set($$('.tab-progress'), { scaleX: 0 })
  }

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => selectTab(index, { fromUser: true }))
    tab.addEventListener('keydown', event => {
      const keys = { ArrowDown: 1, ArrowRight: 1, ArrowUp: -1, ArrowLeft: -1 }
      let next = null
      if (event.key in keys) next = (index + keys[event.key] + tabs.length) % tabs.length
      if (event.key === 'Home') next = 0
      if (event.key === 'End') next = tabs.length - 1
      if (next === null) return
      event.preventDefault()
      selectTab(next, { focus: true, fromUser: true })
    })
  })

  if (motion) {
    whileVisible(showcase, {
      onShow: () => { if (autoplay && !progressTween?.isActive()) runProgress() },
      onHide: () => progressTween?.pause()
    })
    showcase.addEventListener('pointerenter', () => progressTween?.pause())
    showcase.addEventListener('pointerleave', () => { if (autoplay) progressTween?.resume() })
    showcase.addEventListener('focusin', () => progressTween?.pause())
  }

  /* ------------------------------------------------------------------------
     4. Utilitários de motion
     ------------------------------------------------------------------------ */
  // Divide um título em palavras com máscara (mantém o texto acessível)
  function splitWords(el) {
    if (el.dataset.splitDone) return $$('.word-inner', el)
    const words = el.textContent.trim().split(/\s+/)
    el.setAttribute('aria-label', el.textContent.trim())
    el.innerHTML = words
      .map(word => `<span class="word" aria-hidden="true"><span class="word-inner">${word}</span></span>`)
      .join(' ')
    el.dataset.splitDone = 'true'
    return $$('.word-inner', el)
  }

  // Prepara os traços de um SVG para serem "desenhados"
  function prepareDraw(svg) {
    return $$('path', svg).map(path => {
      const length = path.getTotalLength()
      path.style.strokeDasharray = length
      path.style.strokeDashoffset = length
      return path
    })
  }

  /* ------------------------------------------------------------------------
     5. Jogo de demonstração
     ------------------------------------------------------------------------ */
  const ICONS = {
    won: '<svg viewBox="0 0 24 24"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4ZM7 6H4v2a3 3 0 0 0 3 3M17 6h3v2a3 3 0 0 1-3 3" /></svg>',
    lost: '<svg viewBox="0 0 24 24"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18ZM8 15h8M9 9.5h.01M15 9.5h.01" /></svg>',
    retry: '<svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 0 1 15.5-6.2L21 8M21 3v5h-5M21 12a9 9 0 0 1-15.5 6.2L3 16M3 21v-5h5" /></svg>'
  }
  const PRIZES = ['Smartphone', 'Cabaz alimentar', 'Voucher de compras', 'Auriculares sem fios', 'Recarga de saldo']

  const kioskGrid = $('#kioskGrid')
  const kioskFlip = $('#kioskFlip')
  const kioskBack = $('#kioskBack')
  const kioskAgain = $('#kioskAgain')
  const kioskStatus = $('#kioskStatus')
  const resultIcon = $('#resultIcon')
  const resultNumber = $('#resultNumber')
  const resultTitle = $('#resultTitle')
  const resultText = $('#resultText')
  let kioskBusy = false
  let lastOutcome = null
  let lastCell = null

  function buildKiosk() {
    const numbers = Array.from({ length: 30 }, (_, i) => 101 + i)
    const opened = new Set(shuffle(numbers).slice(0, 8))
    kioskGrid.innerHTML = ''

    numbers.forEach(number => {
      const cell = document.createElement('button')
      cell.type = 'button'
      cell.className = 'k-cell'
      cell.textContent = number
      cell.dataset.number = number
      if (opened.has(number)) {
        cell.disabled = true
        cell.setAttribute('aria-label', `Número ${number}, já aberto`)
      } else {
        cell.setAttribute('aria-label', `Número ${number}, disponível`)
      }
      kioskGrid.append(cell)
    })
  }

  function setFlipped(flipped) {
    kioskFlip.classList.toggle('is-flipped', flipped)
    kioskBack.setAttribute('aria-hidden', String(!flipped))
  }

  function burst() {
    if (!motion) return
    const colors = ['#27227f', '#0088cc', '#00b4d8', '#f5b301']
    for (let i = 0; i < 22; i++) {
      const piece = document.createElement('span')
      piece.className = 'burst'
      piece.style.background = colors[i % colors.length]
      kioskBack.append(piece)
      const angle = (Math.PI * 2 * i) / 22 + Math.random() * 0.3
      const distance = random(70, 150)
      gsap.fromTo(piece,
        { x: 0, y: -60, scale: 1, rotation: 0, opacity: 1 },
        {
          x: Math.cos(angle) * distance,
          y: Math.sin(angle) * distance - 60,
          rotation: random(-180, 180),
          scale: 0.4,
          opacity: 0,
          duration: random(8, 12) / 10,
          ease: 'power3.out',
          onComplete: () => piece.remove()
        })
    }
  }

  async function play(cell) {
    if (kioskBusy || cell.disabled) return
    kioskBusy = true
    const number = Number(cell.dataset.number)
    cell.classList.add('is-selected')
    kioskStatus.textContent = `A abrir o número ${number}…`

    if (motion) {
      await gsap.fromTo(cell, { scale: 1 }, { scale: 1.15, duration: 0.18, yoyo: true, repeat: 3, ease: 'power1.inOut', clearProps: 'transform' })
    }

    const roll = Math.random()
    const outcome = roll < 0.35 ? 'won' : roll < 0.55 ? 'retry' : 'lost'
    const prize = PRIZES[random(0, PRIZES.length - 1)]

    cell.classList.remove('is-selected')
    cell.disabled = true
    cell.classList.toggle('is-won', outcome === 'won')
    cell.setAttribute('aria-label', `Número ${number}, ${outcome === 'won' ? 'premiado' : 'já aberto'}`)

    const content = {
      won: ['Parabéns!', `Ganhou: ${prize}. Na campanha real, o prémio fica registado e o participante recebe um SMS.`, 'Jogar de novo'],
      lost: ['Desta vez não foi', 'Obrigado por participar. Na campanha real, o participante recebe o resultado por SMS.', 'Jogar de novo'],
      retry: ['Tente novamente', 'Este número dá direito a uma nova tentativa. Escolha outro número disponível.', 'Escolher outro número']
    }[outcome]

    resultIcon.className = `result-icon is-${outcome}`
    resultIcon.innerHTML = ICONS[outcome]
    resultNumber.textContent = `Número ${number}`
    resultTitle.textContent = content[0]
    resultText.textContent = content[1]
    kioskAgain.textContent = content[2]
    lastOutcome = outcome
    lastCell = cell

    setFlipped(true)
    kioskStatus.textContent = `Número ${number}: ${content[0].replace(/[.!]$/, '')}. ${content[1]}`

    await wait(motion ? 450 : 0)
    resultTitle.focus({ preventScroll: true })
    if (motion) {
      gsap.fromTo([resultIcon, resultNumber, resultTitle, resultText, kioskAgain],
        { opacity: 0, y: 14 },
        { opacity: 1, y: 0, stagger: 0.07, duration: 0.45, ease: 'power3.out', clearProps: 'transform,opacity' })
      if (outcome === 'won') burst()
    }
    kioskBusy = false
  }

  kioskGrid.addEventListener('click', event => {
    const cell = event.target.closest('.k-cell')
    if (cell) play(cell)
  })

  kioskAgain.addEventListener('click', async () => {
    setFlipped(false)
    if (lastOutcome !== 'retry') {
      await wait(motion ? 350 : 0)
      buildKiosk()
      if (motion) gsap.from($$('.k-cell', kioskGrid), { scale: 0.6, autoAlpha: 0, stagger: { each: 0.015, from: 'random' }, duration: 0.35, clearProps: 'transform,opacity,visibility' })
    }
    await wait(motion ? 400 : 0)
    const target = lastOutcome === 'retry'
      ? $$('.k-cell:not(:disabled)', kioskGrid).find(c => c !== lastCell)
      : $('.k-cell:not(:disabled)', kioskGrid)
    target?.focus()
    kioskStatus.textContent = 'Escolha um número disponível.'
  })

  buildKiosk()

  /* ------------------------------------------------------------------------
     6. Motion graphics
     ------------------------------------------------------------------------ */
  // 6a. Hero: um sorteio a decorrer na mini grelha
  const drawGrid = $('#drawGrid')
  const drawToast = $('#drawToast')
  const drawToastTitle = $('#drawToastTitle')
  const drawToastPrize = $('#drawToastPrize')
  const drawCells = Array.from({ length: 20 }, (_, i) => {
    const cell = document.createElement('span')
    cell.className = 'dw-cell'
    cell.textContent = 201 + i
    drawGrid.append(cell)
    return cell
  })

  function resetDrawGrid() {
    drawCells.forEach(cell => cell.classList.remove('is-opened', 'is-picking', 'is-won'))
    shuffle(drawCells).slice(0, 5).forEach(cell => cell.classList.add('is-opened'))
  }

  function showDrawWinner(cell) {
    cell.classList.add('is-won')
    drawToastTitle.textContent = `Número ${cell.textContent}`
    drawToastPrize.textContent = `Vencedor · ${PRIZES[random(0, PRIZES.length - 1)]}`
    drawToast.classList.remove('is-hiding')
    drawToast.classList.add('is-shown')
  }

  let drawRunning = false
  let drawLoopId = 0

  async function drawLoop(id) {
    const alive = () => drawRunning && id === drawLoopId
    while (alive()) {
      const available = drawCells.filter(c => !c.classList.contains('is-opened') && !c.classList.contains('is-won'))
      if (available.length < 4) resetDrawGrid()

      const picks = shuffle(drawCells.filter(c => !c.classList.contains('is-opened'))).slice(0, 3)
      for (let i = 0; i < picks.length; i++) {
        if (!alive()) return
        const cell = picks[i]
        cell.classList.add('is-picking')
        await wait(650)
        cell.classList.remove('is-picking')
        if (i < picks.length - 1) {
          cell.classList.add('is-opened')
          await wait(350)
        } else {
          showDrawWinner(cell)
        }
      }
      await wait(2400)
      if (!alive()) return
      drawToast.classList.add('is-hiding')
      drawToast.classList.remove('is-shown')
      picks[picks.length - 1].classList.remove('is-won')
      picks[picks.length - 1].classList.add('is-opened')
      await wait(700)
    }
  }

  resetDrawGrid()

  if (motion) {
    whileVisible($('.hero-visual'), {
      onShow: () => {
        if (drawRunning) return
        drawRunning = true
        drawLoop(++drawLoopId)
      },
      onHide: () => { drawRunning = false }
    })
  } else {
    // Estado estático: um vencedor visível
    showDrawWinner(drawCells.find(c => !c.classList.contains('is-opened')))
  }

  // 6b. Segurança: o código OTP é introduzido e validado
  const otpCard = $('#otpCard')
  const otpBoxes = $$('.otp-boxes span', otpCard)
  const otpText = $('.otp-state-text', otpCard)

  function otpReset() {
    otpCard.classList.remove('is-valid')
    otpBoxes.forEach(box => { box.textContent = ''; box.className = '' })
    otpText.textContent = 'A aguardar o código…'
  }

  function otpValid(code) {
    otpBoxes.forEach((box, i) => { box.textContent = code[i]; box.className = 'is-filled' })
    otpCard.classList.add('is-valid')
    otpText.textContent = 'Código validado · acesso autorizado'
  }

  let otpRunning = false
  let otpLoopId = 0

  async function otpLoop(id) {
    const alive = () => otpRunning && id === otpLoopId
    while (alive()) {
      otpReset()
      await wait(700)
      const code = String(random(100000, 999999))
      for (let i = 0; i < 6; i++) {
        if (!alive()) return
        otpBoxes.forEach(box => box.classList.remove('is-active'))
        otpBoxes[i].classList.add('is-active')
        await wait(160)
        otpBoxes[i].textContent = code[i]
        otpBoxes[i].classList.add('is-filled')
        gsap.fromTo(otpBoxes[i], { y: -6, opacity: 0 }, { y: 0, opacity: 1, duration: 0.25, ease: 'back.out(3)', clearProps: 'transform,opacity' })
        await wait(140)
      }
      otpBoxes.forEach(box => box.classList.remove('is-active'))
      otpText.textContent = 'A validar…'
      await wait(700)
      if (!alive()) return
      otpValid(code)
      gsap.fromTo($('.otp-check', otpCard), { scale: 0.4 }, { scale: 1, duration: 0.45, ease: 'back.out(3)' })
      await wait(2600)
    }
  }

  if (motion) {
    whileVisible(otpCard, {
      onShow: () => {
        if (otpRunning) return
        otpRunning = true
        otpLoop(++otpLoopId)
      },
      onHide: () => { otpRunning = false }
    })
  } else {
    otpValid('482913')
  }

  /* ------------------------------------------------------------------------
     7. FAQ com abertura suave
     ------------------------------------------------------------------------ */
  if (motion) {
    $$('.faq details').forEach(details => {
      const summary = $('summary', details)
      const answer = $('.faq-answer', details)

      summary.addEventListener('click', event => {
        event.preventDefault()
        if (details.dataset.animating) return
        details.dataset.animating = 'true'

        if (details.open) {
          gsap.to(answer, {
            height: 0,
            duration: 0.3,
            ease: 'power2.inOut',
            onComplete: () => {
              details.open = false
              gsap.set(answer, { clearProps: 'height' })
              delete details.dataset.animating
            }
          })
        } else {
          details.open = true
          gsap.fromTo(answer, { height: 0 }, {
            height: 'auto',
            duration: 0.4,
            ease: 'power2.out',
            onComplete: () => {
              gsap.set(answer, { clearProps: 'height' })
              delete details.dataset.animating
            }
          })
          gsap.fromTo($('p', answer), { autoAlpha: 0, y: 6 }, { autoAlpha: 1, y: 0, duration: 0.35, delay: 0.08 })
        }
      })
    })
  }

  /* ------------------------------------------------------------------------
     8. Coreografia de scroll
     ------------------------------------------------------------------------ */
  if (motion) {
    const ease = 'power3.out'

    // Barra de progresso de leitura
    gsap.to('#scrollProgress', {
      scaleX: 1,
      ease: 'none',
      scrollTrigger: { start: 0, end: 'max', scrub: 0.3 }
    })

    // Hero: entrada coreografada
    const heroWords = splitWords($('.hero h1'))
    gsap.timeline({ defaults: { ease } })
      .from('.hero-shape', { clipPath: 'polygon(100% 100%, 100% 100%, 100% 100%)', duration: 1.1, ease: 'power3.inOut', clearProps: 'clipPath' }, 0)
      .fromTo('.hero h1', { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.01 }, 0.1)
      .from(heroWords, { yPercent: 110, duration: 0.9, stagger: 0.06 }, 0.1)
      .fromTo('.hero [data-hero]', { autoAlpha: 0, y: 18 }, { autoAlpha: 1, y: 0, duration: 0.7, stagger: 0.1 }, 0.35)
      .fromTo('.hero-frame', { autoAlpha: 0, x: 60 }, { autoAlpha: 1, x: 0, duration: 1.1 }, 0.3)
      .fromTo('.draw-widget', { autoAlpha: 0, y: 30, scale: 0.94 }, { autoAlpha: 1, y: 0, scale: 1, duration: 0.8, ease: 'back.out(1.4)' }, 0.8)
      .from(drawCells, { scale: 0, duration: 0.3, stagger: { each: 0.02, from: 'random' }, clearProps: 'transform' }, 1)

    // Títulos das secções: palavras sobem da máscara
    $$('[data-split]').forEach(heading => {
      if (heading.closest('.hero')) return
      const words = splitWords(heading)
      gsap.from(words, {
        yPercent: 110,
        duration: 0.8,
        ease,
        stagger: 0.045,
        scrollTrigger: { trigger: heading, start: 'top 88%', once: true }
      })
    })

    // Revelação genérica em lotes (escalonada)
    gsap.set('[data-reveal]', { autoAlpha: 0, y: 24 })
    ScrollTrigger.batch('[data-reveal]', {
      start: 'top bottom-=24',
      once: true,
      onEnter: batch => gsap.to(batch, { autoAlpha: 1, y: 0, duration: 0.8, ease, stagger: 0.08, overwrite: true })
    })

    // Ícones que se desenham (factos e funcionalidades)
    $$('.fact-icon, .f-icon svg').forEach(svg => {
      const paths = prepareDraw(svg)
      gsap.to(paths, {
        strokeDashoffset: 0,
        duration: 1.1,
        ease: 'power2.inOut',
        scrollTrigger: { trigger: svg, start: 'top 90%', once: true }
      })
      const card = svg.closest('.feature')
      card?.addEventListener('pointerenter', () => {
        gsap.fromTo(paths, { strokeDashoffset: (_, el) => el.getTotalLength() }, { strokeDashoffset: 0, duration: 0.7, ease: 'power2.out' })
      })
    })

    // Porquê: a coluna MPoints entra em diagonal e risca os problemas do papel
    const beforeItems = $$('.compare-before li')
    gsap.timeline({ scrollTrigger: { trigger: '#compare', start: 'top 75%', once: true }, defaults: { ease } })
      .from('.compare', { autoAlpha: 0, y: 30, duration: 0.7 })
      .from(beforeItems, { autoAlpha: 0, x: -14, stagger: 0.08, duration: 0.5 }, '-=0.3')
      .fromTo('.compare-after',
        { clipPath: 'polygon(115% 0%, 115% 0%, 100% 100%, 100% 100%)' },
        { clipPath: 'polygon(-15% 0%, 115% 0%, 100% 100%, 0% 100%)', duration: 1, ease: 'power3.inOut', clearProps: 'clipPath' })
      .from('.compare-after h3, .compare-after li', { autoAlpha: 0, x: 18, stagger: 0.1, duration: 0.5 }, '-=0.45')
      .add(() => beforeItems.forEach((li, i) => setTimeout(() => li.classList.add('is-struck'), i * 160)), '-=0.5')

    // Como funciona: o trilho preenche-se com o scroll e acende os marcadores
    const stepsEl = $('#steps')
    const steps = $$('.step', stepsEl)
    const markers = $$('.step-marker', stepsEl)
    const segments = steps.length - 1
    stepsEl.classList.add('is-tracking')

    function paintSteps(progress) {
      const done = progress * segments
      steps.forEach((step, i) => {
        if (i < segments) step.style.setProperty('--fill', Math.min(Math.max(done - i, 0), 1).toFixed(3))
      })
      markers.forEach((marker, i) => marker.classList.toggle('is-on', done >= i - 0.02 && progress > 0))
    }

    paintSteps(0)
    ScrollTrigger.create({
      trigger: stepsEl,
      start: 'top 75%',
      end: 'bottom 55%',
      scrub: true,
      onUpdate: self => paintSteps(self.progress)
    })

    gsap.from($$('.step-card', stepsEl), {
      autoAlpha: 0,
      y: 40,
      duration: 0.8,
      ease,
      stagger: 0.12,
      clearProps: 'transform',
      scrollTrigger: { trigger: stepsEl, start: 'top 80%', once: true }
    })

    // Experimente: as casas do jogo aparecem em cascata
    gsap.from($$('.k-cell', kioskGrid), {
      scale: 0.5,
      autoAlpha: 0,
      duration: 0.4,
      ease: 'back.out(1.6)',
      stagger: { each: 0.018, from: 'start', grid: 'auto' },
      clearProps: 'transform,opacity,visibility',
      scrollTrigger: { trigger: '#kiosk', start: 'top 75%', once: true }
    })

    // Acentos em diagonal das secções índigo e da chamada final
    $$('.brand-accent, .cta-accent').forEach(accent => {
      const final = getComputedStyle(accent).clipPath
      gsap.fromTo(accent,
        { clipPath: 'polygon(100% 0%, 100% 0%, 100% 100%, 100% 100%)' },
        {
          clipPath: final,
          duration: 1.2,
          ease: 'power3.inOut',
          clearProps: 'clipPath',
          scrollTrigger: { trigger: accent.parentElement, start: 'top 75%', once: true }
        })
    })

    gsap.from('#ctaBox', {
      autoAlpha: 0,
      y: 40,
      duration: 0.8,
      ease,
      scrollTrigger: { trigger: '#ctaBox', start: 'top 85%', once: true }
    })
    gsap.from('#ctaBox .cta-copy > p, #ctaBox .cta-actions .btn', {
      autoAlpha: 0,
      y: 16,
      duration: 0.6,
      ease,
      stagger: 0.1,
      scrollTrigger: { trigger: '#ctaBox', start: 'top 75%', once: true }
    })

    // Profundidade subtil no desktop (parallax), desligada no telemóvel
    gsap.matchMedia().add('(min-width: 960px)', () => {
      gsap.to('.hero-frame', { yPercent: -6, ease: 'none', scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true } })
      gsap.to('.draw-widget', { yPercent: -18, ease: 'none', scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true } })
      gsap.fromTo('#otpCard', { yPercent: 8 }, { yPercent: -8, ease: 'none', scrollTrigger: { trigger: '#seguranca', start: 'top bottom', end: 'bottom top', scrub: true } })
      gsap.fromTo('#kiosk', { yPercent: 5 }, { yPercent: -5, ease: 'none', scrollTrigger: { trigger: '#experimente', start: 'top bottom', end: 'bottom top', scrub: true } })
    })

    // As imagens lazy alteram alturas: recalcula os gatilhos quando carregam
    $$('img[loading="lazy"]').forEach(img => img.addEventListener('load', () => ScrollTrigger.refresh(), { once: true }))
  }

  $('#year').textContent = new Date().getFullYear()
})()
