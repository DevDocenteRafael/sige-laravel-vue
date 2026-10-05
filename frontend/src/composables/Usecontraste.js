import { ref } from 'vue'

const CHAVE = 'sige:contraste'

export const MODOS_CONTRASTE = [
  { valor: 'normal', rotulo: 'Padrão',               filtro: '' },
  { valor: 'alto',   rotulo: 'Alto contraste',       filtro: 'contrast(1.5)' },
  { valor: 'baixo1', rotulo: 'Contraste reduzido 1', filtro: 'contrast(0.85)' },
  { valor: 'baixo2', rotulo: 'Contraste reduzido 2', filtro: 'contrast(0.7)' },
]

function lerSalvo() {
  try {
    const salvo = localStorage.getItem(CHAVE)
    return MODOS_CONTRASTE.some((m) => m.valor === salvo) ? salvo : 'normal'
  } catch {
    return 'normal'
  }
}

// Estado compartilhado entre todos os componentes que usarem o composable
const modo = ref(lerSalvo())

function aplicar(valor) {
  const raiz = document.documentElement
  const config = MODOS_CONTRASTE.find((m) => m.valor === valor) ?? MODOS_CONTRASTE[0]
  // filtro vazio remove a propriedade (não cria contexto de empilhamento à toa)
  raiz.style.filter = config.filtro
  raiz.dataset.contraste = config.valor
}

// Chame uma vez no main.js, antes do app.mount(), para restaurar a preferência salva
export function iniciarContraste() {
  aplicar(modo.value)
}

export function useContraste() {
  function definir(valor) {
    modo.value = valor
    aplicar(valor)
    try {
      localStorage.setItem(CHAVE, valor)
    } catch {
      /* armazenamento indisponível: a escolha vale só nesta sessão */
    }
  }

  return { modo, definir, MODOS_CONTRASTE }
}