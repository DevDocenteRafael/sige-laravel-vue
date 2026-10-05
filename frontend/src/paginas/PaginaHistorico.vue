<template>
  <div class="p-6 min-h-screen bg-white dark:bg-black text-slate-900 dark:text-white">
    <!-- Cabeçalho -->
    <div class="flex items-start justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Histórico de Movimentações</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Visualize todas as entradas e saídas de estoque</p>
      </div>
      <div class="flex gap-2">
        <button class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition" @click="exportarCSV">
          <Download :size="16" /> Exportar CSV
        </button>
        <button class="flex items-center gap-2 bg-green-700 hover:bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition" @click="exportarExcel">
          <FileSpreadsheet :size="16" /> Exportar Excel
        </button>
        <button class="flex items-center gap-2 bg-red-700 hover:bg-red-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition" @click="exportarPDF">
          <FileText :size="16" /> Exportar PDF
        </button>
      </div>
    </div>

    <!-- Filtros -->
    <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 mb-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Buscar Produto</label>
          <div class="relative">
            <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              v-model="filtros.busca"
              type="text"
              placeholder="Nome ou SKU do produto..."
              class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500 transition"
            />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Tipo</label>
          <div class="relative">
            <button
              class="w-full flex items-center justify-between bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500 transition"
              @click="dropdownTipoAberto = !dropdownTipoAberto; dropdownLoteAberto = false; dropdownCentroAberto = false"
            >
              {{ filtros.tipo }} <ChevronDown :size="16" class="text-slate-400" />
            </button>
            <div v-if="dropdownTipoAberto" class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden">
              <div
                v-for="opcao in opcoesTipo"
                :key="opcao"
                class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer transition"
                :class="filtros.tipo === opcao ? 'bg-blue-600 text-white' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                @click="selecionarTipo(opcao)"
              >
                {{ opcao }} <Check v-if="filtros.tipo === opcao" :size="16" />
              </div>
            </div>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Lote</label>
          <div class="relative">
            <button
              class="w-full flex items-center justify-between bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500 transition"
              @click="dropdownLoteAberto = !dropdownLoteAberto; dropdownTipoAberto = false; dropdownCentroAberto = false"
            >
              {{ filtros.lote }} <ChevronDown :size="16" class="text-slate-400" />
            </button>
            <div v-if="dropdownLoteAberto" class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden max-h-56 overflow-y-auto">
              <div
                v-for="opcao in opcoesLote"
                :key="opcao"
                class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer transition"
                :class="filtros.lote === opcao ? 'bg-blue-600 text-white' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                @click="selecionarLote(opcao)"
              >
                {{ opcao }} <Check v-if="filtros.lote === opcao" :size="16" />
              </div>
            </div>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Centro de Custo</label>
          <div class="relative">
            <button
              class="w-full flex items-center justify-between bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500 transition"
              @click="dropdownCentroAberto = !dropdownCentroAberto; dropdownTipoAberto = false; dropdownLoteAberto = false"
            >
              <span class="truncate">{{ filtros.centroCusto }}</span> <ChevronDown :size="16" class="text-slate-400 shrink-0" />
            </button>
            <div v-if="dropdownCentroAberto" class="absolute z-10 mt-1 w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg overflow-hidden max-h-56 overflow-y-auto">
              <div
                v-for="opcao in opcoesCentro"
                :key="opcao"
                class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer transition"
                :class="filtros.centroCusto === opcao ? 'bg-blue-600 text-white' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700'"
                @click="selecionarCentro(opcao)"
              >
                {{ opcao }} <Check v-if="filtros.centroCusto === opcao" :size="16" />
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
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
        <div class="md:col-span-2 flex items-end">
          <button
            class="w-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-medium py-2 rounded-lg transition"
            @click="limparFiltros"
          >
            Limpar Filtros
          </button>
        </div>
      </div>
      <p class="text-slate-500 dark:text-slate-400 text-sm mt-4">{{ formatNumero(movimentacoesFiltradas.length) }} movimentação(ões) encontrada(s)</p>
    </div>

    <!-- Tabela -->
    <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 min-h-[200px]">
      <div v-if="movimentacoesFiltradas.length === 0" class="text-center py-16">
        <PackageMinus class="mx-auto mb-3 text-slate-400 dark:text-slate-600" :size="40" />
        <p class="text-slate-400 dark:text-slate-500">Nenhuma movimentação encontrada</p>
      </div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
            <th class="text-left px-4 py-3 font-medium">Data</th>
            <th class="text-left px-4 py-3 font-medium">Produto</th>
            <th class="text-left px-4 py-3 font-medium">SKU</th>
            <th class="text-left px-4 py-3 font-medium">Lote</th>
            <th class="text-left px-4 py-3 font-medium">Centro de Custo</th>
            <th class="text-left px-4 py-3 font-medium">Tipo</th>
            <th class="text-right px-4 py-3 font-medium">Qtd</th>
            <th class="text-left px-4 py-3 font-medium">Forn./Motivo</th>
            <th class="text-left px-4 py-3 font-medium">Usuário</th>
            <th v-if="ehRoot" class="text-center px-4 py-3 font-medium">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
          <tr v-for="mov in movimentacoesFiltradas" :key="mov.id" class="hover:bg-slate-100 dark:hover:bg-slate-800/50 transition">
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ formatarDataHora(mov.data) }}</td>
            <td class="px-4 py-3 text-slate-900 dark:text-white font-medium">{{ mov.produto }}</td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ mov.sku }}</td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ mov.lote }}</td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ mov.centro_custo || '—' }}</td>
            <td class="px-4 py-3">
              <span
                class="px-2 py-0.5 rounded text-xs font-bold"
                :class="mov.tipo === 'Entrada' ? 'bg-green-700 text-white' : 'bg-red-600 text-white'"
              >
                {{ mov.tipo }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-semibold" :class="mov.tipo === 'Entrada' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
              {{ mov.tipo === 'Entrada' ? '+' : '-' }}{{ formatNumero(mov.quantidade) }}
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ mov.motivo }}</td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ mov.usuario }}</td>
            <td v-if="ehRoot" class="px-4 py-3 text-center">
              <button
                class="text-slate-400 dark:text-slate-500 hover:text-red-600 dark:hover:text-red-400 transition"
                title="Excluir movimentação"
                @click="confirmarExclusao(mov)"
              >
                <Trash2 :size="15" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div v-if="movParaExcluir" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-sm p-6">
        <div class="flex items-center gap-3 mb-4">
          <Trash2 class="text-red-600 dark:text-red-400" :size="20" />
          <h2 class="text-slate-900 dark:text-white font-bold">Excluir Movimentação</h2>
        </div>
        <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">
          Tem certeza que deseja excluir a movimentação de
          <strong class="text-slate-900 dark:text-white">{{ movParaExcluir.produto }}</strong>
          em <strong class="text-slate-900 dark:text-white">{{ formatarDataHora(movParaExcluir.data) }}</strong>?
          Esta ação não pode ser desfeita.
        </p>
        <div class="flex gap-3">
          <button
            class="flex-1 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm"
            @click="movParaExcluir = null"
          >
            Cancelar
          </button>
          <button
            class="flex-1 py-2 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-40 text-white font-medium transition text-sm"
            :disabled="excluindo"
            @click="excluir"
          >
            {{ excluindo ? 'Excluindo...' : 'Excluir' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/servicos/api'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import { Download, FileSpreadsheet, FileText, Search, ChevronDown, Check, PackageMinus, Trash2 } from 'lucide-vue-next'
import ExcelJS from 'exceljs'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

const auth  = useAutenticacaoStore()
const ehRoot = computed(() => auth.ehRoot)

// ----- ESTADO -----
const SEM_CENTRO = 'Sem centro de custo'
const TODOS_CENTROS = 'Todos os Centros'

const dropdownTipoAberto = ref(false)
const dropdownLoteAberto = ref(false)
const dropdownCentroAberto = ref(false)
const opcoesTipo = ['Todos', 'Entrada', 'Saída']
const opcoesLote = ref(['Todos os Lotes'])
const opcoesCentro = ref([TODOS_CENTROS])
const movimentacoes = ref([])
const movParaExcluir = ref(null)
const excluindo = ref(false)

const filtros = ref({
  busca: '',
  tipo: 'Todos',
  lote: 'Todos os Lotes',
  centroCusto: TODOS_CENTROS,
  dataInicial: '',
  dataFinal: ''
})

// Formata números com separador de milhar no padrão brasileiro (ex: 17050 -> 17.050)
function formatNumero(valor) {
  return Number(valor ?? 0).toLocaleString('pt-BR')
}

onMounted(async () => {
  try {
    const { data } = await api.get('/movimentacoes')
    movimentacoes.value = data
    const lotes = [...new Set(data.map(m => m.lote).filter(Boolean))]
    opcoesLote.value = ['Todos os Lotes', ...lotes]
    const centros = [...new Set(data.map(m => m.centro_custo).filter(Boolean))].sort()
    opcoesCentro.value = [TODOS_CENTROS, SEM_CENTRO, ...centros]
  } catch (e) {
    console.error('Erro ao carregar movimentações:', e)
  }
})

// ----- EXCLUIR -----
function confirmarExclusao(mov) {
  movParaExcluir.value = mov
}

async function excluir() {
  excluindo.value = true
  try {
    await api.delete(`/movimentacoes/${movParaExcluir.value.id}`)
    movimentacoes.value = movimentacoes.value.filter(m => m.id !== movParaExcluir.value.id)
    movParaExcluir.value = null
  } catch (e) {
    console.error('Erro ao excluir:', e)
  } finally {
    excluindo.value = false
  }
}

// ----- COMPUTED -----
const movimentacoesFiltradas = computed(() => {
  return movimentacoes.value.filter((mov) => {
    const buscaOk = filtros.value.busca === '' ||
      mov.produto.toLowerCase().includes(filtros.value.busca.toLowerCase()) ||
      mov.sku.toLowerCase().includes(filtros.value.busca.toLowerCase())
    const tipoOk = filtros.value.tipo === 'Todos' || mov.tipo === filtros.value.tipo
    const loteOk = filtros.value.lote === 'Todos os Lotes' || mov.lote === filtros.value.lote
    const centroOk =
      filtros.value.centroCusto === TODOS_CENTROS ||
      (filtros.value.centroCusto === SEM_CENTRO ? !mov.centro_custo : mov.centro_custo === filtros.value.centroCusto)
    const dataMov = mov.data.slice(0, 10)
    const dataInicialOk = !filtros.value.dataInicial || dataMov >= filtros.value.dataInicial
    const dataFinalOk = !filtros.value.dataFinal || dataMov <= filtros.value.dataFinal
    return buscaOk && tipoOk && loteOk && centroOk && dataInicialOk && dataFinalOk
  })
})

function selecionarTipo(opcao) { filtros.value.tipo = opcao; dropdownTipoAberto.value = false }
function selecionarLote(opcao) { filtros.value.lote = opcao; dropdownLoteAberto.value = false }
function selecionarCentro(opcao) { filtros.value.centroCusto = opcao; dropdownCentroAberto.value = false }
function limparFiltros() {
  filtros.value = { busca: '', tipo: 'Todos', lote: 'Todos os Lotes', centroCusto: TODOS_CENTROS, dataInicial: '', dataFinal: '' }
}

function formatarDataHora(dataISO) {
  if (!dataISO) return ''
  const d = new Date(dataISO)
  const dia = String(d.getDate()).padStart(2, '0')
  const mes = String(d.getMonth() + 1).padStart(2, '0')
  const ano = d.getFullYear()
  const hora = String(d.getHours()).padStart(2, '0')
  const min = String(d.getMinutes()).padStart(2, '0')
  return `${dia}/${mes}/${ano}, ${hora}:${min}`
}

const CABECALHOS = ['Data', 'Produto', 'SKU', 'Lote', 'Centro de Custo', 'Tipo', 'Quantidade', 'Fornecedor/Motivo', 'Usuário']
const CABECALHOS_PDF = ['Data', 'Produto', 'SKU', 'Lote', 'Centro de Custo', 'Tipo', 'Qtd', 'Forn./Motivo', 'Usuário']

function linhasParaExportar() {
  return movimentacoesFiltradas.value.map((mov) => [
    formatarDataHora(mov.data), mov.produto, mov.sku, mov.lote, mov.centro_custo || '—',
    mov.tipo, formatNumero(mov.quantidade), mov.motivo, mov.usuario
  ])
}

function nomeArquivo(extensao) {
  const hoje = new Date()
  return `historico-movimentacoes-${hoje.getFullYear()}-${String(hoje.getMonth()+1).padStart(2,'0')}-${String(hoje.getDate()).padStart(2,'0')}.${extensao}`
}

function baixarArquivo(blob, nome) {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url; link.download = nome
  document.body.appendChild(link); link.click()
  document.body.removeChild(link); URL.revokeObjectURL(url)
}

function exportarCSV() {
  const csv = [CABECALHOS, ...linhasParaExportar()]
    .map(l => l.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n')
  baixarArquivo(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }), nomeArquivo('csv'))
}

async function exportarExcel() {
  const wb = new ExcelJS.Workbook()
  const ws = wb.addWorksheet('Movimentações')

  ws.columns = [
    { header: 'Data', key: 'data', width: 18 },
    { header: 'Produto', key: 'produto', width: 30 },
    { header: 'SKU', key: 'sku', width: 16 },
    { header: 'Lote', key: 'lote', width: 14 },
    { header: 'Centro de Custo', key: 'centro_custo', width: 24 },
    { header: 'Tipo', key: 'tipo', width: 12 },
    { header: 'Quantidade', key: 'quantidade', width: 14 },
    { header: 'Fornecedor/Motivo', key: 'motivo', width: 24 },
    { header: 'Usuário', key: 'usuario', width: 18 },
  ]

  ws.getRow(1).eachCell((celula) => {
    celula.font = { bold: true, color: { argb: 'FFFFFFFF' } }
    celula.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF3A6EA5' } }
    celula.alignment = { vertical: 'middle', horizontal: 'left' }
  })

  movimentacoesFiltradas.value.forEach((mov) => {
    const linha = ws.addRow({
      data: formatarDataHora(mov.data),
      produto: mov.produto,
      sku: mov.sku,
      lote: mov.lote,
      centro_custo: mov.centro_custo || '—',
      tipo: mov.tipo,
      quantidade: Number(mov.quantidade ?? 0),
      motivo: mov.motivo,
      usuario: mov.usuario,
    })

    linha.getCell('quantidade').numFmt = '#,##0'
    linha.getCell('quantidade').alignment = { horizontal: 'right' }

    const ehEntrada = mov.tipo === 'Entrada'
    const celulaTipo = linha.getCell('tipo')
    celulaTipo.font = { bold: true, color: { argb: 'FFFFFFFF' } }
    celulaTipo.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: ehEntrada ? 'FF2E7D32' : 'FFC62828' } }
    celulaTipo.alignment = { horizontal: 'center' }

    linha.getCell('quantidade').font = { color: { argb: ehEntrada ? 'FF2E7D32' : 'FFC62828' }, bold: true }
  })

  ws.autoFilter = { from: 'A1', to: 'I1' }
  ws.views = [{ state: 'frozen', ySplit: 1 }]

  const buffer = await wb.xlsx.writeBuffer()
  baixarArquivo(
    new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }),
    nomeArquivo('xlsx')
  )
}

function exportarPDF() {
  const doc = new jsPDF({ orientation: 'landscape' })
  doc.setFontSize(16)
  doc.text('Histórico de Movimentações - SIGE', 14, 15)
  autoTable(doc, {
    head: [CABECALHOS_PDF],
    body: linhasParaExportar(),
    startY: 22,
    theme: 'striped',
    headStyles: { fillColor: [58, 110, 165], textColor: [255, 255, 255], fontStyle: 'bold' },
    styles: { fontSize: 9, cellPadding: 3 },
    alternateRowStyles: { fillColor: [240, 240, 240] },
    columnStyles: {
      6: { halign: 'right' }, // Quantidade
    },
    didParseCell: (data) => {
      if (data.section === 'body' && data.column.index === 5) { // Tipo
        const ehEntrada = String(data.cell.raw) === 'Entrada'
        data.cell.styles.fillColor = ehEntrada ? [46, 125, 50] : [198, 40, 40]
        data.cell.styles.textColor = [255, 255, 255]
        data.cell.styles.fontStyle = 'bold'
        data.cell.styles.halign = 'center'
      }
    },
  })
  doc.save(nomeArquivo('pdf'))
}
</script>