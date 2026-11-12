<template>
  <div>
    <p class="text-slate-500 dark:text-slate-400 text-xs font-medium mb-2">Contraste</p>

    <div role="radiogroup" aria-label="Nível de contraste" class="grid grid-cols-1 gap-2">
      <button
        v-for="opcao in MODOS_CONTRASTE"
        :key="opcao.valor"
        type="button"
        role="radio"
        :aria-checked="modo === opcao.valor"
        class="w-full text-left text-sm px-3 py-2 rounded-lg border transition"
        :class="modo === opcao.valor
          ? 'bg-blue-600 border-blue-600 text-white font-medium'
          : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
        @click="definir(opcao.valor)"
      >
        {{ opcao.rotulo }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useContraste } from '@/composables/useContraste'

const { modo, definir, MODOS_CONTRASTE } = useContraste()
</script>