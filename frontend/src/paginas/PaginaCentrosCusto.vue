<template>
  <div class="p-6 min-h-screen bg-white dark:bg-black text-slate-900 dark:text-white">

    <!-- Cabeçalho -->
    <div class="flex justify-between items-start mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Centros de Custo</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Cadastro e gerenciamento dos centros de custo</p>
      </div>
      <button
        class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium"
        @click="abrirNovo"
      >
        <Plus :size="18" />
        Novo Centro de Custo
      </button>
    </div>

    <!-- Carregando -->
    <div v-if="carregando" class="text-center py-12 text-slate-500 dark:text-slate-400">
      Carregando centros de custo...
    </div>

    <!-- Sem centros cadastrados -->
    <div
      v-else-if="centros.length === 0"
      class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-16 text-center"
    >
      <Building2 class="mx-auto mb-4 text-slate-400 dark:text-slate-600" :size="48" />
      <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Nenhum centro de custo cadastrado</h2>
      <p class="text-slate-500 dark:text-slate-400 mb-6">Crie o primeiro centro de custo para vincular aos itens dos lotes.</p>
      <button
        class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition font-medium flex items-center justify-center gap-2"
        @click="abrirNovo"
      >
        <Plus :size="18" />
        Criar Primeiro Centro de Custo
      </button>
    </div>

    <template v-else>
      <!-- Cards de resumo -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex items-center gap-4">
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center">
            <Building2 class="text-blue-600 dark:text-blue-400" :size="20" />
          </div>
          <div>
            <p class="text-slate-500 dark:text-slate-400 text-xs">Total de Centros</p>
            <p class="text-slate-900 dark:text-white text-2xl font-bold">{{ formatNumero(centros.length) }}</p>
          </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex items-center gap-4">
          <div class="w-10 h-10 bg-green-100 dark:bg-green-900/40 rounded-lg flex items-center justify-center">
            <Power class="text-green-600 dark:text-green-400" :size="20" />
          </div>
          <div>
            <p class="text-slate-500 dark:text-slate-400 text-xs">Ativos</p>
            <p class="text-slate-900 dark:text-white text-2xl font-bold">{{ formatNumero(totalAtivos) }}</p>
          </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex items-center gap-4">
          <div class="w-10 h-10 bg-slate-200 dark:bg-slate-800 rounded-lg flex items-center justify-center">
            <PowerOff class="text-slate-600 dark:text-slate-400" :size="20" />
          </div>
          <div>
            <p class="text-slate-500 dark:text-slate-400 text-xs">Inativos</p>
            <p class="text-slate-900 dark:text-white text-2xl font-bold">{{ formatNumero(totalInativos) }}</p>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Buscar Centro de Custo</label>
            <div class="relative">
              <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
              <input
                v-model="busca"
                type="text"
                placeholder="Código ou nome..."
                class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500 transition"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Status</label>
            <select
              v-model="filtroStatus"
              class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500 transition"
            >
              <option value="todos">Todos</option>
              <option value="ativos">Ativos</option>
              <option value="inativos">Inativos</option>
            </select>
          </div>

          <div class="flex items-end">
            <button
              class="w-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm font-medium py-2 rounded-lg transition"
              @click="limparFiltros"
            >
              Limpar Filtros
            </button>
          </div>
        </div>

        <p class="text-slate-500 dark:text-slate-400 text-sm mt-4">
          {{ formatNumero(centrosFiltrados.length) }} centro(s) de custo encontrado(s)
        </p>
      </div>

      <!-- Tabela -->
      <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 min-h-[200px]">
        <div v-if="centrosFiltrados.length === 0" class="text-center py-16">
          <Building2 class="mx-auto mb-3 text-slate-400 dark:text-slate-600" :size="40" />
          <p class="text-slate-400 dark:text-slate-500">Nenhum centro de custo encontrado</p>
        </div>

        <div v-else class="overflow-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                <th class="text-left px-4 py-3 font-medium">Código</th>
                <th class="text-left px-4 py-3 font-medium">Nome</th>
                <th class="text-left px-4 py-3 font-medium">Status</th>
                <th class="text-left px-4 py-3 font-medium">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
              <tr
                v-for="c in centrosPaginados"
                :key="c.id_centro_custo"
                class="hover:bg-slate-100 dark:hover:bg-slate-800/50 transition"
                :class="!c.ativo && 'opacity-60'"
              >
                <td class="px-4 py-3 text-slate-900 dark:text-white font-medium">{{ c.codigo }}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ c.nome }}</td>
                <td class="px-4 py-3">
                  <span
                    class="px-2 py-0.5 rounded text-xs font-bold text-white"
                    :class="c.ativo ? 'bg-green-700' : 'bg-slate-500'"
                  >
                    {{ c.ativo ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <button
                      class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition"
                      title="Editar"
                      @click="abrirEdicao(c)"
                    >
                      <Pencil :size="16" />
                    </button>
                    <button
                      class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-500 dark:hover:text-yellow-300 transition"
                      :title="c.ativo ? 'Desativar' : 'Ativar'"
                      @click="alternarAtivo(c)"
                    >
                      <PowerOff v-if="c.ativo" :size="16" />
                      <Power v-else :size="16" />
                    </button>
                    <button
                      class="text-red-600 dark:text-red-400 hover:text-red-500 dark:hover:text-red-300 transition"
                      title="Excluir"
                      @click="abrirExclusao(c)"
                    >
                      <Trash2 :size="16" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="px-4 pb-4">
          <PaginacaoControles
            v-model:pagina-atual="paginaAtual"
            v-model:por-pagina="porPagina"
            :total-paginas="totalPaginas"
            :total="centrosFiltrados.length"
            rotulo="centros"
          />
        </div>
      </div>
    </template>

    <!-- ===== MODAL CRIAR / EDITAR ===== -->
    <div
      v-if="modalAberto"
      class="fixed inset-0 bg-black/70 flex items-center justify-center z-50"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Building2 class="text-blue-600 dark:text-blue-400" :size="20" />
            <div>
              <h2 class="text-slate-900 dark:text-white font-bold">
                {{ editandoId ? 'Editar Centro de Custo' : 'Novo Centro de Custo' }}
              </h2>
              <p class="text-slate-500 dark:text-slate-400 text-xs">
                {{ editandoId ? 'Altere os dados do centro de custo' : 'Preencha os dados do novo centro de custo' }}
              </p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="fecharModal">
            <X :size="18" />
          </button>
        </div>

        <div class="space-y-4 mb-6">
          <div>
            <label class="block text-sm text-slate-600 dark:text-slate-300 font-medium mb-1">Código *</label>
            <input
              v-model="form.codigo"
              maxlength="20"
              placeholder="Ex: CC04"
              class="w-full bg-white dark:bg-slate-800 border rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500 transition"
              :class="erros.codigo ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
              @keyup.enter="salvar"
            />
            <p v-if="erros.codigo" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ erros.codigo[0] }}</p>
          </div>

          <div>
            <label class="block text-sm text-slate-600 dark:text-slate-300 font-medium mb-1">Nome *</label>
            <input
              v-model="form.nome"
              maxlength="100"
              placeholder="Ex: Laboratório"
              class="w-full bg-white dark:bg-slate-800 border rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500 transition"
              :class="erros.nome ? 'border-red-500' : 'border-slate-300 dark:border-slate-700'"
              @keyup.enter="salvar"
            />
            <p v-if="erros.nome" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ erros.nome[0] }}</p>
          </div>

          <label
            v-if="editandoId"
            class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 cursor-pointer select-none"
          >
            <input v-model="form.ativo" type="checkbox" class="cursor-pointer" />
            Centro de custo ativo
          </label>
        </div>

        <div class="flex gap-3">
          <button
            class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm"
            @click="fecharModal"
          >
            Cancelar
          </button>
          <button
            :disabled="salvando"
            class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition font-medium text-sm disabled:opacity-50"
            @click="salvar"
          >
            {{ salvando ? 'Salvando...' : 'Salvar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL CONFIRMAÇÃO EXCLUSÃO ===== -->
    <div
      v-if="modalExcluirAberto"
      class="fixed inset-0 bg-black/70 flex items-center justify-center z-50"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Shield class="text-red-600 dark:text-red-400" :size="20" />
            <div>
              <h2 class="text-slate-900 dark:text-white font-bold">Excluir Centro de Custo</h2>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Esta ação não pode ser desfeita</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="modalExcluirAberto = false">
            <X :size="18" />
          </button>
        </div>

        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
          <p class="text-slate-600 dark:text-slate-300 text-sm">
            Você está excluindo o centro de custo
            <strong class="text-slate-900 dark:text-white">{{ centroParaExcluir?.codigo }} - {{ centroParaExcluir?.nome }}</strong>.
            Se ele já estiver em uso, a exclusão será bloqueada e você poderá desativá-lo.
          </p>
        </div>

        <div class="flex gap-3">
          <button
            class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm"
            @click="modalExcluirAberto = false"
          >
            Cancelar
          </button>
          <button
            :disabled="excluindo"
            class="flex-1 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 transition font-medium text-sm disabled:opacity-50"
            @click="excluir"
          >
            {{ excluindo ? 'Excluindo...' : 'Confirmar Exclusão' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, watchEffect, onMounted } from 'vue'
import { Plus, Pencil, Trash2, X, Shield, Building2, Search, Power, PowerOff } from 'lucide-vue-next'
import api from '@/servicos/api'
import { useNotificacao } from '@/composables/useNotificacao'
import { useCentrosCusto } from '@/composables/useCentrosCusto'
import PaginacaoControles from '@/paginas/PaginacaoControles.vue'

const { centros, carregar } = useCentrosCusto()
const { sucesso, erro } = useNotificacao()

const carregando = ref(false)

// Formata números com separador de milhar no padrão brasileiro
function formatNumero(valor) {
  return Number(valor ?? 0).toLocaleString('pt-BR')
}

// ===== Resumo =====
const totalAtivos   = computed(() => centros.value.filter((c) => c.ativo).length)
const totalInativos = computed(() => centros.value.filter((c) => !c.ativo).length)

// ===== Filtros =====
const busca = ref('')
const filtroStatus = ref('todos')

const centrosFiltrados = computed(() => {
  const termo = busca.value.trim().toLowerCase()
  return centros.value.filter((c) => {
    if (filtroStatus.value === 'ativos' && !c.ativo) return false
    if (filtroStatus.value === 'inativos' && c.ativo) return false
    if (!termo) return true
    return c.codigo.toLowerCase().includes(termo) || c.nome.toLowerCase().includes(termo)
  })
})

function limparFiltros() {
  busca.value = ''
  filtroStatus.value = 'todos'
}

// ===== Paginação =====
const paginaAtual = ref(1)
const porPagina = ref(10)

const totalPaginas = computed(() =>
  Math.max(1, Math.ceil(centrosFiltrados.value.length / porPagina.value))
)

const centrosPaginados = computed(() => {
  const inicio = (paginaAtual.value - 1) * porPagina.value
  return centrosFiltrados.value.slice(inicio, inicio + porPagina.value)
})

watch([busca, filtroStatus], () => {
  paginaAtual.value = 1
})

// evita ficar numa página vazia se a lista encolher (ex: exclusão do último item da página)
watchEffect(() => {
  if (paginaAtual.value > totalPaginas.value) {
    paginaAtual.value = totalPaginas.value
  }
})

// ===== Criar / editar =====
const modalAberto = ref(false)
const editandoId = ref(null)
const salvando = ref(false)
const erros = ref({})
const form = reactive({ codigo: '', nome: '', ativo: true })

function abrirNovo() {
  editandoId.value = null
  Object.assign(form, { codigo: '', nome: '', ativo: true })
  erros.value = {}
  modalAberto.value = true
}

function abrirEdicao(c) {
  editandoId.value = c.id_centro_custo
  Object.assign(form, { codigo: c.codigo, nome: c.nome, ativo: !!c.ativo })
  erros.value = {}
  modalAberto.value = true
}

function fecharModal() {
  modalAberto.value = false
}

async function salvar() {
  if (salvando.value) return
  salvando.value = true
  erros.value = {}
  try {
    const payload = { codigo: form.codigo.trim(), nome: form.nome.trim() }
    if (editandoId.value) {
      payload.ativo = form.ativo
      await api.put(`/centros-custo/${editandoId.value}`, payload)
      sucesso('Centro de custo atualizado com sucesso.')
    } else {
      await api.post('/centros-custo', payload)
      sucesso('Centro de custo criado com sucesso.')
    }
    await carregar(true)
    fecharModal()
  } catch (e) {
    if (e.response?.status === 422) {
      erros.value = e.response.data.errors ?? {}
    } else {
      erro(e.response?.data?.message || 'Erro ao salvar centro de custo.')
    }
  } finally {
    salvando.value = false
  }
}

// ===== Ativar / desativar =====
async function alternarAtivo(c) {
  try {
    await api.put(`/centros-custo/${c.id_centro_custo}`, {
      codigo: c.codigo,
      nome: c.nome,
      ativo: !c.ativo,
    })
    sucesso(c.ativo ? 'Centro de custo desativado.' : 'Centro de custo ativado.')
    await carregar(true)
  } catch (e) {
    erro(e.response?.data?.message || 'Erro ao alterar o status.')
  }
}

// ===== Excluir =====
const modalExcluirAberto = ref(false)
const centroParaExcluir = ref(null)
const excluindo = ref(false)

function abrirExclusao(c) {
  centroParaExcluir.value = c
  modalExcluirAberto.value = true
}

async function excluir() {
  excluindo.value = true
  try {
    await api.delete(`/centros-custo/${centroParaExcluir.value.id_centro_custo}`)
    sucesso('Centro de custo excluído com sucesso.')
    modalExcluirAberto.value = false
    await carregar(true)
  } catch (e) {
    erro(e.response?.data?.message || 'Erro ao excluir centro de custo.')
    modalExcluirAberto.value = false
  } finally {
    excluindo.value = false
  }
}

onMounted(async () => {
  carregando.value = true
  try {
    await carregar(true)
  } catch (e) {
    console.error(e)
    erro('Não foi possível carregar os centros de custo.')
  } finally {
    carregando.value = false
  }
})
</script>