import { ref, watch } from 'vue'

const NIVEIS = {
  normal: 100,
  grande: 115,
  extra: 130,
}

const CHAVE_STORAGE = 'sige_acessibilidade_fonte'

const nivelAtual = ref(localStorage.getItem(CHAVE_STORAGE) || 'normal')

function aplicarFonte(nivel) {
  const porcentagem = NIVEIS[nivel] ?? NIVEIS.normal
  document.documentElement.style.fontSize = `${porcentagem}%`
}

aplicarFonte(nivelAtual.value)

watch(nivelAtual, (novoNivel) => {
  aplicarFonte(novoNivel)
  localStorage.setItem(CHAVE_STORAGE, novoNivel)
})

export function useAcessibilidade() {
  function definirNivel(nivel) {
    if (NIVEIS[nivel] === undefined) return
    nivelAtual.value = nivel
  }

  function aumentarFonte() {
    if (nivelAtual.value === 'normal') definirNivel('grande')
    else if (nivelAtual.value === 'grande') definirNivel('extra')
  }

  function diminuirFonte() {
    if (nivelAtual.value === 'extra') definirNivel('grande')
    else if (nivelAtual.value === 'grande') definirNivel('normal')
  }

  function restaurarPadrao() {
    definirNivel('normal')
  }

  return {
    nivelAtual,
    definirNivel,
    aumentarFonte,
    diminuirFonte,
    restaurarPadrao,
  }
}