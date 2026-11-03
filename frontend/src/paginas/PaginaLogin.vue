<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-black">

    <div class="text-center mb-8">
  <img :src="logo" alt="Logo Senac" class="h-20 mx-auto mb-8" />
  <h1 class="text-4xl font-bold text-sky-400">SIGE</h1>
  <p class="text-sm mt-1 text-slate-400">Sistema de Gerenciamento de Estoque</p>
</div>

    <div class="w-full max-w-md px-8 py-8 rounded-xl bg-slate-900 border border-slate-800">
      <form @submit.prevent="fazerLogin">

        <div class="mb-4">
          <label class="block text-sm font-medium mb-1 text-slate-300">Email</label>
          <input
            v-model="email"
            type="email"
            placeholder="seu@email.com"
            required
            class="w-full rounded-lg px-4 py-3 text-sm outline-none bg-slate-800 border border-slate-700 text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
          />
        </div>
<div class="mb-6">
  <label class="block text-sm font-medium mb-1 text-slate-300">Senha</label>
  <div class="relative">
    <input
      v-model="senha"
      :type="mostrarSenha ? 'text' : 'password'"
      placeholder="••••••••"
      required
      class="w-full rounded-lg px-4 py-3 pr-11 text-sm outline-none bg-slate-800 border border-slate-700 text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
    />
    <button
      type="button"
      class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 transition"
      @click="mostrarSenha = !mostrarSenha"
    >
      <Eye v-if="!mostrarSenha" :size="18" />
      <EyeOff v-else :size="18" />
    </button>
  </div>
</div>

        <div v-if="mensagemErro" class="mb-4 p-3 rounded-lg text-sm bg-red-900 border border-red-700 text-red-200 flex items-center gap-2">
          <AlertCircle :size="16" class="shrink-0" />
          {{ mensagemErro }}
        </div>

        <button
          type="submit"
          :disabled="carregando"
          class="w-full rounded-lg py-3 font-semibold text-white bg-blue-600 hover:bg-blue-700 transition disabled:opacity-50"
        >
          {{ carregando ? 'Entrando...' : 'Entrar' }}
        </button>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Eye, EyeOff, AlertCircle } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import logoSenac from '@/componentes/img/Senac_logo.svg.png'

const email        = ref('')
const logo         = logoSenac
const senha        = ref('')
const mostrarSenha = ref(false)
const carregando   = ref(false)
const mensagemErro = ref('')

const router       = useRouter()
const autenticacao = useAutenticacaoStore()

const mensagensErroCredenciais = [
  'Login ou senha incorretos. Que tal conferir com calma?',
  'Essa combinação não bateu por aqui. Tente novamente.',
  'Login ou senha incorretos — o estoque pode esperar mais um segundinho.',
  'Não reconhecemos esses dados. Confira e tente de novo.',
]

async function fazerLogin() {
  mensagemErro.value = ''
  carregando.value   = true

  try {
    await autenticacao.fazerLogin(email.value, senha.value)
    router.push('/dashboard')

  } catch (erro) {
    if (erro.response?.status === 401 || erro.response?.status === 422) {
      const aleatoria = mensagensErroCredenciais[
        Math.floor(Math.random() * mensagensErroCredenciais.length)
      ]
      mensagemErro.value = erro.response?.data?.message || aleatoria
    } else {
      mensagemErro.value = 'Não foi possível entrar agora. Tente novamente em instantes.'
    }

  } finally {
    carregando.value = false
  }
}
</script>