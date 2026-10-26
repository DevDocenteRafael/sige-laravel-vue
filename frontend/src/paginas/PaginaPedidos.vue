<template>
  <div class="p-6 min-h-screen bg-white dark:bg-black text-slate-900 dark:text-white">

    <!-- ===================== LISTA ===================== -->
    <template v-if="!detalhe">
      <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-3 mb-6">
        <div>
          <h1 class="text-2xl font-bold">Pedidos de Compra</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400">Pedidos no layout do MXM, usados na conferência do recebimento</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button :class="botaoSecundario" @click="exportar()">
            <Download :size="16" /> Exportar planilha
          </button>
          <button v-if="autenticacao.podeCadastrar" :class="botaoSecundario" @click="abrirImportacao">
            <Upload :size="16" /> Importar
          </button>
          <button v-if="autenticacao.podeCadastrar" :class="botaoPrimario" @click="abrirNovo">
            <Plus :size="16" /> Novo pedido
          </button>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <div class="relative flex-1 max-w-md">
          <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
          <input
            v-model="busca"
            type="text"
            placeholder="Buscar por número ou fornecedor"
            :class="[campo, 'w-full pl-9']"
          />
        </div>
        <select v-model="filtroStatus" :class="campo">
          <option value="">Todos os status</option>
          <option value="aberto">Aberto</option>
          <option value="parcial">Parcial</option>
          <option value="concluido">Concluído</option>
          <option value="cancelado">Cancelado</option>
        </select>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 overflow-x-auto">
        <p v-if="carregandoLista" class="text-sm text-slate-500 dark:text-slate-400 py-6 text-center">Carregando pedidos...</p>
        <p v-else-if="pedidosFiltrados.length === 0" class="text-sm text-slate-500 dark:text-slate-400 py-6 text-center">
          Nenhum pedido encontrado.
        </p>
        <table v-else class="w-full text-sm">
          <thead>
            <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <th class="text-left pb-3 font-medium">Pedido</th>
              <th class="text-left pb-3 font-medium">Fornecedor</th>
              <th class="text-left pb-3 font-medium">Emissão</th>
              <th class="text-left pb-3 font-medium">Itens</th>
              <th class="text-left pb-3 font-medium">Recebido</th>
              <th class="text-left pb-3 font-medium">Valor</th>
              <th class="text-left pb-3 font-medium">Status</th>
              <th class="text-left pb-3 font-medium">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr v-for="p in pedidosFiltrados" :key="p.id_pedido" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
              <td class="py-3 font-medium">{{ p.numero }}</td>
              <td class="py-3 text-slate-600 dark:text-slate-300">{{ p.fornecedor_nome || '—' }}</td>
              <td class="py-3 text-slate-500 dark:text-slate-400">{{ formatarData(p.data_emissao) }}</td>
              <td class="py-3 text-slate-500 dark:text-slate-400">{{ p.itens_count }}</td>
              <td class="py-3 text-slate-500 dark:text-slate-400">
                {{ formatarNumero(p.qtd_recebida) }} / {{ formatarNumero(p.qtd_pedida) }}
              </td>
              <td class="py-3 text-slate-500 dark:text-slate-400">{{ formatarMoeda(p.valor_total) }}</td>
              <td class="py-3">
                <span class="px-2 py-0.5 rounded text-xs font-bold text-white whitespace-nowrap" :class="classeStatus(p.status)">
                  {{ rotuloStatus(p.status) }}
                </span>
              </td>
              <td class="py-3">
                <div class="flex items-center gap-3">
                  <button class="text-blue-600 dark:text-blue-400 hover:text-blue-500 text-sm font-medium" @click="abrirDetalhe(p.id_pedido)">
                    Abrir
                  </button>
                  <button
                    class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white"
                    title="Exportar este pedido"
                    @click="exportar(p.id_pedido)"
                  >
                    <Download :size="16" />
                  </button>
                  <button
                    v-if="autenticacao.podeCadastrar"
                    class="text-slate-400 hover:text-red-500"
                    title="Excluir pedido"
                    @click="pedidoParaExcluir = p"
                  >
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- ===================== DETALHE ===================== -->
    <template v-else>
      <button class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white mb-4" @click="fecharDetalhe">
        <ArrowLeft :size="16" /> Voltar para os pedidos
      </button>

      <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-3 mb-6">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold">Pedido {{ detalhe.numero }}</h1>
            <span class="px-2 py-0.5 rounded text-xs font-bold text-white" :class="classeStatus(detalhe.status)">
              {{ rotuloStatus(detalhe.status) }}
            </span>
          </div>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {{ detalhe.fornecedor_nome || 'Fornecedor não informado' }}
            <span v-if="detalhe.fornecedor_cnpj"> · CNPJ {{ detalhe.fornecedor_cnpj }}</span>
            · Emissão {{ formatarData(detalhe.data_emissao) }}
          </p>
          <p v-if="detalhe.observacao" class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ detalhe.observacao }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button :class="botaoSecundario" @click="exportar(detalhe.id_pedido)">
            <Download :size="16" /> Exportar planilha
          </button>
          <button v-if="autenticacao.podeCadastrar && detalhe.editavel" :class="botaoSecundario" @click="abrirEdicaoCabecalho">
            <Pencil :size="16" /> Editar dados
          </button>
        </div>
      </div>

      <p
        v-if="autenticacao.podeCadastrar && !detalhe.editavel"
        class="mb-4 text-sm rounded-lg px-4 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"
      >
        Este pedido não pode mais ser editado: só pedidos abertos, sem nota fiscal vinculada e sem itens recebidos.
      </p>

      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <th class="text-left pb-3 font-medium">Código</th>
              <th class="text-left pb-3 font-medium">Descrição</th>
              <th class="text-left pb-3 font-medium">Un.</th>
              <th class="text-left pb-3 font-medium">Pedido</th>
              <th class="text-left pb-3 font-medium">Recebido</th>
              <th class="text-left pb-3 font-medium">Saldo</th>
              <th class="text-left pb-3 font-medium">Valor unit.</th>
              <th class="text-left pb-3 font-medium">Produto no SIGE</th>
              <th v-if="autenticacao.podeCadastrar && detalhe.editavel" class="text-left pb-3 font-medium">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr v-for="i in detalhe.itens" :key="i.id_pedido_item">
              <td class="py-3 font-mono text-xs">{{ i.sku }}</td>
              <td class="py-3 max-w-md">{{ i.descricao }}</td>
              <td class="py-3 text-slate-500 dark:text-slate-400">{{ i.unidade || '—' }}</td>
              <td class="py-3">{{ formatarNumero(i.quantidade_pedida) }}</td>
              <td class="py-3">{{ formatarNumero(i.quantidade_recebida) }}</td>
              <td class="py-3">{{ formatarNumero(i.saldo) }}</td>
              <td class="py-3">{{ formatarMoeda(i.valor_unitario) }}</td>
              <td class="py-3 text-xs">
                <span v-if="i.produto" class="text-emerald-600 dark:text-emerald-400">{{ i.produto.nome }}</span>
                <span v-else class="text-yellow-600 dark:text-yellow-400">Sem cadastro</span>
              </td>
              <td v-if="autenticacao.podeCadastrar && detalhe.editavel" class="py-3">
                <div class="flex items-center gap-3">
                  <button class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white" title="Editar item" @click="abrirItem(i)">
                    <Pencil :size="16" />
                  </button>
                  <button class="text-slate-400 hover:text-red-500" title="Remover item" @click="removerItem(i)">
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
          <button v-if="autenticacao.podeCadastrar && detalhe.editavel" :class="botaoSecundario" @click="abrirItem(null)">
            <Plus :size="16" /> Adicionar item
          </button>
          <span v-else />
          <p class="text-sm font-semibold">Total: {{ formatarMoeda(detalhe.valor_total) }}</p>
        </div>
      </div>
    </template>

    <!-- ===================== MODAL: NOVO PEDIDO / EDITAR DADOS ===================== -->
    <div v-if="modalPedido" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
          <h2 class="font-bold">{{ editandoCabecalho ? 'Editar dados do pedido' : 'Novo pedido' }}</h2>
          <button class="text-slate-500 hover:text-slate-900 dark:hover:text-white" @click="modalPedido = false"><X :size="18" /></button>
        </div>

        <div class="p-5 space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="block">
              <span :class="rotulo">Número do pedido *</span>
              <input v-model="form.numero" type="text" maxlength="30" :class="[campo, 'w-full']" />
            </label>
            <label class="block">
              <span :class="rotulo">Data de emissão</span>
              <input v-model="form.data_emissao" type="date" :class="[campo, 'w-full']" />
            </label>
            <label class="block">
              <span :class="rotulo">Fornecedor</span>
              <input v-model="form.fornecedor_nome" type="text" maxlength="150" :class="[campo, 'w-full']" />
            </label>
            <label class="block">
              <span :class="rotulo">CNPJ do fornecedor</span>
              <input v-model="form.fornecedor_cnpj" type="text" maxlength="18" placeholder="Só números ou com pontuação" :class="[campo, 'w-full']" />
            </label>
            <label class="block sm:col-span-2">
              <span :class="rotulo">Observação</span>
              <input v-model="form.observacao" type="text" maxlength="2000" :class="[campo, 'w-full']" />
            </label>
          </div>

          <div v-if="!editandoCabecalho">
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-sm font-semibold">Itens</h3>
              <button :class="botaoSecundario" @click="adicionarLinha"><Plus :size="14" /> Linha</button>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-slate-500 dark:text-slate-400">
                    <th class="text-left pb-2 font-medium">Código *</th>
                    <th class="text-left pb-2 font-medium">Descrição *</th>
                    <th class="text-left pb-2 font-medium">Un.</th>
                    <th class="text-left pb-2 font-medium">Qtde. *</th>
                    <th class="text-left pb-2 font-medium">Valor unit.</th>
                    <th class="pb-2" />
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(l, idx) in form.itens" :key="idx">
                    <td class="pr-2 pb-2"><input v-model="l.sku" type="text" maxlength="30" :class="[campo, 'w-40']" /></td>
                    <td class="pr-2 pb-2"><input v-model="l.descricao" type="text" maxlength="255" :class="[campo, 'w-full min-w-48']" /></td>
                    <td class="pr-2 pb-2"><input v-model="l.unidade" type="text" maxlength="10" :class="[campo, 'w-16']" /></td>
                    <td class="pr-2 pb-2"><input v-model="l.quantidade_pedida" type="number" min="0" step="0.01" :class="[campo, 'w-24']" /></td>
                    <td class="pr-2 pb-2"><input v-model="l.valor_unitario" type="number" min="0" step="0.01" :class="[campo, 'w-28']" /></td>
                    <td class="pb-2">
                      <button
                        v-if="form.itens.length > 1"
                        class="text-slate-400 hover:text-red-500"
                        title="Remover linha"
                        @click="form.itens.splice(idx, 1)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <ul v-if="errosForm.length" class="text-sm text-red-600 dark:text-red-400 list-disc pl-5 space-y-0.5">
            <li v-for="(e, idx) in errosForm" :key="idx">{{ e }}</li>
          </ul>
        </div>

        <div class="flex justify-end gap-2 px-5 py-4 border-t border-slate-200 dark:border-slate-800">
          <button :class="botaoSecundario" @click="modalPedido = false">Cancelar</button>
          <button :class="botaoPrimario" :disabled="salvando" @click="salvarPedido">
            {{ salvando ? 'Salvando...' : 'Salvar pedido' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ===================== MODAL: ITEM ===================== -->
    <div v-if="modalItem" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
          <h2 class="font-bold">{{ itemForm.id_pedido_item ? 'Editar item' : 'Adicionar item' }}</h2>
          <button class="text-slate-500 hover:text-slate-900 dark:hover:text-white" @click="modalItem = false"><X :size="18" /></button>
        </div>
        <div class="p-5 grid grid-cols-2 gap-3">
          <label class="block col-span-2 sm:col-span-1">
            <span :class="rotulo">Código *</span>
            <input v-model="itemForm.sku" type="text" maxlength="30" :class="[campo, 'w-full']" />
          </label>
          <label class="block col-span-2 sm:col-span-1">
            <span :class="rotulo">Unidade</span>
            <input v-model="itemForm.unidade" type="text" maxlength="10" :class="[campo, 'w-full']" />
          </label>
          <label class="block col-span-2">
            <span :class="rotulo">Descrição *</span>
            <input v-model="itemForm.descricao" type="text" maxlength="255" :class="[campo, 'w-full']" />
          </label>
          <label class="block">
            <span :class="rotulo">Quantidade *</span>
            <input v-model="itemForm.quantidade_pedida" type="number" min="0" step="0.01" :class="[campo, 'w-full']" />
          </label>
          <label class="block">
            <span :class="rotulo">Valor unitário</span>
            <input v-model="itemForm.valor_unitario" type="number" min="0" step="0.01" :class="[campo, 'w-full']" />
          </label>
          <ul v-if="errosForm.length" class="col-span-2 text-sm text-red-600 dark:text-red-400 list-disc pl-5">
            <li v-for="(e, idx) in errosForm" :key="idx">{{ e }}</li>
          </ul>
        </div>
        <div class="flex justify-end gap-2 px-5 py-4 border-t border-slate-200 dark:border-slate-800">
          <button :class="botaoSecundario" @click="modalItem = false">Cancelar</button>
          <button :class="botaoPrimario" :disabled="salvando" @click="salvarItem">Salvar item</button>
        </div>
      </div>
    </div>

    <!-- ===================== MODAL: IMPORTAR ===================== -->
    <div v-if="modalImportar" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
          <h2 class="font-bold">Importar pedido</h2>
          <button class="text-slate-500 hover:text-slate-900 dark:hover:text-white" @click="modalImportar = false"><X :size="18" /></button>
        </div>
        <div class="p-5 space-y-3">
          <p class="text-sm text-slate-500 dark:text-slate-400">
            Envie o PDF do pedido do MXM ou uma planilha (.xlsx, .xls ou .csv) no layout do SIGE.
            Planilhas são conferidas antes de gravar.
          </p>
          <input
            ref="campoArquivo"
            type="file"
            accept=".pdf,.xlsx,.xls,.csv"
            class="block w-full text-sm text-slate-600 dark:text-slate-300"
            @change="aoEscolherArquivo"
          />

          <div v-if="previa" class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm">
            <p class="font-medium text-emerald-700 dark:text-emerald-300">
              Pedido {{ previa.numero }}: {{ previa.total_itens }} item(ns), {{ formatarMoeda(previa.valor_total) }}.
            </p>
            <p v-if="previa.produtos_sem_cadastro.length" class="text-yellow-700 dark:text-yellow-400 mt-1">
              {{ previa.produtos_sem_cadastro.length }} código(s) ainda sem produto cadastrado no SIGE.
            </p>
          </div>

          <ul v-if="errosImportacao.length" class="text-sm text-red-600 dark:text-red-400 list-disc pl-5 space-y-0.5 max-h-48 overflow-y-auto">
            <li v-for="(e, idx) in errosImportacao" :key="idx">{{ e }}</li>
          </ul>
        </div>
        <div class="flex justify-end gap-2 px-5 py-4 border-t border-slate-200 dark:border-slate-800">
          <button :class="botaoSecundario" @click="modalImportar = false">Cancelar</button>
          <button :class="botaoPrimario" :disabled="!arquivo || importando || errosImportacao.length > 0" @click="confirmarImportacao">
            {{ importando ? 'Importando...' : previa ? 'Confirmar importação' : 'Importar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ===================== MODAL: EXCLUIR ===================== -->
    <div v-if="pedidoParaExcluir" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl w-full max-w-md p-5">
        <h2 class="font-bold mb-2">Excluir pedido {{ pedidoParaExcluir.numero }}?</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
          Os itens do pedido também serão excluídos. Pedidos com itens já recebidos não podem ser excluídos.
        </p>
        <div class="flex justify-end gap-2">
          <button :class="botaoSecundario" @click="pedidoParaExcluir = null">Cancelar</button>
          <button class="px-4 py-2 rounded-lg text-sm font-medium bg-red-600 hover:bg-red-500 text-white" @click="excluirPedido">Excluir</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Plus, Upload, Download, Search, Trash2, Pencil, X, ArrowLeft } from 'lucide-vue-next'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import { useNotificacao } from '@/composables/useNotificacao'
import { formatarData } from '@/utils/date'

const autenticacao      = useAutenticacaoStore()
const { sucesso, erro } = useNotificacao()

const campo           = 'bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500'
const rotulo          = 'block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1'
const botaoPrimario   = 'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 hover:bg-blue-500 text-white transition disabled:opacity-50 disabled:cursor-not-allowed'
const botaoSecundario = 'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition'

// ===== Estado =====
const lista            = ref([])
const carregandoLista  = ref(false)
const detalhe          = ref(null)
const busca            = ref('')
const filtroStatus     = ref('')
const pedidoParaExcluir = ref(null)

const modalPedido       = ref(false)
const editandoCabecalho = ref(false)
const salvando          = ref(false)
const errosForm         = ref([])
const form              = ref(formVazio())

const modalItem = ref(false)
const itemForm  = ref({})

const modalImportar   = ref(false)
const arquivo         = ref(null)
const campoArquivo    = ref(null)
const previa          = ref(null)
const errosImportacao = ref([])
const importando      = ref(false)

function formVazio() {
  return {
    numero: '', fornecedor_nome: '', fornecedor_cnpj: '', data_emissao: '', observacao: '',
    itens: [linhaVazia()],
  }
}
function linhaVazia() {
  return { sku: '', descricao: '', unidade: '', quantidade_pedida: '', valor_unitario: '' }
}

// ===== Formatação =====
function formatarNumero(v) {
  return Number(v ?? 0).toLocaleString('pt-BR', { maximumFractionDigits: 2 })
}
function formatarMoeda(v) {
  return Number(v ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}
const STATUS = {
  aberto:    { rotulo: 'Aberto',    classe: 'bg-blue-600' },
  parcial:   { rotulo: 'Parcial',   classe: 'bg-yellow-600' },
  concluido: { rotulo: 'Concluído', classe: 'bg-emerald-600' },
  cancelado: { rotulo: 'Cancelado', classe: 'bg-slate-500' },
}
const rotuloStatus = (s) => STATUS[s]?.rotulo ?? s
const classeStatus = (s) => STATUS[s]?.classe ?? 'bg-slate-500'

// Junta as mensagens de validação do Laravel (errors) ou usa a mensagem geral
function mensagensDeErro(e, padrao) {
  const data = e.response?.data
  if (data?.erros?.length) return data.erros
  if (data?.errors) return Object.values(data.errors).flat()
  return [data?.message || padrao]
}

// ===== Lista =====
const pedidosFiltrados = computed(() => {
  const termo = busca.value.trim().toLowerCase()
  return lista.value.filter((p) => {
    if (filtroStatus.value && p.status !== filtroStatus.value) return false
    if (!termo) return true
    return String(p.numero).toLowerCase().includes(termo)
      || String(p.fornecedor_nome ?? '').toLowerCase().includes(termo)
  })
})

async function carregarLista() {
  carregandoLista.value = true
  try {
    lista.value = (await api.get('/pedidos')).data
  } catch (e) {
    console.error(e)
    erro('Não foi possível carregar os pedidos.')
  } finally {
    carregandoLista.value = false
  }
}

async function abrirDetalhe(id) {
  try {
    detalhe.value = (await api.get(`/pedidos/${id}`)).data
  } catch (e) {
    console.error(e)
    erro(mensagensDeErro(e, 'Não foi possível abrir o pedido.')[0])
  }
}

function fecharDetalhe() {
  detalhe.value = null
  carregarLista()
}

// ===== Exportação =====
async function exportar(id) {
  try {
    const resposta = await api.get(id ? `/pedidos/${id}/exportar` : '/pedidos/exportar', { responseType: 'blob' })
    const url = URL.createObjectURL(resposta.data)
    const link = document.createElement('a')
    link.href = url
    link.download = id ? `pedido-${detalhe.value?.numero ?? id}.xlsx` : 'pedidos.xlsx'
    link.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    console.error(e)
    erro('Não foi possível exportar a planilha.')
  }
}

// ===== Novo pedido / editar dados =====
function abrirNovo() {
  editandoCabecalho.value = false
  form.value = formVazio()
  errosForm.value = []
  modalPedido.value = true
}

function abrirEdicaoCabecalho() {
  editandoCabecalho.value = true
  const d = detalhe.value
  form.value = {
    numero: d.numero,
    fornecedor_nome: d.fornecedor_nome ?? '',
    fornecedor_cnpj: d.fornecedor_cnpj ?? '',
    data_emissao: d.data_emissao ? String(d.data_emissao).slice(0, 10) : '',
    observacao: d.observacao ?? '',
    itens: [],
  }
  errosForm.value = []
  modalPedido.value = true
}

function adicionarLinha() {
  form.value.itens.push(linhaVazia())
}

function validarLocal() {
  const erros = []
  if (!form.value.numero.trim()) erros.push('Informe o número do pedido.')
  if (!editandoCabecalho.value) {
    const vistos = new Set()
    form.value.itens.forEach((l, i) => {
      const n = i + 1
      if (!l.sku.trim()) erros.push(`Item ${n}: informe o código.`)
      if (!l.descricao.trim()) erros.push(`Item ${n}: informe a descrição.`)
      if (!(Number(l.quantidade_pedida) > 0)) erros.push(`Item ${n}: a quantidade precisa ser maior que zero.`)
      if (l.sku.trim() && vistos.has(l.sku.trim())) erros.push(`Item ${n}: código repetido.`)
      vistos.add(l.sku.trim())
    })
  }
  return erros
}

async function salvarPedido() {
  errosForm.value = validarLocal()
  if (errosForm.value.length) return

  salvando.value = true
  try {
    const base = {
      numero: form.value.numero.trim(),
      fornecedor_nome: form.value.fornecedor_nome.trim() || null,
      fornecedor_cnpj: form.value.fornecedor_cnpj.trim() || null,
      data_emissao: form.value.data_emissao || null,
      observacao: form.value.observacao.trim() || null,
    }

    if (editandoCabecalho.value) {
      detalhe.value = (await api.put(`/pedidos/${detalhe.value.id_pedido}`, base)).data
      sucesso('Dados do pedido atualizados.')
    } else {
      const itens = form.value.itens.map((l) => ({
        sku: l.sku.trim(),
        descricao: l.descricao.trim(),
        unidade: l.unidade.trim() || null,
        quantidade_pedida: Number(l.quantidade_pedida),
        valor_unitario: l.valor_unitario === '' ? 0 : Number(l.valor_unitario),
      }))
      const criado = (await api.post('/pedidos', { ...base, itens })).data
      sucesso(`Pedido ${criado.numero} cadastrado.`)
      await carregarLista()
      detalhe.value = criado
    }
    modalPedido.value = false
  } catch (e) {
    console.error(e)
    errosForm.value = mensagensDeErro(e, 'Erro ao salvar o pedido.')
  } finally {
    salvando.value = false
  }
}

// ===== Itens do pedido =====
function abrirItem(item) {
  itemForm.value = item
    ? {
        id_pedido_item: item.id_pedido_item,
        sku: item.sku,
        descricao: item.descricao,
        unidade: item.unidade ?? '',
        quantidade_pedida: Number(item.quantidade_pedida),
        valor_unitario: Number(item.valor_unitario),
      }
    : { sku: '', descricao: '', unidade: '', quantidade_pedida: '', valor_unitario: '' }
  errosForm.value = []
  modalItem.value = true
}

async function salvarItem() {
  const f = itemForm.value
  const erros = []
  if (!String(f.sku).trim()) erros.push('Informe o código do item.')
  if (!String(f.descricao).trim()) erros.push('Informe a descrição do item.')
  if (!(Number(f.quantidade_pedida) > 0)) erros.push('A quantidade precisa ser maior que zero.')
  errosForm.value = erros
  if (erros.length) return

  const corpo = {
    sku: String(f.sku).trim(),
    descricao: String(f.descricao).trim(),
    unidade: String(f.unidade ?? '').trim() || null,
    quantidade_pedida: Number(f.quantidade_pedida),
    valor_unitario: f.valor_unitario === '' ? 0 : Number(f.valor_unitario),
  }

  salvando.value = true
  try {
    const id = detalhe.value.id_pedido
    detalhe.value = f.id_pedido_item
      ? (await api.put(`/pedidos/${id}/itens/${f.id_pedido_item}`, corpo)).data
      : (await api.post(`/pedidos/${id}/itens`, corpo)).data
    sucesso('Item salvo.')
    modalItem.value = false
  } catch (e) {
    console.error(e)
    errosForm.value = mensagensDeErro(e, 'Erro ao salvar o item.')
  } finally {
    salvando.value = false
  }
}

async function removerItem(item) {
  try {
    detalhe.value = (await api.delete(`/pedidos/${detalhe.value.id_pedido}/itens/${item.id_pedido_item}`)).data
    sucesso('Item removido.')
  } catch (e) {
    console.error(e)
    erro(mensagensDeErro(e, 'Erro ao remover o item.')[0])
  }
}

// ===== Excluir pedido =====
async function excluirPedido() {
  const p = pedidoParaExcluir.value
  try {
    await api.delete(`/pedidos/${p.id_pedido}`)
    sucesso(`Pedido ${p.numero} excluído.`)
    pedidoParaExcluir.value = null
    await carregarLista()
  } catch (e) {
    console.error(e)
    pedidoParaExcluir.value = null
    erro(mensagensDeErro(e, 'Erro ao excluir o pedido.')[0])
  }
}

// ===== Importação (PDF do MXM direto; planilha com prévia) =====
function abrirImportacao() {
  arquivo.value = null
  previa.value = null
  errosImportacao.value = []
  modalImportar.value = true
}

const ehPdf = (f) => f && /\.pdf$/i.test(f.name)

async function aoEscolherArquivo(e) {
  arquivo.value = e.target.files?.[0] ?? null
  previa.value = null
  errosImportacao.value = []

  if (!arquivo.value || ehPdf(arquivo.value)) return

  // Planilha: valida antes de gravar
  const dados = new FormData()
  dados.append('arquivo', arquivo.value)
  dados.append('previa', '1')
  importando.value = true
  try {
    previa.value = (await api.post('/pedidos/importar-planilha', dados)).data
  } catch (err) {
    console.error(err)
    errosImportacao.value = mensagensDeErro(err, 'Não foi possível ler a planilha.')
  } finally {
    importando.value = false
  }
}

async function confirmarImportacao() {
  if (!arquivo.value) return
  const dados = new FormData()
  dados.append('arquivo', arquivo.value)
  importando.value = true
  try {
    const rota = ehPdf(arquivo.value) ? '/pedidos/importar' : '/pedidos/importar-planilha'
    const resposta = (await api.post(rota, dados)).data
    sucesso(`Pedido ${resposta.numero} importado (${resposta.total_itens} itens).`)
    if (resposta.avisos?.length) erro(resposta.avisos.join(' '))
    modalImportar.value = false
    await carregarLista()
  } catch (err) {
    console.error(err)
    errosImportacao.value = mensagensDeErro(err, 'Erro ao importar o pedido.')
  } finally {
    importando.value = false
  }
}

onMounted(carregarLista)
</script>
