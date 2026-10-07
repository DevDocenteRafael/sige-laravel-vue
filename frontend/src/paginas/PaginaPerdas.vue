<template>
  <div class="p-6 bg-white dark:bg-black min-h-screen">

    <!-- Cabeçalho -->
    <div class="flex justify-between items-center mb-1">
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Registro de Perdas</h1>
    </div>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Controle de perdas por vencimento, quebra, furto e outros motivos</p>

    <!-- Estatísticas -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex items-center gap-4">
        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/40 rounded-lg flex items-center justify-center">
          <AlertTriangle class="text-red-600 dark:text-red-400" :size="20" />
        </div>
        <div>
          <p class="text-slate-500 dark:text-slate-400 text-xs">Total de Perdas</p>
          <p class="text-slate-900 dark:text-white text-2xl font-bold">{{ estatisticas.total }}</p>
        </div>
      </div>
      <div class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex items-center gap-4">
        <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/40 rounded-lg flex items-center justify-center">
          <Trash2 class="text-orange-600 dark:text-orange-400" :size="20" />
        </div>
        <div>
          <p class="text-slate-500 dark:text-slate-400 text-xs">Unidades Perdidas</p>
          <p class="text-slate-900 dark:text-white text-2xl font-bold">{{ formatarNumero(estatisticas.unidades) }}</p>
        </div>
      </div>
      <div class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex items-center gap-4">
        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center">
          <Calendar class="text-blue-600 dark:text-blue-400" :size="20" />
        </div>
        <div>
          <p class="text-slate-500 dark:text-slate-400 text-xs">Este Mês</p>
          <p class="text-slate-900 dark:text-white text-2xl font-bold">{{ estatisticas.esteMes }}</p>
        </div>
      </div>
    </div>

    <!-- Registrar Nova Perda -->
    <div class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 mb-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-slate-900 dark:text-white font-bold">Registrar Nova Perda</h2>

        <div class="flex items-center gap-2">
          <template v-if="modoSelecao">
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 cursor-pointer select-none">
              <input
                ref="inputSelecionarTodos"
                type="checkbox"
                :checked="todosSelecionados"
                :disabled="itensSelecionaveis.length === 0"
                @change="alternarSelecaoTodos"
              />
              Selecionar todos da página
            </label>
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ itensSelecionados.size }} selecionado(s)</span>
            <button
              class="flex items-center gap-2 bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm px-4 py-2 rounded-lg transition font-medium"
              :disabled="itensSelecionados.size === 0"
              @click="abrirModalVarios"
            >
              <Trash2 :size="14" />
              Registrar Perdas Selecionadas
            </button>
            <button
              class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm font-medium"
              @click="alternarModoSelecao"
            >
              Cancelar
            </button>
          </template>
          <button
            v-else-if="itens.length > 0"
            class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm font-medium"
            @click="alternarModoSelecao"
          >
            Selecionar
          </button>
        </div>
      </div>

      <!-- Filtros: lista de itens -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <div class="relative md:col-span-4">
          <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
          <input
            v-model="busca"
            type="text"
            placeholder="Pesquisar por nome, SKU ou lote..."
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500 transition"
          />
        </div>

        <select
          v-model="filtroLote"
          class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500"
        >
          <option value="">Todos os lotes</option>
          <option :value="SEM_LOTE">Sem lote</option>
          <option v-for="l in lotesOpcoes" :key="l" :value="l">{{ l }}</option>
        </select>

        <select
          v-model="filtroCentroCusto"
          class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500"
        >
          <option value="">Todos os centros de custo</option>
          <option :value="SEM_CC">Sem centro de custo</option>
          <option v-for="cc in centrosCustoOpcoes" :key="cc.codigo" :value="cc.codigo">{{ cc.rotulo }}</option>
        </select>

        <select
          v-model="filtroEstoque"
          class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500"
        >
          <option value="todos">Todo o estoque</option>
          <option value="com">Com estoque</option>
          <option value="sem">Sem estoque</option>
        </select>
      </div>

      <div v-if="filtrosItensAtivos" class="flex justify-end mb-3">
        <button class="text-xs text-blue-600 dark:text-blue-400 hover:underline" @click="limparFiltrosItens">
          Limpar filtros
        </button>
      </div>

      <div v-if="carregandoItens" class="text-slate-500 dark:text-slate-400 text-sm text-center py-4">
        Carregando itens...
      </div>

      <div v-else class="space-y-2">
        <p v-if="itensFiltrados.length === 0" class="text-slate-400 dark:text-slate-500 text-sm text-center py-6">
          Nenhum item encontrado
        </p>
        <div
          v-for="item in itensPaginados"
          :key="item.id_item"
          class="flex items-center justify-between bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-3"
          :class="{ 'opacity-60': item.quantidade === 0 }"
        >
          <div class="flex items-center gap-3">
            <input
              v-if="modoSelecao"
              type="checkbox"
              :disabled="item.quantidade === 0"
              :checked="itensSelecionados.has(item.id_item)"
              @change="alternarSelecaoItem(item.id_item)"
            />
            <div>
              <div class="flex items-center gap-2">
                <p class="text-slate-900 dark:text-white font-semibold text-sm">{{ item.produto?.nome }}</p>
                <span
                  v-if="item.quantidade === 0"
                  class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400"
                >
                  Sem estoque
                </span>
              </div>
              <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">
                SKU: {{ item.produto?.sku || '—' }}
                &nbsp;&nbsp;Lote: {{ item.lote?.numero_lote || '—' }}
                &nbsp;&nbsp;Estoque: {{ item.quantidade }} {{ item.unidade_medida }}
                &nbsp;&nbsp;Centro de Custo: {{ rotuloCentroCusto(item.centro_custo) }}
              </p>
            </div>
          </div>
          <button
            v-if="!modoSelecao"
            class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-lg transition font-medium disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-red-600"
            :disabled="item.quantidade === 0"
            @click="abrirModal(item)"
          >
            <Trash2 :size="14" />
            Registrar Perda
          </button>
        </div>
      </div>

      <!-- Paginação: lista de itens -->
      <PaginacaoControles
        v-model:pagina-atual="paginaAtualItens"
        v-model:por-pagina="itensPorPaginaItens"
        :total-paginas="totalPaginasItens"
        :total="formatarNumero(itensFiltrados.length)"
        rotulo="itens"
      />
    </div>

    <!-- Perdas Recentes -->
    <div class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5">
      <h2 class="text-slate-900 dark:text-white font-bold mb-4">Perdas Recentes</h2>

      <!-- Filtros: perdas recentes -->
      <div v-if="perdas.length > 0" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
        <input
          v-model="buscaPerda"
          type="text"
          placeholder="Produto..."
          class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500"
        />
        <select
          v-model="filtroMotivoPerda"
          class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500"
        >
          <option value="">Todos os motivos</option>
          <option v-for="m in motivosOpcoes" :key="m" :value="m">{{ m }}</option>
        </select>
        <select
          v-model="filtroCentroCustoPerda"
          class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500"
        >
          <option value="">Todos os centros de custo</option>
          <option :value="SEM_CC">Sem centro de custo</option>
          <option v-for="cc in centrosCustoPerdasOpcoes" :key="cc.codigo" :value="cc.codigo">{{ cc.rotulo }}</option>
        </select>
        <div class="flex items-center gap-2">
          <label class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">De</label>
          <input
            v-model="dataInicio"
            type="date"
            :max="dataFim || undefined"
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500"
          />
        </div>
        <div class="flex items-center gap-2">
          <label class="text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">até</label>
          <input
            v-model="dataFim"
            type="date"
            :min="dataInicio || undefined"
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500"
          />
        </div>
      </div>

      <div v-if="filtrosPerdasAtivos" class="flex justify-end mb-3">
        <button class="text-xs text-blue-600 dark:text-blue-400 hover:underline" @click="limparFiltrosPerdas">
          Limpar filtros
        </button>
      </div>

      <div v-if="carregandoPerdas" class="text-slate-500 dark:text-slate-400 text-sm text-center py-4">
        Carregando...
      </div>

      <div v-else-if="perdas.length === 0" class="text-slate-400 dark:text-slate-500 text-sm text-center py-8">
        Nenhuma perda registrada
      </div>

      <p v-else-if="perdasFiltradas.length === 0" class="text-slate-400 dark:text-slate-500 text-sm text-center py-8">
        Nenhuma perda encontrada com os filtros aplicados
      </p>

      <table v-else class="w-full text-sm">
        <thead>
          <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 text-left">
            <th class="pb-3 font-medium">Produto</th>
            <th class="pb-3 font-medium">Centro de Custo</th>
            <th class="pb-3 font-medium">Quantidade</th>
            <th class="pb-3 font-medium">Motivo</th>
            <th class="pb-3 font-medium">Data</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
          <tr v-for="perda in perdasPaginadas" :key="perda.id_movimentacao" class="hover:bg-slate-100 dark:hover:bg-slate-800/50 transition">
            <td class="py-3 text-slate-900 dark:text-white font-medium">{{ perda.item?.produto?.nome || '—' }}</td>
            <td class="py-3 text-slate-500 dark:text-slate-400">{{ rotuloCentroCusto(perda.item?.centro_custo) }}</td>
            <td class="py-3 text-red-600 dark:text-red-400 font-bold">-{{ formatarNumero(perda.quantidade) }}</td>
            <td class="py-3 text-slate-600 dark:text-slate-300">{{ perda.observacao || '—' }}</td>
            <td class="py-3 text-slate-500 dark:text-slate-400">{{ formatarData(perda.data_movimentacao) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Paginação: perdas recentes -->
      <PaginacaoControles
        v-model:pagina-atual="paginaAtualPerdas"
        v-model:por-pagina="itensPorPaginaPerdas"
        :total-paginas="totalPaginasPerdas"
        :total="formatarNumero(perdasFiltradas.length)"
        rotulo="registros"
      />
    </div>

    <!-- Modal: perda individual -->
    <div v-if="modalAberto" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">

      <!-- ETAPA 0: Formulário -->
      <div v-if="etapa === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-5">
          <h2 class="text-slate-900 dark:text-white font-bold">Registrar Perda</h2>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="fecharModal"><X :size="18" /></button>
        </div>

        <div class="bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-4 mb-4">
          <p class="text-slate-500 dark:text-slate-400 text-xs mb-1">Produto</p>
          <p class="text-slate-900 dark:text-white font-bold">{{ itemSelecionado?.produto?.nome }}</p>
          <p class="text-slate-500 dark:text-slate-400 text-xs mt-2">Centro de Custo</p>
          <p class="text-slate-900 dark:text-white text-sm font-medium">{{ rotuloCentroCusto(itemSelecionado?.centro_custo) }}</p>
          <p class="text-slate-500 dark:text-slate-400 text-xs mt-2">Estoque disponível</p>
          <p class="text-slate-900 dark:text-white font-bold text-xl">{{ itemSelecionado?.quantidade }} {{ itemSelecionado?.unidade_medida }}</p>
        </div>

        <div class="mb-4">
          <label class="block text-sm text-slate-600 dark:text-slate-300 font-medium mb-1">Quantidade *</label>
          <input
            v-model.number="form.quantidade"
            type="number"
            min="1"
            :max="itemSelecionado?.quantidade"
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-red-500"
            placeholder="Ex: 5"
          />
        </div>

        <div class="mb-4">
          <label class="block text-sm text-slate-600 dark:text-slate-300 font-medium mb-1">Motivo *</label>
          <select
            v-model="form.motivo"
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-red-500"
            @change="form.motivoOutro = ''"
          >
            <option value="">Selecione um motivo</option>
            <option value="Vencimento">Vencimento</option>
            <option value="Quebra">Quebra</option>
            <option value="Furto">Furto</option>
            <option value="Avaria">Avaria</option>
            <option value="Uso interno">Uso interno</option>
            <option value="Outro">Outro</option>
          </select>
        </div>

        <div v-if="form.motivo === 'Outro'" class="mb-6">
          <label class="block text-sm text-slate-600 dark:text-slate-300 font-medium mb-1">Especifique o motivo *</label>
          <input
            v-model="form.motivoOutro"
            type="text"
            maxlength="150"
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-red-500"
            placeholder="Descreva o motivo da perda"
          />
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded text-red-600 dark:text-red-400 text-sm">{{ erro }}</div>

        <div class="flex gap-3">
          <button class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm" @click="fecharModal">Cancelar</button>
          <button
            class="flex-1 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-40 text-white font-medium transition text-sm"
            :disabled="!form.quantidade || !form.motivo || (form.motivo === 'Outro' && !form.motivoOutro.trim())"
            @click="abrirConfirmacao"
          >
            Confirmar Perda
          </button>
        </div>
      </div>

      <!-- ETAPA 1: Confirmação -->
      <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Shield class="text-red-600 dark:text-red-400" :size="20" />
            <div>
              <h2 class="text-slate-900 dark:text-white font-bold">Confirmação de Segurança</h2>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Confirme para prosseguir</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="fecharModal"><X :size="18" /></button>
        </div>

        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
          <div class="flex items-center gap-2 mb-1">
            <AlertTriangle class="text-red-600 dark:text-red-400" :size="16" />
            <span class="text-slate-900 dark:text-white font-medium text-sm">Registrar Perda</span>
          </div>
          <p class="text-slate-600 dark:text-slate-300 text-sm">
            Confirmar perda de <strong class="text-slate-900 dark:text-white">{{ form.quantidade }} {{ itemSelecionado?.unidade_medida }}</strong>
            de "<strong class="text-slate-900 dark:text-white">{{ itemSelecionado?.produto?.nome }}</strong>" por motivo de
            "<strong class="text-slate-900 dark:text-white">{{ motivoExibicao }}</strong>". Esta ação reduz o estoque e não pode ser desfeita.
          </p>
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded text-red-600 dark:text-red-400 text-sm">{{ erro }}</div>

        <div class="flex gap-3">
          <button class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm" @click="etapa = 0">Voltar</button>
          <button
            class="flex-1 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white font-medium transition text-sm"
            :disabled="salvando"
            @click="confirmarPerda"
          >
            {{ salvando ? 'Confirmando...' : 'Confirmar Perda' }}
          </button>
        </div>
      </div>

    </div>

    <!-- Modal: perdas em massa -->
    <div v-if="modalVariosAberto" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">

      <!-- ETAPA 0: quantidade por item + motivo único -->
      <div v-if="etapaVarios === 0" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-5">
          <h2 class="text-slate-900 dark:text-white font-bold">Registrar Perdas ({{ itensParaVarios.length }} itens)</h2>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="fecharModalVarios"><X :size="18" /></button>
        </div>

        <div class="space-y-3 mb-4">
          <div
            v-for="item in itensParaVarios"
            :key="item.id_item"
            class="bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-3"
          >
            <div class="flex items-center justify-between mb-2">
              <div>
                <p class="text-slate-900 dark:text-white font-semibold text-sm">{{ item.produto?.nome }}</p>
                <p class="text-slate-500 dark:text-slate-400 text-xs">Centro de Custo: {{ rotuloCentroCusto(item.centro_custo) }}</p>
              </div>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Estoque: {{ item.quantidade }} {{ item.unidade_medida }}</p>
            </div>
            <input
              v-model.number="quantidadesVarios[item.id_item]"
              type="number"
              min="1"
              :max="item.quantidade"
              class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-red-500"
              :placeholder="`Quantidade perdida (máx. ${item.quantidade})`"
            />
          </div>
        </div>

        <div class="mb-6">
          <label class="block text-sm text-slate-600 dark:text-slate-300 font-medium mb-1">Motivo (aplicado a todos) *</label>
          <select
            v-model="formVarios.motivo"
            class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-red-500"
            @change="formVarios.motivoOutro = ''"
          >
            <option value="">Selecione um motivo</option>
            <option value="Vencimento">Vencimento</option>
            <option value="Quebra">Quebra</option>
            <option value="Furto">Furto</option>
            <option value="Avaria">Avaria</option>
            <option value="Uso interno">Uso interno</option>
            <option value="Outro">Outro</option>
          </select>
          <input
            v-if="formVarios.motivo === 'Outro'"
            v-model="formVarios.motivoOutro"
            type="text"
            maxlength="150"
            class="w-full mt-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-red-500"
            placeholder="Descreva o motivo da perda"
          />
        </div>

        <div v-if="erroVarios" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded text-red-600 dark:text-red-400 text-sm">{{ erroVarios }}</div>

        <div class="flex gap-3">
          <button class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm" @click="fecharModalVarios">Cancelar</button>
          <button
            class="flex-1 py-2.5 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-40 text-white font-medium transition text-sm"
            :disabled="!formVariosValido"
            @click="etapaVarios = 1"
          >
            Confirmar Perdas
          </button>
        </div>
      </div>

      <!-- ETAPA 1: confirmação -->
      <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Shield class="text-red-600 dark:text-red-400" :size="20" />
            <div>
              <h2 class="text-slate-900 dark:text-white font-bold">Confirmação de Segurança</h2>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Confirme para prosseguir</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="fecharModalVarios"><X :size="18" /></button>
        </div>

        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
          <div class="flex items-center gap-2 mb-1">
            <AlertTriangle class="text-red-600 dark:text-red-400" :size="16" />
            <span class="text-slate-900 dark:text-white font-medium text-sm">Registrar {{ itensParaVarios.length }} perda(s)</span>
          </div>
          <p class="text-slate-600 dark:text-slate-300 text-sm">
            Confirmar perda de <strong class="text-slate-900 dark:text-white">{{ itensParaVarios.length }} item(ns)</strong> por motivo de
            "<strong class="text-slate-900 dark:text-white">{{ motivoExibicaoVarios }}</strong>". Esta ação reduz o estoque de cada item e não pode ser desfeita.
          </p>
        </div>

        <div v-if="erroVarios" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded text-red-600 dark:text-red-400 text-sm">{{ erroVarios }}</div>

        <div class="flex gap-3">
          <button class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-sm" @click="etapaVarios = 0">Voltar</button>
          <button
            class="flex-1 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white font-medium transition text-sm"
            :disabled="salvandoVarios"
            @click="confirmarPerdasVarias"
          >
            {{ salvandoVarios ? 'Confirmando...' : 'Confirmar Perdas' }}
          </button>
        </div>
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, watchEffect } from 'vue'
import { AlertTriangle, Trash2, Calendar, X, Shield, Search } from 'lucide-vue-next'
import api from '@/servicos/api'
import PaginacaoControles from './PaginacaoControles.vue'

const itens            = ref([])
const perdas           = ref([])
const carregandoItens  = ref(false)
const carregandoPerdas = ref(false)
const modalAberto      = ref(false)
const itemSelecionado  = ref(null)
const salvando         = ref(false)
const erro             = ref('')

const etapa = ref(0)

const estatisticas = ref({ total: 0, unidades: 0, esteMes: 0 })
const form = ref({ quantidade: null, motivo: '', motivoOutro: '' })

const motivoExibicao = computed(() =>
  form.value.motivo === 'Outro' ? form.value.motivoOutro.trim() : form.value.motivo
)

// Centro de custo é opcional no item: sem centro vira "—"
function rotuloCentroCusto(cc) {
  return cc ? `${cc.codigo} - ${cc.nome}` : '—'
}

// ===== Utilitários de filtro =====
const SEM_CC = '__sem_cc__'

// Ignora acentos e maiúsculas/minúsculas
const normalizar = (s) =>
  String(s ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()

// Data local no formato YYYY-MM-DD (mesmo fuso usado em formatarData)
function dataLocalISO(valor) {
  const d = new Date(valor)
  const p = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`
}

// ===== Paginação + filtros: lista de itens (Registrar Nova Perda) =====
const itensPorPaginaItens = ref(10)
const paginaAtualItens    = ref(1)

const SEM_LOTE = '__sem_lote__'

const busca             = ref('')
const filtroLote        = ref('')
const filtroCentroCusto = ref('')
const filtroEstoque     = ref('todos')

const lotesOpcoes = computed(() =>
  [...new Set(itens.value.map((i) => i.lote?.numero_lote).filter(Boolean))]
    .sort((a, b) => String(a).localeCompare(String(b), 'pt-BR', { numeric: true }))
)

const centrosCustoOpcoes = computed(() => {
  const mapa = new Map()
  itens.value.forEach((i) => {
    if (i.centro_custo) mapa.set(i.centro_custo.codigo, rotuloCentroCusto(i.centro_custo))
  })
  return [...mapa]
    .map(([codigo, rotulo]) => ({ codigo, rotulo }))
    .sort((a, b) => a.rotulo.localeCompare(b.rotulo, 'pt-BR'))
})

const itensFiltrados = computed(() => {
  const termo = normalizar(busca.value.trim())

  return itens.value.filter((i) => {
    if (termo) {
      const alvo = normalizar(`${i.produto?.nome ?? ''} ${i.produto?.sku ?? ''} ${i.lote?.numero_lote ?? ''}`)
      if (!alvo.includes(termo)) return false
    }

    if (filtroLote.value === SEM_LOTE) {
      if (i.lote?.numero_lote) return false
    } else if (filtroLote.value && i.lote?.numero_lote !== filtroLote.value) {
      return false
    }

    if (filtroCentroCusto.value === SEM_CC) {
      if (i.centro_custo) return false
    } else if (filtroCentroCusto.value && i.centro_custo?.codigo !== filtroCentroCusto.value) {
      return false
    }

    if (filtroEstoque.value === 'com' && i.quantidade === 0) return false
    if (filtroEstoque.value === 'sem' && i.quantidade > 0) return false

    return true
  })
})

const filtrosItensAtivos = computed(() =>
  !!busca.value || !!filtroLote.value || !!filtroCentroCusto.value || filtroEstoque.value !== 'todos'
)

function limparFiltrosItens() {
  busca.value = ''
  filtroLote.value = ''
  filtroCentroCusto.value = ''
  filtroEstoque.value = 'todos'
}

// volta para a página 1 ao mudar qualquer filtro
watch([busca, filtroLote, filtroCentroCusto, filtroEstoque], () => { paginaAtualItens.value = 1 })

const totalPaginasItens = computed(() =>
  Math.max(1, Math.ceil(itensFiltrados.value.length / itensPorPaginaItens.value))
)

const itensPaginados = computed(() => {
  const inicio = (paginaAtualItens.value - 1) * itensPorPaginaItens.value
  return itensFiltrados.value.slice(inicio, inicio + itensPorPaginaItens.value)
})

// evita ficar numa página vazia se a lista encolher (ex: item some após virar 0 em estoque)
watchEffect(() => {
  if (paginaAtualItens.value > totalPaginasItens.value) {
    paginaAtualItens.value = totalPaginasItens.value
  }
})

// ===== Paginação + filtros: Perdas Recentes =====
const itensPorPaginaPerdas = ref(10)
const paginaAtualPerdas    = ref(1)

const buscaPerda             = ref('')
const filtroMotivoPerda      = ref('')
const filtroCentroCustoPerda = ref('')
const dataInicio             = ref('')
const dataFim                = ref('')

const motivosOpcoes = computed(() =>
  [...new Set(perdas.value.map((p) => p.observacao).filter(Boolean))]
    .sort((a, b) => a.localeCompare(b, 'pt-BR'))
)

const centrosCustoPerdasOpcoes = computed(() => {
  const mapa = new Map()
  perdas.value.forEach((p) => {
    const cc = p.item?.centro_custo
    if (cc) mapa.set(cc.codigo, rotuloCentroCusto(cc))
  })
  return [...mapa]
    .map(([codigo, rotulo]) => ({ codigo, rotulo }))
    .sort((a, b) => a.rotulo.localeCompare(b.rotulo, 'pt-BR'))
})

const perdasFiltradas = computed(() => {
  const termo = normalizar(buscaPerda.value.trim())

  return perdas.value.filter((p) => {
    if (termo && !normalizar(p.item?.produto?.nome).includes(termo)) return false

    if (filtroMotivoPerda.value && p.observacao !== filtroMotivoPerda.value) return false

    const cc = p.item?.centro_custo
    if (filtroCentroCustoPerda.value === SEM_CC) {
      if (cc) return false
    } else if (filtroCentroCustoPerda.value && cc?.codigo !== filtroCentroCustoPerda.value) {
      return false
    }

    if (dataInicio.value || dataFim.value) {
      if (!p.data_movimentacao) return false
      const dia = dataLocalISO(p.data_movimentacao)
      if (dataInicio.value && dia < dataInicio.value) return false
      if (dataFim.value && dia > dataFim.value) return false
    }

    return true
  })
})

const filtrosPerdasAtivos = computed(() =>
  !!buscaPerda.value || !!filtroMotivoPerda.value || !!filtroCentroCustoPerda.value ||
  !!dataInicio.value || !!dataFim.value
)

function limparFiltrosPerdas() {
  buscaPerda.value = ''
  filtroMotivoPerda.value = ''
  filtroCentroCustoPerda.value = ''
  dataInicio.value = ''
  dataFim.value = ''
}

watch([buscaPerda, filtroMotivoPerda, filtroCentroCustoPerda, dataInicio, dataFim], () => {
  paginaAtualPerdas.value = 1
})

const totalPaginasPerdas = computed(() =>
  Math.max(1, Math.ceil(perdasFiltradas.value.length / itensPorPaginaPerdas.value))
)

const perdasPaginadas = computed(() => {
  const inicio = (paginaAtualPerdas.value - 1) * itensPorPaginaPerdas.value
  return perdasFiltradas.value.slice(inicio, inicio + itensPorPaginaPerdas.value)
})

watchEffect(() => {
  if (paginaAtualPerdas.value > totalPaginasPerdas.value) {
    paginaAtualPerdas.value = totalPaginasPerdas.value
  }
})

// ===== Seleção múltipla / perda em massa =====
const modoSelecao       = ref(false)
const itensSelecionados = ref(new Set())

// ===== "Selecionar todos" vale só para a página atual (ignora itens sem estoque) =====
const inputSelecionarTodos = ref(null)

const itensSelecionaveis = computed(() =>
  itensPaginados.value.filter((i) => i.quantidade > 0)
)

const todosSelecionados = computed(() => {
  if (itensSelecionaveis.value.length === 0) return false
  return itensSelecionaveis.value.every((i) => itensSelecionados.value.has(i.id_item))
})

const algunsSelecionados = computed(() => {
  if (itensSelecionaveis.value.length === 0) return false
  return (
    itensSelecionaveis.value.some((i) => itensSelecionados.value.has(i.id_item)) &&
    !todosSelecionados.value
  )
})

// Marca/desmarca só os itens da página atual, preservando o que foi selecionado em outras páginas
function alternarSelecaoTodos() {
  const novo = new Set(itensSelecionados.value)
  if (todosSelecionados.value) {
    itensSelecionaveis.value.forEach((i) => novo.delete(i.id_item))
  } else {
    itensSelecionaveis.value.forEach((i) => novo.add(i.id_item))
  }
  itensSelecionados.value = novo
}

watchEffect(() => {
  if (inputSelecionarTodos.value) {
    inputSelecionarTodos.value.indeterminate = algunsSelecionados.value
  }
})

const modalVariosAberto = ref(false)
const etapaVarios       = ref(0)
const salvandoVarios    = ref(false)
const erroVarios        = ref('')
const quantidadesVarios = ref({})
const formVarios        = ref({ motivo: '', motivoOutro: '' })

const itensParaVarios = computed(() =>
  itens.value.filter(i => itensSelecionados.value.has(i.id_item))
)

const motivoExibicaoVarios = computed(() =>
  formVarios.value.motivo === 'Outro' ? formVarios.value.motivoOutro.trim() : formVarios.value.motivo
)

const formVariosValido = computed(() => {
  if (!formVarios.value.motivo) return false
  if (formVarios.value.motivo === 'Outro' && !formVarios.value.motivoOutro.trim()) return false
  return itensParaVarios.value.every(item => {
    const qtd = quantidadesVarios.value[item.id_item]
    return qtd > 0 && qtd <= item.quantidade
  })
})

function alternarModoSelecao() {
  modoSelecao.value       = !modoSelecao.value
  itensSelecionados.value = new Set()
}

function alternarSelecaoItem(idItem) {
  const novo = new Set(itensSelecionados.value)
  novo.has(idItem) ? novo.delete(idItem) : novo.add(idItem)
  itensSelecionados.value = novo
}

function abrirModalVarios() {
  quantidadesVarios.value = {}
  formVarios.value = { motivo: '', motivoOutro: '' }
  erroVarios.value = ''
  etapaVarios.value = 0
  modalVariosAberto.value = true
}

function fecharModalVarios() {
  modalVariosAberto.value = false
  etapaVarios.value = 0
}

async function confirmarPerdasVarias() {
  salvandoVarios.value = true
  erroVarios.value = ''
  try {
    await api.post('/perdas/varios', {
      motivo: motivoExibicaoVarios.value,
      itens: itensParaVarios.value.map(item => ({
        id_item:    item.id_item,
        quantidade: quantidadesVarios.value[item.id_item],
      })),
    })
    fecharModalVarios()
    modoSelecao.value = false
    itensSelecionados.value = new Set()
    await Promise.all([carregarItens(), carregarPerdas(), carregarEstatisticas()])
  } catch (e) {
    erroVarios.value = e.response?.data?.message || 'Erro ao registrar perdas.'
    etapaVarios.value = 0
  } finally {
    salvandoVarios.value = false
  }
}

const formatarData = (data) => {
  if (!data) return '—'
  return new Date(data).toLocaleDateString('pt-BR')
}

function formatarNumero(valor) {
  const num = Number(valor)
  return num >= 1000 ? num.toLocaleString('pt-BR') : num.toString()
}

function abrirModal(item) {
  itemSelecionado.value = item
  form.value = { quantidade: null, motivo: '', motivoOutro: '' }
  erro.value = ''
  etapa.value = 0
  modalAberto.value = true
}

function abrirConfirmacao() {
  if (!form.value.quantidade || !form.value.motivo) return
  if (form.value.motivo === 'Outro' && !form.value.motivoOutro.trim()) return
  erro.value = ''
  etapa.value = 1
}

async function confirmarPerda() {
  salvando.value = true
  erro.value = ''
  try {
    await api.post('/perdas', {
      id_item:    itemSelecionado.value.id_item,
      quantidade: form.value.quantidade,
      motivo:     motivoExibicao.value,
    })
    fecharModal()
    await Promise.all([carregarItens(), carregarPerdas(), carregarEstatisticas()])
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao registrar perda.'
    etapa.value = 0
  } finally {
    salvando.value = false
  }
}

function fecharModal() {
  modalAberto.value     = false
  itemSelecionado.value = null
  etapa.value           = 0
}

async function carregarItens() {
  carregandoItens.value = true
  try {
    const { data } = await api.get('/itens')
    itens.value = data
  } finally {
    carregandoItens.value = false
  }
}

async function carregarPerdas() {
  carregandoPerdas.value = true
  try {
    const { data } = await api.get('/perdas')
    perdas.value = data
  } finally {
    carregandoPerdas.value = false
  }
}

async function carregarEstatisticas() {
  try {
    const { data } = await api.get('/perdas/estatisticas')
    estatisticas.value = data
  } catch (e) {
    console.error('Erro ao carregar estatísticas:', e)
  }
}

onMounted(() => {
  carregarItens()
  carregarPerdas()
  carregarEstatisticas()
})
</script>