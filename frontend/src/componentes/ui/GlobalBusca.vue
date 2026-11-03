<template>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 bg-black/60 z-[9999] flex items-start justify-center pt-24 px-4" @click.self="fechar">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl w-full max-w-xl overflow-hidden">

        <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-200 dark:border-slate-800">
          <Search :size="18" class="text-slate-400 dark:text-slate-500 shrink-0" />
          <input
            ref="inputRef"
            v-model="termo"
            type="text"
            placeholder="Buscar produtos, páginas..."
            class="flex-1 bg-transparent text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 outline-none"
            @keydown="aoTeclar"
          />
          <kbd class="px-1.5 py-0.5 text-xs font-mono border rounded bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-500 dark:text-slate-400">ESC</kbd>
        </div>

        <div class="max-h-96 overflow-y-auto py-2">
          <div v-if="carregando" class="text-center py-8 text-sm text-slate-400 dark:text-slate-500">
            Buscando...
          </div>

          <div v-else-if="resultados.length === 0" class="text-center py-8 text-sm text-slate-400 dark:text-slate-500">
            Nenhum resultado encontrado.
          </div>

          <button
            v-for="(item, index) in resultados"
            :key="item.chave"
            type="button"
            :class="[
              'w-full flex items-center gap-3 px-4 py-2.5 text-left transition-colors',
              index === indiceSelecionado
                ? 'bg-blue-50 dark:bg-blue-950 border-l-2 border-blue-600'
                : 'hover:bg-slate-50 dark:hover:bg-slate-800 border-l-2 border-transparent',
            ]"
            @mouseenter="indiceSelecionado = index"
            @click="selecionar(item)"
          >
            <div
              :class="[
                'w-8 h-8 rounded-lg flex items-center justify-center shrink-0',
                index === indiceSelecionado
                  ? 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400'
                  : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400',
              ]"
            >
              <component :is="item.icone" :size="16" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ item.titulo }}</p>
              <p v-if="item.subtitulo" class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ item.subtitulo }}</p>
            </div>
            <span class="shrink-0 text-[10px] font-medium px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
              {{ item.tipo === 'menu' ? 'Página' : 'Produto' }}
            </span>
          </button>
        </div>

        <div class="flex items-center gap-4 px-4 py-2.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-xs text-slate-500 dark:text-slate-400">
          <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 border rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">↑↓</kbd> Navegar</span>
          <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 border rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700">Enter</kbd> Selecionar</span>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { Search, Package } from 'lucide-vue-next'
import api from '@/servicos/api'

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  itensMenu: { type: Array, default: () => [] },
})

const emit = defineEmits(['fechar'])

const router = useRouter()
const inputRef = ref(null)
const termo = ref('')
const indiceSelecionado = ref(0)
const carregando = ref(false)
const produtos = ref([])
let produtosCarregados = false

function fechar() {
  emit('fechar')
}

const itensDeMenuComoResultado = computed(() =>
  props.itensMenu.map((item) => ({
    chave: `menu-${item.rota}`,
    tipo: 'menu',
    titulo: item.nome,
    subtitulo: item.rota,
    icone: item.icone,
    acao: () => router.push(item.rota),
  }))
)

const resultados = computed(() => {
  const q = termo.value.toLowerCase().trim()

  if (!q) return itensDeMenuComoResultado.value

  const menusFiltrados = itensDeMenuComoResultado.value.filter((m) =>
    m.titulo.toLowerCase().includes(q)
  )

  const produtosFiltrados = produtos.value
    .filter(
      (p) =>
        p.nome?.toLowerCase().includes(q) ||
        p.sku?.toLowerCase().includes(q)
    )
    .slice(0, 6)
    .map((p) => ({
      chave: `produto-${p.id_item}`,
      tipo: 'produto',
      titulo: p.nome,
      subtitulo: p.sku ? `SKU: ${p.sku}` : undefined,
      icone: Package,
      acao: () => router.push('/produtos'),
    }))

  return [...menusFiltrados, ...produtosFiltrados].slice(0, 10)
})

async function carregarProdutos() {
  if (produtosCarregados) return
  carregando.value = true
  try {
    const { data } = await api.get('/produtos')
    produtos.value = Array.isArray(data) ? data : (data.data ?? [])
    produtosCarregados = true
  } catch (e) {
    console.error('Erro ao carregar produtos na busca global', e)
  } finally {
    carregando.value = false
  }
}

function selecionar(item) {
  item.acao()
  fechar()
}

function aoTeclar(e) {
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    indiceSelecionado.value = Math.min(indiceSelecionado.value + 1, resultados.value.length - 1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    indiceSelecionado.value = Math.max(indiceSelecionado.value - 1, 0)
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (resultados.value[indiceSelecionado.value]) {
      selecionar(resultados.value[indiceSelecionado.value])
    }
  } else if (e.key === 'Escape') {
    fechar()
  }
}

watch(termo, () => {
  indiceSelecionado.value = 0
})

watch(
  () => props.isOpen,
  async (aberto) => {
    if (aberto) {
      termo.value = ''
      indiceSelecionado.value = 0
      carregarProdutos()
      await nextTick()
      inputRef.value?.focus()
    }
  }
)
</script>