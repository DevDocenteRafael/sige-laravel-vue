  import axios from 'axios'

  const api = axios.create({
    baseURL: '/api',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  })

  api.interceptors.request.use((configuracao) => {
    const token = localStorage.getItem('token')

    if (token) {
      configuracao.headers.Authorization = `Bearer ${token}`
    }

    if (configuracao.data instanceof FormData) {
      delete configuracao.headers['Content-Type']
    }

    return configuracao
  })

  api.interceptors.response.use(
    (resposta) => resposta,

    (erro) => {
      const ehRotaDeLogin = erro.config?.url?.includes('/login')

      if (erro.response?.status === 401 && !ehRotaDeLogin) {
        localStorage.removeItem('token')
        localStorage.removeItem('usuario')
        window.location.href = '/login'
      }

      return Promise.reject(erro)
    }
  )

  export default api
