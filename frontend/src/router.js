import { createRouter, createWebHistory } from 'vue-router'
import { useAutenticacaoStore } from '@/servicos/autenticacao.store'
import { perfilPodeAcessarRota } from '@/servicos/permissoes'

const PaginaLogin               = () => import('@/paginas/PaginaLogin.vue')
const PaginaDashboard           = () => import('@/paginas/PaginaDashboard.vue')
const PaginaProdutos            = () => import('@/paginas/PaginaProdutos.vue')
const PaginaLotes               = () => import('@/paginas/PaginaLotes.vue')
const PaginaMovimentos          = () => import('@/paginas/PaginaMovimentos.vue')
const PaginaRelatorios          = () => import('@/paginas/PaginaRelatorios.vue')
const PaginaRelatoriosAvancados = () => import('@/paginas/PaginaRelatoriosAvancados.vue')
const PaginaUsuarios            = () => import('@/paginas/PaginaUsuarios.vue')
const PaginaPerfil              = () => import('@/paginas/PaginaPerfil.vue')
const PaginaPerdas              = () => import('@/paginas/PaginaPerdas.vue')
const PaginaHistorico           = () => import('@/paginas/PaginaHistorico.vue')
const PaginaCentrosCusto        = () => import('@/paginas/PaginaCentrosCusto.vue')
const ImportacaoExportacao      = () => import('@/paginas/ImportacaoExportacao.vue')
const PaginaAuditLogs           = () => import('@/paginas/AuditLogs.vue')
const LayoutPrincipal           = () => import('@/componentes/layout/LayoutPrincipal.vue')

const rotas = [
  {
    path: '/login',
    name: 'login',
    component: PaginaLogin,
  },
  {
    path: '/',
    component: LayoutPrincipal,
    meta: { requerLogin: true },
    children: [
      { path: '',               redirect: '/dashboard' },
      { path: 'dashboard',      name: 'dashboard',      component: PaginaDashboard },
      { path: 'produtos',       name: 'produtos',       component: PaginaProdutos },
      { path: 'lotes',          name: 'lotes',          component: PaginaLotes },
      { path: 'movimentos',     name: 'movimentos',     component: PaginaMovimentos },
      { path: 'perdas',         name: 'perdas',         component: PaginaPerdas },
      { path: 'historico',      name: 'historico',      component: PaginaHistorico },
      { path: 'relatorios',     name: 'relatorios',     component: PaginaRelatorios },
      { path: 'rel-avancados',  name: 'rel-avancados',  component: PaginaRelatoriosAvancados },
      { path: 'importexport',   name: 'importexport',   component: ImportacaoExportacao },
      { path: 'centros-custo',  name: 'centros-custo',  component: PaginaCentrosCusto, meta: { requerPerfil: 'admin' } },
      { path: 'usuarios',       name: 'usuarios',       component: PaginaUsuarios,  meta: { requerPerfil: 'admin' } },
      { path: 'perfil',         name: 'perfil',         component: PaginaPerfil },
      { path: 'log',            name: 'log',            component: PaginaAuditLogs, meta: { requerPerfil: 'admin' } },
    ],
  },
  {
    path: '/:caminhoDesconhecido(.*)*',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes: rotas,
})

router.beforeEach((rotaDestino) => {
  const autenticacao = useAutenticacaoStore()

  if (rotaDestino.meta.requerLogin && !autenticacao.estaLogado) return { name: 'login' }
  if (rotaDestino.meta.requerPerfil && autenticacao.perfil !== rotaDestino.meta.requerPerfil) return { name: 'dashboard' }

  // Bloqueia qualquer rota fora da lista de permissões do perfil, mesmo digitando a URL direto
  if (
    rotaDestino.name &&
    !perfilPodeAcessarRota(autenticacao.perfil, rotaDestino.name)
  ) {
    return { name: 'dashboard' }
  }

  if (rotaDestino.name === 'login' && autenticacao.estaLogado) return { name: 'dashboard' }
})

export default router