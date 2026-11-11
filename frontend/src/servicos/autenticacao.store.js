import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api, { prepararCsrf } from '@/servicos/api'

export const useAutenticacaoStore = defineStore('autenticacao', () => {
  const usuario = ref(null)
  const estaLogado = computed(() => !!usuario.value)

  const perfil = computed(() => usuario.value?.perfil || null)

  const ehAdmin        = computed(() => perfil.value === 'admin')
  const podeMovimentar = computed(() => ['admin', 'operador'].includes(perfil.value))
  const podeCadastrar  = computed(() => ['admin', 'operador'].includes(perfil.value))
  const podeGerenciarUsuarios = computed(() => perfil.value === 'admin')

  async function carregarUsuario() {
    localStorage.removeItem('token')
    localStorage.removeItem('usuario')

    try {
      usuario.value = (await api.get('/user')).data
    } catch (erro) {
      if (erro.response?.status !== 401) throw erro
      usuario.value = null
    }
  }

  async function fazerLogin(email, senha) {
    await prepararCsrf()
    const resposta = await api.post('/login', { email, senha })
    usuario.value = resposta.data.usuario

    return resposta.data
  }

  async function fazerLogout() {
    try {
      await api.post('/logout')
    } catch (erro) {
      if (erro.response?.status !== 401) throw erro
    } finally {
      usuario.value = null

      localStorage.removeItem('sige_chat_historico')
      sessionStorage.removeItem('lotes_pin_valido')
    }
  }

  return {
    usuario,
    estaLogado,
    perfil,
    ehAdmin,
    podeMovimentar,
    podeCadastrar,
    podeGerenciarUsuarios,
    carregarUsuario,
    fazerLogin,
    fazerLogout,
  }
})