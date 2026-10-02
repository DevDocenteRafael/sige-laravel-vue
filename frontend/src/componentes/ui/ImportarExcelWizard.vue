<template>
  <div class="fixed inset-0 bg-black/50 dark:bg-black/70 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-4xl max-h-[90vh] flex flex-col">

      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <Upload class="w-5 h-5 text-blue-600 dark:text-blue-400" />
          Importar Planilha Excel
        </h2>
        <button class="text-slate-400 hover:text-slate-900 dark:hover:text-white" @click="tentarFechar">
          <X class="w-5 h-5" />
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-6">

        <!-- ETAPA 1: Upload -->
        <div v-if="etapa === 'upload'">
          <label
            class="flex flex-col items-center justify-center border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-10 cursor-pointer hover:border-blue-500 transition"
          >
            <Upload class="w-10 h-10 text-slate-400 dark:text-slate-500 mb-3" />
            <span class="text-slate-700 dark:text-slate-300 font-medium">{{ arquivo ? arquivo.name : 'Clique para selecionar o arquivo Excel' }}</span>
            <span class="text-slate-400 dark:text-slate-500 text-sm mt-1">.xlsx ou .xls</span>
            <input type="file" accept=".xlsx,.xls" class="hidden" @change="onFileChange" />
          </label>

          <p v-if="erro" class="text-red-600 dark:text-red-400 text-sm mt-3">{{ erro }}</p>

          <button
            :disabled="!arquivo || carregando"
            class="mt-5 w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium py-2.5 rounded-lg transition"
            @click="enviarPreview"
          >
            {{ carregando ? 'Lendo planilha...' : 'Analisar Planilha' }}
          </button>
        </div>

        <!-- ETAPA 2: Preview + escolha de modo -->
        <div v-else-if="etapa === 'modo'">
          <div class="bg-slate-100 dark:bg-slate-800 rounded-lg p-4 mb-5 flex gap-6">
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Produtos encontrados</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ itens.length }}</p>
            </div>
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Linhas ignoradas</p>
              <p class="text-2xl font-bold text-slate-500 dark:text-slate-400">{{ ignorados }}</p>
            </div>
            <div>
              <p class="text-slate-500 dark:text-slate-400 text-xs">Total de unidades</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ totalUnidades }}</p>
            </div>
          </div>

          <p class="text-slate-700 dark:text-slate-300 font-medium mb-3">Como deseja organizar esses itens em lotes?</p>

          <div class="grid grid-cols-2 gap-4 mb-6">
            <button
              class="border border-slate-200 dark:border-slate-700 hover:border-blue-500 rounded-lg p-5 text-left transition"
              @click="modo = 'unico'; etapa = 'confirmarUnico'"
            >
              <Package class="w-6 h-6 text-blue-600 dark:text-blue-400 mb-2" />
              <p class="text-slate-900 dark:text-white font-semibold">Lote único</p>
              <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Todos os {{ itens.length }} produtos entram em um só lote</p>
            </button>

            <button
              class="border border-slate-200 dark:border-slate-700 hover:border-blue-500 rounded-lg p-5 text-left transition"
              @click="modo = 'multiplo'; etapa = 'confirmarMultiplo'"
            >
              <Layers class="w-6 h-6 text-orange-500 dark:text-orange-400 mb-2" />
              <p class="text-slate-900 dark:text-white font-semibold">Lotes separados</p>
              <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Você escolhe quantas unidades de cada produto vai em cada lote</p>
            </button>
          </div>

          <!-- Tabela de itens -->
          <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
              <thead class="bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                <tr>
                  <th class="text-left px-3 py-2">SKU</th>
                  <th class="text-left px-3 py-2">Produto</th>
                  <th class="text-right px-3 py-2">Qtd</th>
                  <th class="text-right px-3 py-2">Est. Mínimo</th>
                  <th class="text-left px-3 py-2">Validade</th>
                  <th class="text-left px-3 py-2">Centro de Custo</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="it in itens" :key="it.sku" class="border-t border-slate-100 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                  <td class="px-3 py-1.5">{{ it.sku }}</td>
                  <td class="px-3 py-1.5">{{ it.nome }}</td>
                  <td class="px-3 py-1.5 text-right">{{ it.quantidade }} {{ it.unidade }}</td>
                  <td class="px-3 py-1.5 text-right">{{ it.estoque_minimo }}</td>
                  <td class="px-3 py-1.5">{{ it.validade || '—' }}</td>
                  <td class="px-3 py-1.5">{{ it.centro_custo || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ETAPA 3A: Confirmar lote único -->
        <div v-else-if="etapa === 'confirmarUnico'">
          <button class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-sm mb-4 flex items-center gap-1" @click="etapa = 'modo'">
            <ArrowLeft class="w-4 h-4" /> Voltar
          </button>

          <p class="text-slate-700 dark:text-slate-300 font-medium mb-3">Configurar lote único ({{ itens.length }} produtos)</p>

          <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
              <label class="text-slate-500 dark:text-slate-400 text-sm block mb-1">Nome ou Número do lote</label>
              <input
                v-model="loteUnico.numero_lote"
                placeholder="Ex: LOTE-001"
                class="campo"
              />
            </div>
            <div>
              <label class="text-slate-500 dark:text-slate-400 text-sm block mb-1">Data de validade (opcional)</label>
              <input
                v-model="loteUnico.data_validade"
                type="date"
                class="campo"
              />
            </div>
          </div>

          <p v-if="erro" class="text-red-600 dark:text-red-400 text-sm mb-3">{{ erro }}</p>

          <button
            :disabled="carregando"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium py-2.5 rounded-lg transition"
            @click="confirmarUnico"
          >
            {{ carregando ? 'Importando...' : `Criar lote com ${itens.length} produtos` }}
          </button>
        </div>

        <!-- ETAPA 3B: Confirmar lotes múltiplos -->
        <div v-else-if="etapa === 'confirmarMultiplo'">
          <button class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-sm mb-4 flex items-center gap-1" @click="etapa = 'modo'">
            <ArrowLeft class="w-4 h-4" /> Voltar
          </button>

          <div class="flex items-center justify-between mb-4">
            <p class="text-slate-700 dark:text-slate-300 font-medium">Distribuir produtos entre lotes</p>
            <button
              class="flex items-center gap-1 text-sm bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white px-3 py-1.5 rounded-lg"
              @click="adicionarLote"
            >
              <Plus class="w-4 h-4" /> Adicionar lote
            </button>
          </div>

          <!-- Config de cada lote -->
          <div class="flex gap-3 overflow-x-auto pb-2 mb-4">
            <div
              v-for="(lote, idx) in lotesMultiplos"
              :key="idx"
              class="min-w-[220px] bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-3 relative"
            >
              <button
                v-if="lotesMultiplos.length > 1"
                class="absolute top-2 right-2 text-slate-400 dark:text-slate-500 hover:text-red-500 dark:hover:text-red-400"
                @click="removerLote(idx)"
              >
                <Trash2 class="w-4 h-4" />
              </button>
              <p class="text-slate-500 dark:text-slate-400 text-xs mb-2">Lote {{ idx + 1 }}</p>
              <input
                v-model="lote.numero_lote"
                placeholder="Número (auto)"
                class="campo campo-sm mb-2"
              />
              <input
                v-model="lote.data_validade"
                type="date"
                class="campo campo-sm"
              />
            </div>
          </div>

          <!-- Tabela de distribuição -->
          <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                <tr>
                  <th class="text-left px-3 py-2 sticky left-0 bg-slate-100 dark:bg-slate-800">Produto</th>
                  <th class="text-right px-3 py-2">Total</th>
                  <th v-for="(lote, idx) in lotesMultiplos" :key="idx" class="text-right px-3 py-2 whitespace-nowrap">
                    Lote {{ idx + 1 }}
                  </th>
                  <th class="text-right px-3 py-2">Restante</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="it in itens" :key="it.sku" class="border-t border-slate-100 dark:border-slate-800 text-slate-700 dark:text-slate-300">
                  <td class="px-3 py-1.5 sticky left-0 bg-white dark:bg-slate-900">{{ it.nome }}</td>
                  <td class="px-3 py-1.5 text-right text-slate-400 dark:text-slate-500">{{ it.quantidade }}</td>
                  <td v-for="(lote, idx) in lotesMultiplos" :key="idx" class="px-2 py-1.5 text-right">
                    <input
                      v-model.number="lote.quantidades[it.sku]"
                      type="number"
                      min="0"
                      :max="it.quantidade"
                      class="w-16 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded px-1.5 py-1 text-right text-slate-900 dark:text-white"
                    />
                  </td>
                  <td
                    class="px-3 py-1.5 text-right font-medium"
                    :class="restante(it) === 0 ? 'text-green-600 dark:text-green-400' : restante(it) < 0 ? 'text-red-600 dark:text-red-400' : 'text-orange-500 dark:text-orange-400'"
                  >
                    {{ restante(it) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <p v-if="erro" class="text-red-600 dark:text-red-400 text-sm mt-3">{{ erro }}</p>
          <p v-if="temRestante" class="text-orange-500 dark:text-orange-400 text-sm mt-3">
            Ainda há unidades não distribuídas. Elas não serão importadas se você continuar.
          </p>

          <button
            :disabled="carregando"
            class="mt-4 w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium py-2.5 rounded-lg transition"
            @click="confirmarMultiplo"
          >
            {{ carregando ? 'Importando...' : `Criar ${lotesMultiplos.length} lotes` }}
          </button>
        </div>

        <!-- ETAPA 4: Sucesso -->
        <div v-else-if="etapa === 'sucesso'" class="text-center py-10">
          <Check class="w-14 h-14 text-green-600 dark:text-green-400 mx-auto mb-4" />
          <p class="text-slate-900 dark:text-white font-semibold text-lg">Importação concluída!</p>
          <p class="text-slate-500 dark:text-slate-400 mt-1">{{ resultado.lotes_criados }} lote(s) criado(s).</p>
          <button class="mt-6 bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded-lg" @click="fechar">
            Fechar
          </button>
        </div>

      </div>
    </div>

    <!-- Modal de confirmação para fechar com importação em andamento -->
    <Transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="mostrarConfirmFechar" class="fixed inset-0 bg-black/70 z-[60] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-orange-300 dark:border-orange-700 rounded-xl p-6 max-w-sm w-full space-y-4">
          <p class="text-slate-900 dark:text-white font-semibold text-lg flex items-center gap-2">
            <AlertTriangle :size="20" class="text-orange-500" />
            Importação em andamento
          </p>
          <p class="text-slate-600 dark:text-slate-300 text-sm">
            Você tem uma importação em andamento. Deseja realmente cancelar e fechar?
          </p>
          <div class="flex gap-3">
            <button
              class="flex-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-900 dark:text-white py-2 rounded-lg transition"
              @click="mostrarConfirmFechar = false"
            >
              Continuar importação
            </button>
            <button
              class="flex-1 bg-orange-600 hover:bg-orange-700 text-white py-2 rounded-lg transition"
              @click="confirmarFechar"
            >
              Cancelar e fechar
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Toast de aviso -->
    <Transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="toast.show"
        class="fixed bottom-6 right-6 z-[70] px-5 py-3 rounded-lg shadow-lg text-white flex items-center gap-3 text-sm bg-slate-800 dark:bg-slate-700"
      >
        <X class="w-4 h-4 text-orange-400" />
        {{ toast.message }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Upload, X, Package, Layers, ArrowLeft, Plus, Trash2, Check, AlertTriangle } from 'lucide-vue-next'
import api from '@/servicos/api'

const emit = defineEmits(['fechar', 'importado'])

const etapa = ref('upload') // upload | modo | confirmarUnico | confirmarMultiplo | sucesso
const arquivo = ref(null)
const carregando = ref(false)
const erro = ref('')

const itens = ref([])
const ignorados = ref(0)
const modo = ref('unico')
const resultado = ref({})

const loteUnico = reactive({ numero_lote: '', data_validade: '' })
const lotesMultiplos = ref([criarLoteVazio()])

const mostrarConfirmFechar = ref(false)
const toast = ref({ show: false, message: '' })

function criarLoteVazio() {
  return { numero_lote: '', data_validade: '', quantidades: {} }
}

function onFileChange(e) {
  arquivo.value = e.target.files[0] || null
  erro.value = ''
}

const totalUnidades = computed(() =>
  itens.value.reduce((soma, it) => soma + it.quantidade, 0)
)

async function enviarPreview() {
  if (!arquivo.value) return
  carregando.value = true
  erro.value = ''

  const formData = new FormData()
  formData.append('arquivo', arquivo.value)

  try {
    const { data } = await api.post('/importacao-exportacao/preview', formData)
    itens.value = data.itens
    ignorados.value = data.ignorados

    lotesMultiplos.value = [criarLoteVazio()]
    itens.value.forEach(it => {
      lotesMultiplos.value[0].quantidades[it.sku] = 0
    })

    etapa.value = 'modo'
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao processar a planilha.'
  } finally {
    carregando.value = false
  }
}

function adicionarLote() {
  const novo = criarLoteVazio()
  itens.value.forEach(it => { novo.quantidades[it.sku] = 0 })
  lotesMultiplos.value.push(novo)
}

function removerLote(idx) {
  lotesMultiplos.value.splice(idx, 1)
}

function restante(it) {
  const distribuido = lotesMultiplos.value.reduce(
    (soma, lote) => soma + (Number(lote.quantidades[it.sku]) || 0),
    0
  )
  return it.quantidade - distribuido
}

const temRestante = computed(() =>
  itens.value.some(it => restante(it) !== 0)
)

async function confirmarUnico() {
  carregando.value = true
  erro.value = ''
  try {
    // itens.value já carrega o campo centro_custo vindo do preview
    const { data } = await api.post('/importacao-exportacao/confirmar', {
      modo: 'unico',
      lote: {
        numero_lote: loteUnico.numero_lote || null,
        data_validade: loteUnico.data_validade || null,
      },
      itens: itens.value,
    })
    resultado.value = data
    etapa.value = 'sucesso'
    emit('importado')
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao importar.'
  } finally {
    carregando.value = false
  }
}

async function confirmarMultiplo() {
  carregando.value = true
  erro.value = ''

  const lotesPayload = lotesMultiplos.value.map(lote => ({
    numero_lote: lote.numero_lote || null,
    data_validade: lote.data_validade || null,
    itens: itens.value
      .filter(it => (Number(lote.quantidades[it.sku]) || 0) > 0)
      .map(it => ({
        sku: it.sku,
        nome: it.nome,
        unidade: it.unidade,
        quantidade: Number(lote.quantidades[it.sku]),
        estoque_minimo: it.estoque_minimo,
        centro_custo: it.centro_custo ?? null,
      })),
  })).filter(l => l.itens.length > 0)

  if (lotesPayload.length === 0) {
    erro.value = 'Distribua ao menos uma unidade em algum lote.'
    carregando.value = false
    return
  }

  try {
    const { data } = await api.post('/importacao-exportacao/confirmar', {
      modo: 'multiplo',
      lotes: lotesPayload,
    })
    resultado.value = data
    etapa.value = 'sucesso'
    emit('importado')
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao importar.'
  } finally {
    carregando.value = false
  }
}

function tentarFechar() {
  const temProgresso = etapa.value !== 'upload' && etapa.value !== 'sucesso'
  if (temProgresso) {
    mostrarConfirmFechar.value = true
    return
  }
  emit('fechar')
}

function confirmarFechar() {
  mostrarConfirmFechar.value = false
  toast.value = { show: true, message: 'Importação cancelada.' }
  setTimeout(() => (toast.value.show = false), 3000)
  emit('fechar')
}

function fechar() {
  emit('fechar')
}
</script>

<style scoped>
.campo {
  width: 100%;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  background-color: rgb(241 245 249);
  border: 1px solid rgb(203 213 225);
  color: rgb(15 23 42);
}
.dark .campo {
  background-color: rgb(30 41 59);
  border-color: rgb(71 85 105);
  color: rgb(255 255 255);
}
.campo::placeholder {
  color: rgb(148 163 184);
}
.dark .campo::placeholder {
  color: rgb(100 116 139);
}
.campo:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
}
.campo-sm {
  padding: 0.375rem 0.5rem;
  font-size: 0.875rem;
}
</style>