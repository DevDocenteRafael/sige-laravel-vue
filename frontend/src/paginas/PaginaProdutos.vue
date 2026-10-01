<template>
  <div class="p-6 bg-white dark:bg-black min-h-screen">

    <!-- Cabeçalho -->
    <div class="flex justify-between items-center mb-1">
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Produtos</h1>
    </div>
    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Visão geral de todos os produtos e seus lotes</p>

    <!-- Filtros -->
    <div class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-transparent rounded-xl p-4 mb-6 flex flex-col gap-3">
      <input
        v-model="termoDeBusca"
        type="text"
        placeholder="🔍 Buscar por nome ou SKU..."
        class="w-full bg-white dark:bg-slate-700 border border-slate-300 dark:border-transparent text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-400 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-slate-500"
        @input="buscarComAtraso"
      />
      <div class="flex items-center gap-3 flex-wrap">
        <button
          :class="[
            'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors w-fit',
            filtroBaixo
              ? 'bg-orange-500 text-white'
              : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'
          ]"
          @click="toggleEstoqueBaixo"
        >
          ⬇ Estoque Baixo
        </button>

        <select
          v-model="filtroCategoria"
          class="bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg px-4 py-2 text-sm font-medium border-none focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-slate-500"
        >
          <option value="">Todas as Categorias</option>
          <option v-for="cat in categoriasDisponiveis" :key="cat" :value="cat">{{ cat }}</option>
        </select>

        <select
          v-model="filtroCentro"
          class="bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg px-4 py-2 text-sm font-medium border-none focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-slate-500"
        >
          <option value="">Todos os Centros de Custo</option>
          <option value="sem">Sem centro de custo</option>
          <option v-for="c in centros" :key="c.id_centro_custo" :value="c.id_centro_custo">
            {{ c.codigo }} - {{ c.nome }}
          </option>
        </select>
      </div>
    </div>

    <!-- Cards de resumo -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-transparent rounded-xl p-4">
        <p class="text-slate-500 dark:text-slate-400 text-xs mb-1">Total de Produtos Únicos</p>
        <p class="text-slate-900 dark:text-white text-2xl font-bold">{{ formatNumero(produtos.length) }}</p>
      </div>
      <div class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-transparent rounded-xl p-4">
        <p class="text-slate-500 dark:text-slate-400 text-xs mb-1">Produtos Vencendo</p>
        <p class="text-orange-600 dark:text-orange-400 text-2xl font-bold">{{ formatNumero(totalVencendo) }}</p>
      </div>
      <div class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-transparent rounded-xl p-4">
        <p class="text-slate-500 dark:text-slate-400 text-xs mb-1">Produtos Vencidos</p>
        <p class="text-red-600 dark:text-red-400 text-2xl font-bold">{{ formatNumero(totalVencidos) }}</p>
      </div>
    </div>

    <!-- Carregando -->
    <div v-if="carregando" class="text-center py-12 text-slate-500 dark:text-slate-400">
      Carregando produtos...
    </div>

    <!-- Vazio -->
    <div v-else-if="produtosFiltrados.length === 0" class="text-center py-12 text-slate-500 dark:text-slate-400">
      Nenhum produto encontrado.
    </div>

    <!-- Tabela -->
    <div v-else class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-transparent rounded-xl overflow-visible">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-left">
            <th class="px-4 py-3">SKU</th>
            <th class="px-4 py-3">Nome do Produto</th>
            <th class="px-4 py-3">Quantidade Total</th>
            <th class="px-4 py-3">Próxima Validade</th>
            <th class="px-4 py-3">Lotes</th>
            <th class="px-4 py-3">Centro de Custo</th>
            <th class="px-4 py-3">Status Validade</th>
            <th class="px-4 py-3">Status Estoque</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in produtosPaginados"
            :key="item.id_produto"
            class="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors cursor-pointer"
            @click="abrirModalValidades(item)"
          >
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300 font-mono text-xs">{{ item.sku ?? '—' }}</td>
            <td class="px-4 py-3 text-slate-900 dark:text-white font-medium">{{ item.nome }}</td>
            <td class="px-4 py-3" :class="estoqueBaixo(item) ? 'text-orange-600 dark:text-orange-400 font-bold' : 'text-slate-900 dark:text-white'">
              {{ formatNumero(item.quantidade) }} {{ item.unidade_medida }}
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
              {{ formatarDataCurta(item.data_validade) }}<span v-if="datasUnicas(item).length > 1" class="text-slate-400"> +{{ datasUnicas(item).length - 1 }}</span>
            </td>
            <td class="px-4 py-3" @click.stop>
              <span v-if="!item.lotes || item.lotes.length === 0" class="text-slate-400 dark:text-slate-500 text-xs">—</span>
              <button
                v-else
                type="button"
                class="flex items-center gap-1.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs px-2 py-1 rounded font-medium hover:bg-slate-300 dark:hover:bg-slate-600"
                @click="toggleLotes(item, $event)"
              >
                {{ item.lotes.length }} {{ item.lotes.length === 1 ? 'lote' : 'lotes' }}
                <span class="text-[10px]">{{ dropdownAberto === item.id_produto ? '▲' : '▼' }}</span>
              </button>

              <Teleport to="body">
                <div
                  v-if="dropdownAberto === item.id_produto"
                  :style="{ position: 'fixed', top: posicaoDropdown.top + 'px', left: posicaoDropdown.left + 'px' }"
                  class="z-9999 min-w-40 max-h-56 overflow-y-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-lg p-2 flex flex-col gap-1"
                  @click.stop
                >
                  <!-- Busca dentro do dropdown (só aparece com 10+ lotes) -->
                  <input
                    v-if="item.lotes.length >= 10"
                    v-model="buscaLotePorProduto[item.id_produto]"
                    type="text"
                    placeholder="Filtrar lote..."
                    class="w-full mb-1 bg-slate-100 dark:bg-slate-700 border-none text-slate-900 dark:text-white placeholder-slate-400 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500"
                    @click.stop
                  />

                  <span v-if="lotesFiltrados(item).length === 0" class="text-slate-400 dark:text-slate-500 text-xs px-1 py-1">
                    Nenhum lote encontrado.
                  </span>

                  <button
                    v-for="numeroLote in lotesFiltrados(item)"
                    :key="numeroLote"
                    type="button"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs px-2 py-1 rounded font-medium text-center transition-colors"
                    @click.stop="toggleMenuLote(item, numeroLote, $event)"
                  >
                    {{ numeroLote }}
                  </button>
                </div>
              </Teleport>
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs">
              {{ item.centros_custo?.length ? item.centros_custo.join(', ') : '—' }}
            </td>
            <td class="px-4 py-3">
              <span :class="badgeValidade(item)">
                {{ labelValidade(item) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                :class="estoqueBaixo(item)
                  ? 'bg-orange-100 dark:bg-orange-500/20 text-orange-700 dark:text-orange-400 px-2 py-1 rounded text-xs font-semibold'
                  : 'bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 px-2 py-1 rounded text-xs font-semibold'"
              >
                {{ estoqueBaixo(item) ? '↓ Baixo' : 'OK' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <PaginacaoControles
        v-model:pagina-atual="paginaAtual"
        v-model:por-pagina="porPagina"
        :total-paginas="totalPaginas"
        :total="produtosFiltrados.length"
        rotulo="produtos"
      />
    </div>

    <!-- Submenu de ações rápidas do lote (único, fora do v-for, teleportado) -->
    <Teleport to="body">
      <div
        v-if="menuLoteAtivo"
        :style="{ position: 'fixed', top: posicaoMenuLote.top + 'px', left: posicaoMenuLote.left + 'px' }"
        class="z-9999 min-w-37.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg shadow-lg p-1 flex flex-col"
        @click.stop
      >
        <button
          type="button"
          class="text-left text-xs px-2 py-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200"
          @click="acionarTransferir(menuLoteAtivo.item, menuLoteAtivo.numeroLote)"
        >
          ↔ Transferir
        </button>
        <button
          type="button"
          class="text-left text-xs px-2 py-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200"
          @click="acionarBaixa(menuLoteAtivo.item, menuLoteAtivo.numeroLote)"
        >
          ↓ Registrar Baixa
        </button>
        <button
          type="button"
          class="text-left text-xs px-2 py-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200"
          @click="acionarHistorico(menuLoteAtivo.item, menuLoteAtivo.numeroLote)"
        >
          🕒 Ver Histórico
        </button>
      </div>
    </Teleport>

    <ModalValidadesLotes
      :produto="produtoSelecionado"
      :lote-destaque="loteDestaque"
      :format-numero="formatNumero"
      :formatar-data="formatarData"
      :badge-validade="badgeValidade"
      :label-validade="labelValidade"
      @fechar="fecharModalValidades"
    />

    <ModalBaixaEstoque
      v-if="modalBaixa"
      :item="modalBaixa"
      @fechar="modalBaixa = null"
      @salvo="aoSalvarAcaoLote"
    />

    <ModalTransferirItem
      v-if="modalTransferir"
      :item="modalTransferir.item"
      :lotes="modalTransferir.lotes"
      :lote-destino-inicial="null"
      @fechar="modalTransferir = null"
      @salvo="aoSalvarAcaoLote"
    />

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import api from '@/servicos/api'
import ModalValidadesLotes from '@/componentes/ui/ModalValidadesLotes.vue'
import ModalBaixaEstoque from '@/componentes/ui/ModalBaixaEstoque.vue'
import ModalTransferirItem from '@/componentes/ui/ModalTransferirItem.vue'
import PaginacaoControles from '@/paginas/PaginacaoControles.vue'
import { useNotificacao } from '@/composables/useNotificacao'
import { useCentrosCusto } from '@/composables/useCentrosCusto'

const { erro } = useNotificacao()
const { centros, carregar: carregarCentros } = useCentrosCusto()

const produtos            = ref([])
const carregando          = ref(false)
const termoDeBusca        = ref('')
const filtroBaixo         = ref(false)
const filtroCategoria     = ref('')
const filtroCentro        = ref('') // '' = todos | 'sem' = sem centro | id
const produtoSelecionado  = ref(null)
const loteDestaque        = ref(null)
const dropdownAberto      = ref(null)
const posicaoDropdown     = ref({ top: 0, left: 0 })
const menuLoteAtivo       = ref(null) // { chave, item, numeroLote }
const posicaoMenuLote     = ref({ top: 0, left: 0 })
const buscaLotePorProduto = ref({})
const modalBaixa          = ref(null) // { id_item, nome, quantidade, unidade_medida }
const modalTransferir     = ref(null) // { item: {...}, lotes: [...] }
const paginaAtual         = ref(1)
const porPagina           = ref(10)

let temporizadorBusca = null

const estoqueBaixo = (item) => item.quantidade <= item.estoque_minimo

const formatNumero = (valor) => Number(valor ?? 0).toLocaleString('pt-BR')

const diasParaVencer = (data) => {
  if (!data) return null
  const diff = new Date(data) - new Date()
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
}

const labelValidade = (item) => {
  const dias = diasParaVencer(item.data_validade)
  if (dias === null) return '—'
  if (dias < 0) return `Vencido há ${Math.abs(dias)}d`
  if (dias <= 30) return `${dias}d`
  return 'OK'
}

const badgeValidade = (item) => {
  const dias = diasParaVencer(item.data_validade)
  const base = 'px-2 py-1 rounded text-xs font-semibold'
  if (dias === null) return `${base} text-slate-500 dark:text-slate-400`
  if (dias < 0)     return `${base} bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400`
  if (dias <= 30)   return `${base} bg-orange-100 dark:bg-orange-500/20 text-orange-700 dark:text-orange-400`
  return `${base} bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400`
}

const formatarData = (data) => {
  if (!data) return '—'
  const dias = diasParaVencer(data)
  const dataFmt = new Date(data).toLocaleDateString('pt-BR')
  return dias !== null ? `${dataFmt} (${dias}d)` : dataFmt
}

// Versão curta (sem os dias entre parênteses), usada na lista compacta
const formatarDataCurta = (data) => {
  if (!data) return '—'
  return new Date(data).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: '2-digit' })
}

// Datas distintas de validade que esse produto tem entre os itens de lote
const datasUnicas = (item) => {
  if (!item.validades || item.validades.length === 0) return []
  return [...new Set(item.validades.map(v => v.data_validade))]
}

const abrirModalValidades = (item) => {
  loteDestaque.value = null
  produtoSelecionado.value = item
}

const fecharModalValidades = () => {
  produtoSelecionado.value = null
  loteDestaque.value = null
}

const toggleLotes = (item, evento) => {
  if (dropdownAberto.value === item.id_produto) {
    dropdownAberto.value = null
    menuLoteAtivo.value = null
    return
  }

  const rect = evento.currentTarget.getBoundingClientRect()
  const linhasVisiveis = Math.min(item.lotes.length, 8)
  const alturaEstimada = linhasVisiveis * 30 + (item.lotes.length >= 10 ? 36 : 0) + 20
  const espacoAbaixo = window.innerHeight - rect.bottom
  const abrirParaCima = espacoAbaixo < alturaEstimada

  const larguraMenu = 160
  const espacoDireita = window.innerWidth - rect.left
  const abrirParaEsquerda = espacoDireita < larguraMenu

  posicaoDropdown.value = {
    top: abrirParaCima ? rect.top - alturaEstimada - 4 : rect.bottom + 4,
    left: abrirParaEsquerda ? rect.right - larguraMenu : rect.left,
  }

  dropdownAberto.value = item.id_produto
  menuLoteAtivo.value = null
}

// --- Filtro de busca dentro do dropdown (10+ lotes) ---
const lotesFiltrados = (item) => {
  const termo = (buscaLotePorProduto.value[item.id_produto] || '').toLowerCase().trim()
  if (!termo) return item.lotes
  return item.lotes.filter(numeroLote => String(numeroLote).toLowerCase().includes(termo))
}

// --- Menu de ações rápidas por lote ---
const chaveMenuLote = (item, numeroLote) => `${item.id_produto}-${numeroLote}`

const toggleMenuLote = (item, numeroLote, evento) => {
  const chave = chaveMenuLote(item, numeroLote)
  if (menuLoteAtivo.value?.chave === chave) {
    menuLoteAtivo.value = null
    return
  }

  const rect = evento.currentTarget.getBoundingClientRect()
  const larguraMenu = 150
  const espacoDireita = window.innerWidth - rect.right
  const abrirParaEsquerda = espacoDireita < larguraMenu

  posicaoMenuLote.value = {
    top: rect.top,
    left: abrirParaEsquerda ? rect.left - larguraMenu - 4 : rect.right + 4,
  }

  menuLoteAtivo.value = { chave, item, numeroLote }
}

// Busca a entrada de validade (item_lote) correspondente ao número do lote clicado
const encontrarValidade = (item, numeroLote) =>
  item.validades?.find(v => v.numero_lote === numeroLote)

const acionarTransferir = async (item, numeroLote) => {
  const validade = encontrarValidade(item, numeroLote)
  if (!validade) {
    erro('Não foi possível localizar os dados desse lote.')
    return
  }

  let todosOsLotes = []
  try {
    const { data } = await api.get('/lotes')
    todosOsLotes = data
      .filter(l => l.id_lote !== validade.id_lote) // remove o lote de origem
      .map(l => ({ id_lote: l.id_lote, numero_lote: l.numero_lote }))
  } catch {
    erro('Não foi possível carregar a lista de lotes.')
    return
  }

  modalTransferir.value = {
    item: {
      id_item:        validade.id_item,
      id_lote:        validade.id_lote,
      quantidade:     validade.quantidade,
      unidade_medida: validade.unidade,
      produto:        { nome: item.nome },
    },
    lotes: todosOsLotes,
  }
  menuLoteAtivo.value = null
  dropdownAberto.value = null
}

const acionarBaixa = (item, numeroLote) => {
  const validade = encontrarValidade(item, numeroLote)
  if (!validade) {
    erro('Não foi possível localizar os dados desse lote.')
    return
  }
  modalBaixa.value = {
    id_item:        validade.id_item,
    nome:           item.nome,
    quantidade:     validade.quantidade,
    unidade_medida: validade.unidade,
  }
  menuLoteAtivo.value = null
  dropdownAberto.value = null
}

const acionarHistorico = (item, numeroLote) => {
  loteDestaque.value = numeroLote
  produtoSelecionado.value = item
  menuLoteAtivo.value = null
  dropdownAberto.value = null
}

const aoSalvarAcaoLote = () => {
  modalTransferir.value = null
  modalBaixa.value = null
  carregarProdutos()
}

const fecharDropdownAoClicarFora = () => {
  dropdownAberto.value = null
  menuLoteAtivo.value = null
}

const categoriasDisponiveis = computed(() => {
  const categorias = produtos.value
    .map(item => item.categoria_nome)
    .filter(Boolean)
  return [...new Set(categorias)].sort()
})

const produtosFiltrados = computed(() => {
  return produtos.value.filter(item => {
    const termo = termoDeBusca.value.toLowerCase()
    const buscaOk = !termo ||
      item.nome?.toLowerCase().includes(termo) ||
      item.sku?.toLowerCase().includes(termo)
    const baixoOk = !filtroBaixo.value || estoqueBaixo(item)
    const categoriaOk = !filtroCategoria.value || item.categoria_nome === filtroCategoria.value
    return buscaOk && baixoOk && categoriaOk
  })
})

const totalPaginas = computed(() =>
  Math.max(1, Math.ceil(produtosFiltrados.value.length / porPagina.value))
)

const produtosPaginados = computed(() => {
  const inicio = (paginaAtual.value - 1) * porPagina.value
  return produtosFiltrados.value.slice(inicio, inicio + porPagina.value)
})

watch([termoDeBusca, filtroBaixo, filtroCategoria, filtroCentro], () => {
  paginaAtual.value = 1
})

// O filtro de centro de custo é feito no backend (recalcula quantidade/lotes/validades)
watch(filtroCentro, () => carregarProdutos())

const totalVencendo = computed(() =>
  produtos.value.filter(i => {
    const dias = diasParaVencer(i.data_validade)
    return dias !== null && dias >= 0 && dias <= 30
  }).length
)

const totalVencidos = computed(() =>
  produtos.value.filter(i => {
    const dias = diasParaVencer(i.data_validade)
    return dias !== null && dias < 0
  }).length
)

const toggleEstoqueBaixo = () => { filtroBaixo.value = !filtroBaixo.value }

async function carregarProdutos() {
  carregando.value = true
  try {
    const { data } = await api.get('/produtos', {
      params: filtroCentro.value ? { id_centro_custo: filtroCentro.value } : {},
    })
    produtos.value = data
  } catch (e) {
    console.error(e)
    erro('Não foi possível carregar os produtos.')
  } finally {
    carregando.value = false
  }
}

function buscarComAtraso() {
  clearTimeout(temporizadorBusca)
  temporizadorBusca = setTimeout(carregarProdutos, 400)
}

onMounted(() => {
  carregarProdutos()
  carregarCentros()
  document.addEventListener('click', fecharDropdownAoClicarFora)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', fecharDropdownAoClicarFora)
})
</script>