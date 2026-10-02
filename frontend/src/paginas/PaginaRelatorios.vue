<template>
  <div class="p-6 min-h-screen bg-white dark:bg-black text-slate-900 dark:text-white">

    <!-- Cabeçalho -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Relatórios</h1>
      <p class="text-sm text-slate-500 dark:text-slate-400">Filtros, visualização e exportação de dados</p>
    </div>

    <!-- Filtros -->
    <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 mb-6">
      <div class="flex items-center gap-2 mb-4">
        <Filter :size="16" class="text-blue-600 dark:text-blue-400" />
        <span class="font-semibold text-slate-900 dark:text-white">Filtros</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <!-- Lote -->
        <div>
          <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Lote</label>
          <div class="relative">
            <button
              class="w-full flex items-center justify-between bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white"
              @click="dropdownLoteAberto = !dropdownLoteAberto"
            >
              {{ filtros.lote }} <ChevronDown :size="16" class="text-slate-400" />
            </button>
            <div v-if="dropdownLoteAberto" class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden max-h-56 overflow-y-auto">
              <div
                v-for="opcao in opcoesLote"
                :key="opcao"
                class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer transition"
                :class="filtros.lote === opcao ? 'bg-blue-600 text-white' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                @click="filtros.lote = opcao; dropdownLoteAberto = false"
              >
                {{ opcao }} <Check v-if="filtros.lote === opcao" :size="16" />
              </div>
            </div>
          </div>
        </div>

        <!-- Vencimento -->
        <div>
          <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Vencimento em até</label>
          <div class="relative">
            <button
              class="w-full flex items-center justify-between bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white"
              @click="dropdownVencAberto = !dropdownVencAberto"
            >
              {{ filtros.vencimento }} <ChevronDown :size="16" class="text-slate-400" />
            </button>
            <div v-if="dropdownVencAberto" class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden">
              <div
                v-for="opcao in opcoesVencimento"
                :key="opcao"
                class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer transition"
                :class="filtros.vencimento === opcao ? 'bg-blue-600 text-white' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                @click="filtros.vencimento = opcao; dropdownVencAberto = false"
              >
                {{ opcao }} <Check v-if="filtros.vencimento === opcao" :size="16" />
              </div>
            </div>
          </div>
        </div>

        <!-- Busca -->
        <div>
          <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Buscar</label>
          <div class="relative">
            <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              v-model="filtros.busca"
              type="text"
              placeholder="SKU, produto, fornecedor..."
              class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500 transition"
            />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Data Inicial</label>
          <input
            v-model="filtros.dataInicial"
            type="date"
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500 transition [color-scheme:light] dark:[color-scheme:dark]"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Data Final</label>
          <input
            v-model="filtros.dataFinal"
            type="date"
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500 transition [color-scheme:light] dark:[color-scheme:dark]"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Centro de Custo</label>
          <select
            v-model="filtros.centro"
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500 transition"
          >
            <option value="">Todos</option>
            <option value="sem">Sem centro de custo</option>
            <option v-for="c in centros" :key="c.id_centro_custo" :value="c.id_centro_custo">
              {{ c.codigo }} - {{ c.nome }}
            </option>
          </select>
        </div>
      </div>

      <div class="flex items-center justify-between">
        <button
          class="bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-medium px-4 py-2 rounded-lg transition"
          @click="limparFiltros"
        >
          Limpar Filtros
        </button>
        <div class="flex gap-2">
          <button class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition" @click="exportarCSV">
            <Download :size="16" /> Exportar CSV
          </button>
          <button class="flex items-center gap-2 bg-green-700 hover:bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition" @click="exportarExcel">
            <FileSpreadsheet :size="16" /> Exportar Excel{{ selecionados.size ? ` (${selecionados.size})` : '' }}
          </button>
          <button class="flex items-center gap-2 bg-red-700 hover:bg-red-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition" @click="exportarPDF">
            <FileText :size="16" /> Exportar PDF
          </button>
        </div>
      </div>
    </div>

    <!-- Cards de resumo -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">
        <p class="text-slate-500 dark:text-slate-400 text-sm mb-2">Itens Filtrados</p>
        <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ formatNumero(itensFiltrados.length) }}</p>
      </div>
      <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">
        <p class="text-slate-500 dark:text-slate-400 text-sm mb-2">Itens Vencendo</p>
        <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ formatNumero(totalVencendo) }}</p>
      </div>
      <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">
        <p class="text-slate-500 dark:text-slate-400 text-sm mb-2">Itens Vencidos</p>
        <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ formatNumero(totalVencidos) }}</p>
      </div>
    </div>

    <!-- Tabela -->
    <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
      <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
        <h2 class="font-semibold text-slate-900 dark:text-white">
          Resultados ({{ formatNumero(itensFiltrados.length) }} itens)
          <span v-if="selecionados.size" class="text-blue-600 dark:text-blue-400 font-normal text-sm">
            — {{ formatNumero(selecionados.size) }} selecionado(s)
          </span>
        </h2>
        <button
          v-if="selecionados.size"
          class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white underline"
          @click="limparSelecao"
        >
          Limpar seleção
        </button>
      </div>

      <div v-if="carregando" class="text-center py-12 text-slate-400 dark:text-slate-500">Carregando...</div>

      <div v-else-if="itensFiltrados.length === 0" class="text-center py-12 text-slate-400 dark:text-slate-500">
        Nenhum item encontrado.
      </div>

      <table v-else class="w-full text-sm">
        <thead>
          <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
            <th class="text-left px-4 py-3 font-medium w-10">
              <input
                type="checkbox"
                :checked="todosSelecionados"
                class="w-4 h-4 rounded accent-blue-600 cursor-pointer"
                @change="alternarTodos"
              />
            </th>
            <th class="text-left px-4 py-3 font-medium">Lote</th>
            <th class="text-left px-4 py-3 font-medium">SKU</th>
            <th class="text-left px-4 py-3 font-medium">Produto</th>
            <th class="text-left px-4 py-3 font-medium">Qtd</th>
            <th class="text-left px-4 py-3 font-medium">Validade</th>
            <th class="text-left px-4 py-3 font-medium">Fornecedor</th>
            <th class="text-left px-4 py-3 font-medium">Localização</th>
            <th class="text-left px-4 py-3 font-medium">Centro de Custo</th>
            <th class="text-left px-4 py-3 font-medium">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
          <tr
            v-for="item in itensFiltrados"
            :key="item.id_item"
            class="hover:bg-slate-100 dark:hover:bg-slate-800/50 transition"
            :class="selecionados.has(item.id_item) ? 'bg-blue-50 dark:bg-blue-950/40' : ''"
          >
            <td class="px-4 py-3">
              <input
                type="checkbox"
                :checked="selecionados.has(item.id_item)"
                class="w-4 h-4 rounded accent-blue-600 cursor-pointer"
                @change="alternarSelecao(item.id_item)"
              />
            </td>
            <td class="px-4 py-3">
              <span class="bg-blue-600 text-white text-xs font-bold px-2 py-0.5 rounded">
                {{ item.lote?.numero_lote ?? '—' }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ item.sku }}</td>
            <td class="px-4 py-3 text-slate-900 dark:text-white font-medium">{{ item.nome }}</td>
            <td class="px-4 py-3 text-slate-900 dark:text-white">{{ formatNumero(item.quantidade) }}</td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ item.data_validade ? formatarData(item.data_validade) : '—' }}</td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ item.fornecedor || '—' }}</td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ item.localizacao || '—' }}</td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ centroTexto(item) }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded text-xs font-bold" :class="statusClasse(item)">
                {{ statusTexto(item) }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/servicos/api'
import { formatarData } from '@/utils/date'
import { useCentrosCusto } from '@/composables/useCentrosCusto'
import { Filter, Search, ChevronDown, Check, Download, FileSpreadsheet, FileText } from 'lucide-vue-next'
import ExcelJS from 'exceljs'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

const { centros, carregar: carregarCentros } = useCentrosCusto()

const carregando = ref(false)
const itens      = ref([])
const dropdownLoteAberto = ref(false)
const dropdownVencAberto  = ref(false)

// Seleção de itens (id_item)
const selecionados = ref(new Set())

const opcoesVencimento = ['Todos', 'Vencidos', 'Vencendo em 7 dias', 'Vencendo em 30 dias', 'Vencendo em 90 dias']
const opcoesLote = ref(['Todos os lotes'])

const filtros = ref({
  busca: '',
  lote: 'Todos os lotes',
  vencimento: 'Todos',
  dataInicial: '',
  dataFinal: '',
  centro: '', // '' = todos | 'sem' = sem centro | id
})

// Formata números com separador de milhar no padrão brasileiro (ex: 17050 -> 17.050)
function formatNumero(valor) {
  return Number(valor ?? 0).toLocaleString('pt-BR')
}

// ===== Centro de custo =====
// Aceita id direto, objeto aninhado ou código, conforme o backend devolver
function idCentro(item) {
  return item.id_centro_custo ?? item.centro_custo?.id_centro_custo ?? null
}

function centroTexto(item) {
  const c = item.centro_custo
  if (c) {
    if (typeof c === 'string') return c
    if (c.codigo) return c.codigo
  }
  if (item.centro_custo_codigo) return item.centro_custo_codigo

  const id = idCentro(item)
  if (id !== null && id !== undefined && id !== '') {
    const achado = centros.value.find((x) => Number(x.id_centro_custo) === Number(id))
    if (achado) return achado.codigo
  }
  return '—'
}

function centroConfere(item) {
  const filtro = filtros.value.centro
  if (!filtro) return true
  if (filtro === 'sem') return centroTexto(item) === '—'

  const id = idCentro(item)
  if (id !== null && id !== undefined && id !== '') {
    return Number(id) === Number(filtro)
  }
  // fallback: backend só devolveu o código
  const selecionado = centros.value.find((x) => Number(x.id_centro_custo) === Number(filtro))
  return !!selecionado && centroTexto(item) === selecionado.codigo
}

onMounted(async () => {
  carregando.value = true
  try {
    await Promise.all([
      carregarCentros(),
      api.get('/relatorios/itens').then(({ data }) => {
        itens.value = data
        const lotes = [...new Set(data.map(i => i.lote?.numero_lote).filter(Boolean))]
        opcoesLote.value = ['Todos os lotes', ...lotes]
      }),
    ])
  } catch (e) {
    console.error(e)
  } finally {
    carregando.value = false
  }
})

const itensFiltrados = computed(() => {
  const hoje = new Date()
  return itens.value.filter(item => {
    const buscaOk = !filtros.value.busca ||
      item.nome?.toLowerCase().includes(filtros.value.busca.toLowerCase()) ||
      item.sku?.toLowerCase().includes(filtros.value.busca.toLowerCase()) ||
      item.fornecedor?.toLowerCase().includes(filtros.value.busca.toLowerCase())

    const loteOk = filtros.value.lote === 'Todos os lotes' ||
      item.lote?.numero_lote === filtros.value.lote

    const validade = item.data_validade ? new Date(item.data_validade) : null
    let vencOk = true
    if (filtros.value.vencimento === 'Vencidos') {
      vencOk = validade && validade < hoje
    } else if (filtros.value.vencimento === 'Vencendo em 7 dias') {
      const limite = new Date(); limite.setDate(hoje.getDate() + 7)
      vencOk = validade && validade >= hoje && validade <= limite
    } else if (filtros.value.vencimento === 'Vencendo em 30 dias') {
      const limite = new Date(); limite.setDate(hoje.getDate() + 30)
      vencOk = validade && validade >= hoje && validade <= limite
    } else if (filtros.value.vencimento === 'Vencendo em 90 dias') {
      const limite = new Date(); limite.setDate(hoje.getDate() + 90)
      vencOk = validade && validade >= hoje && validade <= limite
    }

    const dataItem = item.lote?.data_entrada
    const dataInicialOk = !filtros.value.dataInicial || (dataItem && dataItem >= filtros.value.dataInicial)
    const dataFinalOk   = !filtros.value.dataFinal   || (dataItem && dataItem <= filtros.value.dataFinal)

    const centroOk = centroConfere(item)

    return buscaOk && loteOk && vencOk && dataInicialOk && dataFinalOk && centroOk
  })
})

// Se os filtros mudarem, remove da seleção itens que saíram da lista filtrada
watch(itensFiltrados, (novosItens) => {
  if (!selecionados.value.size) return
  const idsVisiveis = new Set(novosItens.map(i => i.id_item))
  const novaSelecao = new Set([...selecionados.value].filter(id => idsVisiveis.has(id)))
  if (novaSelecao.size !== selecionados.value.size) selecionados.value = novaSelecao
})

const todosSelecionados = computed(() =>
  itensFiltrados.value.length > 0 &&
  itensFiltrados.value.every(i => selecionados.value.has(i.id_item))
)

function alternarSelecao(id) {
  const novo = new Set(selecionados.value)
  novo.has(id) ? novo.delete(id) : novo.add(id)
  selecionados.value = novo
}

function alternarTodos() {
  if (todosSelecionados.value) {
    selecionados.value = new Set()
  } else {
    selecionados.value = new Set(itensFiltrados.value.map(i => i.id_item))
  }
}

function limparSelecao() {
  selecionados.value = new Set()
}

const totalVencidos  = computed(() => {
  const hoje = new Date()
  return itensFiltrados.value.filter(i => i.data_validade && new Date(i.data_validade) < hoje).length
})

const totalVencendo = computed(() => {
  const hoje  = new Date()
  const limit = new Date(); limit.setDate(hoje.getDate() + 30)
  return itensFiltrados.value.filter(i => {
    if (!i.data_validade) return false
    const v = new Date(i.data_validade)
    return v >= hoje && v <= limit
  }).length
})

function diasParaVencer(dataValidade) {
  if (!dataValidade) return null
  const hoje = new Date()
  const val  = new Date(dataValidade)
  return Math.ceil((val - hoje) / (1000 * 60 * 60 * 24))
}

function statusTexto(item) {
  if (!item.data_validade) return 'OK'
  const dias = diasParaVencer(item.data_validade)
  if (dias < 0)   return 'Vencido'
  if (dias <= 30) return `Vencendo (${dias}d)`
  return 'OK'
}

function statusClasse(item) {
  if (!item.data_validade) return 'bg-green-700 text-white'
  const dias = diasParaVencer(item.data_validade)
  if (dias < 0)   return 'bg-red-600 text-white'
  if (dias <= 30) return 'bg-orange-500 text-white'
  return 'bg-green-700 text-white'
}

function limparFiltros() {
  filtros.value = { busca: '', lote: 'Todos os lotes', vencimento: 'Todos', dataInicial: '', dataFinal: '', centro: '' }
}

// Exportação
const CABECALHOS = ['Lote', 'SKU', 'Produto', 'Quantidade', 'Validade', 'Fornecedor', 'Localização', 'Centro de Custo', 'Status']

// Retorna apenas os selecionados (se houver seleção) ou todos os filtrados
function itensParaExportar() {
  if (selecionados.value.size > 0) {
    return itensFiltrados.value.filter(i => selecionados.value.has(i.id_item))
  }
  return itensFiltrados.value
}

function linhas() {
  return itensParaExportar().map(i => [
    i.lote?.numero_lote ?? '—',
    i.sku ?? '—',
    i.nome,
    formatNumero(i.quantidade),
    i.data_validade ? formatarData(i.data_validade) : '—',
    i.fornecedor ?? '—',
    i.localizacao ?? '—',
    centroTexto(i),
    statusTexto(i),
  ])
}

function nomeArquivo(ext) {
  const h = new Date()
  const sufixo = selecionados.value.size ? '-selecionados' : ''
  return `relatorio${sufixo}-${h.getFullYear()}-${String(h.getMonth()+1).padStart(2,'0')}-${String(h.getDate()).padStart(2,'0')}.${ext}`
}

function baixar(blob, nome) {
  const url  = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url; link.download = nome
  document.body.appendChild(link); link.click()
  document.body.removeChild(link); URL.revokeObjectURL(url)
}

function exportarCSV() {
  const csv = [CABECALHOS, ...linhas()]
    .map(l => l.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n')
  baixar(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }), nomeArquivo('csv'))
}

async function exportarExcel() {
  const wb = new ExcelJS.Workbook()
  const ws = wb.addWorksheet('Relatório')

  ws.columns = [
    { header: 'Lote', key: 'lote', width: 12 },
    { header: 'SKU', key: 'sku', width: 14 },
    { header: 'Produto', key: 'produto', width: 32 },
    { header: 'Quantidade', key: 'quantidade', width: 14 },
    { header: 'Validade', key: 'validade', width: 14 },
    { header: 'Fornecedor', key: 'fornecedor', width: 20 },
    { header: 'Localização', key: 'localizacao', width: 18 },
    { header: 'Centro de Custo', key: 'centro', width: 18 },
    { header: 'Status', key: 'status', width: 16 },
  ]

  ws.getRow(1).eachCell((celula) => {
    celula.font = { bold: true, color: { argb: 'FFFFFFFF' } }
    celula.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF3A6EA5' } }
    celula.alignment = { vertical: 'middle', horizontal: 'left' }
  })

  itensParaExportar().forEach((item) => {
    const dias = diasParaVencer(item.data_validade)

    const linha = ws.addRow({
      lote: item.lote?.numero_lote ?? '—',
      sku: item.sku ?? '—',
      produto: item.nome,
      quantidade: Number(item.quantidade ?? 0),
      validade: item.data_validade ? formatarData(item.data_validade) : '—',
      fornecedor: item.fornecedor ?? '—',
      localizacao: item.localizacao ?? '—',
      centro: centroTexto(item),
      status: statusTexto(item),
    })

    linha.getCell('quantidade').numFmt = '#,##0'
    linha.getCell('quantidade').alignment = { horizontal: 'right' }

    let cor = 'FF2E7D32' // verde (OK)
    if (item.data_validade) {
      if (dias < 0) cor = 'FFC62828'       // vermelho (vencido)
      else if (dias <= 30) cor = 'FFEF6C00' // laranja (vencendo)
    }

    const celulaStatus = linha.getCell('status')
    celulaStatus.font = { bold: true, color: { argb: 'FFFFFFFF' } }
    celulaStatus.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: cor } }
    celulaStatus.alignment = { horizontal: 'center' }
  })

  ws.autoFilter = { from: 'A1', to: 'I1' }
  ws.views = [{ state: 'frozen', ySplit: 1 }]

  const buffer = await wb.xlsx.writeBuffer()
  baixar(
    new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }),
    nomeArquivo('xlsx')
  )
}

function exportarPDF() {
  const doc = new jsPDF({ orientation: 'landscape' })
  doc.setFontSize(16)
  doc.text('Relatório de Estoque - SIGE', 14, 15)
  doc.setFontSize(10)
  doc.setTextColor(120)
  doc.text(`Gerado em ${new Date().toLocaleDateString('pt-BR')} — ${itensParaExportar().length} item(ns)`, 14, 21)

  autoTable(doc, {
    head: [CABECALHOS],
    body: linhas(),
    startY: 26,
    theme: 'striped',
    headStyles: { fillColor: [58, 110, 165], textColor: [255, 255, 255], fontStyle: 'bold' },
    styles: { fontSize: 9, cellPadding: 3 },
    alternateRowStyles: { fillColor: [245, 245, 245] },
    columnStyles: {
      3: { halign: 'right' }, // Quantidade
    },
    didParseCell: (data) => {
      if (data.section === 'body' && data.column.index === 8) { // Status
        const texto = String(data.cell.raw)
        if (texto.startsWith('Vencido')) {
          data.cell.styles.fillColor = [198, 40, 40]
        } else if (texto.startsWith('Vencendo')) {
          data.cell.styles.fillColor = [239, 108, 0]
        } else {
          data.cell.styles.fillColor = [46, 125, 50]
        }
        data.cell.styles.textColor = [255, 255, 255]
        data.cell.styles.fontStyle = 'bold'
        data.cell.styles.halign = 'center'
      }
    },
  })

  doc.save(nomeArquivo('pdf'))
}
</script>