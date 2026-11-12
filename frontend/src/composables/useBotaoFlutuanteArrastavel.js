import { ref, computed, onMounted, onUnmounted } from 'vue'

export function useBotaoFlutuanteArrastavel({ chave = 'sige:posicao-botao-suporte', margem = 8 } = {}) {
  const posicao = ref(null)
  const arrastando = ref(false)
  let inicio = null
  let moveu = false
  let bloquearClique = false
  let tamanho = { w: 56, h: 56 }

  const larguraTela = () => document.documentElement.clientWidth
  const alturaTela = () => document.documentElement.clientHeight

  function limitar(direita, baixo) {
    return {
      direita: Math.min(Math.max(margem, direita), Math.max(margem, larguraTela() - tamanho.w - margem)),
      baixo: Math.min(Math.max(margem, baixo), Math.max(margem, alturaTela() - tamanho.h - margem)),
    }
  }

  function salvar() {
    try {
      localStorage.setItem(chave, JSON.stringify(posicao.value))
    } catch (erro) {
      console.warn('Não foi possível salvar a posição do botão flutuante.', erro)
    }
  }

  function carregar() {
    try {
      const salvo = JSON.parse(localStorage.getItem(chave) || 'null')
      if (salvo && typeof salvo.direita === 'number' && typeof salvo.baixo === 'number') {
        posicao.value = limitar(salvo.direita, salvo.baixo)
      }
    } catch (erro) {
      console.warn('Não foi possível carregar a posição do botão flutuante.', erro)
    }
  }

  function aoPressionar(e) {
    if (e.button !== undefined && e.button !== 0) return
    const r = e.currentTarget.getBoundingClientRect()
    tamanho = { w: r.width, h: r.height }
    inicio = {
      px: e.clientX,
      py: e.clientY,
      direita: larguraTela() - r.right,
      baixo: alturaTela() - r.bottom,
    }
    moveu = false
    bloquearClique = false
    e.currentTarget.setPointerCapture?.(e.pointerId)
  }

  function aoMover(e) {
    if (!inicio) return
    const dx = e.clientX - inicio.px
    const dy = e.clientY - inicio.py
    if (!moveu && Math.hypot(dx, dy) < 5) return
    moveu = true
    arrastando.value = true
    posicao.value = limitar(inicio.direita - dx, inicio.baixo - dy)
  }

  function aoSoltar() {
    if (!inicio) return
    inicio = null
    arrastando.value = false
    if (moveu) {
      bloquearClique = true
      salvar()
    }
  }

  function aoClicarCapturando(e) {
    if (bloquearClique) {
      e.preventDefault()
      e.stopImmediatePropagation()
      bloquearClique = false
    }
  }

  function aoRedimensionar() {
    if (posicao.value) posicao.value = limitar(posicao.value.direita, posicao.value.baixo)
  }

  onMounted(() => {
    carregar()
    window.addEventListener('resize', aoRedimensionar)
  })
  onUnmounted(() => window.removeEventListener('resize', aoRedimensionar))

  const estilo = computed(() => {
    if (!posicao.value) return {}
    return {
      right: `${posicao.value.direita}px`,
      bottom: `${posicao.value.baixo}px`,
      left: 'auto',
      top: 'auto',
    }
  })

  return { estilo, posicao, arrastando, aoPressionar, aoMover, aoSoltar, aoClicarCapturando }
}