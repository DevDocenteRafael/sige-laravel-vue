<template>
  <div v-if="aberto" class="fixed z-[9999]" :style="estiloPosicao">

    <!-- Painel expandido -->
    <div class="mb-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg p-3 flex flex-col gap-3 w-56">
      <div class="flex items-center justify-between px-1">
        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Acessibilidade</p>
        <button
          class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition"
          title="Fechar"
          aria-label="Fechar opções de acessibilidade"
          @click="emit('update:aberto', false)"
        >
          <X :size="16" />
        </button>
      </div>

      <!-- Tamanho da fonte -->
      <div class="flex flex-col gap-2">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 px-1">Tamanho da fonte</p>

        <div class="flex items-center gap-1">
          <button
            class="flex-1 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-bold text-sm"
            title="Diminuir fonte"
            :disabled="nivelAtual === 'normal'"
            :class="{ 'opacity-40 cursor-not-allowed': nivelAtual === 'normal' }"
            @click="diminuirFonte"
          >
            A-
          </button>
          <button
            class="flex-1 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-bold text-base"
            title="Restaurar padrão"
            @click="restaurarPadrao"
          >
            A
          </button>
          <button
            class="flex-1 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition font-bold text-lg"
            title="Aumentar fonte"
            :disabled="nivelAtual === 'extra'"
            :class="{ 'opacity-40 cursor-not-allowed': nivelAtual === 'extra' }"
            @click="aumentarFonte"
          >
            A+
          </button>
        </div>

        <p class="text-[11px] text-slate-400 dark:text-slate-500 px-1">
          Nível atual: {{ rotuloNivel }}
        </p>
      </div>

      <div class="border-t border-slate-200 dark:border-slate-700"></div>

      <!-- Contraste -->
      <SeletorContraste />
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import { X } from 'lucide-vue-next'
import { useAcessibilidade } from '@/composables/useAcessibilidade'
import SeletorContraste from './SeletorContraste.vue'

defineProps({
  aberto: { type: Boolean, default: false },
  // Posição do painel (right/bottom/top em px). O MenuSuporte calcula para ficar colado na bolinha.
  estiloPosicao: {
    type: Object,
    default: () => ({ bottom: '6rem', right: '1.25rem' })
  }
})
const emit = defineEmits(['update:aberto'])

const { nivelAtual, aumentarFonte, diminuirFonte, restaurarPadrao } = useAcessibilidade()

const rotuloNivel = computed(() => {
  const rotulos = { normal: 'Normal', grande: 'Grande', extra: 'Extra Grande' }
  return rotulos[nivelAtual.value] || 'Normal'
})
</script>