import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/servicos/api'

export const useAutenticacaoStore = defineStore('autenticacao', () => {

  const usuario = ref(JSON.parse(localStorage.getItem('usuario')) || null)

  const token = ref(localStorage.getItem('token') || null)

  const estaLogado = computed(() => !!token.value)

  const perfil = computed(() => usuario.value?.perfil || null)

  const ehAdmin        = computed(() => perfil.value === 'admin')
  const podeMovimentar = computed(() => ['admin', 'operador'].includes(perfil.value))
  const podeCadastrar  = computed(() => ['admin', 'operador'].includes(perfil.value))
  const podeGerenciarUsuarios = computed(() => perfil.value === 'admin')

  async function fazerLogin(email, senha) {
    const resposta = await api.post('/login', { email, senha })

    token.value   = resposta.data.token
    usuario.value = resposta.data.usuario

    localStorage.setItem('token', token.value)
    localStorage.setItem('usuario', JSON.stringify(usuario.value))

    return resposta.data
  }

  async function fazerLogout() {
    try {
      await api.post('/logout')
    } catch {
    } finally {
      token.value   = null
      usuario.value = null

      localStorage.removeItem('token')
      localStorage.removeItem('usuario')
      localStorage.removeItem('sige_chat_historico')
      sessionStorage.removeItem('lotes_pin_valido')
    }
  }

  return {
    usuario,
    token,
    estaLogado,
    perfil,
    ehAdmin,
    podeMovimentar,
    podeCadastrar,
    podeGerenciarUsuarios,
    fazerLogin,
    fazerLogout,
  }
})