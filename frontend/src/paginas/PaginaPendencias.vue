<template>
  <div class="p-6 min-h-screen bg-white dark:bg-black text-slate-900 dark:text-white">
    <div class="mb-6">
      <h1 class="text-2xl font-bold">Notas e Pedidos Pendentes</h1>
      <p class="text-sm text-slate-500 dark:text-slate-400">O que ainda aguarda ação antes de entrar no estoque</p>
    </div>

    <p v-if="carregando" class="text-sm text-slate-500 dark:text-slate-400">Carregando...</p>

    <template v-else>
      <!-- Notas -->
      <section class="mb-8">
        <h2 class="text-lg font-semibold mb-3">Notas fiscais ({{ notas.length }})</h2>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 overflow-x-auto">
          <p v-if="notas.length === 0" class="text-sm text-slate-500 dark:text-slate-400 py-4 text-center">
            Nenhuma nota pendente.
          </p>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                <th class="text-left pb-3 font-medium">NF</th>
                <th class="text-left pb-3 font-medium">Fornecedor</th>
                <th class="text-left pb-3 font-medium">Pedido</th>
                <th class="text-left pb-3 font-medium">Itens que entraram</th>
                <th class="text-left pb-3 font-medium">Status</th>
                <th class="text-left pb-3 font-medium">Motivo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
              <tr v-for="n in notas" :key="n.id_nota" class="align-top">
                <td class="py-3 font-medium">
                  <RouterLink to="/recebimentos" class="text-blue-600 dark:text-blue-400 hover:underline">{{ n.numero }}</RouterLink>
                </td>
                <td class="py-3 text-slate-600 dark:text-slate-300">{{ n.fornecedor_nome || '—' }}</td>
                <td class="py-3 text-slate-500 dark:text-slate-400">{{ n.numero_pedido || '—' }}</td>
                <td class="py-3 text-slate-500 dark:text-slate-400">{{ n.itens_entraram }} / {{ n.itens_total }}</td>
                <td class="py-3">
                  <span class="px-2 py-0.5 rounded text-xs font-bold text-white" :class="n.status === 'parcial' ? 'bg-yellow-600' : 'bg-red-600'">
                    {{ n.status === 'parcial' ? 'Parcial' : 'Pendente' }}
                  </span>
                </td>
                <td class="py-3">
                  <ul v-if="n.motivos.length" class="list-disc pl-4 space-y-0.5 text-slate-600 dark:text-slate-300">
                    <li v-for="(m, i) in n.motivos" :key="i">{{ m }}</li>
                  </ul>
                  <span v-else class="text-slate-400 dark:text-slate-500">Aguardando conferência física.</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Pedidos -->
      <section>
        <h2 class="text-lg font-semibold mb-3">Pedidos aguardando recebimento ({{ pedidos.length }})</h2>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 overflow-x-auto">
          <p v-if="pedidos.length === 0" class="text-sm text-slate-500 dark:text-slate-400 py-4 text-center">
            Nenhum pedido aguardando.
          </p>
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                <th class="text-left pb-3 font-medium">Pedido</th>
                <th class="text-left pb-3 font-medium">Fornecedor</th>
                <th class="text-left pb-3 font-medium">Emissão</th>
                <th class="text-left pb-3 font-medium">Itens</th>
                <th class="text-left pb-3 font-medium">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
              <tr v-for="p in pedidos" :key="p.id_pedido">
                <td class="py-3 font-medium">
                  <RouterLink to="/pedidos" class="text-blue-600 dark:text-blue-400 hover:underline">{{ p.numero }}</RouterLink>
                </td>
                <td class="py-3 text-slate-600 dark:text-slate-300">{{ p.fornecedor_nome || '—' }}</td>
                <td class="py-3 text-slate-500 dark:text-slate-400">{{ formatarData(p.data_emissao) }}</td>
                <td class="py-3 text-slate-500 dark:text-slate-400">{{ p.itens_count }}</td>
                <td class="py-3">
                  <span class="px-2 py-0.5 rounded text-xs font-bold text-white" :class="p.status === 'parcial' ? 'bg-yellow-600' : 'bg-blue-600'">
                    {{ p.status === 'parcial' ? 'Parcial' : 'Aberto' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <p class="mt-6 text-xs text-slate-400 dark:text-slate-500">
        Quem resolve cada pendência e o prazo ainda dependem de definição do responsável pelo almoxarifado.
      </p>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/servicos/api'
import { useNotificacao } from '@/composables/useNotificacao'
import { formatarData } from '@/utils/date'

const { erro } = useNotificacao()

const notas     = ref([])
const pedidos   = ref([])
const carregando = ref(false)

onMounted(async () => {
  carregando.value = true
  try {
    const { data } = await api.get('/recebimentos/pendentes')
    notas.value = data.notas
    pedidos.value = data.pedidos
  } catch (e) {
    console.error(e)
    erro('Não foi possível carregar as pendências.')
  } finally {
    carregando.value = false
  }
}) 
</script>
