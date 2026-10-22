// Converte "YYYY-MM-DD" em Date local, evitando o bug de fuso horário
// (new Date("YYYY-MM-DD") interpreta como UTC e pode voltar 1 dia no Brasil)
function paraDataLocal(dataString) {
  const [ano, mes, dia] = dataString.split('T')[0].split('-').map(Number)
  return new Date(ano, mes - 1, dia)
}

export function formatarData(dataString) {
  if (!dataString) return '—'

  const data = paraDataLocal(dataString)
  return data.toLocaleDateString('pt-BR')
}

export function formatarDataHora(dataString) {
  if (!dataString) return '—'

  const data = new Date(dataString.replace(' ', 'T'))
  const dataFormatada = data.toLocaleDateString('pt-BR')
  const horaFormatada = data.toLocaleTimeString('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
  })

  return `${dataFormatada} às ${horaFormatada}`
}

export function estaVencido(dataString) {
  if (!dataString) return false
  return paraDataLocal(dataString) < new Date()
}

export function proximoDoVencimento(dataString, dias = 30) {
  if (!dataString) return false

  const dataValidade = paraDataLocal(dataString)
  const hoje = new Date()
  const limite = new Date()
  limite.setDate(hoje.getDate() + dias)

  return dataValidade >= hoje && dataValidade <= limite
  
}
