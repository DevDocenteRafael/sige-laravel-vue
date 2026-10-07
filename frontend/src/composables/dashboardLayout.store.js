import { defineStore } from 'pinia'

const PADRAO = [
  // grid de 12 colunas
  { i: 'alertas',      x: 0, y: 0, w: 6, h: 8 },
  { i: 'movimentos',   x: 6, y: 0, w: 6, h: 8 },
  { i: 'categorias',   x: 0, y: 8, w: 6, h: 10 },
  { i: 'evolucao',     x: 6, y: 8, w: 6, h: 10 },
]

const CHAVE = 'sige:dashboard-layout:v1'

export const useDashboardLayoutStore = defineStore('dashboardLayout', {
  state: () => ({
    layout: JSON.parse(localStorage.getItem(CHAVE) || 'null') ?? structuredClone(PADRAO),
    editando: false,
  }),
  actions: {
    salvar() {
      localStorage.setItem(CHAVE, JSON.stringify(this.layout))
    },
    resetar() {
      this.layout = structuredClone(PADRAO)
      this.salvar()
    },
  },
})