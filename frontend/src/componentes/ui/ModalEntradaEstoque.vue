<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">

    <div v-if="etapa === 1" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6" :style="estiloArraste">

      <div class="flex justify-between items-start mb-5 cursor-grab active:cursor-grabbing select-none" @mousedown="aoIniciarArraste">
        <div class="flex items-center gap-2">
          <Shield class="text-yellow-600 dark:text-yellow-400" :size="20" />
          <div>
            <h2 class="text-slate-900 dark:text-white font-bold">Confirmação de Segurança</h2>
            <p class="text-slate-500 dark:text-slate-400 text-xs">Confirme para prosseguir</p>
          </div>
        </div>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="$emit('fechar')">
          <X :size="18" />
        </button>
      </div>

      <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-2 mb-1">
          <Shield class="text-green-600 dark:text-green-400" :size="16" />
          <span class="text-slate-900 dark:text-white font-medium text-sm">Entrada de Estoque</span>
        </div>
        <p class="text-slate-600 dark:text-slate-300 text-sm">
          Você está adicionando
          <strong class="text-slate-900 dark:text-white">{{ form.quantidade || '?' }} unidades</strong>
          do produto <strong class="text-slate-900 dark:text-white">"{{ item.nome }}"</strong>.
          O estoque será atualizado de
          <strong class="text-slate-900 dark:text-white">{{ item.quantidade }}</strong> para
          <strong class="text-slate-900 dark:text-white">{{ item.quantidade + (form.quantidade || 0) }}</strong>.
        </p>
      </div>

      <p v-if="erro" class="text-red-600 dark:text-red-400 text-sm mb-4 text-center">{{ erro }}</p>

      <div class="flex gap-3">
        <button
          class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm"
          @click="etapa = 0"
        >
          Voltar
        </button>
        <button
          class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition font-medium text-sm"
          :disabled="salvando"
          @click="confirmarEntrada"
        >
          {{ salvando ? 'Confirmando...' : 'Confirmar Entrada' }}
        </button>
      </div>
    </div>

    <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-6 w-full max-w-md" :style="estiloArraste">

      <div class="flex justify-between items-center mb-6 cursor-grab active:cursor-grabbing select-none" @mousedown="aoIniciarArraste">
        <div>
          <h2 class="text-lg font-bold text-slate-900 dark:text-white">Entrada de Estoque</h2>
          <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Registre a entrada de produtos no estoque.</p>
        </div>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="$emit('fechar')">
          <X :size="20" />
        </button>
      </div>

      <div class="bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-4 mb-6">
        <p class="text-slate-500 dark:text-slate-400 text-xs mb-1">Produto</p>
        <p class="text-slate-900 dark:text-white font-bold text-base">{{ item.nome }}</p>
        <p class="text-slate-500 dark:text-slate-400 text-xs mt-2">Estoque disponível</p>
        <p class="text-slate-900 dark:text-white font-bold text-xl">{{ item.quantidade }} {{ item.unidade_medida }}</p>
      </div>

      <form @submit.prevent="abrirConfirmacao">

        <div class="mb-4">
          <label class="label">Quantidade para entrada *</label>
          <input
            v-model.number="form.quantidade"
            type="number"
            min="1"
            required
            class="campo"
            placeholder="Ex: 10"
          />
        </div>

        <div class="mb-6">
          <label class="label">Motivo (opcional)</label>
          <textarea
            v-model="form.motivo"
            rows="3"
            class="campo"
            placeholder="Ex: Compra, Retorno, Ajuste..."
          />
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded text-red-600 dark:text-red-400 text-sm">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button
            type="button"
            class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            @click="$emit('fechar')"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="!form.quantidade || form.quantidade < 1"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-40 disabled:cursor-not-allowed transition font-medium"
          >
            Confirmar Entrada
          </button>
        </div>

      </form>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { X, Shield } from 'lucide-vue-next'
import api from '@/servicos/api'
import { useModalArrastavel } from '@/composables/useModalArrastavel'

const props = defineProps({
  item: { type: Object, required: true },
})
const emit = defineEmits(['fechar', 'salvo'])

const { aoIniciarArraste, estiloArraste } = useModalArrastavel()

const etapa    = ref(0)
const salvando = ref(false)
const erro     = ref('')

const form = ref({
  quantidade: null,
  motivo:     '',
})

function abrirConfirmacao() {
  erro.value = ''
  etapa.value = 1
}

async function confirmarEntrada() {
  erro.value     = ''
  salvando.value = true
  try {
    await api.patch(`/itens/${props.item.id_item}/entrada`, {
      quantidade: form.value.quantidade,
      motivo:     form.value.motivo,
    })
    emit('salvo')
  } catch (e) {
    erro.value  = e.response?.data?.message || 'Erro ao registrar entrada.'
    etapa.value = 0
  } finally {
    salvando.value = false
  }
}
</script>

<style scoped>
.label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--muted-foreground);
  margin-bottom: 0.25rem;
}
.campo {
  width: 100%;
  background: var(--input);
  border: 1px solid var(--border);
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  color: var(--foreground);
  outline: none;
  resize: none;
}
.campo:focus {
  border-color: #22c55e;
  box-shadow: 0 0 0 2px rgba(34,197,94,0.2);
}
</style>