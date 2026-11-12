import { ref, computed, watch } from 'vue'

const CHAVE = 'sige:alerta-vencimento:v1'
const PRESETS = [30, 60, 90, 120]

function carregar() {
  try {
    const n = Number(JSON.parse(localStorage.getItem(CHAVE)))
    return Number.isInteger(n) && n >= 1 && n <= 3650 ? n : 30
  } catch (erro) {
    console.warn('Não foi possível recuperar o alerta de vencimento salvo.', erro)
    return 30
  }
}

const dias = ref(carregar())

watch(dias, (v) => {
  try {
    localStorage.setItem(CHAVE, JSON.stringify(v))
  } catch (erro) {
    console.warn('Não foi possível persistir o alerta de vencimento.', erro)
  }
})

export function useAlertaVencimento() {
  const ehPersonalizado = computed(() => !PRESETS.includes(dias.value))
  return { dias, PRESETS, ehPersonalizado }
}