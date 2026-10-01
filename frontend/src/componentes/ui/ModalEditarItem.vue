<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto" :style="estiloArraste">

      <div class="flex justify-between items-center mb-6 cursor-grab active:cursor-grabbing select-none" @mousedown="aoIniciarArraste">
        <div>
          <h2 class="text-lg font-bold text-slate-900 dark:text-white">Editar Item</h2>
          <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">Modifique quantidade, unidade, validade e localização.</p>
        </div>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="tentarFechar">
          <X :size="20" />
        </button>
      </div>

      <!-- Dados do produto: somente leitura. Editar nome/SKU/categoria/fornecedor é feito na tela de Produtos. -->
      <div class="mb-5 p-3 rounded-lg bg-slate-100 dark:bg-slate-800 text-sm">
        <p class="text-slate-900 dark:text-white font-medium">{{ item.produto?.nome || '—' }}</p>
        <p class="text-slate-500 dark:text-slate-400 text-xs mt-0.5">
          SKU: {{ item.produto?.sku || '—' }}
          <span v-if="item.produto?.categoria?.nome"> · Categoria: {{ item.produto.categoria.nome }}</span>
          <span v-if="item.produto?.fornecedor?.nome"> · Fornecedor: {{ item.produto.fornecedor.nome }}</span>
        </p>
      </div>

      <form @submit.prevent="salvar">

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="label">Quantidade *</label>
            <input v-model.number="form.quantidade" type="number" min="0" required class="campo" />
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
          <div>
            <label class="label">Validade</label>
            <input v-model="form.data_validade" type="date" class="campo campo-data" />
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
        <div class="mb-6">
          <label class="label">Prioridade Manual</label>
          <select v-model="form.prioridade_abc" class="campo">
            <option value="">
              Automática<span v-if="classeCalculada"> (atual: {{ classeCalculada }})</span>
            </option>
            <option value="A">A — Alta</option>
            <option value="B">B — Média</option>
            <option value="C">C — Baixa</option>
          </select>
          <p class="text-xs text-slate-400 mt-1">
            <template v-if="form.prioridade_abc">
              Definida manualmente — o sistema não vai recalcular esta prioridade.
            </template>
            <template v-else>
              O sistema recalcula automaticamente com base na movimentação<span v-if="classeCalculada"> (hoje: classe {{ classeCalculada }})</span>.
            </template>
          </p>
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
            {{ salvando ? 'Salvando...' : 'Salvar Alterações' }}
          </button>
        </div>

      </form>
    </div>

    <!-- Modal de confirmação: descartar alterações -->
    <ModalConfirmacao
      v-if="modalDescartarAberto"
      titulo="Descartar alterações?"
      variante="aviso"
      mensagem="Você tem alterações não salvas neste item. Deseja realmente descartar e fechar?"
      texto-cancelar="Continuar Editando"
      texto-confirmar="Descartar"
      @cancelar="modalDescartarAberto = false"
      @confirmar="confirmarDescarte"
    />

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { X } from 'lucide-vue-next'
import api from '@/servicos/api'
import ModalConfirmacao from '@/componentes/ui/ModalConfirmacao.vue'
import { useModalArrastavel } from '@/composables/useModalArrastavel'
import { useCentrosCusto } from '@/composables/useCentrosCusto'
import { onMounted } from 'vue'
const props = defineProps({
  item: { type: Object, required: true },
})
const emit = defineEmits(['fechar', 'salvo'])

const { aoIniciarArraste, estiloArraste } = useModalArrastavel()
const { centros, carregar: carregarCentros } = useCentrosCusto()
onMounted(carregarCentros)

const salvando = ref(false)
const erro     = ref('')

// prioridade_manual pode vir como true/false, 1/0 ou "1"/"0"
const ehManual = Boolean(
  props.item.prioridade_manual === true ||
  props.item.prioridade_manual === 1 ||
  props.item.prioridade_manual === '1'
)

// Classe que o sistema calculou sozinho (só serve de informação quando é automática)
const classeCalculada = computed(() =>
  ehManual ? null : (props.item.prioridade_abc || null)
)

// Normaliza a data para o formato aceito pelo <input type="date">
function paraInputDate(valor) {
  if (!valor) return ''
  return String(valor).slice(0, 10)
}

// Só campos que o backend (ItemLoteController@update) realmente salva
const valoresOriginais = {
  quantidade:      props.item.quantidade     ?? null,
  unidade_medida:  props.item.unidade_medida || 'UN',
  data_validade:   paraInputDate(props.item.data_validade),
  localizacao:     props.item.localizacao    || '',
  id_centro_custo: props.item.id_centro_custo ?? null,
  // Se não for manual, o select fica em "Automática" mesmo que exista
  // uma classe calculada gravada no banco — senão ao salvar ela virava manual.
  prioridade_abc:  ehManual ? (props.item.prioridade_abc || '') : '',
}

const form = ref({ ...valoresOriginais })

const temAlteracoes = computed(() => {
  return Object.keys(valoresOriginais).some(
    (chave) => form.value[chave] !== valoresOriginais[chave]
  )
})

// ===== Confirmação de descarte =====
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
  erro.value     = ''
  salvando.value = true
  try {
    const dados = {
      quantidade:     form.value.quantidade,
      unidade_medida: form.value.unidade_medida,
      data_validade:  form.value.data_validade || null,
      localizacao:    form.value.localizacao || null,
      id_centro_custo: form.value.id_centro_custo,
    }

    // Só envia prioridade_abc quando o usuário escolheu manualmente.
    // Ausente = automática (o backend zera prioridade_manual e recalcula).
    if (form.value.prioridade_abc) {
      dados.prioridade_abc = form.value.prioridade_abc
    }

    await api.put(`/itens/${props.item.id_item}`, dados)
    emit('salvo')
  } catch (e) {
    const erros = e.response?.data?.errors
    erro.value = erros
      ? Object.values(erros).flat().join('. ')
      : e.response?.data?.message || 'Erro ao salvar item.'
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