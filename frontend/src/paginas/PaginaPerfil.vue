<template>
  <div class="p-6 flex justify-center">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl w-full max-w-md">

      <div class="flex items-center justify-between p-5 border-b border-slate-200 dark:border-slate-800">
        <div>
          <h1 class="text-lg font-bold text-slate-900 dark:text-white">Editar Perfil</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Atualize suas informações pessoais e configurações de conta</p>
        </div>
      </div>

      <div class="p-5 space-y-5">

        <div v-if="sucesso" class="p-3 rounded-lg bg-green-50 dark:bg-green-900/40 border border-green-200 dark:border-green-700 text-green-600 dark:text-green-300 text-sm">{{ sucesso }}</div>
        <div v-if="erro" class="p-3 rounded-lg bg-red-50 dark:bg-red-900/40 border border-red-200 dark:border-red-700 text-red-600 dark:text-red-300 text-sm">{{ erro }}</div>

        <div class="flex flex-col items-center gap-3">
          <div class="relative">
            <div class="w-20 h-20 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center overflow-hidden">
              <img v-if="imagemPreview" :src="imagemPreview" class="w-full h-full object-cover" />
              <UserRound v-else :size="36" class="text-slate-500 dark:text-slate-400" />
            </div>
            <label class="absolute bottom-0 right-0 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center cursor-pointer hover:bg-blue-700 transition">
              <Camera :size="13" class="text-white" />
              <input type="file" accept="image/*" class="hidden" @change="onFotoChange" />
            </label>
          </div>
          <button v-if="imagemPreview" type="button" class="text-xs bg-red-50 hover:bg-red-100 dark:bg-red-900/40 dark:hover:bg-red-900/60 text-red-600 dark:text-red-400 px-4 py-1.5 rounded-lg transition" @click="removerImagem">
            Remover Foto
          </button>
        </div>

        <div>
          <label class="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 font-medium mb-1">
            <UserRound :size="14" /> Nome
          </label>
          <input v-model="form.nome" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-blue-500" />
        </div>

        <div>
          <label class="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 font-medium mb-1">
            <Mail :size="14" /> Email
        </label>
          <input
  v-model="form.email"
  type="email"
  class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-blue-500"
/>
        </div>

        <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
          <div class="flex items-center justify-between mb-3">
  <label class="flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 font-medium">
    <Lock :size="14" /> Alterar Senha (opcional)
  </label>
</div>
          <div class="space-y-3">
            <input v-model="senhas.atual" type="password" placeholder="Digite sua senha atual" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-blue-500 placeholder-slate-400 dark:placeholder-slate-600" />
            <input v-model="senhas.nova" type="password" placeholder="Mínimo 6 caracteres" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-blue-500 placeholder-slate-400 dark:placeholder-slate-600" />
            <input v-model="senhas.confirmacao" type="password" placeholder="Digite a senha novamente" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm focus:outline-none focus:border-blue-500 placeholder-slate-400 dark:placeholder-slate-600" />
          </div>
        </div>

        <div class="flex gap-3 pt-2">
          <button type="button" class="flex-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-white text-sm font-medium py-2 rounded-lg transition" @click="$router.back()">Cancelar</button>
          <button type="button" :disabled="salvando" class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium py-2 rounded-lg transition" @click="salvar">
            {{ salvando ? 'Salvando...' : 'Salvar Alterações' }}
          </button>
        </div>

      </div>
    </div>

    
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { UserRound, Mail, Lock, Camera } from 'lucide-vue-next'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import api from '@/servicos/api'


const autenticacao = useAutenticacaoStore()
const usuario = autenticacao.usuario

const sucesso      = ref('')
const erro         = ref('')
const salvando     = ref(false)
const arquivoFoto  = ref(null)
const removerFoto  = ref(false)
const imagemPreview = ref((usuario && usuario.foto_url) ? usuario.foto_url : '')

const form = reactive({
  nome:  (usuario && usuario.name)  ? usuario.name  : '',
  email: (usuario && usuario.email) ? usuario.email : '',
})

const senhas = reactive({
  atual: '',
  nova: '',
  confirmacao: '',
})

function onFotoChange(e) {
  const arquivo = e.target.files[0]
  if (!arquivo) return
  if (arquivo.size > 2 * 1024 * 1024) {
    erro.value = 'A imagem deve ter no máximo 2MB.'
    return
  }
  arquivoFoto.value   = arquivo
  imagemPreview.value = URL.createObjectURL(arquivo)
  removerFoto.value   = false
}

function removerImagem() {
  arquivoFoto.value   = null
  imagemPreview.value = ''
  removerFoto.value   = true
}

function limparMensagens() {
  sucesso.value = ''
  erro.value    = ''
}


async function salvar() {
  limparMensagens()

 if (senhas.nova || senhas.atual || senhas.confirmacao) {
  if (!senhas.atual) { erro.value = 'Informe a senha atual.'; return }
  if (senhas.nova.length < 6) { erro.value = 'A nova senha deve ter pelo menos 6 caracteres.'; return }
  if (senhas.nova !== senhas.confirmacao) { erro.value = 'A nova senha e a confirmação não coincidem.'; return }
}

  salvando.value = true
  try {
    const formData = new FormData()
formData.append('nome', form.nome)
formData.append('email', form.email)
    if (arquivoFoto.value) {
      formData.append('foto', arquivoFoto.value)
    } else if (removerFoto.value) {
      formData.append('remover_foto', '1')
    }

    const resposta = await api.post('/perfil', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

   autenticacao.usuario.name     = resposta.data.usuario.name
autenticacao.usuario.email    = resposta.data.usuario.email
autenticacao.usuario.foto_url = resposta.data.usuario.foto_url
   imagemPreview.value = resposta.data.usuario.foto_url || ''
    arquivoFoto.value   = null
    removerFoto.value   = false

    if (senhas.nova) {
      await api.put('/perfil/senha', {
        senha_atual:             senhas.atual,
        nova_senha:              senhas.nova,
        nova_senha_confirmation: senhas.confirmacao,
      })
      senhas.atual       = ''
      senhas.nova        = ''
      senhas.confirmacao = ''
    }

    sucesso.value = 'Perfil atualizado com sucesso!'
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao salvar.'
  } finally {
    salvando.value = false
  }
}
</script>