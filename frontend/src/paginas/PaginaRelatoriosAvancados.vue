<template>
  <div class="p-6 min-h-screen bg-white dark:bg-black text-slate-900 dark:text-white">

    <!-- Cabeçalho -->
    <div class="flex items-start justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Relatórios Avançados</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Análises detalhadas de perdas e classificação ABC</p>
      </div>
      <div ref="menusRoot" class="flex gap-2">
        <!-- Centro de custo -->
        <div class="relative">
          <button
            class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm px-4 py-2 rounded-lg hover:border-blue-500 transition"
            @click="dropdownCentroAberto = !dropdownCentroAberto; dropdownPeriodoAberto = false; dropdownExportAberto = false"
          >
            <Building2 :size="16" /> <span class="max-w-[160px] truncate">{{ centroLabel }}</span>
            <ChevronDown :size="16" :class="['transition-transform', dropdownCentroAberto ? 'rotate-180' : '']" />
          </button>
          <div
            v-if="dropdownCentroAberto"
            class="absolute right-0 z-10 mt-2 w-64 max-h-64 overflow-y-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl p-1"
          >
            <div
              v-for="c in opcoesCentro"
              :key="c.id ?? 'todos'"
              class="flex items-center justify-between gap-2 px-3 py-2 text-sm rounded-md cursor-pointer transition"
              :class="centroSelecionado.id === c.id
                ? 'bg-blue-600 text-white'
                : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
              @click="selecionarCentro(c)"
            >
              <span class="truncate">{{ c.label }}</span>
              <Check v-if="centroSelecionado.id === c.id" :size="14" class="shrink-0" />
            </div>
          </div>
        </div>

        <div class="relative">
          <button
            class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm px-4 py-2 rounded-lg hover:border-blue-500 transition"
            @click="dropdownPeriodoAberto = !dropdownPeriodoAberto; dropdownExportAberto = false; dropdownCentroAberto = false"
          >
            <Calendar :size="16" /> {{ periodoLabel }}
            <ChevronDown :size="16" :class="['transition-transform', dropdownPeriodoAberto ? 'rotate-180' : '']" />
          </button>
          <div
            v-if="dropdownPeriodoAberto"
            class="absolute right-0 z-10 mt-2 w-48 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl p-1"
          >
            <div
              v-for="p in opcoesPeriodo"
              :key="p.dias"
              class="flex items-center justify-between px-3 py-2 text-sm rounded-md cursor-pointer transition"
              :class="periodo.dias === p.dias
                ? 'bg-blue-600 text-white'
                : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
              @click="selecionarPeriodo(p)"
            >
              {{ p.label }}
              <Check v-if="periodo.dias === p.dias" :size="14" />
            </div>
          </div>
        </div>

        <div class="relative">
          <button
            class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm px-4 py-2 rounded-lg hover:border-blue-500 transition"
            @click="dropdownExportAberto = !dropdownExportAberto; dropdownPeriodoAberto = false; dropdownCentroAberto = false"
          >
            <Download :size="16" /> Exportar
            <ChevronDown :size="16" :class="['transition-transform', dropdownExportAberto ? 'rotate-180' : '']" />
          </button>
          <div
            v-if="dropdownExportAberto"
            class="absolute right-0 z-10 mt-2 w-48 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl p-1"
          >
            <div
              v-for="opt in opcoesExport"
              :key="opt.formato"
              class="flex items-center gap-3 px-3 py-2 text-sm rounded-md cursor-pointer text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
              @click="opt.acao(); dropdownExportAberto = false"
            >
              <component :is="opt.icone" :size="16" :class="opt.cor" />
              <div class="leading-tight">
                <p class="font-medium">{{ opt.label }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500">{{ opt.descricao }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Abas -->
    <div class="flex gap-0 mb-6 border-b border-slate-200 dark:border-slate-800">
      <button
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 transition"
        :class="aba === 'perdas' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
        @click="aba = 'perdas'"
      >
        <TrendingDown :size="16" /> Relatório de Perdas
      </button>
      <button
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 transition"
        :class="aba === 'abc' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
        @click="aba = 'abc'"
      >
        <PieChart :size="16" /> Análise ABC
      </button>
    </div>

    <!-- ===== ABA PERDAS ===== -->
    <div v-if="aba === 'perdas'">
      <div v-if="carregando" class="text-center py-12 text-slate-400 dark:text-slate-500">Carregando...</div>
      <template v-else>
        <!-- Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
              <AlertCircle class="text-red-600 dark:text-red-400" :size="20" />
            </div>
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Registros de Perda</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumero(dadosPerdas.resumo?.total) }}</p>
              <p class="text-slate-400 dark:text-slate-500 text-[11px]">{{ periodoLabel }}</p>
            </div>
          </div>
          <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center">
              <Package class="text-orange-600 dark:text-orange-400" :size="20" />
            </div>
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Unidades Perdidas</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumero(dadosPerdas.resumo?.unidades) }}</p>
              <p class="text-slate-400 dark:text-slate-500 text-[11px]">{{ periodoLabel }}</p>
            </div>
          </div>
          <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
              <FileText class="text-blue-600 dark:text-blue-400" :size="20" />
            </div>
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Motivos Distintos</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumero(dadosPerdas.resumo?.tipos) }}</p>
              <p class="text-slate-400 dark:text-slate-500 text-[11px]">{{ periodoLabel }}</p>
            </div>
          </div>
        </div>

        <!-- Perdas por motivo -->
        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 mb-6">
          <h2 class="font-semibold text-slate-900 dark:text-white mb-4">
            Perdas por Motivo
            <span class="text-xs font-normal text-slate-400 dark:text-slate-500">· {{ periodoLabel }}</span>
          </h2>
          <div v-if="!dadosPerdas.porMotivo?.length" class="text-center py-8 text-slate-400 dark:text-slate-500">
            Nenhuma perda registrada no período
          </div>
          <div v-else class="space-y-3">
            <div v-for="m in dadosPerdas.porMotivo" :key="m.motivo" class="flex items-center gap-4">
              <span class="text-slate-600 dark:text-slate-300 text-sm w-48 truncate">{{ m.motivo }}</span>
              <div class="flex-1 bg-slate-200 dark:bg-slate-800 rounded-full h-2">
                <div
                  class="bg-red-500 h-2 rounded-full"
                  :style="{ width: totalUnidadesPerdas > 0 ? (m.total / totalUnidadesPerdas * 100) + '%' : '0%' }"
                ></div>
              </div>
              <span class="text-slate-500 dark:text-slate-400 text-sm w-24 text-right">{{ formatNumero(m.total) }} unid.</span>
              <span class="text-slate-400 dark:text-slate-500 text-xs w-24 text-right">
                {{ formatNumero(m.ocorrencias) }} {{ m.ocorrencias === 1 ? 'registro' : 'registros' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Tabela detalhada -->
        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
          <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">
              Detalhamento de Perdas
              <span class="text-xs font-normal text-slate-400 dark:text-slate-500">· {{ periodoLabel }}</span>
            </h2>
          </div>
          <table class="w-full text-sm">
            <thead>
              <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                <th class="text-left px-4 py-3 font-medium">Data</th>
                <th class="text-left px-4 py-3 font-medium">Produto</th>
                <th class="text-left px-4 py-3 font-medium">Centro de Custo</th>
                <th class="text-left px-4 py-3 font-medium">Motivo</th>
                <th class="text-right px-4 py-3 font-medium">Quantidade</th>
                <th class="text-left px-4 py-3 font-medium">Responsável</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
              <tr v-if="!dadosPerdas.perdas?.length">
                <td colspan="6" class="text-center py-8 text-slate-400 dark:text-slate-500">Nenhuma perda registrada no período</td>
              </tr>
              <tr v-for="p in dadosPerdas.perdas" :key="p.id" class="hover:bg-slate-100 dark:hover:bg-slate-800/50 transition">
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ formatarDataHora(p.data) }}</td>
                <td class="px-4 py-3 text-slate-900 dark:text-white">{{ p.produto }}</td>
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ p.centro_custo || '—' }}</td>
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ p.motivo }}</td>
                <td class="px-4 py-3 text-right text-red-600 dark:text-red-400 font-semibold">-{{ formatNumero(p.quantidade) }}</td>
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ p.usuario }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- ===== ABA ABC ===== -->
    <div v-if="aba === 'abc'">
      <div v-if="carregando" class="text-center py-12 text-slate-400 dark:text-slate-500">Carregando...</div>
      <template v-else>
        <!-- Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">
            <p class="text-slate-500 dark:text-slate-400 text-xs mb-1">Total Produtos</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumero(dadosAbc.resumo?.total) }}</p>
          </div>
          <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-green-300 dark:border-green-800 p-5">
            <p class="text-green-600 dark:text-green-400 text-xs mb-1">Classe A (80%)</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumero(dadosAbc.resumo?.A) }}</p>
          </div>
          <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-orange-300 dark:border-orange-800 p-5">
            <p class="text-orange-600 dark:text-orange-400 text-xs mb-1">Classe B (15%)</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumero(dadosAbc.resumo?.B) }}</p>
          </div>
          <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-red-300 dark:border-red-800 p-5">
            <p class="text-red-600 dark:text-red-400 text-xs mb-1">Classe C (5%)</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ formatNumero(dadosAbc.resumo?.C) }}</p>
          </div>
        </div>

        <!-- Gráfico de pizza simples em SVG -->
        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 mb-6">
          <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Distribuição por Classe ABC</h2>
          <div class="flex items-center justify-center gap-12">
            <svg viewBox="0 0 200 200" class="w-48 h-48">
              <circle cx="100" cy="100" r="80" :fill="temaClaroSvg.fundo" />
              <template v-if="dadosAbc.resumo?.total > 0">
                <!-- Fatias calculadas -->
                <path v-for="(fatia, i) in fatiasPizza" :key="i" :d="fatia.d" :fill="fatia.cor" />
              </template>
              <circle cx="100" cy="100" r="45" :fill="temaClaroSvg.centro" />
            </svg>
            <div class="space-y-2">
              <div class="flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-sm bg-green-500 inline-block"></span>
                <span class="text-slate-600 dark:text-slate-300">Classe A: {{ formatNumero(dadosAbc.resumo?.A) }} ({{ pct('A') }}%)</span>
              </div>
              <div class="flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-sm bg-orange-400 inline-block"></span>
                <span class="text-slate-600 dark:text-slate-300">Classe B: {{ formatNumero(dadosAbc.resumo?.B) }} ({{ pct('B') }}%)</span>
              </div>
              <div class="flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-sm bg-red-500 inline-block"></span>
                <span class="text-slate-600 dark:text-slate-300">Classe C: {{ formatNumero(dadosAbc.resumo?.C) }} ({{ pct('C') }}%)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabela ABC -->
        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 mb-6">
          <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h2 class="font-semibold text-slate-900 dark:text-white">Classificação ABC de Produtos</h2>
          </div>
          <table class="w-full text-sm">
            <thead>
              <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                <th class="text-left px-4 py-3 font-medium">Classe</th>
                <th class="text-left px-4 py-3 font-medium">Produto</th>
                <th class="text-left px-4 py-3 font-medium">SKU</th>
                <th class="text-left px-4 py-3 font-medium">Centro de Custo</th>
                <th class="text-right px-4 py-3 font-medium">Movimento Total</th>
                <th class="text-right px-4 py-3 font-medium">% do Total</th>
                <th class="text-right px-4 py-3 font-medium">% Acumulado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
              <tr v-if="!dadosAbc.itens?.length">
                <td colspan="7" class="text-center py-8 text-slate-400 dark:text-slate-500">Nenhum dado disponível</td>
              </tr>
              <tr v-for="item in itensAbcPagina" :key="item.id_item" class="hover:bg-slate-100 dark:hover:bg-slate-800/50 transition">
                <td class="px-4 py-3">
                  <span
                    class="w-6 h-6 rounded inline-flex items-center justify-center text-white text-xs font-bold"
                    :class="item.classe === 'A' ? 'bg-green-600' : item.classe === 'B' ? 'bg-orange-500' : 'bg-red-600'"
                  >
                    {{ item.classe }}
                  </span>
                </td>
                <td class="px-4 py-3 text-slate-900 dark:text-white font-medium">{{ item.nome }}</td>
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ item.sku }}</td>
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ item.centro_custo || '—' }}</td>
                <td class="px-4 py-3 text-right text-slate-900 dark:text-white">{{ formatNumero(item.movimento) }}</td>
                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ item.percentual }}%</td>
                <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">{{ item.acumulado }}%</td>
              </tr>
            </tbody>
          </table>

          <!-- Paginação -->
          <div class="px-4 pb-4">
            <Paginacao
              v-model:pagina-atual="paginaAbc"
              v-model:por-pagina="porPaginaAbc"
              :total-paginas="totalPaginasAbc"
              :total="formatNumero(totalItensAbc)"
              rotulo="produtos"
            />
          </div>
        </div>

        <!-- Legenda ABC -->
        <div class="rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 p-5">
          <h3 class="text-slate-900 dark:text-white font-semibold mb-3">Sobre a Análise ABC</h3>
          <p class="text-green-700 dark:text-green-400 text-sm mb-1"><strong>Classe A (80%):</strong> Produtos mais importantes, representam 80% do valor total de movimentação. Merecem atenção especial no controle de estoque.</p>
          <p class="text-orange-700 dark:text-orange-400 text-sm mb-1"><strong>Classe B (15%):</strong> Produtos de importância intermediária, representam 15% do valor total. Controle moderado.</p>
          <p class="text-red-700 dark:text-red-400 text-sm"><strong>Classe C (5%):</strong> Produtos de menor importância, representam apenas 5% do valor total. Controle simplificado.</p>
        </div>
      </template>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { storeToRefs } from 'pinia'
import api from '@/servicos/api'
import { useTemaStore } from '@/servicos/tema.store'
import Paginacao from '@/paginas/PaginacaoControles.vue'
import {
  Calendar, ChevronDown, Download, TrendingDown, PieChart, AlertCircle, Package, FileText,
  Check, Table as TableIcon, FileSpreadsheet, FileType2 as FilePdfIcon, Building2,
} from 'lucide-vue-next'
import ExcelJS from 'exceljs'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

const temaStore     = useTemaStore()
const { temaClaro }  = storeToRefs(temaStore)

const aba = ref('perdas')
const carregando = ref(false)
const dropdownPeriodoAberto = ref(false)
const dropdownExportAberto  = ref(false)
const dropdownCentroAberto  = ref(false)
const menusRoot = ref(null)

const opcoesPeriodo = [
  { label: 'Últimos 7 dias',   dias: 7    },
  { label: 'Últimos 30 dias',  dias: 30   },
  { label: 'Últimos 90 dias',  dias: 90   },
  { label: 'Últimos 6 meses',  dias: 180  },
  { label: 'Último ano',       dias: 365  },
  { label: 'Últimos 2 anos',   dias: 730  },
  { label: 'Todo o período',   dias: 3650 },
]
const periodo = ref(opcoesPeriodo[1])
const periodoLabel = computed(() => periodo.value.label)

// ---- Filtro por centro de custo ----
const TODOS_CENTROS = { id: null, label: 'Todos os Centros' }
const opcoesCentro      = ref([TODOS_CENTROS])
const centroSelecionado = ref(TODOS_CENTROS)
const centroLabel       = computed(() => centroSelecionado.value.label)

function selecionarCentro(c) {
  centroSelecionado.value = c
  dropdownCentroAberto.value = false
}

async function carregarCentros() {
  try {
    const { data } = await api.get('/centros-custo')
    opcoesCentro.value = [
      TODOS_CENTROS,
      ...data.map(c => ({
        id: c.id_centro_custo,
        label: `${c.codigo} - ${c.nome}${c.ativo === false ? ' (inativo)' : ''}`,
      })),
    ]
  } catch (e) {
    console.error('Erro ao carregar centros de custo:', e)
  }
}

const opcoesExport = [
  { formato: 'csv',  label: 'CSV',   descricao: 'Texto separado por vírgula', icone: TableIcon,       cor: 'text-green-500',   acao: () => exportarCSV() },
  { formato: 'xlsx', label: 'Excel', descricao: 'Planilha formatada',          icone: FileSpreadsheet,  cor: 'text-emerald-500', acao: () => exportarExcel() },
  { formato: 'pdf',  label: 'PDF',   descricao: 'Documento para impressão',    icone: FilePdfIcon,      cor: 'text-red-500',     acao: () => exportarPDF() },
]

const dadosPerdas = ref({ perdas: [], porMotivo: [], resumo: {} })
const dadosAbc    = ref({ itens: [], resumo: {} })

// ---- Paginação da aba ABC (feita no front: a API devolve a lista completa) ----
const paginaAbc    = ref(1)
const porPaginaAbc = ref(10)

const totalItensAbc = computed(() => dadosAbc.value.itens?.length ?? 0)

const totalPaginasAbc = computed(() =>
  Math.max(1, Math.ceil(totalItensAbc.value / porPaginaAbc.value))
)

const itensAbcPagina = computed(() => {
  const inicio = (paginaAbc.value - 1) * porPaginaAbc.value
  return (dadosAbc.value.itens ?? []).slice(inicio, inicio + porPaginaAbc.value)
})

// Ao recarregar os dados, volta para a página 1
watch(dadosAbc, () => { paginaAbc.value = 1 })

const totalUnidadesPerdas = computed(() =>
  dadosPerdas.value.porMotivo?.reduce((s, m) => s + m.total, 0) ?? 0
)

// Formata números com separador de milhar no padrão brasileiro (ex: 17050 -> 17.050)
function formatNumero(valor) {
  return Number(valor ?? 0).toLocaleString('pt-BR')
}

// Cores do SVG do gráfico de pizza precisam trocar manualmente (SVG não lê classes dark:)
const temaClaroSvg = computed(() => ({
  fundo:  temaClaro.value ? '#e2e8f0' : '#1e293b',
  centro: temaClaro.value ? '#f8fafc' : '#0f172a',
}))

function selecionarPeriodo(p) {
  periodo.value = p
  dropdownPeriodoAberto.value = false
}

function fecharMenusAoClicarFora(evento) {
  if (menusRoot.value && !menusRoot.value.contains(evento.target)) {
    dropdownPeriodoAberto.value = false
    dropdownExportAberto.value  = false
    dropdownCentroAberto.value  = false
  }
}

async function carregarDados() {
  carregando.value = true
  try {
    const idCentro = centroSelecionado.value.id
    if (aba.value === 'perdas') {
      const params = { dias: periodo.value.dias }
      if (idCentro) params.id_centro_custo = idCentro
      const { data } = await api.get('/relatorios-avancados/perdas', { params })
      dadosPerdas.value = data
    } else {
      const params = {}
      if (idCentro) params.id_centro_custo = idCentro
      const { data } = await api.get('/relatorios-avancados/abc', { params })
      dadosAbc.value = data
    }
  } catch (e) {
    console.error(e)
  } finally {
    carregando.value = false
  }
}

watch([aba, periodo, centroSelecionado], carregarDados)

onMounted(() => {
  carregarCentros()
  carregarDados()
  document.addEventListener('click', fecharMenusAoClicarFora)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', fecharMenusAoClicarFora)
})

// Gráfico de pizza SVG
const fatiasPizza = computed(() => {
  const total = dadosAbc.value.resumo?.total ?? 0
  if (!total) return []
  const a = dadosAbc.value.resumo.A / total
  const b = dadosAbc.value.resumo.B / total
  const c = dadosAbc.value.resumo.C / total
  return calcularFatias([
    { pct: a, cor: '#22c55e' },
    { pct: b, cor: '#f97316' },
    { pct: c, cor: '#ef4444' },
  ])
})

function calcularFatias(dados) {
  const cx = 100, cy = 100, r = 80
  let angulo = -Math.PI / 2

  // Ignora fatias com 0% (evita paths degenerados)
  const visiveis = dados.filter(d => d.pct > 0)

  return visiveis.map(({ pct, cor }) => {
    // Caso especial: fatia ocupa 100% do círculo.
    // Um arco SVG não fecha uma volta completa num único comando A
    // (início == fim vira arco de raio zero e não desenha nada),
    // então nesse caso desenhamos o círculo em duas metades.
    if (pct >= 1) {
      return {
        d: `M${cx - r},${cy} A${r},${r} 0 1,1 ${cx + r},${cy} A${r},${r} 0 1,1 ${cx - r},${cy} Z`,
        cor
      }
    }

    const inicio = angulo
    angulo += pct * 2 * Math.PI
    const fim = angulo
    const x1 = cx + r * Math.cos(inicio)
    const y1 = cy + r * Math.sin(inicio)
    const x2 = cx + r * Math.cos(fim)
    const y2 = cy + r * Math.sin(fim)
    const large = pct > 0.5 ? 1 : 0
    return { d: `M${cx},${cy} L${x1},${y1} A${r},${r} 0 ${large},1 ${x2},${y2} Z`, cor }
  })
}

function pct(classe) {
  const total = dadosAbc.value.resumo?.total ?? 0
  if (!total) return 0
  return Math.round((dadosAbc.value.resumo[classe] / total) * 100)
}

function formatarDataHora(dataISO) {
  if (!dataISO) return '—'
  const d = new Date(dataISO)
  return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}, ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`
}

// =========================================================
// ======================  EXPORTAÇÃO  ========================
// (exporta TODOS os itens, não só a página atual)
// =========================================================

function cabecalhosExport() {
  return aba.value === 'perdas'
    ? ['Data', 'Produto', 'Centro de Custo', 'Motivo', 'Quantidade', 'Responsável']
    : ['Classe', 'Produto', 'SKU', 'Centro de Custo', 'Movimento Total', '% do Total', '% Acumulado']
}

function linhasExport() {
  if (aba.value === 'perdas') {
    return (dadosPerdas.value.perdas ?? []).map(p => [
      formatarDataHora(p.data), p.produto, p.centro_custo || '—', p.motivo, formatNumero(p.quantidade), p.usuario,
    ])
  }
  return (dadosAbc.value.itens ?? []).map(i => [
    i.classe, i.nome, i.sku, i.centro_custo || '—', formatNumero(i.movimento), i.percentual + '%', i.acumulado + '%',
  ])
}

function tituloExport() {
  const base = aba.value === 'perdas'
    ? `Relatório de Perdas — ${periodoLabel.value}`
    : 'Análise ABC de Produtos'
  return centroSelecionado.value.id
    ? `${base} — Centro de Custo: ${centroSelecionado.value.label}`
    : base
}

function nomeArquivoExport(ext) {
  return aba.value === 'perdas'
    ? `perdas-${periodo.value.dias}dias.${ext}`
    : `analise-abc.${ext}`
}

function baixar(blob, nome) {
  const url  = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url; link.download = nome
  document.body.appendChild(link); link.click()
  document.body.removeChild(link); URL.revokeObjectURL(url)
}

// ---- CSV ----

function exportarCSV() {
  const csv = [cabecalhosExport(), ...linhasExport()]
    .map(l => l.map(c => `"${String(c).replace(/"/g, '""')}"`).join(',')).join('\n')
  baixar(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }), nomeArquivoExport('csv'))
}

// ---- Excel (ExcelJS, com estilo) ----

async function exportarExcel() {
  const wb = new ExcelJS.Workbook()
  const ehPerdas = aba.value === 'perdas'
  const ws = wb.addWorksheet(ehPerdas ? 'Perdas' : 'ABC')

  ws.columns = ehPerdas
    ? [
        { header: 'Data', key: 'data', width: 18 },
        { header: 'Produto', key: 'produto', width: 30 },
        { header: 'Centro de Custo', key: 'centro_custo', width: 24 },
        { header: 'Motivo', key: 'motivo', width: 22 },
        { header: 'Quantidade', key: 'quantidade', width: 14 },
        { header: 'Responsável', key: 'responsavel', width: 20 },
      ]
    : [
        { header: 'Classe', key: 'classe', width: 10 },
        { header: 'Produto', key: 'produto', width: 30 },
        { header: 'SKU', key: 'sku', width: 14 },
        { header: 'Centro de Custo', key: 'centro_custo', width: 24 },
        { header: 'Movimento Total', key: 'movimento', width: 16 },
        { header: '% do Total', key: 'percentual', width: 12 },
        { header: '% Acumulado', key: 'acumulado', width: 14 },
      ]

  ws.getRow(1).eachCell((celula) => {
    celula.font = { bold: true, color: { argb: 'FFFFFFFF' } }
    celula.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF3A6EA5' } }
    celula.alignment = { vertical: 'middle', horizontal: 'left' }
  })

  if (ehPerdas) {
    ;(dadosPerdas.value.perdas ?? []).forEach((p) => {
      const linha = ws.addRow({
        data: formatarDataHora(p.data),
        produto: p.produto,
        centro_custo: p.centro_custo || '—',
        motivo: p.motivo,
        quantidade: Number(p.quantidade ?? 0),
        responsavel: p.usuario,
      })
      linha.getCell('quantidade').numFmt = '#,##0'
      linha.getCell('quantidade').alignment = { horizontal: 'right' }
      linha.getCell('quantidade').font = { color: { argb: 'FFC62828' }, bold: true }
    })
  } else {
    ;(dadosAbc.value.itens ?? []).forEach((i) => {
      const linha = ws.addRow({
        classe: i.classe,
        produto: i.nome,
        sku: i.sku,
        centro_custo: i.centro_custo || '—',
        movimento: Number(i.movimento ?? 0),
        percentual: i.percentual / 100,
        acumulado: i.acumulado / 100,
      })
      linha.getCell('movimento').numFmt = '#,##0'
      linha.getCell('movimento').alignment = { horizontal: 'right' }
      linha.getCell('percentual').numFmt = '0.0%'
      linha.getCell('acumulado').numFmt = '0.0%'

      const cores = { A: 'FF2E7D32', B: 'FFEF6C00', C: 'FFC62828' }
      const celulaClasse = linha.getCell('classe')
      celulaClasse.font = { bold: true, color: { argb: 'FFFFFFFF' } }
      celulaClasse.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: cores[i.classe] ?? 'FF64748B' } }
      celulaClasse.alignment = { horizontal: 'center' }
    })
  }

  const ultimaColuna = ehPerdas ? 'F1' : 'G1'
  ws.autoFilter = { from: 'A1', to: ultimaColuna }
  ws.views = [{ state: 'frozen', ySplit: 1 }]

  const buffer = await wb.xlsx.writeBuffer()
  baixar(
    new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }),
    nomeArquivoExport('xlsx')
  )
}

// ---- PDF (jsPDF + autoTable, com estilo) ----

function exportarPDF() {
  const ehPerdas = aba.value === 'perdas'
  const doc = new jsPDF({ orientation: 'landscape' })

  doc.setFontSize(16)
  doc.text('SIGE — Relatórios Avançados', 14, 15)
  doc.setFontSize(11)
  doc.setTextColor(80)
  doc.text(tituloExport(), 14, 22)
  doc.setFontSize(9)
  doc.setTextColor(140)
  doc.text(`Gerado em ${new Date().toLocaleDateString('pt-BR')}`, 14, 27)

  autoTable(doc, {
    head: [cabecalhosExport()],
    body: linhasExport(),
    startY: 32,
    theme: 'striped',
    headStyles: { fillColor: [58, 110, 165], textColor: [255, 255, 255], fontStyle: 'bold' },
    styles: { fontSize: 9, cellPadding: 3 },
    alternateRowStyles: { fillColor: [245, 245, 245] },
    columnStyles: ehPerdas
      ? { 4: { halign: 'right' } }                                  // Quantidade
      : { 4: { halign: 'right' }, 5: { halign: 'right' }, 6: { halign: 'right' } }, // Movimento/%/%
    didParseCell: (data) => {
      if (data.section !== 'body') return

      if (ehPerdas && data.column.index === 4) {
        data.cell.styles.textColor = [198, 40, 40]
        data.cell.styles.fontStyle = 'bold'
      }

      if (!ehPerdas && data.column.index === 0) {
        const classe = String(data.cell.raw)
        const cores = { A: [46, 125, 50], B: [239, 108, 0], C: [198, 40, 40] }
        data.cell.styles.fillColor = cores[classe] ?? [100, 116, 139]
        data.cell.styles.textColor = [255, 255, 255]
        data.cell.styles.fontStyle = 'bold'
        data.cell.styles.halign = 'center'
      }
    },
  })

  doc.save(nomeArquivoExport('pdf'))
}
</script>