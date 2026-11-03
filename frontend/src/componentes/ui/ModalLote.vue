<template>
  <div class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-6 w-full max-w-lg" :style="estiloArraste">

      <div class="flex justify-between items-center mb-6 cursor-grab active:cursor-grabbing select-none" @mousedown="aoIniciarArraste">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
          {{ ehEdicao ? 'Editar Lote' : 'Novo Lote' }}
        </h2>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="tentarFechar">
          <X :size="20" />
        </button>
      </div>

      <form @submit.prevent="salvar">

        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Número do Lote *</label>
          <input
            v-model="formulario.numero"
            type="text"
            required
            class="campo"
            placeholder="Ex: LOT-2024-001"
          />
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Data de Entrada *</label>
          <input v-model="formulario.data_entrada" type="date" required class="campo" />
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-1">Descrição</label>
          <input v-model="formulario.descricao" type="text" class="campo" placeholder="Ex: Compra mensal de janeiro" />
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded text-red-600 dark:text-red-400 text-sm">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition" @click="tentarFechar">
            Cancelar
          </button>
          <button type="submit" :disabled="salvando" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition">
            {{ salvando ? 'Salvando...' : 'Salvar Lote' }}
          </button>
        </div>

      </form>
    </div>

    <div v-if="mostrarConfirmDescartar" class="fixed inset-0 bg-black/70 flex items-center justify-center z-[60]">
      <div class="bg-white dark:bg-slate-900 border border-orange-300 dark:border-orange-700 rounded-xl w-full max-w-sm p-6 space-y-4">
        <p class="text-slate-900 dark:text-white font-semibold text-lg">Descartar alterações?</p>
        <p class="text-slate-600 dark:text-slate-300 text-sm">
          Você tem informações não salvas. Deseja realmente descartar e fechar?
        </p>
        <div class="flex gap-3">
          <button
            class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            @click="mostrarConfirmDescartar = false"
          >
            Continuar editando
          </button>
          <button
            class="flex-1 py-2.5 rounded-lg bg-orange-600 text-white hover:bg-orange-700 transition font-medium"
            @click="confirmarDescartar"
          >
            Descartar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { X } from 'lucide-vue-next'
import api from '@/servicos/api'
import { useModalArrastavel } from '@/composables/useModalArrastavel'

const props = defineProps({
  lote: { type: Object, default: null },
})
const emit = defineEmits(['fechar', 'salvo'])

const { aoIniciarArraste, estiloArraste } = useModalArrastavel()

const ehEdicao = computed(() => !!props.lote)
const salvando = ref(false)
const erro     = ref('')
const mostrarConfirmDescartar = ref(false)

const numeroOriginal      = props.lote?.numero_lote  || ''
const dataEntradaOriginal = props.lote?.data_entrada || new Date().toISOString().split('T')[0]
const descricaoOriginal   = props.lote?.descricao    || ''

const formulario = ref({
  numero:       numeroOriginal,
  data_entrada: dataEntradaOriginal,
  descricao:    descricaoOriginal,
})

const temAlteracoes = computed(() => {
  return (
    formulario.value.numero       !== numeroOriginal ||
    formulario.value.data_entrada !== dataEntradaOriginal ||
    formulario.value.descricao    !== descricaoOriginal
  )
})

function tentarFechar() {
  if (temAlteracoes.value) {
    mostrarConfirmDescartar.value = true
    return
  }
  emit('fechar')
}

function confirmarDescartar() {
  mostrarConfirmDescartar.value = false
  emit('fechar')
}

async function salvar() {
  erro.value     = ''
  salvando.value = true
  try {
    if (ehEdicao.value) {
      await api.put(`/lotes/${props.lote.id_lote}`, formulario.value)
    } else {
      await api.post('/lotes', formulario.value)
    }
    emit('salvo')
  } catch (erroHttp) {
    const errosValidacao = erroHttp.response?.data?.errors
    if (errosValidacao) {
      erro.value = Object.values(errosValidacao).flat().join('. ')
    } else {
      erro.value = erroHttp.response?.data?.message || 'Erro ao salvar lote.'
    }
  } finally {
    salvando.value = false
  }
}
</script>

<style scoped>
.campo {
  width: 100%;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.campo {
  background-color: rgb(241 245 249);
  border: 1px solid rgb(203 213 225);
  color: rgb(15 23 42);
}
.dark .campo {
  background-color: rgb(30 41 59);
  border-color: rgb(71 85 105);
  color: rgb(255 255 255);
}
.campo::placeholder {
  color: rgb(148 163 184);
}
.dark .campo::placeholder {
  color: rgb(100 116 139);
}
.campo:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
}
</style>