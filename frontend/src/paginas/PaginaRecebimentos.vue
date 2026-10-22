<template>
  <div class="p-6 min-h-screen bg-white dark:bg-black text-slate-900 dark:text-white">

    <!-- ===================== LISTA DE NOTAS ===================== -->
    <template v-if="!detalhe">
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Recebimento de Notas</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400">Conferência de notas fiscais e entrada no estoque</p>
        </div>
        <div class="flex items-center gap-2">
          <div class="relative">
            <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              ref="campoChave"
              v-model="chaveBusca"
              type="text"
              inputmode="text"
              autocomplete="off"
              placeholder="Bipar chave de acesso (44 caracteres)"
              class="w-72 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500 transition"
              :readonly="buscandoChave"
              @input="aoDigitarChave"
              @keydown.enter.prevent="buscarPorChave"
            />
            <p
              class="absolute top-full left-0 mt-1 text-xs whitespace-nowrap"
              :class="{
                'text-slate-400 dark:text-slate-500':   estadoLeitura === 'aguardando',
                'text-blue-600 dark:text-blue-400':     estadoLeitura === 'identificando' || estadoLeitura === 'localizada',
                'text-yellow-600 dark:text-yellow-400': estadoLeitura === 'xml_necessario',
                'text-red-600 dark:text-red-400':       estadoLeitura === 'erro',
              }"
            >
              {{ TEXTO_LEITURA[estadoLeitura] }}
            </p>
          </div>
          <button
            v-if="autenticacao.podeCadastrar"
            class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium text-sm"
            @click="modalImportarAberto = true"
          >
            <Upload :size="16" />
            Importar XML
          </button>
        </div>
      </div>

      <div v-if="carregandoLista" class="text-center py-12 text-slate-500 dark:text-slate-400">
        Carregando notas...
      </div>

      <div v-else-if="lista.length === 0" class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-16 text-center">
        <FileText class="mx-auto mb-4 text-slate-400 dark:text-slate-600" :size="48" />
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Nenhuma nota importada</h2>
        <p class="text-slate-500 dark:text-slate-400">Importe o XML de uma nota fiscal para começar a conferência.</p>
      </div>

      <div v-else class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <th class="text-left pb-3 font-medium">NF</th>
              <th class="text-left pb-3 font-medium">Fornecedor</th>
              <th class="text-left pb-3 font-medium">Emissão</th>
              <th class="text-left pb-3 font-medium">Pedido</th>
              <th class="text-left pb-3 font-medium">Valor</th>
              <th class="text-left pb-3 font-medium">Itens</th>
              <th class="text-left pb-3 font-medium">Status</th>
              <th class="text-left pb-3 font-medium">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr
              v-for="n in lista"
              :key="n.id_nota"
              class="hover:bg-slate-100 dark:hover:bg-slate-800/50 transition cursor-pointer"
              @click="abrirNota(n.id_nota)"
            >
              <td class="py-3 font-medium text-slate-900 dark:text-white">{{ n.numero }}<span class="text-slate-400 dark:text-slate-500"> / {{ n.serie }}</span></td>
              <td class="py-3 text-slate-600 dark:text-slate-300">{{ n.fornecedor_nome }}</td>
              <td class="py-3 text-slate-500 dark:text-slate-400">{{ formatarData(n.data_emissao) }}</td>
              <td class="py-3 text-slate-500 dark:text-slate-400">{{ n.numero_pedido || '—' }}</td>
              <td class="py-3 text-slate-600 dark:text-slate-300">{{ moeda(n.valor_total) }}</td>
              <td class="py-3 text-slate-500 dark:text-slate-400">
                {{ n.itens_count }} itens
                <span v-if="n.itens_ok > 0" class="text-green-600 dark:text-green-400">({{ n.itens_ok }} prontos)</span>
              </td>
              <td class="py-3">
                <span class="px-2 py-0.5 rounded text-xs font-bold text-white" :class="classeStatusNota(n.status)">
                  {{ rotuloStatusNota(n.status) }}
                </span>
              </td>
              <td class="py-3" @click.stop>
                <div class="flex items-center gap-3">
                  <button class="text-blue-600 dark:text-blue-400 hover:text-blue-500 transition text-sm font-medium" @click="abrirNota(n.id_nota)">
                    Abrir
                  </button>
                  <button
                    v-if="autenticacao.podeCadastrar"
                    class="text-red-600 dark:text-red-400 hover:text-red-500 transition"
                    title="Excluir nota"
                    @click="notaParaExcluir = n"
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

    <!-- ===================== DETALHE DA NOTA ===================== -->
    <template v-else>
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-6">
        <div>
          <button
            class="flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition mb-2"
            @click="voltarParaLista"
          >
            <ArrowLeft :size="16" />
            Voltar para as notas
          </button>
          <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">NF {{ detalhe.nota.numero }}</h1>
            <span class="px-2 py-0.5 rounded text-xs font-bold text-white" :class="classeStatusNota(detalhe.nota.status)">
              {{ rotuloStatusNota(detalhe.nota.status) }}
            </span>
          </div>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {{ detalhe.nota.fornecedor_nome }} · CNPJ {{ detalhe.nota.fornecedor_cnpj }}
          </p>
          <p class="text-sm text-slate-500 dark:text-slate-400">
            Emissão {{ formatarData(detalhe.nota.data_emissao) }} · {{ moeda(detalhe.nota.valor_total) }}
            ·
            <span v-if="detalhe.pedido">
              Pedido {{ detalhe.pedido.numero }} ({{ detalhe.pedido.status }})
            </span>
            <span v-else class="text-yellow-600 dark:text-yellow-400">Sem pedido vinculado</span>
          </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <div class="relative">
            <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              ref="campoChave"
              v-model="chaveBusca"
              type="text"
              inputmode="text"
              autocomplete="off"
              placeholder="Bipar outra nota (44 caracteres)"
              class="w-64 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500 transition"
              :readonly="buscandoChave"
              @input="aoDigitarChave"
              @keydown.enter.prevent="buscarPorChave"
            />
            <p
              class="absolute top-full left-0 mt-1 text-xs whitespace-nowrap"
              :class="{
                'text-slate-400 dark:text-slate-500':   estadoLeitura === 'aguardando',
                'text-blue-600 dark:text-blue-400':     estadoLeitura === 'identificando' || estadoLeitura === 'localizada',
                'text-yellow-600 dark:text-yellow-400': estadoLeitura === 'xml_necessario',
                'text-red-600 dark:text-red-400':       estadoLeitura === 'erro',
              }"
            >
              {{ TEXTO_LEITURA[estadoLeitura] }}
            </p>
          </div>
          <button
            v-if="autenticacao.podeCadastrar"
            class="flex items-center gap-2 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 px-4 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition text-sm font-medium"
            @click="notaParaExcluir = detalhe.nota"
          >
            <Trash2 :size="16" />
            Excluir nota
          </button>
          <button
            v-if="autenticacao.podeCadastrar"
            class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
            :disabled="selecionadosEntrada.length === 0 || darEntradaEmAndamento"
            @click="darEntrada"
          >
            <PackagePlus :size="16" />
            {{ darEntradaEmAndamento ? 'Dando entrada...' : `Dar entrada (${selecionadosEntrada.length})` }}
          </button>
        </div>
      </div>

      <!-- Avisos da importação -->
      <div
        v-if="avisos.length > 0"
        class="mb-4 rounded-lg border border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20 p-3 flex gap-2"
      >
        <AlertTriangle :size="18" class="text-yellow-600 dark:text-yellow-400 shrink-0 mt-0.5" />
        <ul class="text-sm text-yellow-800 dark:text-yellow-200 space-y-1">
                    <li v-for="(a, i) in avisos" :key="i">{{ a }}</li>
        </ul>
      </div>

      <!-- Comparação chave x XML -->
      <div v-if="detalhe.comparacao_chave" class="mb-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 p-3">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">Conferência: chave de acesso x XML</p>
        <table class="w-full text-sm">
          <thead>
            <tr class="text-slate-500 dark:text-slate-400 text-xs">
              <th class="text-left font-medium pb-1">Campo</th>
              <th class="text-left font-medium pb-1">Na chave</th>
              <th class="text-left font-medium pb-1">No XML</th>
              <th class="w-8"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="l in detalhe.comparacao_chave" :key="l.campo">
              <td class="py-1 text-slate-600 dark:text-slate-300">{{ l.campo }}</td>
              <td class="py-1 font-mono text-slate-900 dark:text-white">{{ l.chave }}</td>
              <td class="py-1 font-mono text-slate-900 dark:text-white">{{ l.xml }}</td>
              <td class="py-1 text-center">
                <span v-if="l.confere" class="text-green-600 dark:text-green-400">✓</span>
                <span v-else class="text-red-600 dark:text-red-400 font-bold">✗</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Resumo -->
      <div class="flex items-center gap-2 flex-wrap mb-4 text-xs">
        <span class="px-2 py-1 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
          {{ detalhe.resumo.total }} itens
        </span>
        <span class="px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
          {{ detalhe.resumo.ok }} prontos
        </span>
        <span class="px-2 py-1 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">
          {{ detalhe.resumo.sem_vinculo }} sem vínculo
        </span>
        <span class="px-2 py-1 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">
          {{ detalhe.resumo.divergente }} divergentes
        </span>
        <span class="px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
          {{ detalhe.resumo.entrou }} já entraram
        </span>
      </div>

      <!-- Itens -->
      <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <th class="text-left pb-3 font-medium w-6">
                <input
                  type="checkbox"
                  class="cursor-pointer"
                  :checked="todosSelecionados"
                  :disabled="itensOk.length === 0"
                  @change="alternarTodos"
                />
              </th>
              <th class="text-left pb-3 font-medium">Item da nota</th>
              <th class="text-left pb-3 font-medium">Qtd</th>
              <th class="text-left pb-3 font-medium">Valor unit.</th>
              <th class="text-left pb-3 font-medium">Status</th>
              <th class="text-left pb-3 font-medium">Localização</th>
              <th class="text-left pb-3 font-medium">Validade</th>
              <th class="text-left pb-3 font-medium">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <template v-for="item in detalhe.itens" :key="item.id_nota_item">
              <tr class="align-top">
                <td class="py-3">
                  <input
                    v-if="item.status === 'ok' && !item.unidade_incompativel && extras[item.id_nota_item]"
                    v-model="extras[item.id_nota_item].selecionado"
                    type="checkbox"
                    class="cursor-pointer"
                  />
                </td>

                <td class="py-3 max-w-md">
                  <p class="font-medium text-slate-900 dark:text-white">{{ item.descricao }}</p>
                  <p class="text-xs text-slate-400 dark:text-slate-500">Cód. fornecedor: {{ item.codigo_fornecedor }}</p>
                  <div v-if="item.pedido_item" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    → {{ item.pedido_item.sku }} · {{ item.pedido_item.descricao }}
                    <span class="text-slate-400 dark:text-slate-500">
                      (saldo {{ formatNumero(item.pedido_item.saldo) }} {{ item.pedido_item.unidade }})
                    </span>
                    <span
                      v-if="unidadeDiferente(item.unidade, item.pedido_item.unidade)"
                      class="ml-1 px-1.5 py-0.5 rounded bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 font-semibold"
                      title="A unidade da nota é diferente da unidade do pedido. Confira antes de dar entrada."
                    >
                      {{ item.unidade }} ≠ {{ item.pedido_item.unidade }}
                    </span>
                  </div>
                  <p v-if="item.status === 'divergente'" class="mt-1 text-xs text-red-600 dark:text-red-400">
                    A quantidade da nota passa do saldo que falta receber no pedido.
                  </p>
                </td>

                <td class="py-3 text-slate-700 dark:text-slate-200 whitespace-nowrap">
                  {{ formatNumero(item.quantidade) }} {{ item.unidade }}
                  <p v-if="item.status === 'entrou'" class="text-xs text-blue-600 dark:text-blue-400">
                    Entrou: {{ formatNumero(item.quantidade_entrada) }}
                  </p>
                </td>

                <td class="py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ moeda(item.valor_unitario) }}</td>

                <td class="py-3">
                  <span class="px-2 py-0.5 rounded text-xs font-bold text-white whitespace-nowrap" :class="classeStatusItem(item.status)">
                    {{ rotuloStatusItem(item.status) }}
                  </span>
                </td>

                <td class="py-3">
                  <input
                    v-if="item.status === 'ok' && extras[item.id_nota_item]"
                    v-model="extras[item.id_nota_item].localizacao"
                    type="text"
                    maxlength="100"
                    placeholder="Rua / Prat. / Nível"
                    class="w-48 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-1.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500"
                  />
                  <span v-else class="text-slate-400 dark:text-slate-500">—</span>
                </td>

                <td class="py-3">
                  <input
                    v-if="item.status === 'ok' && extras[item.id_nota_item]"
                    v-model="extras[item.id_nota_item].data_validade"
                    type="date"
                    max="2099-12-31"
                    class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-2 py-1.5 text-sm text-slate-900 dark:text-white outline-none focus:border-blue-500"
                  />
                  <span v-else class="text-slate-400 dark:text-slate-500">—</span>
                </td>

                <td class="py-3">
                  <button
                    v-if="item.status === 'sem_vinculo' && autenticacao.podeCadastrar && detalhe.pedido"
                    class="flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:text-blue-500 transition text-sm font-medium"
                    @click="alternarExpandido(item.id_nota_item)"
                  >
                    <Link2 :size="14" />
                    Vincular
                    <ChevronDown :size="14" class="transition-transform" :class="itemExpandido === item.id_nota_item ? 'rotate-180' : ''" />
                  </button>
                  <span v-else-if="item.status === 'sem_vinculo' && !detalhe.pedido" class="text-xs text-slate-400 dark:text-slate-500">
                    Sem pedido
                  </span>
                </td>
              </tr>

              <!-- Sugestões de vínculo -->
              <tr v-if="itemExpandido === item.id_nota_item">
                <td></td>
                <td colspan="7" class="pb-4">
                  <div class="rounded-lg bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 p-3">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">
                      Qual item do pedido corresponde a "{{ item.descricao }}"?
                    </p>

                    <p v-if="item.sugestoes.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
                      Nenhum item livre do pedido parece corresponder a este.
                    </p>

                    <div v-else class="space-y-2">
                      <div
                        v-for="s in item.sugestoes"
                        :key="s.id_pedido_item"
                        class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2"
                      >
                        <div class="min-w-0">
                          <p class="text-sm text-slate-900 dark:text-white truncate" :title="s.descricao">{{ s.descricao }}</p>
                          <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ s.sku }} · saldo {{ formatNumero(s.saldo) }} {{ s.unidade }}
                            <span
                              v-if="s.pontuacao >= 1"
                              class="ml-1 px-1.5 py-0.5 rounded bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 font-semibold"
                            >
                              provável
                            </span>
                            <span
                              v-if="unidadeDiferente(item.unidade, s.unidade)"
                              class="ml-1 px-1.5 py-0.5 rounded bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 font-semibold"
                            >
                              {{ item.unidade }} ≠ {{ s.unidade }}
                            </span>
                          </p>
                        </div>
                        <button
                          class="shrink-0 bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50"
                          :disabled="vinculandoId !== null"
                          @click="vincular(item, s)"
                        >
                          {{ vinculandoId === item.id_nota_item ? 'Vinculando...' : 'Vincular' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </template>

    <!-- carregando detalhe -->
    <div v-if="carregandoDetalhe" class="fixed inset-0 bg-black/40 flex items-center justify-center z-40">
      <div class="bg-white dark:bg-slate-900 rounded-lg px-6 py-4 text-sm text-slate-700 dark:text-slate-200">
        Carregando nota...
      </div>
    </div>

    <!-- ===== MODAL IMPORTAR XML ===== -->
    <div v-if="modalImportarAberto" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Upload class="text-blue-600 dark:text-blue-400" :size="20" />
            <div>
              <h2 class="text-slate-900 dark:text-white font-bold">Importar XML da nota</h2>
              <p class="text-slate-500 dark:text-slate-400 text-xs">NF-e em formato XML (até 5 MB)</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="fecharImportar">
            <X :size="18" />
          </button>
        </div>

        <div class="space-y-4 mb-6">
          <p v-if="chaveEsperada" class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 p-3 text-xs text-blue-800 dark:text-blue-200">
            O XML precisa ser da chave <span class="font-mono break-all">{{ chaveEsperada }}</span>.
            Se for de outra nota, a importação é recusada.
          </p>
          <label class="flex flex-col gap-1">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Arquivo XML</span>
            <input
              type="file"
              accept=".xml,text/xml,application/xml"
              class="text-sm text-slate-700 dark:text-slate-200 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-slate-200 dark:file:bg-slate-700 file:text-slate-700 dark:file:text-slate-200 file:cursor-pointer"
              @change="aoEscolherArquivo"
            />
          </label>

          <label class="flex flex-col gap-1">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Número do pedido (opcional)</span>
            <input
              v-model="numeroPedido"
              type="text"
              maxlength="30"
              placeholder="Só se a nota não trouxer o pedido"
              class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none focus:border-blue-500"
            />
          </label>
        </div>

        <div class="flex gap-3">
          <button
            class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            @click="fecharImportar"
          >
            Cancelar
          </button>
          <button
            :disabled="importando || !arquivoXml"
            class="flex-1 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition font-medium disabled:opacity-50"
            @click="importarXml"
          >
            {{ importando ? 'Importando...' : 'Importar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ===== PAINEL: CHAVE BIPADA SEM NOTA CADASTRADA ===== -->
    <NotaNaoImportadaModal
      v-if="notaNaoImportada"
      :chave="notaNaoImportada.chave"
      :dados="notaNaoImportada.dados"
      :pode-importar="autenticacao.podeCadastrar"
      @enviar-xml="enviarXmlDaChave"
      @fechar="fecharNaoImportada"
    />

    <!-- ===== MODAL EXCLUIR NOTA ===== -->
    <div v-if="notaParaExcluir" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-md p-6">
        <div class="flex justify-between items-start mb-5">
          <div class="flex items-center gap-2">
            <Trash2 class="text-red-600 dark:text-red-400" :size="20" />
            <div>
              <h2 class="text-slate-900 dark:text-white font-bold">Excluir nota</h2>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Esta ação não pode ser desfeita</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="notaParaExcluir = null">
            <X :size="18" />
          </button>
        </div>

        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
          <p class="text-slate-600 dark:text-slate-300 text-sm">
            Você está excluindo a nota <strong class="text-slate-900 dark:text-white">{{ notaParaExcluir.numero }}</strong>.
            Notas com itens que já deram entrada no estoque não podem ser excluídas.
          </p>
        </div>

        <div class="flex gap-3">
          <button
            class="flex-1 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            @click="notaParaExcluir = null"
          >
            Cancelar
          </button>
          <button
            :disabled="excluindo"
            class="flex-1 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 transition font-medium disabled:opacity-50"
            @click="excluirNota"
          >
            {{ excluindo ? 'Excluindo...' : 'Confirmar Exclusão' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { Upload, Search, Trash2, X, ArrowLeft, FileText, PackagePlus, Link2, ChevronDown, AlertTriangle } from 'lucide-vue-next'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'
import { useNotificacao } from '@/composables/useNotificacao'
import { formatarData } from '@/utils/date'
import { normalizarChave, validarChave, extrairDadosChave } from '@/utils/chaveAcesso'
import NotaNaoImportadaModal from '@/componentes/NotaNaoImportadaModal.vue'

const autenticacao      = useAutenticacaoStore()
const { sucesso, erro } = useNotificacao()

// ===== Estado geral =====
const lista             = ref([])
const carregandoLista   = ref(false)
const detalhe           = ref(null)   // { nota, pedido, resumo, itens }
const carregandoDetalhe = ref(false)
const avisos            = ref([])

// ===== Bipador (chave de acesso) =====
const chaveBusca    = ref('')
const buscandoChave = ref(false)
const campoChave    = ref(null)   // o mesmo ref serve para a lista e para o detalhe (nunca aparecem juntos)

// aguardando | identificando | localizada | xml_necessario | erro
const estadoLeitura = ref('aguardando')
let timerAuto       = null
let ultimaLeitura   = { chave: '', em: 0 }

const TEXTO_LEITURA = {
  aguardando:     'Aguardando leitura',
  identificando:  'Identificando nota...',
  localizada:     'Nota localizada',
  xml_necessario: 'XML necessário: nota ainda não importada',
  erro:           'Erro: confira a mensagem e bipe novamente',
}

async function focarCampoChave() {
  await nextTick()
  campoChave.value?.focus()
}

// Dispara a busca sozinha quando a chave chega completa (44 caracteres) e o leitor para de enviar.
// Se o leitor mandar Enter, o Enter chama buscarPorChave direto e cancela este timer.
function aoDigitarChave() {
  clearTimeout(timerAuto)
  if (estadoLeitura.value === 'erro') estadoLeitura.value = 'aguardando'
  if (normalizarChave(chaveBusca.value).length === 44) {
    timerAuto = setTimeout(buscarPorChave, 100)
  }
}

const notaNaoImportada = ref(null)   // { chave, dados } quando a chave bipada não tem nota cadastrada
const chaveEsperada    = ref('')     // chave bipada que o XML importado precisa ter

// ===== Estado do detalhe =====
const extras                = ref({})   // { [id_nota_item]: { selecionado, localizacao, data_validade } }
const itemExpandido         = ref(null)
const vinculandoId          = ref(null)
const darEntradaEmAndamento = ref(false)

// ===== Modais =====
const modalImportarAberto = ref(false)
const arquivoXml          = ref(null)
const numeroPedido        = ref('')
const importando          = ref(false)
const notaParaExcluir     = ref(null)
const excluindo           = ref(false)

// ===== Formatação =====
function formatNumero(valor) {
  return Number(valor ?? 0).toLocaleString('pt-BR')
}

function moeda(valor) {
  return Number(valor ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

const normalizarUnidade = (u) => String(u ?? '').trim().toUpperCase()
const unidadeDiferente  = (a, b) => normalizarUnidade(a) !== normalizarUnidade(b)

// ===== Status =====
const ROTULOS_NOTA = { pendente: 'Pendente', parcial: 'Parcial', conferida: 'Conferida' }
const CLASSES_NOTA = { pendente: 'bg-slate-500', parcial: 'bg-yellow-600', conferida: 'bg-green-700' }

const ROTULOS_ITEM = { pendente: 'Pendente', ok: 'Pronto', divergente: 'Divergente', sem_vinculo: 'Sem vínculo', entrou: 'Entrou' }
const CLASSES_ITEM = {
  pendente: 'bg-slate-500',
  ok: 'bg-green-700',
  divergente: 'bg-red-600',
  sem_vinculo: 'bg-yellow-600',
  entrou: 'bg-blue-600',
}

const rotuloStatusNota = (s) => ROTULOS_NOTA[s] || s
const classeStatusNota = (s) => CLASSES_NOTA[s] || 'bg-slate-500'
const rotuloStatusItem = (s) => ROTULOS_ITEM[s] || s
const classeStatusItem = (s) => CLASSES_ITEM[s] || 'bg-slate-500'

// ===== Seleção para entrada =====
// Item com unidade diferente da do pedido fica de fora: o backend bloqueia a entrada dele
const itensOk = computed(() =>
  (detalhe.value?.itens ?? []).filter((i) => i.status === 'ok' && !i.unidade_incompativel)
)

const selecionadosEntrada = computed(() =>
  itensOk.value.filter((i) => extras.value[i.id_nota_item]?.selecionado)
)

const todosSelecionados = computed(() =>
  itensOk.value.length > 0 && selecionadosEntrada.value.length === itensOk.value.length
)

function alternarTodos() {
  const marcar = !todosSelecionados.value
  itensOk.value.forEach((i) => {
    if (extras.value[i.id_nota_item]) extras.value[i.id_nota_item].selecionado = marcar
  })
}

// Mantém o que o usuário já digitou quando a nota é recarregada
function sincronizarExtras() {
  const novo = {}
  for (const item of detalhe.value?.itens ?? []) {
    const anterior = extras.value[item.id_nota_item]
    novo[item.id_nota_item] = {
      selecionado:   (item.status === 'ok' && !item.unidade_incompativel)
        ? (anterior ? anterior.selecionado : true)
        : false,
      localizacao:   anterior?.localizacao ?? '',
      data_validade: anterior?.data_validade ?? '',
    }
  }
  extras.value = novo
}

function definirDetalhe(dados) {
  detalhe.value = dados
  sincronizarExtras()
}

// Foco no campo do bipador só quando muda a nota aberta (ou ao voltar para a lista),
// para não roubar o foco de quem está digitando localização/validade
watch(() => detalhe.value?.nota?.id_nota ?? null, focarCampoChave)

// ===== Lista =====
async function carregarLista() {
  carregandoLista.value = true
  try {
    const resposta = await api.get('/recebimentos')
    lista.value = resposta.data
  } catch (e) {
    console.error(e)
    erro('Não foi possível carregar as notas.')
  } finally {
    carregandoLista.value = false
  }
}

async function abrirNota(id) {
  carregandoDetalhe.value = true
  try {
    const resposta = await api.get(`/recebimentos/${id}`)
    extras.value = {}
    itemExpandido.value = null
    avisos.value = []
    definirDetalhe(resposta.data)
  } catch (e) {
    console.error(e)
    erro(e.response?.data?.message || 'Não foi possível abrir a nota.')
  } finally {
    carregandoDetalhe.value = false
  }
}

async function buscarPorChave() {
  clearTimeout(timerAuto)
  if (buscandoChave.value) return

  // Painel aberto por cima: ignora a leitura para não sobrescrever o que está na tela
  if (notaNaoImportada.value || modalImportarAberto.value || notaParaExcluir.value) {
    chaveBusca.value = ''
    return
  }

  const chave = normalizarChave(chaveBusca.value)

  // Limpa sempre, para a próxima bipada não se somar à anterior
  chaveBusca.value = ''
  if (!chave) return

  // Descarta evento duplicado da mesma chave em até 2 s
  const agora = Date.now()
  if (chave === ultimaLeitura.chave && agora - ultimaLeitura.em < 2000) return
  ultimaLeitura = { chave, em: agora }

  const problema = validarChave(chave)
  if (problema) {
    estadoLeitura.value = 'erro'
    erro(problema)
    focarCampoChave()
    return
  }

  buscandoChave.value = true
  estadoLeitura.value = 'identificando'
  try {
    const resposta = await api.get(`/recebimentos/chave/${chave}`)
    extras.value = {}
    itemExpandido.value = null
    avisos.value = []
    definirDetalhe(resposta.data)
    estadoLeitura.value = 'localizada'
  } catch (e) {
    if (e.response?.status === 404 && e.response.data?.nao_importada) {
      // Nota ainda não cadastrada: mostra o que a chave informa e oferece o envio do XML
      estadoLeitura.value = 'xml_necessario'
      notaNaoImportada.value = { chave, dados: extrairDadosChave(chave) }
    } else {
      console.error(e)
      estadoLeitura.value = 'erro'
      erro(
        e.response
          ? (e.response.data?.message || 'Não foi possível buscar a nota.')
          : 'Falha de comunicação com o servidor. Tente bipar novamente.'
      )
    }
  } finally {
    buscandoChave.value = false
    focarCampoChave()
  }
}

async function voltarParaLista() {
  detalhe.value = null
  extras.value = {}
  itemExpandido.value = null
  avisos.value = []
  estadoLeitura.value = 'aguardando'
  await carregarLista()
}

// ===== Importar XML =====
function aoEscolherArquivo(evento) {
  arquivoXml.value = evento.target.files?.[0] || null
}

function fecharImportar() {
  modalImportarAberto.value = false
  arquivoXml.value = null
  numeroPedido.value = ''
  chaveEsperada.value = ''
  estadoLeitura.value = 'aguardando'
  focarCampoChave()
}

// Chave bipada sem nota cadastrada: abre a importação já presa a essa chave
function enviarXmlDaChave() {
  chaveEsperada.value = notaNaoImportada.value.chave
  notaNaoImportada.value = null
  modalImportarAberto.value = true
}

function fecharNaoImportada() {
  notaNaoImportada.value = null
  estadoLeitura.value = 'aguardando'
  focarCampoChave()
}

async function importarXml() {
  if (!arquivoXml.value) {
    erro('Selecione o XML da nota fiscal.')
    return
  }

  const form = new FormData()
  form.append('arquivo', arquivoXml.value)
  if (numeroPedido.value.trim()) form.append('numero_pedido', numeroPedido.value.trim())
  if (chaveEsperada.value) form.append('chave_esperada', chaveEsperada.value)

  importando.value = true
  try {
    const resposta = await api.post('/recebimentos/xml', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    avisos.value = resposta.data.avisos || []
    extras.value = {}
    itemExpandido.value = null
    definirDetalhe(resposta.data)
    fecharImportar()
    sucesso('Nota importada com sucesso.')
  } catch (e) {
    console.error(e)
    const dados = e.response?.data
    erro(dados?.message || 'Erro ao importar o XML.')
    // nota já importada antes: abre a existente
    if (dados?.id_nota) {
      fecharImportar()
      await abrirNota(dados.id_nota)
    }
  } finally {
    importando.value = false
  }
}

// ===== Vincular item da nota a item do pedido =====
function alternarExpandido(idNotaItem) {
  itemExpandido.value = itemExpandido.value === idNotaItem ? null : idNotaItem
}

async function vincular(item, sugestao) {
  vinculandoId.value = item.id_nota_item
  try {
    const resposta = await api.post(
      `/recebimentos/${detalhe.value.nota.id_nota}/itens/${item.id_nota_item}/vincular`,
      { id_pedido_item: sugestao.id_pedido_item }
    )
    itemExpandido.value = null
    definirDetalhe(resposta.data)
    sucesso('Item vinculado ao pedido.')
  } catch (e) {
    console.error(e)
    erro(e.response?.data?.message || 'Não foi possível vincular o item.')
  } finally {
    vinculandoId.value = null
  }
}

// ===== Dar entrada no estoque =====
async function darEntrada() {
  if (selecionadosEntrada.value.length === 0) return

  const itens = selecionadosEntrada.value.map((i) => {
    const e = extras.value[i.id_nota_item]
    const obj = { id_nota_item: i.id_nota_item }
    if (e.localizacao.trim()) obj.localizacao = e.localizacao.trim()
    if (e.data_validade) obj.data_validade = e.data_validade
    return obj
  })

  darEntradaEmAndamento.value = true
  try {
    const resposta = await api.post(`/recebimentos/${detalhe.value.nota.id_nota}/entrada`, { itens })
    const { entraram, ignorados } = resposta.data.entrada || { entraram: 0, ignorados: [] }
    definirDetalhe(resposta.data)

    if (entraram > 0) sucesso(`${entraram} item(ns) deram entrada no estoque.`)
    if (ignorados.length > 0) {
      const motivos = ignorados.map((x) => x.motivo).join(' ')
      erro(`${ignorados.length} item(ns) não entraram. ${motivos}`)
    }
  } catch (e) {
    console.error(e)
    erro(e.response?.data?.message || 'Erro ao dar entrada no estoque.')
  } finally {
    darEntradaEmAndamento.value = false
  }
}

// ===== Excluir nota =====
async function excluirNota() {
  if (!notaParaExcluir.value) return
  excluindo.value = true
  try {
    await api.delete(`/recebimentos/${notaParaExcluir.value.id_nota}`)
    sucesso('Nota excluída com sucesso.')
    notaParaExcluir.value = null
    detalhe.value = null
    extras.value = {}
    avisos.value = []
    await carregarLista()
  } catch (e) {
    console.error(e)
    erro(e.response?.data?.message || 'Erro ao excluir a nota.')
    notaParaExcluir.value = null
  } finally {
    excluindo.value = false
  }
}

onMounted(async () => {
  await carregarLista()
  focarCampoChave()
})
</script>