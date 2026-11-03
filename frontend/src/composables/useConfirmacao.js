import { reactive } from 'vue'

const estado = reactive({
  aberto: false,
  titulo: '',
  mensagem: '',
  textoConfirmar: 'Confirmar',
  textoCancelar: 'Cancelar',
  variante: 'padrao',
  resolver: null,
})

function confirmar(mensagem, opcoes = {}) {
  estado.mensagem = mensagem
  estado.titulo = opcoes.titulo ?? 'Confirmação'
  estado.textoConfirmar = opcoes.textoConfirmar ?? 'Confirmar'
  estado.textoCancelar = opcoes.textoCancelar ?? 'Cancelar'
  estado.variante = opcoes.variante ?? 'padrao'
  estado.aberto = true

  return new Promise((resolve) => {
    estado.resolver = resolve
  })
}

function responder(valor) {
  estado.aberto = false
  estado.resolver?.(valor)
  estado.resolver = null
}

export function useConfirmacao() {
  return { estado, confirmar, responder }
}