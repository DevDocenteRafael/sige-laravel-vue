<template>
  <div class="p-6 min-h-screen bg-white dark:bg-black text-slate-900 dark:text-white">

    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gerenciamento de Usuários</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Cadastre e gerencie usuários do sistema</p>
      </div>
      <button
        class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium"
        @click="abrirModalNovoUsuario"
      >
        <Plus :size="18" />
        Novo Usuário
      </button>
    </div>

    <div class="relative mb-6">
      <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
      <input
        v-model="busca"
        type="text"
        placeholder="Buscar por nome ou email..."
        class="w-full bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg pl-10 pr-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500 transition"
      />
    </div>

    <div v-if="carregando" class="text-center py-12 text-slate-500 dark:text-slate-400">
      Carregando usuários...
    </div>

    <div v-else-if="usuariosFiltrados.length === 0" class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-16 text-center">
      <UserX class="mx-auto mb-4 text-slate-400 dark:text-slate-600" :size="48" />
      <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Nenhum usuário encontrado</h2>
      <p class="text-slate-500 dark:text-slate-400">
        {{ busca ? 'Tente buscar por outro nome ou email.' : 'Crie o primeiro usuário do sistema.' }}
      </p>
    </div>

    <div v-else class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 overflow-hidden mb-6">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
            <th class="text-left px-6 py-4 font-medium">Nome</th>
            <th class="text-left px-6 py-4 font-medium">Email</th>
            <th class="text-left px-6 py-4 font-medium">Tipo</th>
            <th class="text-left px-6 py-4 font-medium">Status</th>
            <th class="text-left px-6 py-4 font-medium">Data de Cadastro</th>
            <th class="text-right px-6 py-4 font-medium">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
          <tr
            v-for="usuario in usuariosFiltrados"
            :key="usuario.id"
            class="hover:bg-slate-100 dark:hover:bg-slate-800/50 transition"
            :class="{ 'opacity-50': !usuario.ativo }"
          >
            <td class="px-6 py-4 text-slate-900 dark:text-white font-medium">{{ usuario.name }}</td>
            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ usuario.email }}</td>
            <td class="px-6 py-4">
              <span :class="corDoPerfil[usuario.perfil]" class="px-3 py-1 rounded-md text-xs font-semibold">
                {{ nomeDoPerfil[usuario.perfil] || usuario.perfil }}
              </span>
            </td>
            <td class="px-6 py-4">
              <span
                :class="usuario.ativo
                  ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400'
                  : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'"
                class="px-3 py-1 rounded-md text-xs font-semibold"
              >
                {{ usuario.ativo ? 'Ativo' : 'Inativo' }}
              </span>
            </td>
            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ formatarData(usuario.created_at) }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-2">
                <button
                  class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition"
                  title="Editar"
                  @click="abrirModalEdicao(usuario)"
                >
                  <Pencil :size="16" />
                </button>
                <button
                  v-if="usuario.ativo"
                  class="p-2 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition disabled:opacity-30 disabled:cursor-not-allowed"
                  title="Inativar"
                  :disabled="usuario.id === autenticacao.usuario?.id"
                  @click="abrirConfirmacaoStatus(usuario)"
                >
                  <UserX :size="16" />
                </button>
                <button
                  v-else
                  class="p-2 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50 transition"
                  title="Ativar"
                  @click="abrirConfirmacaoStatus(usuario)"
                >
                  <UserCheck :size="16" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Total de Usuários</p>
        <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ usuarios.length }}</p>
      </div>
      <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Administradores</p>
        <p class="text-3xl font-bold text-blue-600 dark:text-blue-500">{{ totalAdministradores }}</p>
      </div>
      <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5">
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">Usuários Inativos</p>
        <p class="text-3xl font-bold text-amber-600 dark:text-amber-500">{{ totalInativos }}</p>
      </div>
    </div>

    <ModalUsuario
      v-if="modalAberto"
      :usuario="usuarioSelecionado"
      @fechar="fecharModal"
      @salvo="aoSalvar"
    />

    <ModalConfirmacao
      v-if="usuarioParaAlternarStatus"
      :titulo="usuarioParaAlternarStatus.ativo ? 'Inativar usuário' : 'Ativar usuário'"
      :subtitulo="usuarioParaAlternarStatus.ativo ? 'O usuário perderá o acesso ao sistema' : 'O usuário voltará a ter acesso ao sistema'"
      :mensagem="`Deseja ${usuarioParaAlternarStatus.ativo ? 'inativar' : 'ativar'} o usuário &quot;${usuarioParaAlternarStatus.name}&quot;?`"
      :variante="usuarioParaAlternarStatus.ativo ? 'perigo' : 'aviso'"
      :texto-confirmar="usuarioParaAlternarStatus.ativo ? 'Inativar' : 'Ativar'"
      texto-carregando="Salvando..."
      :carregando="alternandoStatus"
      @cancelar="fecharConfirmacaoStatus"
      @confirmar="confirmarAlternarStatus"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Plus, Search, UserX, UserCheck, Pencil } from 'lucide-vue-next'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import ModalUsuario from '@/componentes/ui/ModalUsuario.vue'
import ModalConfirmacao from '@/componentes/ui/ModalConfirmacao.vue'
import { formatarData } from '@/utils/date'
import { useNotificacao } from '@/composables/useNotificacao'

const { erro: notificarErro } = useNotificacao()

const autenticacao = useAutenticacaoStore()

const usuarios                  = ref([])
const carregando                = ref(false)
const busca                     = ref('')
const modalAberto               = ref(false)
const usuarioSelecionado        = ref(null)
const usuarioParaAlternarStatus = ref(null)
const alternandoStatus          = ref(false)

const corDoPerfil = {
  admin:        'bg-blue-600 text-white',
  operador:     'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200',
  visualizador: 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200',
}

const nomeDoPerfil = {
  admin:        'Administrador',
  operador:     'Operador',
  visualizador: 'Visualizador',
}

const usuariosFiltrados = computed(() => {
  const termo = busca.value.trim().toLowerCase()
  if (!termo) return usuarios.value
  return usuarios.value.filter(u =>
    u.name?.toLowerCase().includes(termo) ||
    u.email?.toLowerCase().includes(termo)
  )
})

const totalAdministradores = computed(() =>
  usuarios.value.filter(u => u.perfil === 'admin').length
)

const totalInativos = computed(() =>
  usuarios.value.filter(u => !u.ativo).length
)

async function carregarUsuarios() {
  carregando.value = true
  try {
    const resposta = await api.get('/usuarios')
    usuarios.value = resposta.data
  } catch {
    notificarErro('Erro ao carregar usuários.')
  } finally {
    carregando.value = false
  }
}

function abrirModalNovoUsuario() {
  usuarioSelecionado.value = null
  modalAberto.value = true
}

function abrirModalEdicao(usuario) {
  usuarioSelecionado.value = usuario
  modalAberto.value = true
}

function fecharModal() {
  modalAberto.value = false
  usuarioSelecionado.value = null
}

async function aoSalvar() {
  fecharModal()
  await carregarUsuarios()
}

function abrirConfirmacaoStatus(usuario) {
  usuarioParaAlternarStatus.value = usuario
}

function fecharConfirmacaoStatus() {
  usuarioParaAlternarStatus.value = null
}

async function confirmarAlternarStatus() {
  const usuario = usuarioParaAlternarStatus.value
  if (!usuario) return

  const acao = usuario.ativo ? 'inativar' : 'ativar'
  alternandoStatus.value = true

  try {
    await api.patch(`/usuarios/${usuario.id}/status`, { ativo: !usuario.ativo })
    await carregarUsuarios()
    fecharConfirmacaoStatus()
  } catch (erro) {
    notificarErro(erro.response?.data?.mensagem || erro.response?.data?.message || `Erro ao ${acao} usuário.`)
  } finally {
    alternandoStatus.value = false
  }
}

onMounted(carregarUsuarios)
</script>