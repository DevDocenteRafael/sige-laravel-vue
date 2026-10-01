<template>
  <div class="p-6 min-h-screen bg-white dark:bg-black text-slate-900 dark:text-white">

    <!-- Cabeçalho -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Lotes</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Gerenciamento de lotes por tabs</p>
      </div>
      <div class="flex items-center gap-2">
        <template v-if="modoSelecaoLotes">
          <span class="text-sm text-slate-500 dark:text-slate-400">{{ lotesSelecionados.size }} selecionado(s)</span>
          <button
            class="flex items-center gap-2 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 px-4 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
            :disabled="lotesSelecionados.size === 0"
            @click="modalExcluirVariosAberto = true"
          >
            <Trash2 :size="16" />
            Excluir Selecionados
          </button>
          <button
            class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm font-medium"
            @click="alternarModoSelecao"
          >
            Cancelar
          </button>
        </template>
        <template v-else>
          <button
            v-if="lotes.length > 0"
            class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm font-medium"
            @click="alternarModoSelecao"
          >
            Selecionar
          </button>
          <button
            v-if="autenticacao.podeCadastrar"
            class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium"
            @click="iniciarCriacaoLote"
          >
            <Plus :size="18" />
            Novo Lote
          </button>
        </template>
      </div>
    </div>

    <!-- Carregando -->
    <div v-if="carregando" class="text-center py-12 text-slate-500 dark:text-slate-400">
      Carregando lotes...
    </div>

    <!-- Sem lotes -->
    <div v-else-if="lotes.length === 0" class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-16 text-center">
      <PackageMinus class="mx-auto mb-4 text-slate-400 dark:text-slate-600" :size="48" />
      <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Nenhum lote cadastrado</h2>
      <p class="text-slate-500 dark:text-slate-400 mb-6">Crie seu primeiro lote para começar a gerenciar os itens.</p>
      <button
        class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition font-medium flex items-center justify-center gap-2"
        @click="iniciarCriacaoLote"
      >
        <Plus :size="18" />
        Criar Primeiro Lote
      </button>
    </div>

    <!-- TABS DE LOTES -->
    <div v-else class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">

      <!-- Abas -->
      <div class="flex items-center gap-1 px-4 pt-4 border-b border-slate-200 dark:border-slate-800 overflow-x-auto">
        <button
          v-for="lote in lotes"
          :key="lote.id_lote"
          :data-id-lote="lote.id_lote"
          :class="tabAtiva === lote.id_lote
            ? 'bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white border-b-2 border-blue-500'
            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800'"
          class="px-4 py-2 rounded-t-lg text-sm font-medium transition whitespace-nowrap flex items-center gap-1.5"
          @click="aoClicarTab(lote.id_lote)"
          @dragover.prevent
          @drop="aoSoltarNaTab(lote.id_lote)"
        >
          <input
            v-if="modoSelecaoLotes"
            type="checkbox"
            :checked="lotesSelecionados.has(lote.id_lote)"
            class="pointer-events-none"
          />
          {{ lote.numero_lote }}
        </button>
      </div>

      <!-- Conteúdo da tab ativa -->
      <div v-if="loteAtivo" class="p-6">

        <!-- Cabeçalho do lote -->
        <div class="flex justify-between items-start mb-6">
          <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ loteAtivo.numero_lote }}</h2>
            <p v-if="loteAtivo.descricao" class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
              {{ loteAtivo.descricao }}
            </p>
            <div class="flex items-center gap-4 mt-1 text-slate-500 dark:text-slate-400 text-sm">
              <span class="flex items-center gap-1">
                <Calendar :size="14" />
                Criado em: {{ formatarData(loteAtivo.data_entrada) }}
              </span>
              <span class="flex items-center gap-1">
                <Package :size="14" />
                {{ formatNumero(loteAtivo.itens?.length || 0) }} itens
              </span>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <template v-if="modoSelecaoItens">
              <span class="text-sm text-slate-500 dark:text-slate-400">{{ itensSelecionados.size }} selecionado(s)</span>
              <button
                class="flex items-center gap-2 border border-purple-300 dark:border-purple-700 text-purple-600 dark:text-purple-400 px-4 py-2 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-900/20 transition text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                :disabled="itensSelecionados.size === 0"
                @click="iniciarTransferenciaVarios"
              >
                <ArrowRightLeft :size="16" />
                Transferir Selecionados
              </button>
              <button
                class="flex items-center gap-2 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 px-4 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
                :disabled="itensSelecionados.size === 0"
                @click="modalExcluirItensAberto = true"
              >
                <Trash2 :size="16" />
                Excluir Selecionados
              </button>
              <button
                class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm font-medium"
                @click="alternarModoSelecaoItens"
              >
                Cancelar
              </button>
            </template>
            <template v-else>
              <button
                v-if="loteAtivo.itens?.length > 0"
                class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm font-medium"
                @click="alternarModoSelecaoItens"
              >
                Selecionar
              </button>
              <button
                class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium"
                @click="iniciarAdicaoItem"
              >
                <Plus :size="16" />
                Adicionar Item
              </button>
              <button
                class="flex items-center gap-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 px-4 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm font-medium"
                @click="iniciarEdicaoLote"
              >
                <Pencil :size="16" />
                Editar Lote
              </button>
              <button
                class="flex items-center gap-2 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 px-4 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition text-sm font-medium"
                @click="iniciarExclusaoLote"
              >
                <Trash2 :size="16" />
                Excluir Lote
              </button>
            </template>
          </div>
        </div>

        <!-- Filtro por centro de custo -->
        <div v-if="loteAtivo.itens?.length > 0" class="flex items-center gap-3 mb-4">
          <label class="text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">Centro de custo</label>
          <div class="w-72">
            <select
              v-model="filtroCentro"
              class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
            >
              <option :value="null">Todos</option>
              <option value="sem">Sem centro de custo</option>
              <option v-for="c in centros" :key="c.id_centro_custo" :value="c.id_centro_custo">
                {{ c.codigo }} - {{ c.nome }}
              </option>
            </select>
          </div>
          <span class="text-xs text-slate-400">{{ formatNumero(itensFiltrados.length) }} de {{ formatNumero(loteAtivo.itens.length) }} itens</span>
        </div>

        <!-- Sem itens -->
        <div v-if="!loteAtivo.itens || loteAtivo.itens.length === 0" class="text-center py-16">
          <Package class="mx-auto mb-3 text-slate-400 dark:text-slate-600" :size="40" />
          <p class="text-slate-500 dark:text-slate-500">Nenhum item neste lote</p>
        </div>

        <!-- Filtro sem resultado -->
        <div v-else-if="itensFiltrados.length === 0" class="text-center py-16">
          <Package class="mx-auto mb-3 text-slate-400 dark:text-slate-600" :size="40" />
          <p class="text-slate-500 dark:text-slate-500">Nenhum item com esse centro de custo neste lote</p>
        </div>

        <!-- Tabela de itens (arrastável com o mouse) -->
        <div
          v-else
          ref="tabelaRef"
          class="overflow-auto cursor-grab select-none"
          @mousedown="aoIniciar"
        >
          <table class="w-full text-sm">
            <thead>
              <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                <th v-if="modoSelecaoItens" class="text-left pb-3 font-medium w-6">
                  <input
                    ref="inputSelecionarTodosItens"
                    type="checkbox"
                    :checked="todosItensSelecionados"
                    class="cursor-pointer"
                    @change="alternarSelecaoTodosItens"
                  />
                </th>
                <th class="text-left pb-3 font-medium w-6"></th>
                <th class="text-left pb-3 font-medium">SKU</th>
                <th class="text-left pb-3 font-medium">Nome</th>
                <th class="text-left pb-3 font-medium">Qtd</th>
                <th class="text-left pb-3 font-medium">Validade</th>
                <th class="text-left pb-3 font-medium">Fornecedor</th>
                <th class="text-left pb-3 font-medium">Localização</th>
                <th class="text-left pb-3 font-medium">Centro de custo</th>
                <th class="text-left pb-3 font-medium">Prioridade</th>
                <th class="text-left pb-3 font-medium">Status</th>
                <th class="text-left pb-3 font-medium">Ações</th>
              </tr>
            </thead>
            <draggable
              :list="itensPaginados"
              :disabled="modoSelecaoItens || filtroCentro !== null"
              tag="tbody"
              item-key="id_item"
              handle=".drag-handle"
              animation="200"
              class="divide-y divide-slate-200 dark:divide-slate-800"
              @start="aoIniciarArraste"
              @end="aoTerminarArraste"
            >
              <template #item="{ element: item }">
                <tr
                  class="hover:bg-slate-100 dark:hover:bg-slate-800/50 transition cursor-pointer"
                  @click="modoSelecaoItens ? alternarSelecaoItem(item.id_item) : (itemSelecionado = item, modalDetalhesAberto = true)"
                >
                  <td v-if="modoSelecaoItens" class="py-3" @click.stop="alternarSelecaoItem(item.id_item)">
                    <input
                      type="checkbox"
                      :checked="itensSelecionados.has(item.id_item)"
                      class="pointer-events-none"
                    />
                  </td>
                  <td class="py-3" @click.stop>
                    <span class="drag-handle cursor-grab text-slate-400 dark:text-slate-600 hover:text-slate-600 dark:hover:text-slate-400 select-none">⠿</span>
                  </td>
                  <td class="py-3 text-slate-500 dark:text-slate-400">{{ item.produto?.sku || '—' }}</td>
                  <td class="py-3 text-slate-900 dark:text-white font-medium">{{ item.produto?.nome || '—' }}</td>

                  <td class="py-3">
                    <span :class="item.quantidade === 0 ? 'text-red-600 dark:text-red-400 font-bold' : item.quantidade <= (item.produto?.estoque_minimo ?? 0) ? 'text-yellow-600 dark:text-yellow-400 font-semibold' : 'text-green-600 dark:text-green-400 font-semibold'">
                      {{ formatNumero(item.quantidade) }} {{ item.unidade_medida }}
                    </span>
                  </td>

                  <td class="py-3 text-slate-600 dark:text-slate-300">
                    <span v-if="item.data_validade">{{ formatarData(item.data_validade) }}</span>
                    <span v-else class="text-slate-400 dark:text-slate-500">—</span>
                  </td>

                  <td class="py-3 text-slate-500 dark:text-slate-400">{{ item.produto?.fornecedor?.nome || '—' }}</td>
                  <td class="py-3 text-slate-500 dark:text-slate-400">{{ item.localizacao || '—' }}</td>
                  <td class="py-3 text-slate-500 dark:text-slate-400">{{ item.centro_custo?.codigo || '—' }}</td>

                  <td class="py-3">
                    <span
                      class="px-2 py-0.5 rounded text-xs font-bold text-white inline-flex items-center gap-1"
                      :class="item.prioridade_abc === 'A' ? 'bg-green-600' : item.prioridade_abc === 'B' ? 'bg-orange-500' : 'bg-red-600'"
                      :title="item.prioridade_manual ? 'Prioridade manual' : 'Prioridade automática'"
                    >
                      <Lock v-if="item.prioridade_manual" :size="10" />
                      {{ item.prioridade_abc || 'C' }}
                    </span>
                  </td>

                  <td class="py-3">
                    <div class="flex items-center gap-1 flex-wrap">
                      <span v-if="item.data_validade && estaVencido(item.data_validade)" class="px-2 py-0.5 rounded text-xs font-bold bg-red-600 text-white">
                        Vencido
                      </span>
                      <span v-else-if="item.data_validade && proximoDoVencimento(item.data_validade)" class="px-2 py-0.5 rounded text-xs font-bold bg-yellow-600 text-white">
                        Vencendo
                      </span>
                      <span v-else class="px-2 py-0.5 rounded text-xs font-bold bg-green-700 text-white">
                        OK
                      </span>

                      <span v-if="item.quantidade === 0 || item.quantidade <= (item.produto?.estoque_minimo ?? 0)" class="px-2 py-0.5 rounded text-xs font-bold bg-orange-700 text-white">
                        Crítico
                      </span>
                      <span v-else class="px-2 py-0.5 rounded text-xs font-bold bg-green-700 text-white">
                        OK
                      </span>
                    </div>
                  </td>

                  <td class="py-3" @click.stop>
                    <div class="flex items-center gap-3">
                      <button class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition" title="Editar" @click="itemSelecionado = item; modalEditarAberto = true">
                        <Pencil :size="16" />
                      </button>
                      <button class="text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 transition" title="Transferir" @click="iniciarTransferenciaManual(item)">
                        <ArrowRightLeft :size="16" />
                      </button>
                      <button class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-500 dark:hover:text-yellow-300 transition" title="Baixa de estoque" @click="itemSelecionado = item; modalBaixaAberto = true">
                        <PackageOpen :size="16" />
                      </button>
                      <button class="text-green-600 dark:text-green-400 hover:text-green-500 dark:hover:text-green-300 transition" title="Entrada de estoque" @click="itemSelecionado = item; modalEntradaAberto = true">
                        <PackagePlus :size="16" />
                      </button>
                      <button class="text-red-600 dark:text-red-400 hover:text-red-500 dark:hover:text-red-300 transition" title="Excluir" @click="itemSelecionado = item; modalExcluirAberto = true">
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
              </template>
            </draggable>
          </table>
        </div>

        <!-- Controles de paginação -->
        <Paginacao
          v-model:pagina-atual="paginaAtual"
          v-model:por-pagina="itensPorPagina"
          :total-paginas="totalPaginas"
          :total="itensFiltrados.length"
          rotulo="itens"
        />
      </div>
    </div>

    <!-- ===== MODAL CONFIRMAÇÃO EXCLUSÃO DE LOTE (individual) ===== -->
    <div v-if="modalExcluirLoteAberto" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Shield class="text-red-600 dark:text-red-400" :size="20" />
            <div>
              <h2 class="text-slate-900 dark:text-white font-bold">Excluir Lote</h2>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Esta ação não pode ser desfeita</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="modalExcluirLoteAberto = false">
            <X :size="18" />
          </button>
        </div>

        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
          <p class="text-slate-600 dark:text-slate-300 text-sm">
            Você está excluindo o lote <strong class="text-slate-900 dark:text-white">{{ loteAtivo?.numero_lote }}</strong>. Esta ação não pode ser desfeita.
          </p>
        </div>

        <div class="flex gap-3">
          <button class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" @click="modalExcluirLoteAberto = false">
            Cancelar
          </button>
          <button class="flex-1 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 transition font-medium" @click="excluirLote">
            Confirmar Exclusão
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL CONFIRMAÇÃO EXCLUSÃO EM MASSA DE LOTES ===== -->
    <div v-if="modalExcluirVariosAberto" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Shield class="text-red-600 dark:text-red-400" :size="20" />
            <div>
              <h2 class="text-slate-900 dark:text-white font-bold">Excluir Lotes</h2>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Esta ação não pode ser desfeita</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="modalExcluirVariosAberto = false">
            <X :size="18" />
          </button>
        </div>

        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
          <p class="text-slate-600 dark:text-slate-300 text-sm">
            Você está prestes a excluir <strong class="text-slate-900 dark:text-white">{{ lotesSelecionados.size }}</strong> lote(s). Esta ação não pode ser desfeita.
          </p>
        </div>

        <div class="flex gap-3">
          <button class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" @click="modalExcluirVariosAberto = false">
            Cancelar
          </button>
          <button :disabled="excluindoVarios" class="flex-1 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 transition font-medium disabled:opacity-50" @click="excluirLotesSelecionados">
            {{ excluindoVarios ? 'Excluindo...' : 'Confirmar Exclusão' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL CONFIRMAÇÃO EXCLUSÃO EM MASSA DE ITENS ===== -->
    <div v-if="modalExcluirItensAberto" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Shield class="text-red-600 dark:text-red-400" :size="20" />
            <div>
              <h2 class="text-slate-900 dark:text-white font-bold">Excluir Itens</h2>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Esta ação não pode ser desfeita</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="modalExcluirItensAberto = false">
            <X :size="18" />
          </button>
        </div>

        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
          <p class="text-slate-600 dark:text-slate-300 text-sm">
            Você está prestes a excluir <strong class="text-slate-900 dark:text-white">{{ itensSelecionados.size }}</strong> item(ns) deste lote. O estoque dos produtos será ajustado automaticamente. Esta ação não pode ser desfeita.
          </p>
        </div>

        <div class="flex gap-3">
          <button class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" @click="modalExcluirItensAberto = false">
            Cancelar
          </button>
          <button :disabled="excluindoItens" class="flex-1 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 transition font-medium disabled:opacity-50" @click="excluirItensSelecionados">
            {{ excluindoItens ? 'Excluindo...' : 'Confirmar Exclusão' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal criar/editar lote -->
    <ModalLote
      v-if="modalAberto"
      :lote="loteSelecionado"
      @fechar="fecharModal"
      @salvo="aoSalvar"
    />

    <!-- Modal adicionar item -->
    <ModalAdicionarItem
      v-if="modalItemAberto"
      :lote="loteAtivo"
      @fechar="modalItemAberto = false"
      @salvo="modalItemAberto = false; carregarLotes()"
    />

    <!-- Modal editar item -->
    <ModalEditarItem
      v-if="modalEditarAberto"
      :item="itemSelecionado"
      @fechar="modalEditarAberto = false"
      @salvo="modalEditarAberto = false; carregarLotes()"
    />

    <!-- Modal transferir item -->
    <ModalTransferirItem
      v-if="modalTransferirAberto"
      :item="itemParaTransferir"
      :lotes="lotes"
      :lote-destino-inicial="loteDestinoPreSelecionado"
      @fechar="modalTransferirAberto = false"
      @salvo="modalTransferirAberto = false; carregarLotes()"
    />

    <!-- Modal transferir vários itens selecionados -->
    <ModalTransferirVarios
      v-if="modalTransferirVariosAberto"
      :itens="loteAtivo.itens.filter(i => itensSelecionados.has(i.id_item))"
      :lotes="lotes"
      @fechar="modalTransferirVariosAberto = false"
      @salvo="aoConcluirTransferenciaVarios"
    />

    <!-- Modal baixa de estoque -->
    <ModalBaixaEstoque
      v-if="modalBaixaAberto"
      :item="itemSelecionado"
      @fechar="modalBaixaAberto = false"
      @salvo="modalBaixaAberto = false; carregarLotes()"
    />

    <!-- Modal entrada de estoque -->
    <ModalEntradaEstoque
      v-if="modalEntradaAberto"
      :item="itemSelecionado"
      @fechar="modalEntradaAberto = false"
      @salvo="modalEntradaAberto = false; carregarLotes()"
    />

    <!-- Modal excluir item -->
    <ModalExcluirItem
      v-if="modalExcluirAberto"
      :item="itemSelecionado"
      @fechar="modalExcluirAberto = false"
      @salvo="modalExcluirAberto = false; carregarLotes()"
    />

    <!-- Modal detalhes produto -->
    <ModalDetalhesProduto
      v-if="modalDetalhesAberto"
      :item="itemSelecionado"
      :lote-numero="loteAtivo?.numero_lote"
      @fechar="modalDetalhesAberto = false"
      @editar="modalDetalhesAberto = false; itemSelecionado = $event; modalEditarAberto = true"
      @baixa="modalDetalhesAberto = false; itemSelecionado = $event; modalBaixaAberto = true"
      @entrada="modalDetalhesAberto = false; itemSelecionado = $event; modalEntradaAberto = true"
      @excluir="modalDetalhesAberto = false; itemSelecionado = $event; modalExcluirAberto = true"
      @transferir="modalDetalhesAberto = false; iniciarTransferenciaManual($event)"
    />

  </div>
</template>

<script setup>
import draggable from 'vuedraggable'
import { ref, computed, onMounted, watch, watchEffect } from 'vue'
import { Plus, Shield, X, PackageMinus, Package, Trash2, Calendar, Pencil, PackageOpen, PackagePlus, ArrowRightLeft, Lock } from 'lucide-vue-next'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import { useNotificacao } from '@/composables/useNotificacao'
import { useCentrosCusto } from '@/composables/useCentrosCusto'
import ModalLote            from '@/componentes/ui/ModalLote.vue'
import ModalAdicionarItem   from '@/componentes/ui/ModalAdicionarItem.vue'
import ModalEditarItem      from '@/componentes/ui/ModalEditarItem.vue'
import ModalTransferirItem  from '@/componentes/ui/ModalTransferirItem.vue'
import ModalTransferirVarios from '@/componentes/ui/ModalTransferirVarios.vue'
import ModalExcluirItem     from '@/componentes/ui/ModalExcluirItem.vue'
import ModalDetalhesProduto from '@/componentes/ui/ModalDetalhesProduto.vue'
import ModalBaixaEstoque    from '@/componentes/ui/ModalBaixaEstoque.vue'
import ModalEntradaEstoque  from '@/componentes/ui/ModalEntradaEstoque.vue'
import Paginacao            from '@/paginas/PaginacaoControles.vue'
import { formatarData, estaVencido, proximoDoVencimento } from '@/utils/date'
import { useArrastarParaRolar } from '@/composables/useArrastarParaRolar'

const { elementoRef: tabelaRef, aoIniciar } = useArrastarParaRolar()
const { centros, carregar: carregarCentros } = useCentrosCusto()

const autenticacao        = useAutenticacaoStore()
const { sucesso, erro }   = useNotificacao()
const lotes               = ref([])
const carregando          = ref(false)
const modalAberto         = ref(false)
const modalItemAberto     = ref(false)
const modalEditarAberto   = ref(false)
const modalDetalhesAberto = ref(false)
const modalBaixaAberto    = ref(false)
const modalEntradaAberto  = ref(false)
const modalExcluirAberto  = ref(false)
const loteSelecionado     = ref(null)
const itemSelecionado     = ref(null)
const tabAtiva            = ref(null)

// ===== Seleção múltipla / exclusão em massa de lotes =====
const modoSelecaoLotes         = ref(false)
const lotesSelecionados        = ref(new Set())
const modalExcluirVariosAberto = ref(false)
const excluindoVarios          = ref(false)

function alternarModoSelecao() {
  modoSelecaoLotes.value  = !modoSelecaoLotes.value
  lotesSelecionados.value = new Set()
}

function alternarSelecaoLote(idLote) {
  const novo = new Set(lotesSelecionados.value)
  novo.has(idLote) ? novo.delete(idLote) : novo.add(idLote)
  lotesSelecionados.value = novo
}

async function excluirLotesSelecionados() {
  excluindoVarios.value = true
  try {
    await api.delete('/lotes', { data: { ids: Array.from(lotesSelecionados.value) } })
    sucesso(`${lotesSelecionados.value.size} lote(s) excluído(s) com sucesso.`)
    modalExcluirVariosAberto.value = false
    modoSelecaoLotes.value = false
    lotesSelecionados.value = new Set()
    tabAtiva.value = null
    await carregarLotes()
  } catch (e) {
    console.error(e)
    erro(e.response?.data?.message || 'Erro ao excluir lotes selecionados.')
    // lista desatualizada (lote já excluído por outro usuário/aba): recarrega
    if ([404, 422].includes(e.response?.status)) {
      modalExcluirVariosAberto.value = false
      modoSelecaoLotes.value = false
      lotesSelecionados.value = new Set()
      await carregarLotes()
    }
  } finally {
    excluindoVarios.value = false
  }
}

// ===== Seleção múltipla / exclusão em massa de ITENS do lote ativo =====
const modoSelecaoItens        = ref(false)
const itensSelecionados       = ref(new Set())
const modalExcluirItensAberto = ref(false)
const excluindoItens          = ref(false)

// ===== Selecionar todos os itens do lote (respeita o filtro de centro de custo) =====
const inputSelecionarTodosItens = ref(null)

const todosItensSelecionados = computed(() => {
  if (!itensFiltrados.value.length) return false
  return itensFiltrados.value.every((i) => itensSelecionados.value.has(i.id_item))
})

const algunsItensSelecionados = computed(() => {
  if (!itensFiltrados.value.length) return false
  return (
    itensFiltrados.value.some((i) => itensSelecionados.value.has(i.id_item)) &&
    !todosItensSelecionados.value
  )
})

function alternarSelecaoTodosItens() {
  itensSelecionados.value = todosItensSelecionados.value
    ? new Set()
    : new Set(itensFiltrados.value.map((i) => i.id_item))
}

// checkbox nativo não tem v-model pra "indeterminate" — seta via DOM direto
watchEffect(() => {
  if (inputSelecionarTodosItens.value) {
    inputSelecionarTodosItens.value.indeterminate = algunsItensSelecionados.value
  }
})

function alternarModoSelecaoItens() {
  modoSelecaoItens.value  = !modoSelecaoItens.value
  itensSelecionados.value = new Set()
}

function alternarSelecaoItem(idItem) {
  const novo = new Set(itensSelecionados.value)
  novo.has(idItem) ? novo.delete(idItem) : novo.add(idItem)
  itensSelecionados.value = novo
}

async function excluirItensSelecionados() {
  excluindoItens.value = true
  try {
    await api.delete('/itens', { data: { ids: Array.from(itensSelecionados.value) } })
    sucesso(`${itensSelecionados.value.size} item(ns) excluído(s) com sucesso.`)
    modalExcluirItensAberto.value = false
    modoSelecaoItens.value = false
    itensSelecionados.value = new Set()
    await carregarLotes()
  } catch (e) {
    console.error(e)
    erro('Erro ao excluir itens selecionados.')
  } finally {
    excluindoItens.value = false
  }
}

// ===== Transferência em massa de itens selecionados =====
const modalTransferirVariosAberto = ref(false)

function iniciarTransferenciaVarios() {
  modalTransferirVariosAberto.value = true
}

async function aoConcluirTransferenciaVarios() {
  modalTransferirVariosAberto.value = false
  modoSelecaoItens.value = false
  itensSelecionados.value = new Set()
  await carregarLotes()
}

// ===== Transferência de item entre lotes =====
const modalTransferirAberto     = ref(false)
const itemArrastado             = ref(null)
const itemParaTransferir        = ref(null)
const loteDestinoPreSelecionado = ref(null)

// Formata números com separador de milhar no padrão brasileiro (ex: 17050 -> 17.050)
function formatNumero(valor) {
  return Number(valor ?? 0).toLocaleString('pt-BR')
}

const loteAtivo = computed(() => lotes.value.find(l => l.id_lote === tabAtiva.value) || null)

// ===== Filtro por centro de custo (null = todos | 'sem' = sem centro | id) =====
const filtroCentro = ref(null)

const itensFiltrados = computed(() => {
  const itens = loteAtivo.value?.itens ?? []
  if (filtroCentro.value === null) return itens
  if (filtroCentro.value === 'sem') return itens.filter(i => !i.id_centro_custo)
  return itens.filter(i => i.id_centro_custo === filtroCentro.value)
})

// ===== Paginação de itens (controles ficam no componente Paginacao) =====
const itensPorPagina = ref(10)
const paginaAtual = ref(1)

const totalPaginas = computed(() =>
  Math.max(1, Math.ceil(itensFiltrados.value.length / itensPorPagina.value))
)

const itensPaginados = computed(() => {
  const inicio = (paginaAtual.value - 1) * itensPorPagina.value
  return itensFiltrados.value.slice(inicio, inicio + itensPorPagina.value)
})

// ao trocar de lote: volta pra página 1 e limpa a seleção de itens
watch(tabAtiva, () => {
  paginaAtual.value       = 1
  modoSelecaoItens.value  = false
  itensSelecionados.value = new Set()
})

// ao mudar itens por página: volta pra página 1 (evita ficar numa página inexistente)
watch(itensPorPagina, () => {
  paginaAtual.value = 1
})

// ao mudar o filtro: volta pra página 1 e limpa a seleção
watch(filtroCentro, () => {
  paginaAtual.value       = 1
  itensSelecionados.value = new Set()
})

// ===== Troca de tab (desvia pra seleção quando ativo) =====
function aoClicarTab(idLote) {
  if (modoSelecaoLotes.value) {
    alternarSelecaoLote(idLote)
    return
  }
  tabAtiva.value = idLote
}

// ===== Ações (criar lote / adicionar item) =====
function iniciarCriacaoLote() {
  loteSelecionado.value = null
  modalAberto.value = true
}

function iniciarEdicaoLote() {
  loteSelecionado.value = loteAtivo.value
  modalAberto.value = true
}

function iniciarAdicaoItem() {
  modalItemAberto.value = true
}

// ===== Transferência de item entre lotes =====
function aoIniciarArraste(evt) {
  itemArrastado.value = itensPaginados.value[evt.oldIndex]
}

function aoSoltarNaTab(idLoteDestino) {
  if (!itemArrastado.value || idLoteDestino === tabAtiva.value) return
  itemParaTransferir.value = itemArrastado.value
  loteDestinoPreSelecionado.value = idLoteDestino
  modalTransferirAberto.value = true
  itemArrastado.value = null
}

function iniciarTransferenciaManual(item) {
  itemParaTransferir.value = item
  loteDestinoPreSelecionado.value = null
  modalTransferirAberto.value = true
}

// ===== Exclusão de lote (confirmação simples) =====
const modalExcluirLoteAberto = ref(false)

function iniciarExclusaoLote() {
  modalExcluirLoteAberto.value = true
}

async function excluirLote() {
  try {
    await api.delete(`/lotes/${loteAtivo.value.id_lote}`)
    tabAtiva.value = null
    modalExcluirLoteAberto.value = false
    sucesso('Lote excluído com sucesso.')
    await carregarLotes()
  } catch (e) {
    console.error(e)
    erro('Erro ao excluir lote.')
    modalExcluirLoteAberto.value = false
    // lista desatualizada (lote já excluído): recarrega
    if ([404, 422].includes(e.response?.status)) await carregarLotes()
  }
}

function fecharModal() {
  modalAberto.value     = false
  loteSelecionado.value = null
}

async function aoSalvar() {
  fecharModal()
  await carregarLotes()
}

async function aoTerminarArraste(evt) {
  const idLoteDestino = detectarTabAlvo(evt)

  if (idLoteDestino && idLoteDestino !== tabAtiva.value && itemArrastado.value) {
    itemParaTransferir.value = itemArrastado.value
    loteDestinoPreSelecionado.value = idLoteDestino
    modalTransferirAberto.value = true
    itemArrastado.value = null
    await carregarLotes() // desfaz a reordenação visual local, já que o item saiu do lote
    return
  }

  itemArrastado.value = null
  await salvarOrdemItens()
}

function detectarTabAlvo(evt) {
  const originalEvent = evt.originalEvent
  if (!originalEvent) return null

  const { clientX, clientY } = originalEvent
  if (clientX === undefined || clientY === undefined) return null

  const elemento = document.elementFromPoint(clientX, clientY)
  const tab = elemento?.closest('[data-id-lote]')
  return tab ? Number(tab.dataset.idLote) : null
}

async function salvarOrdemItens() {
  if (!loteAtivo.value?.itens) return

  const itens = itensPaginados.value.map((item, index) => ({
    id_item: item.id_item,
    ordem:   index,
  }))

  try {
    await api.patch(`/lotes/${loteAtivo.value.id_lote}/itens/reordenar`, { itens })
  } catch (e) {
    console.error(e)
    erro('Não foi possível salvar a nova ordem dos itens.')
    await carregarLotes()
  }
}

async function carregarLotes() {
  carregando.value = true
  try {
    const resposta = await api.get('/lotes')
    lotes.value = resposta.data
    // se a aba ativa não existe mais (lote excluído), cai para o primeiro lote
    if (lotes.value.length > 0 && !lotes.value.some(l => l.id_lote === tabAtiva.value)) {
      tabAtiva.value = lotes.value[0].id_lote
    }
  } catch (e) {
    console.error(e)
    erro('Não foi possível carregar os lotes.')
  } finally {
    carregando.value = false
  }
}

onMounted(() => {
  carregarLotes()
  carregarCentros()
})
</script>