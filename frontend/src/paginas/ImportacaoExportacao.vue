<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Importação e Exportação</h1>
      <p class="text-slate-500 dark:text-gray-400 text-sm mt-1">Gerencie importação de dados e exportação de relatórios</p>
    </div>

    <!-- Cards de Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="card in statsCards"
        :key="card.label"
        class="bg-white dark:bg-[#1e1e2e] border border-slate-200 dark:border-gray-700 rounded-lg p-4 flex items-center gap-4"
      >
        <div :class="card.bg" class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl">
          <i :class="card.icon"></i>
        </div>
        <div>
          <p class="text-slate-500 dark:text-gray-400 text-sm">{{ card.label }}</p>
          <p class="text-slate-900 dark:text-white text-2xl font-bold">{{ stats[card.key] ?? 0 }}</p>
        </div>
      </div>
    </div>

    <!-- Exportar -->
    <div class="bg-slate-50 dark:bg-[#1a1a2e] border border-slate-200 dark:border-gray-700 rounded-xl p-6">
      <h2 class="text-green-600 dark:text-green-400 font-semibold text-lg mb-4 flex items-center gap-2">
        <i class="fas fa-download"></i> Exportar Dados
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Backup JSON -->
        <div class="bg-white dark:bg-[#1e1e2e] border border-slate-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
          <div>
            <p class="text-slate-900 dark:text-white font-semibold">Backup Completo</p>
            <p class="text-slate-500 dark:text-gray-400 text-sm">Exporta todos os dados do sistema em formato JSON</p>
          </div>
          <button
            :disabled="loadingExport.backup"
            class="w-full bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition"
            @click="exportar('backup')"
          >
            <i :class="loadingExport.backup ? 'fas fa-spinner fa-spin' : 'fas fa-database'"></i>
            Exportar Tudo
          </button>
        </div>

        <!-- Produtos -->
        <div class="bg-white dark:bg-[#1e1e2e] border border-slate-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
          <div>
            <p class="text-slate-900 dark:text-white font-semibold">Produtos</p>
            <p class="text-slate-500 dark:text-gray-400 text-sm">Exporta cadastro de produtos com estoque atual</p>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <button
              :disabled="loadingExport.produtos"
              class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition text-sm"
              @click="exportar('produtos-xlsx')"
            >
              <i :class="loadingExport.produtos ? 'fas fa-spinner fa-spin' : 'fas fa-file-excel'"></i>
              Excel
            </button>
            <button
              :disabled="loadingExport.produtos"
              class="bg-slate-600 hover:bg-slate-700 disabled:opacity-50 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition text-sm"
              @click="exportar('produtos-csv')"
            >
              <i :class="loadingExport.produtos ? 'fas fa-spinner fa-spin' : 'fas fa-file-csv'"></i>
              CSV
            </button>
          </div>
        </div>

        <!-- Movimentações -->
        <div class="bg-white dark:bg-[#1e1e2e] border border-slate-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
          <div>
            <p class="text-slate-900 dark:text-white font-semibold">Movimentações</p>
            <p class="text-slate-500 dark:text-gray-400 text-sm">Exporta histórico de entradas e saídas</p>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <button
              :disabled="loadingExport.movimentacoes"
              class="bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition text-sm"
              @click="exportar('movimentacoes-xlsx')"
            >
              <i :class="loadingExport.movimentacoes ? 'fas fa-spinner fa-spin' : 'fas fa-file-excel'"></i>
              Excel
            </button>
            <button
              :disabled="loadingExport.movimentacoes"
              class="bg-slate-600 hover:bg-slate-700 disabled:opacity-50 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition text-sm"
              @click="exportar('movimentacoes-csv')"
            >
              <i :class="loadingExport.movimentacoes ? 'fas fa-spinner fa-spin' : 'fas fa-file-csv'"></i>
              CSV
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Importar -->
    <div class="bg-slate-50 dark:bg-[#1a1a2e] border border-slate-200 dark:border-gray-700 rounded-xl p-6">
      <h2 class="text-blue-600 dark:text-blue-400 font-semibold text-lg mb-4 flex items-center gap-2">
        <i class="fas fa-upload"></i> Importar Dados
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Importar Excel -->
        <div class="bg-white dark:bg-[#1e1e2e] border border-slate-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
          <div>
            <p class="text-slate-900 dark:text-white font-semibold">Planilha Excel (.xlsx)</p>
            <p class="text-slate-500 dark:text-gray-400 text-sm">
              Importa produtos do almoxarifado. Você escolhe se cria um lote único ou distribui em vários lotes.
            </p>
          </div>
          <div class="space-y-2">
            <!-- Preview das colunas esperadas -->
            <div class="bg-blue-50 dark:bg-[#0f1a2e] border border-blue-200 dark:border-blue-900 rounded-lg px-3 py-2 text-xs text-blue-700 dark:text-blue-300 space-y-1">
              <p class="font-semibold text-blue-700 dark:text-blue-400">Colunas lidas da planilha:</p>
              <p>CÓDIGO · DESCRIÇÃO · UNIDADE · SALDO · ESTOQUE MÍNIMO · VALIDADE</p>
            </div>

            <button
              class="w-full bg-slate-100 dark:bg-[#2a2a3e] hover:bg-slate-200 dark:hover:bg-[#33334d] text-slate-700 dark:text-gray-200 py-2 rounded-lg flex items-center justify-center gap-2 transition text-sm"
              @click="baixarModelo"
            >
              <i class="fas fa-download"></i>
              Baixar planilha modelo
            </button>

            <button
              class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition"
              @click="mostrarWizard = true"
            >
              <i class="fas fa-file-excel"></i>
              Importar Planilha
            </button>
          </div>
        </div>

        <!-- Restaurar Backup -->
        <div class="bg-white dark:bg-[#1e1e2e] border border-slate-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
          <div>
            <p class="text-slate-900 dark:text-white font-semibold">Restaurar Backup</p>
            <p class="text-slate-500 dark:text-gray-400 text-sm">Restaura backup completo do sistema (JSON)</p>
          </div>
          <div class="space-y-2">
            <label class="text-slate-500 dark:text-gray-400 text-xs">Selecione o arquivo JSON</label>
            <label class="flex items-center gap-2 bg-slate-100 dark:bg-[#2a2a3e] border border-slate-300 dark:border-gray-600 rounded-lg px-3 py-2 cursor-pointer hover:border-red-500 transition">
              <i class="fas fa-folder-open text-slate-400 dark:text-gray-400"></i>
              <span class="text-slate-600 dark:text-gray-300 text-sm truncate">
                {{ arquivoJSON ? arquivoJSON.name : 'Nenhum arquivo escolhido' }}
              </span>
              <input type="file" accept=".json" class="hidden" @change="onArquivoJSON" />
            </label>
            <div class="bg-red-50 dark:bg-red-900/40 border border-red-200 dark:border-red-700 rounded-lg px-3 py-2 flex items-center gap-2 text-red-600 dark:text-red-400 text-xs">
              <i class="fas fa-triangle-exclamation"></i>
              Substitui todos os dados atuais
            </div>
            <button
              :disabled="!arquivoJSON || loadingImport.backup"
              class="w-full bg-red-700 hover:bg-red-800 disabled:opacity-40 text-white py-2 rounded-lg flex items-center justify-center gap-2 transition"
              @click="confirmarRestaurar"
            >
              <i :class="loadingImport.backup ? 'fas fa-spinner fa-spin' : 'fas fa-undo'"></i>
              {{ loadingImport.backup ? 'Restaurando...' : 'Restaurar' }}
            </button>
          </div>
        </div>

        <!-- Info formato -->
        <div class="bg-blue-50 dark:bg-[#1a2744] border border-blue-200 dark:border-blue-800 rounded-lg p-4 space-y-3">
          <p class="text-blue-700 dark:text-blue-300 font-semibold flex items-center gap-2">
            <i class="fas fa-circle-info"></i> Como funciona a importação
          </p>
          <ul class="text-blue-600 dark:text-blue-200 text-xs space-y-2">
            <li>✅ Lê a planilha do almoxarifado diretamente</li>
            <li>✅ Ignora linhas de agrupamento (LETRA A, LETRA B...)</li>
            <li>✅ Converte validade serial do Excel automaticamente</li>
            <li>✅ Produtos sem código recebem SKU gerado pelo nome</li>
            <li>✅ <strong>Você escolhe: lote único ou lotes separados</strong></li>
            <li>✅ Produto já existente tem estoque incrementado</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <transition name="fade">
      <div
        v-if="toast.show"
        :class="['fixed bottom-6 right-6 z-50 px-5 py-3 rounded-lg shadow-lg text-white flex items-center gap-3 text-sm',
          toast.type === 'success' ? 'bg-green-700' : 'bg-red-700']"
      >
        <i :class="toast.type === 'success' ? 'fas fa-check-circle' : 'fas fa-circle-xmark'"></i>
        {{ toast.message }}
      </div>
    </transition>

    <!-- Modal confirmação restaurar -->
    <div v-if="modalRestaurar" class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center">
      <div class="bg-white dark:bg-[#1e1e2e] border border-red-300 dark:border-red-700 rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
        <p class="text-slate-900 dark:text-white font-semibold text-lg flex items-center gap-2">
          <AlertTriangle :size="20" class="text-red-600 dark:text-red-400" />
          Atenção!
        </p>
        <p class="text-slate-600 dark:text-gray-300 text-sm">
          Esta ação irá <strong class="text-red-600 dark:text-red-400">apagar todos os dados atuais</strong> e substituir pelo backup selecionado. Essa ação não pode ser desfeita.
        </p>
        <div class="flex gap-3">
          <button
            class="flex-1 bg-slate-200 hover:bg-slate-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-slate-900 dark:text-white py-2 rounded-lg transition"
            @click="modalRestaurar = false"
          >
            Cancelar
          </button>
          <button
            class="flex-1 bg-red-700 hover:bg-red-800 text-white py-2 rounded-lg transition"
            @click="restaurarBackup"
          >
            Confirmar
          </button>
        </div>
      </div>
    </div>

    <!-- Wizard de importação Excel -->
    <ImportarExcelWizard
      v-if="mostrarWizard"
      @fechar="mostrarWizard = false"
      @importado="() => { carregarStats(); showToast('Importação concluída!') }"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/servicos/api'
import { AlertTriangle } from 'lucide-vue-next'
import ImportarExcelWizard from '@/componentes/ui/ImportarExcelWizard.vue'

const stats           = ref({ lotes: 0, produtos: 0, movimentacoes: 0, itens_estoque: 0 })
const arquivoJSON     = ref(null)
const modalRestaurar  = ref(false)
const mostrarWizard   = ref(false)

const loadingExport = ref({ backup: false, produtos: false, movimentacoes: false })
const loadingImport = ref({ backup: false })
const toast          = ref({ show: false, message: '', type: 'success' })

const statsCards = [
  { label: 'Lotes',         key: 'lotes',         icon: 'fas fa-database',  bg: 'bg-blue-600'   },
  { label: 'Produtos',      key: 'produtos',       icon: 'fas fa-box',       bg: 'bg-green-600'  },
  { label: 'Movimentações', key: 'movimentacoes',  icon: 'fas fa-sync',      bg: 'bg-purple-600' },
  { label: 'Itens Estoque', key: 'itens_estoque',  icon: 'fas fa-warehouse', bg: 'bg-orange-600' },
]

function showToast(message, type = 'success') {
  toast.value = { show: true, message, type }
  setTimeout(() => (toast.value.show = false), 4000)
}

async function carregarStats() {
  try {
    const { data } = await api.get('/importacao-exportacao/stats')
    stats.value = data
  } catch {
    console.error('Erro ao carregar stats')
  }
}

// Salva um blob como arquivo
function salvarBlob(blob, nome) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = nome
  a.click()
  URL.revokeObjectURL(url)
}

// Confere se o blob é mesmo um xlsx (zip começa com "PK")
async function ehXlsxValido(blob) {
  if (!blob || typeof blob.slice !== 'function') return false
  const head = new Uint8Array(await blob.slice(0, 2).arrayBuffer())
  return head[0] === 0x50 && head[1] === 0x4b
}

async function exportar(tipo) {
  const chave = tipo === 'backup' ? 'backup'
    : (tipo === 'produtos-xlsx' || tipo === 'produtos-csv') ? 'produtos'
    : 'movimentacoes'
  loadingExport.value[chave] = true
  try {
    const response = await api.get(`/importacao-exportacao/exportar/${tipo}`, { responseType: 'blob' })
    const blob = response.data ?? response
    const ext  = tipo === 'backup' ? 'json' : tipo.endsWith('xlsx') ? 'xlsx' : 'csv'

    if (ext === 'xlsx' && !(await ehXlsxValido(blob))) {
      console.error('Resposta não é um xlsx:', blob?.text ? await blob.text() : blob)
      showToast('O servidor não retornou um Excel válido.', 'error')
      return
    }

    const nome = `${tipo}_${new Date().toISOString().slice(0, 10)}.${ext}`
    salvarBlob(new Blob([blob]), nome)
    showToast('Exportação concluída!')
  } catch {
    showToast('Erro ao exportar.', 'error')
  } finally {
    loadingExport.value[chave] = false
  }
}

async function baixarModelo() {
  try {
    const response = await api.get('/importacao-exportacao/template', { responseType: 'blob' })
    const blob = response.data ?? response

    if (!(await ehXlsxValido(blob))) {
      console.error('Resposta não é um xlsx:', blob?.text ? await blob.text() : blob)
      showToast('O servidor não retornou um Excel válido. Veja o console (F12).', 'error')
      return
    }

    salvarBlob(
      new Blob([blob], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }),
      'modelo_importacao_almoxarifado.xlsx'
    )
  } catch {
    showToast('Erro ao baixar modelo.', 'error')
  }
}

function onArquivoJSON(e) { arquivoJSON.value = e.target.files[0] }

function confirmarRestaurar() {
  if (!arquivoJSON.value) return
  modalRestaurar.value = true
}

async function restaurarBackup() {
  modalRestaurar.value = false
  loadingImport.value.backup = true
  const form = new FormData()
  form.append('arquivo', arquivoJSON.value)
  try {
    const { data } = await api.post('/importacao-exportacao/restaurar/backup', form)
    showToast(data.message)
    arquivoJSON.value = null
    carregarStats()
  } catch (err) {
    showToast(err.response?.data?.message ?? 'Erro ao restaurar backup.', 'error')
  } finally {
    loadingImport.value.backup = false
  }
}

onMounted(carregarStats)
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>