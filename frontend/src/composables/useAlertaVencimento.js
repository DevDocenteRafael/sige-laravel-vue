// src/composables/useAlertaVencimento.js
import { ref, computed, watch } from 'vue'

const CHAVE = 'sige:alerta-vencimento:v1'
const PRESETS = [30, 60, 90, 120]

function carregar() {
  try {
    const n = Number(JSON.parse(localStorage.getItem(CHAVE)))
    return Number.isInteger(n) && n >= 1 && n <= 3650 ? n : 30
  } catch {
    return 30
  }
}

// Fora da função: o mesmo ref é compartilhado entre as páginas (Produtos e Lotes)
const dias = ref(carregar())

watch(dias, (v) => {
  try {
    localStorage.setItem(CHAVE, JSON.stringify(v))
  } catch {
    /* ignora */
  }
})

export function useAlertaVencimento() {
  const ehPersonalizado = computed(() => !PRESETS.includes(dias.value))
  return { dias, PRESETS, ehPersonalizado }
}