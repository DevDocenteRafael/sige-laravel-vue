<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="$emit('fechar')">

    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-lg">

      <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-bold text-gray-800">
          {{ ehEdicao ? 'Editar Produto' : 'Novo Produto' }}
        </h2>
        <button class="text-gray-400 hover:text-gray-600 text-xl" @click="$emit('fechar')">✕</button>
      </div>

      <form @submit.prevent="salvar">

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
            <input v-model="formulario.codigo" type="text" required class="campo-texto" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Unidade *</label>
            <select v-model="formulario.unidade" required class="campo-texto">
              <option v-for="unidade in unidades" :key="unidade" :value="unidade">{{ unidade }}</option>
            </select>
          </div>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
          <input v-model="formulario.nome" type="text" required class="campo-texto" />
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
          <textarea v-model="formulario.descricao" rows="2" class="campo-texto"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
            <select v-model="formulario.categoria_id" class="campo-texto">
              <option value="">Sem categoria</option>
              <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nome }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estoque Mínimo *</label>
            <input v-model="formulario.estoque_minimo" type="number" min="0" required class="campo-texto" />
          </div>
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-600 text-sm">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" class="px-4 py-2 text-gray-600 border rounded-lg hover:bg-gray-50" @click="$emit('fechar')">
            Cancelar
          </button>
          <button type="submit" :disabled="salvando" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ salvando ? 'Salvando...' : 'Salvar' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/servicos/api'

const props = defineProps({
  produto: { type: Object, default: null },
})

const emit = defineEmits(['fechar', 'salvo'])

const formulario = ref({
  codigo:          props.produto?.codigo          || '',
  nome:            props.produto?.nome            || '',
  descricao:       props.produto?.descricao       || '',
  categoria_id:    props.produto?.categoria_id    || '',
  unidade:         props.produto?.unidade         || 'UN',
  estoque_minimo:  props.produto?.estoque_minimo  || 0,
})

const categorias = ref([])
const salvando   = ref(false)
const erro       = ref('')

const ehEdicao = computed(() => !!props.produto)

const unidades = ['UN', 'CX', 'PCT', 'KG', 'G', 'L', 'ML', 'FR', 'RL', 'KIT', 'EMB']

async function carregarCategorias() {
  try {
    const resposta = await api.get('/categorias')
    categorias.value = resposta.data
  } catch {
  }
}

async function salvar() {
  erro.value   = ''
  salvando.value = true

  try {
    if (ehEdicao.value) {
      await api.put(`/produtos/${props.produto.id}`, formulario.value)
    } else {
      await api.post('/produtos', formulario.value)
    }

    emit('salvo')

  } catch (erroHttp) {
    const errosValidacao = erroHttp.response?.data?.errors
    if (errosValidacao) {
      erro.value = Object.values(errosValidacao).flat().join('. ')
    } else {
      erro.value = erroHttp.response?.data?.message || 'Erro ao salvar produto.'
    }
  } finally {
    salvando.value = false
  }
}

onMounted(carregarCategorias)
</script>

<style scoped>
.campo-texto {
  width: 100%;
  border: 1px solid #D1D5DB;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.campo-texto:focus {
  outline: none;
  border-color: #3B82F6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
}
</style>
  