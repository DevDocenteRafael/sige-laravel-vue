<template>
  <div :class="['p-6 min-h-screen', temaClaro ? 'bg-gray-100 text-gray-900' : 'bg-[#0f1117] text-white']">
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Logs de Auditoria</h1>
        <p :class="['text-sm mt-0.5', temaClaro ? 'text-gray-500' : 'text-gray-400']">
          Histórico completo de todas as ações realizadas no sistema
        </p>
      </div>
    </div>

    <!-- Cards de Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div
        v-for="card in statCards"
        :key="card.label"
        :class="[
          'rounded-xl p-4 flex items-center gap-4 border transition-shadow hover:shadow-lg',
          temaClaro ? 'bg-white border-gray-200 shadow-sm' : 'bg-[#1a1d27] border-gray-800',
        ]"
      >
        <div :class="['p-3 rounded-lg shrink-0', card.bg]">
          <component :is="card.icon" :size="22" class="text-white" />
        </div>
        <div class="min-w-0">
          <p :class="['text-sm', temaClaro ? 'text-gray-500' : 'text-gray-400']">{{ card.label }}</p>
          <p class="text-2xl font-bold leading-tight truncate">{{ card.value }}</p>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div
      :class="[
        'rounded-xl p-4 mb-4 border flex flex-wrap gap-3 items-center',
        temaClaro ? 'bg-white border-gray-200 shadow-sm' : 'bg-[#1a1d27] border-gray-800',
      ]"
    >
      <div class="relative flex-1 min-w-[220px]">
        <SearchIcon :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input
          v-model="search"
          type="text"
          placeholder="Buscar por usuário, ação, descrição..."
          :class="[
            'w-full border rounded-lg pl-10 pr-4 py-2 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500',
            temaClaro ? 'bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-400' : 'bg-[#0f1117] border-gray-700 text-white placeholder-gray-500',
          ]"
          @input="onSearchInput"
        />
      </div>

      <select
        v-model="period"
        :class="[
          'border rounded-lg px-4 py-2 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500',
          temaClaro ? 'bg-gray-50 border-gray-300 text-gray-900' : 'bg-[#0f1117] border-gray-700 text-white',
        ]"
        @change="fetchLogs()"
      >
        <option value="7d">Últimos 7 dias</option>
        <option value="30d">Últimos 30 dias</option>
        <option value="90d">Últimos 90 dias</option>
        <option value="180d">Últimos 6 meses</option>
        <option value="365d">Último ano</option>
        <option value="730d">Últimos 2 anos</option>
        <option value="all">Todo o período</option>
      </select>

      <select
        v-model="actionFilter"
        :class="[
          'border rounded-lg px-4 py-2 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500',
          temaClaro ? 'bg-gray-50 border-gray-300 text-gray-900' : 'bg-[#0f1117] border-gray-700 text-white',
        ]"
        @change="fetchLogs()"
      >
        <option value="all">Todas Ações</option>
        <option value="Login">Login</option>
        <option value="Logout">Logout</option>
        <option value="Criação">Criação</option>
        <option value="Edição">Edição</option>
        <option value="Exclusão">Exclusão</option>
        <option value="Ativação">Ativação</option>
        <option value="Inativação">Inativação</option>
        <option value="Entrada de Estoque">Entrada de Estoque</option>
        <option value="Saída de Estoque">Saída de Estoque</option>
        <option value="Transferência">Transferência</option>
      </select>

      <!-- Botão de exportação com opções -->
      <div ref="exportMenuRoot" class="relative">
        <button
          :class="[
            'flex items-center gap-2 border rounded-lg px-4 py-2 text-sm transition-colors',
            temaClaro ? 'bg-gray-50 border-gray-300 text-gray-700 hover:border-blue-500' : 'bg-[#0f1117] border-gray-700 text-white hover:border-blue-500',
          ]"
          :disabled="exporting !== null"
          @click="showExportMenu = !showExportMenu"
        >
          <LoaderCircleIcon v-if="exporting" :size="16" class="animate-spin" />
          <DownloadIcon v-else :size="16" />
          Exportar
          <ChevronDownIcon :size="14" :class="['transition-transform', showExportMenu ? 'rotate-180' : '']" />
        </button>

        <div
          v-if="showExportMenu"
          :class="[
            'absolute right-0 mt-2 w-48 rounded-lg border shadow-lg overflow-hidden z-20',
            temaClaro ? 'bg-white border-gray-200' : 'bg-[#1a1d27] border-gray-800',
          ]"
        >
          <button
            v-for="opt in exportOptions"
            :key="opt.format"
            :class="[
              'w-full flex items-center gap-3 px-4 py-2.5 text-sm text-left transition-colors',
              temaClaro ? 'hover:bg-gray-50 text-gray-700' : 'hover:bg-[#22263a] text-gray-200',
            ]"
            @click="exportLogs(opt.format)"
          >
            <component :is="opt.icon" :size="16" :class="opt.color" />
            <span>{{ opt.label }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Tabela -->
    <div :class="['rounded-xl border overflow-hidden', temaClaro ? 'bg-white border-gray-200 shadow-sm' : 'bg-[#1a1d27] border-gray-800']">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr :class="['border-b', temaClaro ? 'border-gray-200 bg-gray-50/60' : 'border-gray-800 bg-white/[0.02]']">
              <th class="text-left px-6 py-3.5 text-blue-500 font-semibold text-xs uppercase tracking-wide">Data/Hora</th>
              <th class="text-left px-6 py-3.5 text-blue-500 font-semibold text-xs uppercase tracking-wide">Usuário</th>
              <th class="text-left px-6 py-3.5 text-blue-500 font-semibold text-xs uppercase tracking-wide">Ação</th>
              <th class="text-left px-6 py-3.5 text-blue-500 font-semibold text-xs uppercase tracking-wide">Descrição</th>
              <th class="text-right px-6 py-3.5 text-blue-500 font-semibold text-xs uppercase tracking-wide">IP</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="py-14">
                <div class="flex flex-col items-center gap-2">
                  <LoaderCircleIcon :size="22" class="animate-spin text-blue-500" />
                  <span :class="temaClaro ? 'text-gray-400' : 'text-gray-500'">Carregando registros...</span>
                </div>
              </td>
            </tr>
            <tr v-else-if="logs.length === 0">
              <td colspan="5" class="py-14">
                <div class="flex flex-col items-center gap-2">
                  <InboxIcon :size="26" :class="temaClaro ? 'text-gray-300' : 'text-gray-600'" />
                  <span :class="temaClaro ? 'text-gray-400' : 'text-gray-500'">Nenhum log encontrado para os filtros atuais.</span>
                </div>
              </td>
            </tr>
            <tr
              v-for="log in logs"
              :key="log.id"
              :class="['border-b last:border-b-0 transition-colors', temaClaro ? 'border-gray-100 hover:bg-gray-50' : 'border-gray-800 hover:bg-[#22263a]']"
            >
              <td class="px-6 py-3.5 whitespace-nowrap">
                <p class="font-semibold">{{ formatDate(log.created_at) }}</p>
                <p :class="['text-xs', temaClaro ? 'text-gray-400' : 'text-gray-500']">{{ formatTime(log.created_at) }}</p>
              </td>
              <td class="px-6 py-3.5">
                <p class="font-semibold">{{ log.user?.name ?? 'Sistema' }}</p>
                <p :class="['text-xs', temaClaro ? 'text-gray-400' : 'text-gray-500']">{{ log.user?.email ?? '-' }}</p>
              </td>
              <td class="px-6 py-3.5">
                <span :class="['inline-flex items-center gap-1.5 w-fit px-2.5 py-1 rounded-md text-xs font-semibold', actionClass(log.action)]">
                  <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70" />
                  {{ log.action }}
                </span>
              </td>
              <td :class="['px-6 py-3.5 max-w-md truncate', temaClaro ? 'text-gray-600' : 'text-gray-300']" :title="log.description">
                {{ log.description }}
              </td>
              <td :class="['px-6 py-3.5 text-right font-mono text-xs', temaClaro ? 'text-gray-400' : 'text-gray-500']">
                {{ log.ip_address }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginação -->
      <div
        :class="[
          'flex justify-between items-center px-6 py-4 border-t text-sm',
          temaClaro ? 'border-gray-200 text-gray-500' : 'border-gray-800 text-gray-400',
        ]"
      >
        <span>Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button
            :disabled="pagination.current_page === 1"
            :class="[
              'px-3 py-1.5 rounded-lg border transition-colors disabled:opacity-30 disabled:cursor-not-allowed',
              temaClaro ? 'border-gray-300 hover:border-blue-500 hover:text-blue-600' : 'border-gray-700 hover:border-blue-500 hover:text-blue-400',
            ]"
            @click="changePage(pagination.current_page - 1)"
          >Anterior</button>
          <button
            :disabled="pagination.current_page === pagination.last_page"
            :class="[
              'px-3 py-1.5 rounded-lg border transition-colors disabled:opacity-30 disabled:cursor-not-allowed',
              temaClaro ? 'border-gray-300 hover:border-blue-500 hover:text-blue-600' : 'border-gray-700 hover:border-blue-500 hover:text-blue-400',
            ]"
            @click="changePage(pagination.current_page + 1)"
          >Próximo</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import {
  ShieldCheck as ShieldCheckIcon,
  Calendar as CalendarIcon,
  User as UserIcon,
  FileText as FileTextIcon,
  Search as SearchIcon,
  Download as DownloadIcon,
  ChevronDown as ChevronDownIcon,
  LoaderCircle as LoaderCircleIcon,
  Inbox as InboxIcon,
  FileSpreadsheet as FileSpreadsheetIcon,
  FileType2 as FilePdfIcon,
  FileSpreadsheet as FileCsvIcon,
} from 'lucide-vue-next'
import { storeToRefs } from 'pinia'
import { useTemaStore } from '@/servicos/tema.store'
import api from '@/servicos/api'

const temaStore     = useTemaStore()
const { temaClaro } = storeToRefs(temaStore)

const logs         = ref([])
const stats        = ref({ total: 0, last_24h: 0, active_users: 0, most_common: null })
const loading      = ref(false)
const search       = ref('')
const period       = ref('30d')
const actionFilter = ref('all')
const pagination   = ref({ current_page: 1, last_page: 1 })

const showExportMenu = ref(false)
const exporting       = ref(null) // 'csv' | 'xlsx' | 'pdf' | null
const exportMenuRoot  = ref(null)

let searchDebounce = null

const statCards = computed(() => [
  { label: 'Total de Logs',    value: stats.value.total,             icon: ShieldCheckIcon, bg: 'bg-blue-600' },
  { label: 'Últimas 24h',      value: stats.value.last_24h,          icon: CalendarIcon,    bg: 'bg-green-600' },
  { label: 'Usuários Ativos',  value: stats.value.active_users,      icon: UserIcon,        bg: 'bg-purple-600' },
  { label: 'Ação + Comum',     value: stats.value.most_common ?? '-',icon: FileTextIcon,    bg: 'bg-orange-600' },
])

const exportOptions = [
  { format: 'csv',  label: 'Exportar CSV',   icon: FileCsvIcon,        color: 'text-green-500' },
  { format: 'xlsx', label: 'Exportar Excel', icon: FileSpreadsheetIcon, color: 'text-emerald-500' },
  { format: 'pdf',  label: 'Exportar PDF',   icon: FilePdfIcon,        color: 'text-red-500' },
]

async function fetchLogs(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/audit-logs', {
      params: {
        search: search.value,
        period: period.value,
        action: actionFilter.value,
        page,
      },
    })
    logs.value  = data.logs.data
    stats.value = data.stats
    pagination.value = {
      current_page: data.logs.current_page,
      last_page:    data.logs.last_page,
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => fetchLogs(), 350)
}

function changePage(page) {
  if (page < 1 || page > pagination.value.last_page) return
  fetchLogs(page)
}

function formatDate(dt) {
  return new Date(dt).toLocaleDateString('pt-BR')
}

function formatTime(dt) {
  return new Date(dt).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

function actionClass(action) {
  const map = {
    'Login':    'bg-blue-900 text-blue-300',
    'Logout':   'bg-gray-700 text-gray-300',
    'Criação':  'bg-green-900 text-green-300',
    'Edição':   'bg-yellow-900 text-yellow-300',
    'Exclusão': 'bg-red-900 text-red-300',
    'Ativação':   'bg-teal-900 text-teal-300',
    'Inativação': 'bg-zinc-700 text-zinc-300',
    'Entrada de Estoque': 'bg-emerald-900 text-emerald-300',
    'Saída de Estoque':   'bg-orange-900 text-orange-300',
    'Transferência':      'bg-indigo-900 text-indigo-300',
  }
  return map[action] ?? 'bg-gray-700 text-gray-300'
}

const EXPORT_CONFIG = {
  csv:  { mime: 'text/csv',                                                                    ext: 'csv'  },
  xlsx: { mime: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',            ext: 'xlsx' },
  pdf:  { mime: 'application/pdf',                                                              ext: 'pdf'  },
}

async function exportLogs(format) {
  showExportMenu.value = false
  exporting.value = format
  try {
    const { data } = await api.get('/audit-logs/export', {
      params: { search: search.value, period: period.value, action: actionFilter.value, format },
      responseType: 'blob',
    })
    const { mime, ext } = EXPORT_CONFIG[format]
    const url = URL.createObjectURL(new Blob([data], { type: mime }))
    const a    = document.createElement('a')
    a.href     = url
    a.download = `audit-logs-${new Date().toISOString().slice(0, 10)}.${ext}`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    console.error(e)
  } finally {
    exporting.value = null
  }
}

function handleClickOutside(event) {
  if (showExportMenu.value && exportMenuRoot.value && !exportMenuRoot.value.contains(event.target)) {
    showExportMenu.value = false
  }
}

onMounted(() => {
  fetchLogs()
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  clearTimeout(searchDebounce)
})
</script>