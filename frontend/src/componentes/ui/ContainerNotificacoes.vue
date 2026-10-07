<template>
  <!-- ===== TOASTS ===== -->
  <div class="fixed top-6 left-1/2 -translate-x-1/2 z-[100] w-full max-w-lg px-4 pointer-events-none">
    <TransitionGroup
      tag="div"
      class="flex flex-col gap-3"
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0 -translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-4"
    >
      <div
        v-for="t in toasts"
        :key="t.id"
        class="pointer-events-auto flex items-center gap-3 rounded-xl border-2 px-5 py-4 shadow-2xl text-white"
        :class="estilos[t.tipo]?.caixa || estilos.info.caixa"
      >
        <component :is="estilos[t.tipo]?.icone || estilos.info.icone" :size="28" class="shrink-0" />
        <p class="flex-1 text-base font-semibold">{{ t.mensagem }}</p>
        <button class="text-white/80 hover:text-white transition" @click="removerToast(t.id)">
          <X :size="18" />
        </button>
      </div>
    </TransitionGroup>
  </div>

  <!-- ===== CONFIRMAÇÃO ===== -->
  <div
    v-if="confirmacaoAberta"
    class="fixed inset-0 bg-black/70 flex items-center justify-center z-[90]"
  >
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
      <div class="flex items-center gap-2 mb-4">
        <Shield class="text-red-600 dark:text-red-400" :size="20" />
        <h2 class="text-slate-900 dark:text-white font-bold">{{ confirmacaoTitulo }}</h2>
      </div>

      <p class="text-slate-600 dark:text-slate-300 text-sm mb-6">{{ confirmacaoMensagem }}</p>

      <div class="flex gap-3">
        <button
          class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm"
          @click="responderConfirmacao(false)"
        >
          Cancelar
        </button>
        <button
          class="flex-1 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium transition text-sm"
          @click="responderConfirmacao(true)"
        >
          Confirmar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X, Shield } from 'lucide-vue-next'
import { useNotificacao } from '@/composables/useNotificacao'

const {
  toasts,
  removerToast,
  confirmacaoAberta,
  confirmacaoTitulo,
  confirmacaoMensagem,
  responderConfirmacao,
} = useNotificacao()

const estilos = {
  sucesso: { caixa: 'bg-green-600 border-green-300',   icone: CheckCircle2 },
  erro:    { caixa: 'bg-red-600 border-red-300',       icone: AlertCircle },
  aviso:   { caixa: 'bg-yellow-500 border-yellow-200', icone: AlertTriangle },
  info:    { caixa: 'bg-blue-600 border-blue-300',     icone: Info },
}
</script>