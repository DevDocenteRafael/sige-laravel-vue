<template>
  <div class="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 w-full max-w-lg" :style="estiloArraste">
      <div class="flex justify-between items-center mb-6 cursor-grab active:cursor-grabbing select-none" @mousedown="aoIniciarArraste">
        <h2 class="text-lg font-bold text-white">
          {{ ehEdicao ? 'Editar Usuário' : 'Novo Usuário' }}
        </h2>
        <button class="text-slate-400 hover:text-white" @click="$emit('fechar')">
          <X :size="20" />
        </button>
      </div>
      <form @submit.prevent="salvar">
        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-300 mb-1">Nome completo *</label>
          <input
            v-model="formulario.name"
            type="text"
            required
            class="campo"
            placeholder="Ex: Maria Silva"
          />
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-300 mb-1">Email *</label>
          <input
            v-model="formulario.email"
            type="email"
            required
            class="campo"
            placeholder="Ex: maria@sige.com"
          />
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-300 mb-1">Perfil de acesso *</label>
          <select v-model="formulario.perfil" required class="campo">
            <option value="visualizador">Visualizador — só leitura</option>
            <option value="operador">Operador — pode movimentar estoque</option>
            <option value="admin">Admin — acesso total</option>
          </select>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-slate-300 mb-1">
            Senha {{ ehEdicao ? '(deixe em branco para não alterar)' : '*' }}
          </label>
          <input
            v-model="formulario.password"
            type="password"
            :required="!ehEdicao"
            class="campo"
            placeholder="Mínimo 6 caracteres"
          />
        </div>

        <!-- Confirmação de senha (só aparece se digitou uma nova senha) -->
        <div v-if="formulario.password" class="mb-6">
          <label class="block text-sm font-medium text-slate-300 mb-1">Confirmar senha *</label>
          <input
            v-model="formulario.password_confirmation"
            type="password"
            required
            class="campo"
            placeholder="Repita a senha"
          />
          <p v-if="senhasDiferentes" class="text-red-400 text-xs mt-1">As senhas não coincidem.</p>
        </div>

        <div v-if="erro" class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded text-red-400 text-sm">
          {{ erro }}
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" class="px-4 py-2 border border-slate-600 text-slate-300 rounded-lg hover:bg-slate-800 transition" @click="$emit('fechar')">
            Cancelar
          </button>
          <button type="submit" :disabled="salvando || senhasDiferentes" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition">
            {{ salvando ? 'Salvando...' : 'Salvar Usuário' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { X } from 'lucide-vue-next'
import api from '@/servicos/api'
import { useModalArrastavel } from '@/composables/useModalArrastavel'

const props = defineProps({
  usuario: { type: Object, default: null },
})
const emit = defineEmits(['fechar', 'salvo'])

const { aoIniciarArraste, estiloArraste } = useModalArrastavel()

const ehEdicao = computed(() => !!props.usuario)
const salvando = ref(false)
const erro     = ref('')

const formulario = ref({
  name:                  props.usuario?.name   || '',
  email:                 props.usuario?.email  || '',
  perfil:                props.usuario?.perfil || 'visualizador',
  password:              '',
  password_confirmation: '',
})

const senhasDiferentes = computed(() =>
  !!formulario.value.password &&
  !!formulario.value.password_confirmation &&
  formulario.value.password !== formulario.value.password_confirmation
)

async function salvar() {
  erro.value     = ''
  salvando.value = true
  try {
    const dados = { ...formulario.value }
    if (ehEdicao.value && !dados.password) {
      delete dados.password
      delete dados.password_confirmation
    }
    if (ehEdicao.value) {
      await api.put(`/usuarios/${props.usuario.id}`, dados)
    } else {
      await api.post('/usuarios', dados)
    }
    emit('salvo')
  } catch (erroHttp) {
    const errosValidacao = erroHttp.response?.data?.errors
    if (errosValidacao) {
      erro.value = Object.values(errosValidacao).flat().join('. ')
    } else {
      erro.value = erroHttp.response?.data?.message || erroHttp.response?.data?.mensagem || 'Erro ao salvar usuário.'
    }
  } finally {
    salvando.value = false
  }
}
</script>

<style scoped>
.campo {
  width: 100%;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 0.5rem;
  padding: 0.5rem 0.75rem;
  color: #f1f5f9;
  outline: none;
}
.campo:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
}
option {
  background: #1e293b;
}
</style>