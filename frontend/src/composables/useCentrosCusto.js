import { ref } from 'vue'
import api from '@/servicos/api'

const centros = ref([])
let carregado = false

export function useCentrosCusto() {
  async function carregar(forcar = false) {
    if (carregado && !forcar) return
    const { data } = await api.get('/centros-custo')
    centros.value = Array.isArray(data) ? data : (data.data ?? [])
    carregado = true
  }
  return { centros, carregar }
}