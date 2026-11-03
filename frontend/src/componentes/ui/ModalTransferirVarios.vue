<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto" :style="estiloArraste">

      <div class="flex justify-between items-start mb-5 cursor-grab active:cursor-grabbing select-none" @mousedown="aoIniciarArraste">
        <div>
          <h2 class="text-slate-900 dark:text-white font-bold text-lg">Transferir Itens</h2>
          <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">
            {{ itens.length }} item(ns) selecionado(s). Defina o lote de destino e a quantidade de cada um.
          </p>
        </div>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="$emit('fechar')">
          <X :size="18" />
        </button>
      </div>

      <div class="flex items-end gap-2 mb-5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-lg p-3">
        <div class="flex-1">
          <label class="text-xs text-slate-500 dark:text-slate-400 mb-1 block">Aplicar este lote a todos os itens</label>
          <select
            v-model="loteParaTodos"
            class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-3 py-2 text-sm outline-none"
          >
            <option :value="null">Selecione um lote...</option>
            <option v-for="lote in lotes" :key="lote.id_lote" :value="lote.id_lote">
              {{ lote.numero_lote }}
            </option>
          </select>
        </div>
        <button
          type="button"
          class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="!loteParaTodos"
          @click="aplicarLoteATodos"
        >
          Aplicar a todos
        </button>
      </div>

      <div class="space-y-3 mb-6">
        <div
          v-for="linha in linhas"
          :key="linha.id_item"
          class="grid grid-cols-12 gap-3 items-center bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 rounded-lg p-3"
        >
          <div class="col-span-4">
            <p class="text-slate-900 dark:text-white font-medium text-sm truncate">{{ linha.nome }}</p>
            <p class="text-slate-500 dark:text-slate-400 text-xs">SKU: {{ linha.sku }} · Lote atual: {{ linha.numeroLoteOrigem }}</p>
          </div>

          <div class="col-span-3">
            <label class="text-xs text-slate-500 dark:text-slate-400 mb-1 block">Quantidade</label>
            <input
              type="number"
              v-model.number.="linha.quantidade"
              :min="1"
              :max="linha.quantidadeDisponivel"
              class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-2 py-1.5 text-sm outline-none"
            />
            <p v-if="linha.quantidade > linha.quantidadeDisponivel" class="text-red-500 text-xs mt-1">
              Máximo: {{ linha.quantidadeDisponivel }}
            </p>
          </div>

          <div class="col-span-5">
            <label class="text-xs text-slate-500 dark:text-slate-400 mb-1 block">Lote de destino</label>
            <select
              v-model="linha.idLoteDestino"
              class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-2 py-1.5 text-sm outline-none"
              :class="{ 'border-red-500': linha.idLoteDestino === linha.idLoteOrigem }"
            >
              <option :value="null">Selecione...</option>
              <option
                v-for="lote in lotes"
                :key="lote.id_lote"
                :value="lote.id_lote"
                :disabled="lote.id_lote === linha.idLoteOrigem"
              >
                {{ lote.numero_lote }}{{ lote.id_lote === linha.idLoteOrigem ? ' (lote atual)' : '' }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <p v-if="erro" class="text-red-500 text-sm mb-4">{{ erro }}</p>

      <div class="flex gap-3">
        <button
          class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
          @click="$emit('fechar')"
        >
          Cancelar
        </button>
        <button
          :disabled="!podeConfirmar || enviando"
          class="flex-1 py-2.5 rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition font-medium disabled:opacity-50"
          @click="confirmar"
        >
          {{ enviando ? 'Transferindo...' : 'Confirmar Transferência' }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { X } from 'lucide-vue-next'
import api from '@/servicos/api'
import { useNotificacao } from '@/composables/useNotificacao'
import { useModalArrastavel } from '@/composables/useModalArrastavel'

const props = defineProps({
  itens: { type: Array, required: true },
  lotes: { type: Array, required: true },
})

const emit = defineEmits(['fechar', 'salvo'])

const { erro: notificarErro } = useNotificacao()
const { aoIniciarArraste, estiloArraste } = useModalArrastavel()

function numeroLote(idLote) {
  return props.lotes.find(l => l.id_lote === idLote)?.numero_lote || idLote
}

const linhas = ref(
  props.itens.map(item => ({
    id_item:              item.id_item,
    nome:                 item.produto?.nome || '—',
    sku:                  item.produto?.sku || '—',
    idLoteOrigem:         item.id_lote,
    numeroLoteOrigem:     numeroLote(item.id_lote),
    quantidadeDisponivel: item.quantidade,
    quantidade:           item.quantidade,
    idLoteDestino:        null,
  }))
)

const loteParaTodos = ref(null)
const enviando       = ref(false)
const erro           = ref('')

function aplicarLoteATodos() {
  linhas.value.forEach(linha => {
    if (loteParaTodos.value !== linha.idLoteOrigem) {
      linha.idLoteDestino = loteParaTodos.value
    }
  })
}

const podeConfirmar = computed(() =>
  linhas.value.every(l =>
    l.idLoteDestino &&
    l.idLoteDestino !== l.idLoteOrigem &&
    l.quantidade >= 1 &&
    l.quantidade <= l.quantidadeDisponivel
  )
)

async function confirmar() {
  erro.value = ''
  enviando.value = true
  try {
    const transferencias = linhas.value.map(l => ({
      id_item:         l.id_item,
      id_lote_destino: l.idLoteDestino,
      quantidade:      l.quantidade,
    }))

    const resposta = await api.post('/itens/transferir-lote', { transferencias })
    emit('salvo', resposta.data)
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao transferir itens.'
    notificarErro(erro.value)
  } finally {
    enviando.value = false
  }
}
</script>