import { ref, computed, onBeforeUnmount } from 'vue'

export function useModalArrastavel() {
  const arrastando = ref(false)
  const arrastou = ref(false)
  const posicao = ref({ x: 0, y: 0 })
  let inicioMouse = { x: 0, y: 0 }
  let inicioPosicao = { x: 0, y: 0 }

  function aoMoverGlobal(evento) {
    if (!arrastando.value) return
    evento.preventDefault()
    arrastou.value = true
    posicao.value = {
      x: inicioPosicao.x + (evento.pageX - inicioMouse.x),
      y: inicioPosicao.y + (evento.pageY - inicioMouse.y),
    }
  }

  function aoSoltarGlobal() {
    if (!arrastando.value) return
    arrastando.value = false
    window.removeEventListener('mousemove', aoMoverGlobal)
    window.removeEventListener('mouseup', aoSoltarGlobal)

    setTimeout(() => {
      arrastou.value = false
    }, 0)
  }

  function aoIniciarArraste(evento) {
    if (evento.target.closest('button, a, input, select, textarea')) return

    arrastando.value = true
    arrastou.value   = false
    inicioMouse    = { x: evento.pageX, y: evento.pageY }
    inicioPosicao  = { ...posicao.value }

    window.addEventListener('mousemove', aoMoverGlobal)
    window.addEventListener('mouseup', aoSoltarGlobal)
  }

  function resetarPosicao() {
    posicao.value = { x: 0, y: 0 }
  }

  const estiloArraste = computed(() => ({
    transform: `translate(${posicao.value.x}px, ${posicao.value.y}px)`,
  }))

  onBeforeUnmount(() => {
    window.removeEventListener('mousemove', aoMoverGlobal)
    window.removeEventListener('mouseup', aoSoltarGlobal)
  })

  return { aoIniciarArraste, estiloArraste, resetarPosicao, arrastou }
}