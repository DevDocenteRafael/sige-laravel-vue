export const ROTAS_PERMITIDAS_POR_PERFIL = {
  visualizador: ['dashboard', 'produtos', 'relatorios', 'rel-avancados', 'perfil'],
}

export function perfilPodeAcessarRota(perfil, nomeRota) {
  const permitidas = ROTAS_PERMITIDAS_POR_PERFIL[perfil]
  if (!permitidas) return true
  return permitidas.includes(nomeRota)
}