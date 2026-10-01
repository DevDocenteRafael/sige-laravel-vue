import { ref, onUnmounted } from 'vue'

// Arrastar com o mouse para rolar um container (usado na tabela de itens)
export function useArrastarParaRolar() {
  const elementoRef = ref(null)

  let inicioX = 0
  let inicioY = 0
  let scrollX = 0
  let scrollY = 0
  let moveu = false

  function aoIniciar(e) {
    const el = elementoRef.value
    if (!el || e.button !== 0) return
    if (e.target.closest('button, a, input, select, textarea, .drag-handle')) return

    inicioX = e.clientX
    inicioY = e.clientY
    scrollX = el.scrollLeft
    scrollY = el.scrollTop
    moveu = false

    window.addEventListener('mousemove', aoMover)
    window.addEventListener('mouseup', aoSoltar)
  }

  function aoMover(e) {
    const el = elementoRef.value
    if (!el) return
    const dx = e.clientX - inicioX
    const dy = e.clientY - inicioY
    if (!moveu && Math.abs(dx) + Math.abs(dy) > 5) {
      moveu = true
      el.style.cursor = 'grabbing'
    }
    if (moveu) {
      el.scrollLeft = scrollX - dx
      el.scrollTop = scrollY - dy
    }
  }

  function aoSoltar() {
    window.removeEventListener('mousemove', aoMover)
    window.removeEventListener('mouseup', aoSoltar)
    if (elementoRef.value) elementoRef.value.style.cursor = ''

    // após arrastar, engole o click para não abrir o modal de detalhes da linha
    if (moveu) {
      const engolir = (ev) => {
        ev.stopPropagation()
        ev.preventDefault()
      }
      window.addEventListener('click', engolir, { capture: true, once: true })
      setTimeout(() => window.removeEventListener('click', engolir, { capture: true }), 0)
    }
  }

  onUnmounted(() => {
    window.removeEventListener('mousemove', aoMover)
    window.removeEventListener('mouseup', aoSoltar)
  })

  return { elementoRef, aoIniciar }
}