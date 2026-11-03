<template>
  <div class="p-6 min-h-screen">

    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Movimentos</h1>
        <p class="text-sm text-gray-500">Histórico de entradas e saídas de estoque</p>
      </div>
      <div v-if="autenticacao.podeMovimentar">
        <router-link
          to="/lotes"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium"
        >
          Ir para Lotes para movimentar
        </router-link>
      </div>
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
      <select v-model="filtroTipo" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <option value="">Todos os tipos</option>
        <option value="Entrada">Entradas</option>
        <option value="Saída">Saídas</option>
      </select>
      <input v-model="filtroDataInicio" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
      <input v-model="filtroDataFim" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
      <button class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 transition" @click="limparFiltros">
        Limpar
      </button>
    </div>

    <div v-if="carregando" class="text-center py-12 text-gray-500">
      Carregando movimentos...
    </div>

    <div v-else-if="movimentosFiltrados.length === 0" class="text-center py-12 text-gray-500">
      Nenhum movimento encontrado.
    </div>

    <div v-else class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Tipo</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Produto</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">SKU</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Lote</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Quantidade</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Motivo</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Usuário</th>
            <th class="text-left px-4 py-3 text-sm font-medium text-gray-600">Data</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="mov in movimentosFiltrados" :key="mov.id" class="border-b hover:bg-gray-50">
            <td class="px-4 py-3">
              <span
                class="px-2 py-1 rounded-full text-xs font-medium"
                :class="mov.tipo === 'Entrada' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
              >
                {{ mov.tipo === 'Entrada' ? '⬆️ Entrada' : '⬇️ Saída' }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-800">{{ mov.produto }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ mov.sku }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ mov.lote }}</td>
            <td class="px-4 py-3 text-sm font-semibold" :class="mov.tipo === 'Entrada' ? 'text-green-600' : 'text-red-600'">
              {{ mov.tipo === 'Entrada' ? '+' : '-' }}{{ mov.quantidade }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ mov.motivo || '—' }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ mov.usuario }}</td>
            <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ formatarDataHora(mov.data) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import { useNotificacao } from '@/composables/useNotificacao'
const { erro } = useNotificacao()

const autenticacao = useAutenticacaoStore()

const movimentos     = ref([])
const carregando     = ref(false)
const filtroTipo     = ref('')
const filtroDataInicio = ref('')
const filtroDataFim    = ref('')

const movimentosFiltrados = computed(() => {
  return movimentos.value.filter(mov => {
    const tipoOk = !filtroTipo.value || mov.tipo === filtroTipo.value
    const dataMov = mov.data?.slice(0, 10)
    const inicioOk = !filtroDataInicio.value || dataMov >= filtroDataInicio.value
    const fimOk    = !filtroDataFim.value    || dataMov <= filtroDataFim.value
    return tipoOk && inicioOk && fimOk
  })
})

async function carregarMovimentos() {
  carregando.value = true
  try {
    const { data } = await api.get('/movimentacoes')
    movimentos.value = data
  } catch {
    erro('Não foi possível carregar os movimentos.')
  } finally {
    carregando.value = false
  }
}

function limparFiltros() {
  filtroTipo.value = ''
  filtroDataInicio.value = ''
  filtroDataFim.value = ''
}

function formatarDataHora(dataISO) {
  if (!dataISO) return '—'
  const d = new Date(dataISO)
  const dia  = String(d.getDate()).padStart(2, '0')
  const mes  = String(d.getMonth() + 1).padStart(2, '0')
  const ano  = d.getFullYear()
  const hora = String(d.getHours()).padStart(2, '0')
  const min  = String(d.getMinutes()).padStart(2, '0')
  return `${dia}/${mes}/${ano}, ${hora}:${min}`
}

onMounted(carregarMovimentos)
</script>