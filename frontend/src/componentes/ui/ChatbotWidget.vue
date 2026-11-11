<!-- eslint-disable vue/no-v-html -->
<template>
  <div
    v-if="aberto"
    class="fixed z-50 w-96 max-w-[calc(100vw-1rem)] h-[32rem] max-h-[calc(100vh-1rem)] rounded-xl shadow-2xl flex flex-col bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 animate-chat-in"
    :style="estiloPosicao"
  >
    <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-800 bg-blue-600 rounded-t-xl">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
          <Bot :size="18" class="text-white" />
        </div>
        <div class="flex flex-col leading-tight">
          <span class="font-semibold text-white text-sm">Assistente SIGE</span>
          <span class="text-[11px] text-blue-100">Online</span>
        </div>
      </div>
      <div class="flex items-center gap-1">
        <button
          class="text-white/80 hover:text-white transition p-1"
          title="Limpar conversa"
          @click="limparConversa"
        >
          <Trash2 :size="16" />
        </button>
        <button class="text-white/80 hover:text-white transition p-1" @click="emit('update:aberto', false)">
          <X :size="20" />
        </button>
      </div>
    </div>

    <div ref="areaMensagens" class="flex-1 overflow-y-auto p-4 space-y-3">
      <div v-if="mensagens.length === 0" class="flex flex-col items-center text-center mt-6 gap-4">
        <p class="text-sm text-slate-400 dark:text-slate-500">
          Pergunte sobre estoque, validades, perdas, movimentações — ou qualquer outra coisa.
        </p>
        <div class="flex flex-col gap-2 w-full">
          <button
            v-for="sugestao in sugestoes"
            :key="sugestao.texto"
            class="text-left text-xs px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
            @click="enviarMensagem(sugestao.texto)"
          >
            {{ sugestao.rotulo }}
          </button>
        </div>
      </div>

      <TransitionGroup name="msg" tag="div" class="space-y-3">
        <div
          v-for="msg in mensagens"
          :key="msg.id"
          class="flex items-end gap-2"
          :class="msg.autor === 'usuario' ? 'justify-end' : 'justify-start'"
        >
          <div
            v-if="msg.autor === 'bot'"
            class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center shrink-0 mb-1"
          >
            <Bot :size="13" class="text-white" />
          </div>

          <div class="max-w-[78%] flex flex-col" :class="msg.autor === 'usuario' ? 'items-end' : 'items-start'">
            <div
              class="rounded-lg px-3 py-2 text-sm break-words"
              :class="msg.autor === 'usuario'
                ? 'bg-blue-600 text-white rounded-br-sm'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-bl-sm'"
            >
              <span
                v-if="msg.autor === 'bot'"
                class="prose-chat"
                v-html="renderizarMarkdown(msg.texto || (msg.digitando ? '' : ''))"
              />
              <span v-else class="whitespace-pre-wrap">{{ msg.texto }}</span>

              <span v-if="msg.digitando && !msg.texto" class="inline-flex gap-1 py-1">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:-0.3s]"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:-0.15s]"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce"></span>
              </span>
            </div>
            <span v-if="msg.hora" class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 px-1">
              {{ formatarHora(msg.hora) }}
            </span>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <form class="p-3 border-t border-slate-200 dark:border-slate-800 flex gap-2" @submit.prevent="enviarMensagem()">
      <input
        v-model="pergunta"
        type="text"
        placeholder="Digite sua pergunta..."
        :disabled="carregando"
        class="flex-1 rounded-lg px-3 py-2 text-sm outline-none bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:border-blue-500 transition disabled:opacity-50"
      />
      <button
        type="submit"
        :disabled="carregando || !pergunta.trim()"
        class="w-10 h-10 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center transition shrink-0"
      >
        <Send :size="16" class="text-white" />
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, nextTick, onMounted, watch } from 'vue'
import { Bot, X, Send, Trash2 } from 'lucide-vue-next'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
import api from '@/servicos/api'

const CHAVE_HISTORICO = 'sige_chat_historico'
const LIMITE_HISTORICO = 50

const props = defineProps({
  aberto: { type: Boolean, default: false },
  estiloPosicao: {
    type: Object,
    default: () => ({ bottom: '6rem', right: '1.25rem' })
  }
})
const emit = defineEmits(['update:aberto'])

const pergunta      = ref('')
const mensagens     = ref([])
const carregando    = ref(false)
const areaMensagens = ref(null)

const sugestoes = [
  { rotulo: '📦 Estoque crítico', texto: 'Quais itens estão em estoque crítico?' },
  { rotulo: '⏳ Vencendo essa semana', texto: 'O que está vencendo essa semana?' },
  { rotulo: '📉 Perdas do mês', texto: 'Quais foram as perdas do último mês?' },
  { rotulo: '🔄 Últimas movimentações', texto: 'Quais foram as últimas movimentações?' },
]

marked.setOptions({ breaks: true })

function renderizarMarkdown(texto) {
  if (!texto) return ''
  return DOMPurify.sanitize(marked.parse(texto))
}

function formatarHora(iso) {
  return new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}

function novoId() {
  return `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
}

async function rolarParaFinal() {
  await nextTick()
  if (areaMensagens.value) {
    areaMensagens.value.scrollTop = areaMensagens.value.scrollHeight
  }
}

function salvarHistorico() {
  const paraSalvar = mensagens.value.slice(-LIMITE_HISTORICO)
  localStorage.setItem(CHAVE_HISTORICO, JSON.stringify(paraSalvar))
}

function carregarHistorico() {
  try {
    const salvo = localStorage.getItem(CHAVE_HISTORICO)
    if (salvo) mensagens.value = JSON.parse(salvo)
  } catch {
    mensagens.value = []
  }
}

function limparConversa() {
  mensagens.value = []
  localStorage.removeItem(CHAVE_HISTORICO)
}

onMounted(() => {
  carregarHistorico()
  rolarParaFinal()
})

watch(() => props.aberto, (val) => {
  if (val) rolarParaFinal()
})

async function enviarMensagem(textoForcado) {
  const texto = (textoForcado ?? pergunta.value).trim()
  if (!texto || carregando.value) return

  mensagens.value.push({ id: novoId(), autor: 'usuario', texto, hora: new Date().toISOString() })
  pergunta.value = ''
  carregando.value = true
  salvarHistorico()
  rolarParaFinal()

  const msgBot = reactive({ id: novoId(), autor: 'bot', texto: '', hora: null, digitando: true })
  mensagens.value.push(msgBot)
  rolarParaFinal()

  try {
    const baseURL = api.defaults.baseURL || ''
    const token = localStorage.getItem('token')
    const resposta = await fetch(`${baseURL}/chatbot/stream`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'text/event-stream',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: JSON.stringify({ mensagem: texto }),
      credentials: 'include',
    })

    if (!resposta.ok || !resposta.body) throw new Error('Falha na resposta do servidor')

    const leitor = resposta.body.getReader()
    const decodificador = new TextDecoder('utf-8')
    let buffer = ''

    let terminou = false
    while (!terminou) {
      const { value, done } = await leitor.read()
      if (done) {
        terminou = true
        break
      }

      buffer += decodificador.decode(value, { stream: true })
      const blocos = buffer.split('\n\n')
      buffer = blocos.pop()

      for (const bloco of blocos) {
        const linha = bloco.trim()
        if (!linha.startsWith('data:')) continue

        const bruto = linha.slice(5).trim()
        if (!bruto) continue

        const evento = JSON.parse(bruto)

        if (evento.delta) {
          msgBot.texto += evento.delta
          rolarParaFinal()
        }
        if (evento.done) {
          msgBot.digitando = false
          msgBot.hora = new Date().toISOString()
        }
      }
    }

    if (!msgBot.texto.trim()) {
      msgBot.texto = 'Não consegui gerar uma resposta agora.'
    }
    msgBot.digitando = false
    if (!msgBot.hora) msgBot.hora = new Date().toISOString()
  } catch (e) {
    msgBot.texto = 'Não foi possível responder agora. Tente novamente.'
    msgBot.digitando = false
    msgBot.hora = new Date().toISOString()
  } finally {
    carregando.value = false
    salvarHistorico()
    rolarParaFinal()
  }
}
</script>

<style scoped>
.msg-enter-active {
  transition: all 0.25s ease;
}
.msg-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.animate-chat-in {
  animation: chat-in 0.2s ease;
}
@keyframes chat-in {
  from { opacity: 0; transform: translateY(12px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.prose-chat :deep(p) { margin: 0 0 0.4em 0; }
.prose-chat :deep(p:last-child) { margin-bottom: 0; }
.prose-chat :deep(ul), .prose-chat :deep(ol) { margin: 0.3em 0; padding-left: 1.2em; }
.prose-chat :deep(strong) { font-weight: 600; }
.prose-chat :deep(code) {
  background: rgba(0,0,0,0.08);
  padding: 0.1em 0.3em;
  border-radius: 4px;
  font-size: 0.85em;
}
</style>