  import axios from 'axios'

  const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
  })

  export async function prepararCsrf() {
    await axios.get('/sanctum/csrf-cookie', {
      withCredentials: true,
      withXSRFToken: true,
    })
  }

  export function obterTokenCsrf() {
    const cookie = document.cookie
      .split('; ')
      .find((item) => item.startsWith('XSRF-TOKEN='))

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : null
  }

  api.interceptors.request.use((configuracao) => {
    if (configuracao.data instanceof FormData) {
      delete configuracao.headers['Content-Type']
    }

    return configuracao
  })

  api.interceptors.response.use(
    (resposta) => resposta,

    (erro) => {
      const rotaSemRedirecionamento = ['/login', '/user', '/logout'].some((rota) => erro.config?.url?.includes(rota))

      if (erro.response?.status === 401 && !rotaSemRedirecionamento) {
        window.location.href = '/login'
      }

      return Promise.reject(erro)
    }
  )

  export default api
