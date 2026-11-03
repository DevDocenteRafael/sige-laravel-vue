<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto" :style="estiloArraste">

      <div class="flex justify-between items-start mb-6 cursor-grab active:cursor-grabbing select-none" @mousedown="aoIniciarArraste">
        <div>
          <h2 class="text-slate-900 dark:text-white font-bold text-lg">Detalhes do Produto</h2>
          <p class="text-sky-600 dark:text-sky-400 text-xs mt-0.5">Visualize informações completas e histórico de movimentações do produto.</p>
        </div>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="$emit('fechar')">
          <X :size="18" />
        </button>
      </div>

      <div class="grid grid-cols-2 gap-6 mb-6">
        <div>
          <p class="text-sky-600 dark:text-sky-400 text-xs mb-1">Código / SKU</p>
          <p class="text-slate-900 dark:text-white font-bold">{{ item.produto?.sku || '—' }}</p>
        </div>
        <div>
          <p class="text-sky-600 dark:text-sky-400 text-xs mb-1">Nome do Produto</p>
          <p class="text-slate-900 dark:text-white font-bold">{{ item.produto?.nome || '—' }}</p>
        </div>
        <div>
          <p class="text-sky-600 dark:text-sky-400 text-xs mb-1">Lote</p>
          <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-600 text-white">
            {{ item.lote?.numero_lote || loteNumero || '—' }}
          </span>
        </div>
        <div>
          <p class="text-sky-600 dark:text-sky-400 text-xs mb-1">Status</p>
          <span
            v-if="item.data_validade && estaVencido(item.data_validade)"
            class="px-2 py-0.5 rounded text-xs font-bold bg-red-600 text-white"
          >Vencido</span>
          <span
            v-else-if="item.data_validade && proximoDoVencimento(item.data_validade)"
            class="px-2 py-0.5 rounded text-xs font-bold bg-yellow-600 text-white"
          >Vencendo</span>
          <span
            v-else-if="item.quantidade === 0"
            class="px-2 py-0.5 rounded text-xs font-bold bg-red-800 text-white"
          >Sem Estoque</span>
          <span
           v-else-if="item.quantidade <= (item.produto?.estoque_minimo ?? 0)"
            class="px-2 py-0.5 rounded text-xs font-bold bg-orange-600 text-white"
          >Crítico</span>
          <span
            v-else
            class="px-2 py-0.5 rounded text-xs font-bold bg-green-700 text-white"
          >Normal</span>
        </div>
        <div>
          <p class="text-sky-600 dark:text-sky-400 text-xs mb-1">Quantidade</p>
          <p class="text-slate-900 dark:text-white font-bold">{{ item.quantidade }} {{ item.unidade_medida || 'unidades' }}</p>
        </div>
        <div>
          <p class="text-sky-600 dark:text-sky-400 text-xs mb-1">Data de Validade</p>
          <p class="text-slate-900 dark:text-white font-bold">{{ item.data_validade ? formatarData(item.data_validade) : '—' }}</p>
        </div>
        <div>
          <p class="text-sky-600 dark:text-sky-400 text-xs mb-1">Fornecedor</p>
         <p class="text-slate-700 dark:text-white">{{ item.produto?.fornecedor?.nome || '—' }}</p>
        </div>
        <div>
          <p class="text-sky-600 dark:text-sky-400 text-xs mb-1">Localização / Prateleira</p>
          <p class="text-slate-700 dark:text-white">{{ item.localizacao || '—' }}</p>
        </div>
      </div>

      <div class="flex gap-2 mb-6 flex-wrap">
        <button
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium"
          @click="$emit('editar', item)"
        >
          <Pencil :size="15" />
          Editar Produto
        </button>
        <button
          class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-medium"
          @click="$emit('transferir', item)"
        >
          <ArrowRightLeft :size="15" />
          Transferir
        </button>
        <button
          class="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition text-sm font-medium"
          @click="$emit('baixa', item)"
        >
          <PackageOpen :size="15" />
          Registrar Baixa
        </button>
        <button
          class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium"
          @click="$emit('entrada', item)"
        >
          <PackagePlus :size="15" />
          Registrar Entrada
        </button>
        <button
          class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium"
          @click="$emit('excluir', item)"
        >
          <Trash2 :size="15" />
          Excluir
        </button>
      </div>

      <div>
        <h3 class="text-slate-900 dark:text-white font-bold mb-3">Histórico do Item</h3>

        <div v-if="carregandoHistorico" class="text-slate-500 dark:text-slate-400 text-sm text-center py-4">
          Carregando histórico...
        </div>

        <div v-else-if="historico.length === 0" class="text-slate-400 dark:text-slate-500 text-sm text-center py-4">
          Nenhuma movimentação registrada.
        </div>

        <table v-else class="w-full text-sm">
          <thead>
            <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <th class="text-left pb-2 font-medium">Data</th>
              <th class="text-left pb-2 font-medium">Tipo</th>
              <th class="text-left pb-2 font-medium">Quantidade</th>
              <th class="text-left pb-2 font-medium">Motivo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr v-for="mov in historico" :key="mov.id" class="hover:bg-slate-100 dark:hover:bg-slate-800/40 transition">
              <td class="py-2 text-slate-600 dark:text-slate-300">{{ formatarDataHora(mov.data_movimentacao) }}</td>
              <td class="py-2">
                <span
                  :class="mov.tipo === 'ENTRADA'
                    ? 'bg-green-700 text-white'
                    : mov.tipo === 'TRANSFERENCIA'
                      ? 'bg-purple-600 text-white'
                      : 'bg-red-600 text-white'"
                  class="px-2 py-0.5 rounded text-xs font-bold capitalize"
                >
                  {{ mov.tipo === 'ENTRADA' ? 'Entrada' : mov.tipo === 'TRANSFERENCIA' ? 'Transferência' : 'Saída' }}
                </span>
              </td>
              <td
                class="py-2"
                :class="mov.tipo === 'ENTRADA' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
              >
                {{ mov.tipo === 'ENTRADA' ? '+' : '' }}{{ mov.quantidade }}
              </td>
              <td class="py-2 text-slate-500 dark:text-slate-400">{{ mov.observacao || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { X, Pencil, PackageOpen, PackagePlus, Trash2, ArrowRightLeft } from 'lucide-vue-next'
import api from '@/servicos/api'
import { formatarData, formatarDataHora, estaVencido, proximoDoVencimento } from '@/utils/date'
import { useModalArrastavel } from '@/composables/useModalArrastavel'

const props = defineProps({
  item:        { type: Object, required: true },
  loteNumero:  { type: String, default: '' },
})

defineEmits(['fechar', 'editar', 'baixa', 'entrada', 'excluir', 'transferir'])

const { aoIniciarArraste, estiloArraste } = useModalArrastavel()

const historico          = ref([])
const carregandoHistorico = ref(false)

onMounted(async () => {
  carregandoHistorico.value = true
  try {
    const res = await api.get(`/itens/${props.item.id_item}/historico`)
    historico.value = res.data
  } catch {
    historico.value = []
  } finally {
    carregandoHistorico.value = false
  }
})
</script>