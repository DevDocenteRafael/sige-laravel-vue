<template>
  <div v-if="totalPaginas > 1 || porPagina > 10" class="flex flex-wrap items-center justify-between gap-3 mt-4 px-1">
    <p class="shrink-0 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
      Página {{ paginaAtual }} de {{ totalPaginas }} — {{ total }} {{ rotulo }}
    </p>

    <div class="flex flex-wrap items-center justify-center gap-1">
      <button
        class="p-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
        :disabled="paginaAtual === 1"
        @click="ir(paginaAtual - 1)"
      >
        <ChevronLeft :size="16" />
      </button>

      <template v-for="(p, i) in paginasVisiveis" :key="i">
        <span v-if="p === '...'" class="px-1 text-slate-400 select-none">…</span>
        <button
          v-else
          :class="p === paginaAtual
            ? 'bg-blue-600 text-white'
            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
          class="w-9 h-9 rounded-lg text-sm font-medium transition"
          @click="ir(p)"
        >
          {{ p }}
        </button>
      </template>

      <button
        class="p-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition"
        :disabled="paginaAtual === totalPaginas"
        @click="ir(paginaAtual + 1)"
      >
        <ChevronRight :size="16" />
      </button>
    </div>

    <select
      :value="porPagina"
      class="shrink-0 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-2 py-1.5 text-sm outline-none"
      @change="mudarPorPagina($event.target.value)"
    >
      <option :value="10">10 por página</option>
      <option :value="25">25 por página</option>
      <option :value="50">50 por página</option>
    </select>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
  paginaAtual:  { type: Number, required: true },
  totalPaginas: { type: Number, required: true },
  porPagina:    { type: Number, required: true },
  total:        { type: [Number, String], default: 0 },
  rotulo:       { type: String, default: 'itens' },
})

const emit = defineEmits(['update:paginaAtual', 'update:porPagina'])

// Janela de páginas: 1 … (atual-2 … atual+2) … última
const paginasVisiveis = computed(() => {
  const { paginaAtual: atual, totalPaginas: total } = props
  const delta = 2
  const paginas = []

  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= atual - delta && i <= atual + delta)) {
      paginas.push(i)
    } else if (paginas[paginas.length - 1] !== '...') {
      paginas.push('...')
    }
  }
  return paginas
})

function ir(pagina) {
  if (pagina < 1 || pagina > props.totalPaginas) return
  emit('update:paginaAtual', pagina)
}

function mudarPorPagina(valor) {
  emit('update:porPagina', Number(valor))
  emit('update:paginaAtual', 1)
}
</script>