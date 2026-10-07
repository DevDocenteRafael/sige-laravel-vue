<template>
  <div class="p-6 min-h-screen bg-white dark:bg-black text-slate-900 dark:text-white">

    <div class="mb-8">
      <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Dashboard</h1>
      <p class="text-slate-500 dark:text-slate-400 mt-1">Visão geral do sistema de gerenciamento de estoque</p>
    </div>

    <div v-if="carregando" class="text-center py-12 text-slate-500 dark:text-slate-400">
      Carregando dados...
    </div>

    <div v-else class="space-y-6">

      <!-- CARDS -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <div class="rounded-xl p-6 bg-slate-50 dark:bg-slate-900 border-2 border-blue-300 dark:border-blue-700 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 transition flex flex-col gap-4" @click="$router.push('/lotes')">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm">Total de Lotes</p>
              <p class="text-4xl font-bold text-slate-900 dark:text-white mt-2">{{ formatNumero(resumo.totalLotes) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-600/20 rounded-lg flex items-center justify-center">
              <PackagePlus class="text-blue-600 dark:text-blue-500" :size="24" />
            </div>
          </div>
          <p class="text-xs text-blue-600 dark:text-blue-400">Clique para ver todos os lotes</p>
        </div>

        <div class="rounded-xl p-6 bg-slate-50 dark:bg-slate-900 border-2 border-cyan-300 dark:border-cyan-700 cursor-pointer hover:bg-cyan-50 dark:hover:bg-cyan-900/20 transition flex flex-col gap-4" @click="$router.push('/produtos')">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm">Total de Itens</p>
              <p class="text-4xl font-bold text-slate-900 dark:text-white mt-2">{{ formatNumero(resumo.totalProdutos) }}</p>
            </div>
            <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-600/20 rounded-lg flex items-center justify-center">
              <Package class="text-cyan-600 dark:text-cyan-500" :size="24" />
            </div>
          </div>
          <p class="text-xs text-cyan-600 dark:text-cyan-400">Clique para ver todos os produtos</p>
        </div>

        <div class="rounded-xl p-6 bg-slate-50 dark:bg-slate-900 border-2 border-amber-300 dark:border-amber-700 cursor-pointer hover:bg-amber-50 dark:hover:bg-amber-900/20 transition flex flex-col gap-4" @click="$router.push('/lotes?filtro=vencendo')">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm">Vencendo em 30 dias</p>
              <p class="text-4xl font-bold text-slate-900 dark:text-white mt-2">{{ formatNumero(resumo.vencendoEm30Dias) }}</p>
            </div>
            <div class="w-12 h-12 bg-amber-100 dark:bg-amber-600/20 rounded-lg flex items-center justify-center">
              <AlertTriangle class="text-amber-600 dark:text-amber-500" :size="24" />
            </div>
          </div>
          <p class="text-xs text-amber-600 dark:text-amber-400">Clique para ver produtos vencendo</p>
        </div>

        <div class="rounded-xl p-6 bg-slate-50 dark:bg-slate-900 border-2 border-red-300 dark:border-red-700 cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20 transition flex flex-col gap-4" @click="$router.push('/produtos?filtro=critico')">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-sm">Estoque Baixo</p>
              <p class="text-4xl font-bold text-slate-900 dark:text-white mt-2">{{ formatNumero(resumo.estoqueCritico) }}</p>
            </div>
            <div class="w-12 h-12 bg-red-100 dark:bg-red-600/20 rounded-lg flex items-center justify-center">
              <TrendingDown class="text-red-600 dark:text-red-500" :size="24" />
            </div>
          </div>
          <p class="text-xs text-red-600 dark:text-red-400">Clique para ver estoque baixo</p>
        </div>

      </div>

      <!-- DISTRIBUIÇÃO POR CATEGORIA + GRÁFICO DE LINHA -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
              <PieChart class="text-purple-600 dark:text-purple-400" :size="24" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900 dark:text-white">Distribuição por Categoria</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400">{{ formatNumero(resumo.totalCategorias || 0) }} categorias</p>
            </div>
          </div>
          <div v-if="semDadosPizza" class="flex items-center justify-center h-64 text-slate-400 dark:text-slate-500">
            Nenhum dado disponível
          </div>
          <canvas v-else ref="graficoPizza" height="300"></canvas>
        </div>

        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
              <TrendingUp class="text-blue-600 dark:text-blue-400" :size="24" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900 dark:text-white">Evolução do Estoque</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400">Últimos 30 dias</p>
            </div>
          </div>
          <canvas ref="graficoLinha" height="300"></canvas>
        </div>

      </div>

      <!-- ALERTAS CRÍTICOS + MOVIMENTOS -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6">
          <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <AlertTriangle class="text-red-600 dark:text-red-500" :size="20" />
            Alertas Críticos
          </h2>
          <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
            <div v-if="resumo.vencendoEm30Dias > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900 rounded-lg p-4">
              <p class="text-red-600 dark:text-red-400 font-medium">{{ formatNumero(resumo.vencendoEm30Dias) }} produto(s) vencendo em 30 dias</p>
              <p class="text-red-500 dark:text-red-300 text-sm mt-1 mb-3">Verificar validade e priorizar saída (FEFO)</p>

              <div class="space-y-2">
                <div
                  v-for="produto in produtosVencendo"
                  :key="produto.id"
                  class="flex items-center justify-between bg-red-100 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 rounded-md px-3 py-2"
                >
                  <div class="flex flex-col">
                    <span class="text-sm text-red-700 dark:text-red-100 font-medium">{{ produto.nome }}</span>
                    <span class="text-xs text-red-500 dark:text-red-300">Lote: {{ produto.lote || '—' }}</span>
                  </div>
                  <div class="flex flex-col items-end">
                    <span class="text-xs text-red-500 dark:text-red-300">{{ formatarDataSimples(produto.data_validade) }}</span>
                    <span class="text-xs font-semibold" :class="produto.dias_restantes <= 7 ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400'">
                      {{ produto.dias_restantes <= 0 ? 'Vence hoje' : `${produto.dias_restantes} dia(s)` }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="resumo.estoqueCritico > 0" class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-900 rounded-lg p-4">
              <p class="text-orange-600 dark:text-orange-400 font-medium">{{ formatNumero(resumo.estoqueCritico) }} produto(s) com estoque baixo</p>
              <p class="text-orange-500 dark:text-orange-300 text-sm mt-1 mb-3">Solicitar reposição de estoque</p>

              <div class="space-y-2">
                <div
                  v-for="produto in produtosEstoqueCritico"
                  :key="produto.id"
                  class="flex items-center justify-between bg-orange-100 dark:bg-orange-950/40 border border-orange-200 dark:border-orange-900/50 rounded-md px-3 py-2"
                >
                  <span class="text-sm text-orange-700 dark:text-orange-100 font-medium">{{ produto.nome }}</span>
                  <span class="text-xs text-orange-500 dark:text-orange-300">
                    {{ formatNumero(produto.quantidade) }} / {{ formatNumero(produto.estoque_minimo) }} {{ produto.unidade_medida || '' }}
                  </span>
                </div>
              </div>
            </div>

            <div v-if="semAlertas" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-900 rounded-lg p-4">
              <p class="text-green-600 dark:text-green-400 font-medium">✓ Nenhum alerta crítico</p>
              <p class="text-green-500 dark:text-green-300 text-sm mt-1">Tudo está funcionando perfeitamente</p>
            </div>
          </div>
        </div>

        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6">
          <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <History class="text-blue-600 dark:text-blue-500" :size="20" />
            Movimentos Recentes
          </h2>
          <div class="space-y-3">
            <p v-if="movimentosRecentes.length === 0" class="text-slate-400 dark:text-slate-500 text-center py-8">
              Nenhum movimento registrado ainda.
            </p>
            <div
              v-for="mov in movimentosRecentes"
              :key="mov.id"
              class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-transparent rounded-lg p-4 flex items-center justify-between"
            >
              <div class="flex items-center gap-3">
                <div :class="mov.tipo === 'entrada' ? 'bg-green-100 dark:bg-green-900/40' : 'bg-red-100 dark:bg-red-900/40'" class="w-8 h-8 rounded-lg flex items-center justify-center">
                  <TrendingUp v-if="mov.tipo === 'entrada'" class="text-green-600 dark:text-green-400" :size="16" />
                  <TrendingDown v-else class="text-red-600 dark:text-red-400" :size="16" />
                </div>
                <div>
                  <p class="text-slate-900 dark:text-white font-medium text-sm">{{ mov.item_lote?.produto?.nome || '—' }}</p>
                  <p class="text-slate-500 dark:text-slate-400 text-xs">Por {{ mov.usuario?.nome || 'Sistema' }} • {{ formatarData(mov.data_movimento) }}</p>
                </div>
              </div>
              <span :class="mov.tipo === 'entrada' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="font-bold text-sm">
                {{ mov.tipo === 'entrada' ? '+' : '-' }}{{ formatNumero(mov.quantidade) }}
              </span>
            </div>
          </div>
        </div>

      </div>

      <!-- TOP PRODUTOS -->
      <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
              <TrendingUp class="text-green-600 dark:text-green-400" :size="24" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900 dark:text-white">Top Produtos</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400">Maiores estoques</p>
            </div>
          </div>

          <!-- Filtros: quantidade e categoria -->
          <div class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2">
              <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">Mostrar</label>
              <select
                :value="modoPersonalizado ? 'personalizado' : opcaoLimite"
                class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-2 py-1.5 text-sm outline-none"
                @change="aoMudarSelectLimite"
              >
                <option v-for="opcao in opcoesLimite" :key="opcao" :value="opcao">{{ opcao }}</option>
                <option value="personalizado">Personalizado</option>
              </select>

              <input
                v-if="modoPersonalizado"
                v-model.number="limitePersonalizado"
                type="number"
                min="1"
                max="1000"
                placeholder="Qtd"
                class="w-20 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-2 py-1.5 text-sm outline-none"
              />
            </div>

            <div class="flex items-center gap-2">
              <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">Categoria</label>
              <select
                v-model="filtroCategoria"
                class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-2 py-1.5 text-sm outline-none max-w-[180px]"
              >
                <option value="todas">Todas</option>
                <option v-for="cat in categoriasDisponiveis" :key="cat" :value="cat">{{ cat }}</option>
              </select>
            </div>
          </div>
        </div>

        <div v-if="carregandoTopProdutos" class="flex items-center justify-center h-40 text-slate-400 dark:text-slate-500">
          Carregando...
        </div>

        <div v-else-if="topProdutos.length === 0" class="flex items-center justify-center h-40 text-slate-400 dark:text-slate-500">
          Nenhum dado disponível
        </div>

        <table v-else class="w-full text-sm">
          <thead>
            <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <th class="text-left pb-3 font-medium">#</th>
              <th class="text-left pb-3 font-medium">Produto</th>
              <th class="text-left pb-3 font-medium">Categoria</th>
              <th class="text-right pb-3 font-medium">Estoque</th>
              <th class="text-right pb-3 font-medium">Mín.</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr
              v-for="(produto, index) in topProdutos"
              :key="produto.id_produto"
              class="hover:bg-slate-100 dark:hover:bg-slate-800/50 transition"
            >
              <td class="py-3 text-slate-400 dark:text-slate-500">{{ index + 1 }}</td>
              <td class="py-3 text-slate-900 dark:text-white font-medium">{{ produto.nome }}</td>
              <td class="py-3 text-slate-500 dark:text-slate-400">{{ produto.categoria?.nome || '—' }}</td>
              <td class="py-3 text-right">
                <span :class="produto.estoque_atual <= produto.estoque_minimo ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" class="font-bold">
                  {{ formatNumero(produto.estoque_atual) }}
                </span>
              </td>
              <td class="py-3 text-right text-slate-500 dark:text-slate-400">{{ formatNumero(produto.estoque_minimo) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { PackagePlus, Package, AlertTriangle, TrendingDown, TrendingUp, PieChart, History } from 'lucide-vue-next'
import api from '@/servicos/api'
import Chart from 'chart.js/auto'
import { useTemaStore } from '@/servicos/tema.store'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'

const temaStore    = useTemaStore()
const { temaClaro } = storeToRefs(temaStore)

const autenticacaoStore = useAutenticacaoStore()
const { perfil }        = storeToRefs(autenticacaoStore)

const carregando            = ref(true)
const graficoLinha          = ref(null)
const graficoPizza          = ref(null)
const movimentosRecentes    = ref([])
const topProdutos           = ref([])
const produtosEstoqueCritico = ref([])
const produtosVencendo      = ref([])
const semDadosPizza         = ref(false)

// ===== Filtro do Top Produtos =====
const opcoesLimite          = ref([5, 10, 20, 50, 100]) // opções fixas do select
const opcaoLimite          = ref(10)     // sempre número, só as opções fixas
const modoPersonalizado    = ref(false)  // toggle separado, sem misturar tipos no select
const limitePersonalizado  = ref(50)
const filtroCategoria      = ref('todas')
const categoriasDisponiveis = ref([])
const carregandoTopProdutos = ref(false)

const filtroLimite = computed(() => {
  if (modoPersonalizado.value) {
    const valor = Number(limitePersonalizado.value)
    return valor > 0 ? valor : opcaoLimite.value
  }
  return opcaoLimite.value
})

function aoMudarSelectLimite(event) {
  const valor = event.target.value
  if (valor === 'personalizado') {
    modoPersonalizado.value = true
  } else {
    modoPersonalizado.value = false
    opcaoLimite.value = Number(valor)
  }
}

const resumo = ref({
  totalProdutos:    0,
  totalLotes:       0,
  estoqueCritico:   0,
  vencendoEm30Dias: 0,
  totalCategorias:  0,
})
const semAlertas = computed(() =>
  resumo.value.vencendoEm30Dias === 0 && resumo.value.estoqueCritico === 0
)
let chartLinha = null
let chartPizza = null
let ultimosDadosLinha  = []
let ultimosDadosPizza  = []

// --- Auto-refresh (somente para o perfil visualizador) ---
const INTERVALO_POLLING_MS = 30000
let idIntervaloPolling = null

function formatNumero(valor) {
  return Number(valor ?? 0).toLocaleString('pt-BR')
}

function formatarData(data) {
  if (!data) return '—'
  return new Date(data).toLocaleDateString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

function formatarDataSimples(data) {
  if (!data) return '—'
  return new Date(data).toLocaleDateString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric'
  })
}

function recortarDiasVazios(dados) {
  const primeiroIndiceComDado = dados.findIndex(
    d => (d.estoqueTotal || 0) > 0 || (d.entradas || 0) > 0 || (d.saidas || 0) > 0
  )
  if (primeiroIndiceComDado <= 1) return dados
  return dados.slice(primeiroIndiceComDado - 1)
}

async function carregarDashboard({ mostrarLoading = true } = {}) {
  if (mostrarLoading) carregando.value = true
  let dadosEvolucao = []
  let dadosDistribuicao = []

  try {
    const resposta = await api.get('/dashboard')
    resumo.value = resposta.data?.resumo ?? resumo.value
    movimentosRecentes.value     = resposta.data.movimentosRecentes || []
    topProdutos.value            = resposta.data.topProdutos || []
    produtosEstoqueCritico.value = resposta.data.produtosEstoqueCritico || []
    produtosVencendo.value       = resposta.data.produtosVencendo || []
    dadosEvolucao                = recortarDiasVazios(resposta.data.evolucaoEstoque || [])
    dadosDistribuicao            = resposta.data.distribuicaoCategorias || []

    categoriasDisponiveis.value = dadosDistribuicao.map(c => c.categoria).filter(Boolean)
  } catch (erro) {
    console.error('Erro ao carregar dashboard:', erro)
  } finally {
    if (mostrarLoading) carregando.value = false
  }

  ultimosDadosLinha = dadosEvolucao
  ultimosDadosPizza = dadosDistribuicao

  await nextTick()
  montarGraficoLinha(dadosEvolucao)
  montarGraficoPizza(dadosDistribuicao)
}

async function carregarTopProdutosFiltrado() {
  carregandoTopProdutos.value = true
  try {
    const resposta = await api.get('/dashboard/top-produtos', {
      params: {
        limite: filtroLimite.value,
        categoria: filtroCategoria.value,
      },
    })
    topProdutos.value = resposta.data || []
  } catch (erro) {
    console.error('Erro ao carregar top produtos filtrado:', erro)
  } finally {
    carregandoTopProdutos.value = false
  }
}

// Debounce manual — evita disparar uma requisição a cada tecla no campo personalizado
let idDebounceTopProdutos = null
function agendarCarregarTopProdutosFiltrado() {
  clearTimeout(idDebounceTopProdutos)
  idDebounceTopProdutos = setTimeout(() => {
    carregarTopProdutosFiltrado()
  }, 400)
}

watch([filtroLimite, filtroCategoria], () => {
  agendarCarregarTopProdutosFiltrado()
})

function coresDoTema() {
  return temaClaro.value
    ? { texto: '#475569', textoEixo: '#64748b', grid: 'rgba(0,0,0,0.08)', bordaFatia: '#ffffff' }
    : { texto: '#94a3b8', textoEixo: '#64748b', grid: 'rgba(255,255,255,0.05)', bordaFatia: '#0f172a' }
}

function montarGraficoLinha(dados) {
  if (chartLinha) chartLinha.destroy()
  if (!graficoLinha.value) return

  const cores = coresDoTema()

  chartLinha = new Chart(graficoLinha.value, {
    type: 'line',
    data: {
      labels: dados.map(d => d.label),
      datasets: [
        {
          label: 'Estoque Total',
          data: dados.map(d => d.estoqueTotal || 0),
          borderColor: '#3b82f6',
          backgroundColor: '#3b82f6',
          fill: false,
          tension: 0.3,
          pointRadius: 3,
          pointHoverRadius: 5,
          pointBackgroundColor: temaClaro.value ? '#ffffff' : '#0f172a',
          pointBorderColor: '#3b82f6',
          pointBorderWidth: 2,
          pointHoverBackgroundColor: '#3b82f6',
          borderWidth: 2,
        },
        {
          label: 'Entradas',
          data: dados.map(d => d.entradas || 0),
          borderColor: '#10b981',
          backgroundColor: '#10b981',
          fill: false,
          tension: 0.3,
          pointRadius: 3,
          pointHoverRadius: 5,
          pointBackgroundColor: temaClaro.value ? '#ffffff' : '#0f172a',
          pointBorderColor: '#10b981',
          pointBorderWidth: 2,
          pointHoverBackgroundColor: '#10b981',
          borderWidth: 2,
        },
        {
          label: 'Saídas',
          data: dados.map(d => d.saidas || 0),
          borderColor: '#ef4444',
          backgroundColor: '#ef4444',
          fill: false,
          tension: 0.3,
          pointRadius: 3,
          pointHoverRadius: 5,
          pointBackgroundColor: temaClaro.value ? '#ffffff' : '#0f172a',
          pointBorderColor: '#ef4444',
          pointBorderWidth: 2,
          pointHoverBackgroundColor: '#ef4444',
          borderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      animation: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: cores.texto, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle', padding: 16 },
        },
        tooltip: {
          backgroundColor: temaClaro.value ? '#ffffff' : '#1e293b',
          titleColor: temaClaro.value ? '#0f172a' : '#f1f5f9',
          bodyColor: temaClaro.value ? '#334155' : '#cbd5e1',
          borderColor: temaClaro.value ? '#e2e8f0' : '#334155',
          borderWidth: 1,
          padding: 10,
          cornerRadius: 8,
          usePointStyle: true,
        },
      },
      scales: {
        x: {
          ticks: {
            color: cores.textoEixo,
            font: { size: 10 },
            maxRotation: 0,
            autoSkip: true,
            maxTicksLimit: 8,
          },
          grid: { color: cores.grid, borderDash: [4, 4] },
        },
        y: {
          ticks: { color: cores.textoEixo, font: { size: 10 } },
          grid: { color: cores.grid, borderDash: [4, 4] },
          beginAtZero: true,
        },
      },
    },
  })
}

function montarGraficoPizza(dados) {
  if (chartPizza) chartPizza.destroy()

  if (!dados || dados.length === 0) {
    semDadosPizza.value = true
    return
  }

  semDadosPizza.value = false
  if (!graficoPizza.value) return

  const cores = coresDoTema()
  const paleta = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#14b8a6']

  chartPizza = new Chart(graficoPizza.value, {
    type: 'pie',
    data: {
      labels: dados.map(d => d.categoria),
      datasets: [{
        data: dados.map(d => d.percentual || d.quantidade),
        backgroundColor: paleta.slice(0, dados.length),
        borderColor: cores.bordaFatia,
        borderWidth: 2,
      }],
    },
    options: {
      responsive: true,
      animation: false,
      plugins: {
        legend: { position: 'bottom', labels: { color: cores.texto, font: { size: 11 }, padding: 12, boxWidth: 12 } },
        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed.toFixed(1)}%` } },
      },
    },
  })
}

watch(temaClaro, async () => {
  await nextTick()
  montarGraficoLinha(ultimosDadosLinha)
  montarGraficoPizza(ultimosDadosPizza)
})

onMounted(async () => {
  await carregarDashboard()

  if (perfil.value === 'visualizador') {
    idIntervaloPolling = setInterval(() => {
      carregarDashboard({ mostrarLoading: false })
    }, INTERVALO_POLLING_MS)
  }
})

onUnmounted(() => {
  if (idIntervaloPolling) clearInterval(idIntervaloPolling)
  if (idDebounceTopProdutos) clearTimeout(idDebounceTopProdutos)
})
</script>