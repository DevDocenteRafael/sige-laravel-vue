import { ref } from 'vue'

// ===== Toasts (substituem window.alert) =====
const toasts = ref([])
let proximoIdToast = 1

function notificar(mensagem, tipo = 'info', duracaoMs = 3500) {
  const id = proximoIdToast++
  toasts.value.push({ id, mensagem, tipo })

  setTimeout(() => {
    removerToast(id)
  }, duracaoMs)
}

function removerToast(id) {
  toasts.value = toasts.value.filter(t => t.id !== id)
}

// ===== Confirmação (substitui window.confirm) =====
const confirmacaoAberta   = ref(false)
const confirmacaoTitulo   = ref('')
const confirmacaoMensagem = ref('')
let resolverConfirmacao = null

function confirmar(mensagem, titulo = 'Confirmar ação') {
  confirmacaoTitulo.value   = titulo
  confirmacaoMensagem.value = mensagem
  confirmacaoAberta.value   = true

  return new Promise((resolve) => {
    resolverConfirmacao = resolve
  })
}

function responderConfirmacao(resposta) {
  confirmacaoAberta.value = false
  if (resolverConfirmacao) {
    resolverConfirmacao(resposta)
    resolverConfirmacao = null
  }
}

export function useNotificacao() {
  return {
    // toasts
    toasts,
    notificar,
    removerToast,
    sucesso: (msg, duracaoMs = 5000) => notificar(msg, 'sucesso', duracaoMs),
    erro:    (msg, duracaoMs = 6000) => notificar(msg, 'erro', duracaoMs),
    aviso:   (msg, duracaoMs = 5000) => notificar(msg, 'aviso', duracaoMs),
    info:    (msg, duracaoMs) => notificar(msg, 'info', duracaoMs),

    // confirmação
    confirmacaoAberta,
    confirmacaoTitulo,
    confirmacaoMensagem,
    confirmar,
    responderConfirmacao,
  }
}