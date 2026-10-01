<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto" :style="estiloArraste">

      <div class="flex justify-between items-center mb-6 cursor-grab active:cursor-grabbing select-none" @mousedown="aoIniciarArraste">
        <div>
          <h2 class="text-lg font-bold text-slate-900 dark:text-white">Adicionar Item ao Lote {{ lote?.numero_lote }}</h2>
          <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Preencha as informações do produto para adicionar ao lote.</p>
        </div>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="tentarFechar">
          <X :size="20" />
        </button>
      </div>

      <form @submit.prevent="salvar">

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="label">Código / SKU *</label>
            <input v-model="form.sku" type="text" required class="campo" placeholder="Ex: PROD001" />
            <p v-if="buscandoProduto" class="text-xs text-slate-400 mt-1">Buscando...</p>
            <p v-else-if="produtoEncontrado" class="text-xs text-green-600 dark:text-green-400 mt-1">
              ✓ Produto existente: {{ produtoEncontrado.nome }} (estoque atual: {{ produtoEncontrado.estoque_atual }})
            </p>
            <p v-else-if="form.sku && form.sku.length >= 2" class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
              Nenhum produto encontrado — será cadastrado como novo.
            </p>
          </div>
          <div>
            <label class="label">Nome do Produto *</label>
            <input
              v-model="form.nome"
              type="text"
              required
              :disabled="!!produtoEncontrado"
              class="campo"
              :class="{ 'opacity-60 cursor-not-allowed': produtoEncontrado }"
              placeholder="Ex: Arroz Integral"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="label">Quantidade *</label>
            <input v-model.number="form.quantidade" type="number" min="1" required class="campo" placeholder="Ex: 50" />
          </div>
          <div>
            <label class="label">Unidade *</label>
            <select v-model="form.unidade_medida" class="campo">
              <option value="UN">UN — Unidade</option>
              <option value="CX">CX — Caixa</option>
              <option value="PCT">PCT — Pacote</option>
              <option value="PTC">PTC — Pacote (variação)</option>
              <option value="FR">FR — Frasco</option>
              <option value="RL">RL — Rolo</option>
              <option value="EMB">EMB — Embalagem</option>
              <option value="KIT">KIT — Kit</option>
              <option value="BEM">BEM — Bem</option>
              <option value="UM">UM — Unidade de Medida</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div v-if="!produtoEncontrado">
            <label class="label">Estoque Mínimo *</label>
            <input v-model.number="form.estoque_minimo" type="number" min="1" required class="campo" placeholder="Ex: 10" />
          </div>
          <div>
            <label class="label">Validade *</label>
            <input v-model="form.data_validade" type="date" required class="campo campo-data" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="label">Fornecedor</label>
            <input v-model="form.fornecedor" type="text" class="campo" placeholder="Ex: Fornecedor ABC" />
          </div>
          <div>
            <label class="label">Localização / Prateleira</label>
            <input v-model="form.localizacao" type="text" class="campo" placeholder="Ex: A-12" />
          </div>
        </div>
 <div class="mb-4">
          <label class="label">Centro de Custo</label>
          <select v-model="form.id_centro_custo" class="campo">
            <option :value="null">Sem centro de custo</option>
            <option v-for="c in centros" :key="c.id_centro_custo" :value="c.id_centro_custo">
              {{ c.codigo }} — {{ c.nome }}
            </option>
          </select>
        </div>
        <div class="mb-4">
          <label class="label">Prioridade Manual</label>
          <select v-model="form.prioridade_abc" class="campo">
            <option value="">Automática</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
          </select>
          <p class="text-xs text-slate-400 mt-1">
            Se definida manualmente, a prioridade não será recalculada automaticamente pelo sistema.
          </p>
        </div>

        <div v-if="!produtoEncontrado" class="mb-6">
          <label class="label">Categoria *</label>
          <select v-model="form.categoria" required class="campo">
            <option value="" disabled>Selecione uma categoria *</option>
            <option value="Medicina">Medicina</option>
            <option value="Enfermagem">Enfermagem</option>
            <option value="Odontologia">Odontologia</option>
            <option value="Laboratório">Laboratório</option>
            <option value="Higiene e Antissepsia">Higiene e Antissepsia</option>
            <option value="Estética">Estética</option>
            <option value="Podologia">Podologia</option>
            <option value="Equipamentos">Equipamentos</option>
            <option value="Consumíveis">Consumíveis</option>
            <option value="Outros">Outros</option>
          </select>
          <input
            v-if="form.categoria === 'Outros'"
            v-model="form.categoria_outros"
            type="text"
            required
            class="campo mt-2"
            placeholder="Digite a categoria"
          />
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded text-red-600 dark:text-red-400 text-sm">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button
            type="button"
            class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            @click="tentarFechar"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="salvando"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
          >
            {{ salvando ? 'Adicionando...' : 'Adicionar' }}
          </button>
        </div>

      </form>
    </div>

    <!-- Modal de confirmação: descartar informações não salvas -->
    <ModalConfirmacao
      v-if="modalDescartarAberto"
      titulo="Descartar alterações?"
      variante="aviso"
      mensagem="Você tem informações não salvas neste formulário. Deseja realmente descartar e fechar?"
      texto-cancelar="Continuar Editando"
      texto-confirmar="Descartar"
      @cancelar="modalDescartarAberto = false"
      @confirmar="confirmarDescarte"
    />

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { X } from 'lucide-vue-next'
import api from '@/servicos/api'
import ModalConfirmacao from '@/componentes/ui/ModalConfirmacao.vue'
import { useModalArrastavel } from '@/composables/useModalArrastavel'
import { useCentrosCusto } from '@/composables/useCentrosCusto'
import { onMounted } from 'vue'

const props = defineProps({
  lote: { type: Object, required: true },
})
const emit = defineEmits(['fechar', 'salvo'])

const { aoIniciarArraste, estiloArraste } = useModalArrastavel()
const { centros, carregar: carregarCentros } = useCentrosCusto()
onMounted(carregarCentros)

const salvando = ref(false)
const erro     = ref('')

const form = ref({
  sku:              '',
  nome:             '',
  quantidade:       null,
  unidade_medida:   'UN',
  estoque_minimo:   null,
  data_validade:    '',
  fornecedor:       '',
  localizacao:      '',
  prioridade_abc:   '',
  categoria:        '',
  categoria_outros: '',
  id_centro_custo:  null,
})

const buscandoProduto   = ref(false)
const produtoEncontrado = ref(null)
let debounceTimer = null

let idBuscaAtual = 0

watch(() => form.value.sku, (sku) => {
  produtoEncontrado.value = null
  clearTimeout(debounceTimer)
  if (!sku || sku.trim().length < 2) return

  debounceTimer = setTimeout(async () => {
    const idDestaBusca = ++idBuscaAtual   // marca esta busca como "a mais recente"
    buscandoProduto.value = true
    try {
      const { data } = await api.get('/produtos/buscar-por-sku', { params: { sku } })

      // se outra busca começou depois desta, ignora essa resposta atrasada
      if (idDestaBusca !== idBuscaAtual) return

      produtoEncontrado.value = data && data.id_produto ? data : null
      if (produtoEncontrado.value) {
        form.value.nome = produtoEncontrado.value.nome
      }
    } catch {
      if (idDestaBusca === idBuscaAtual) produtoEncontrado.value = null
    } finally {
      if (idDestaBusca === idBuscaAtual) buscandoProduto.value = false
    }
  }, 400)
})

const temAlteracoes = computed(() => {
  return !!(
    form.value.sku ||
    form.value.nome ||
    form.value.quantidade ||
    form.value.estoque_minimo ||
    form.value.data_validade ||
    form.value.fornecedor ||
    form.value.localizacao ||
    form.value.prioridade_abc ||
    form.value.categoria ||
    form.value.categoria_outros ||
    form.value.id_centro_custo
  )
})

const modalDescartarAberto = ref(false)

function tentarFechar() {
  if (temAlteracoes.value) {
    modalDescartarAberto.value = true
    return
  }
  emit('fechar')
}

function confirmarDescarte() {
  modalDescartarAberto.value = false
  emit('fechar')
}

async function salvar() {
  erro.value = ''

  if (!form.value.data_validade) {
    erro.value = 'A data de validade é obrigatória.'
    return
  }
  if (!form.value.sku) {
    erro.value = 'O SKU é obrigatório — use um SKU existente ou defina um novo para o produto.'
    return
  }

  salvando.value = true
  let dados = {}
  try {
    dados = { ...form.value }

    // Só envia prioridade_abc quando o usuário escolheu manualmente.
    // Ausente = automática, backend calcula normalmente.
    if (!dados.prioridade_abc) {
      delete dados.prioridade_abc
    }

    if (produtoEncontrado.value) {
      dados.id_produto = produtoEncontrado.value.id_produto
      delete dados.nome
      delete dados.categoria
      delete dados.categoria_outros
      delete dados.fornecedor
      delete dados.estoque_minimo
    } else {
      if (dados.categoria === 'Outros') {
        dados.categoria = dados.categoria_outros
      }
      delete dados.categoria_outros
    }

    await api.post(`/lotes/${props.lote.id_lote}/itens`, dados)
    emit('salvo')
  } catch (e) {
    const erros = e.response?.data?.errors
    erro.value = erros
      ? Object.values(erros).flat().join('. ')
      : e.response?.data?.message || 'Erro ao adicionar item.'
  } finally {
    salvando.value = false
  }
}
</script>

<style scoped>
.label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--muted-foreground);
  margin-bottom: 0.25rem;
}
.campo {
  width: 100%;
  background: var(--input);
  border: 1px solid var(--border);
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  color: var(--foreground);
  outline: none;
}
.campo:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
}
option {
  background: var(--card);
  color: var(--foreground);
}
.campo-data {
  color-scheme: light;
}
:global(.dark) .campo-data {
  color-scheme: dark;
}
</style>