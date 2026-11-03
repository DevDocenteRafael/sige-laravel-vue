<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-[60]">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
      <div class="flex justify-between items-start mb-5">
        <div class="flex items-center gap-2">
          <component :is="icone" :class="corIcone" :size="20" />
          <div>
            <h2 class="text-slate-900 dark:text-white font-bold">{{ titulo }}</h2>
            <p class="text-slate-500 dark:text-slate-400 text-xs">{{ subtitulo }}</p>
          </div>
        </div>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="$emit('cancelar')">
          <X :size="18" />
        </button>
      </div>

      <div :class="corCaixa" class="border rounded-lg p-4 mb-6">
        <p class="text-slate-600 dark:text-slate-300 text-sm">
          <slot>{{ mensagem }}</slot>
        </p>
      </div>

      <div class="flex gap-3">
        <button
          class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
          @click="$emit('cancelar')"
        >
          {{ textoCancelar }}
        </button>
        <button
          :disabled="carregando"
          :class="corBotaoConfirmar"
          class="flex-1 py-2.5 rounded-lg text-white transition font-medium disabled:opacity-50"
          @click="$emit('confirmar')"
        >
          {{ carregando ? textoCarregando : textoConfirmar }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { X, Shield, TriangleAlert } from 'lucide-vue-next'

const props = defineProps({
  titulo:          { type: String, required: true },
  subtitulo:       { type: String, default: 'Esta ação não pode ser desfeita' },
  mensagem:        { type: String, default: '' },
  variante:        { type: String, default: 'perigo' },
  textoCancelar:   { type: String, default: 'Cancelar' },
  textoConfirmar:  { type: String, default: 'Confirmar' },
  textoCarregando: { type: String, default: 'Processando...' },
  carregando:      { type: Boolean, default: false },
})
defineEmits(['confirmar', 'cancelar'])

const icone = computed(() => (props.variante === 'aviso' ? TriangleAlert : Shield))

const corIcone = computed(() =>
  props.variante === 'aviso'
    ? 'text-yellow-600 dark:text-yellow-400'
    : 'text-red-600 dark:text-red-400'
)

const corCaixa = computed(() =>
  props.variante === 'aviso'
    ? 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-700'
    : 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700'
)

const corBotaoConfirmar = computed(() =>
  props.variante === 'aviso'
    ? 'bg-yellow-600 hover:bg-yellow-700'
    : 'bg-red-600 hover:bg-red-700'
)
</script>