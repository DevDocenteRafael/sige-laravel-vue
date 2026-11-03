// Chave de acesso da NF-e (44 caracteres).
// Estrutura: cUF(2) AAMM(4) CNPJ(14, pode ter letras) modelo(2) série(3) número(9) tpEmis(1) cNF(8) DV(1)
// Mesma regra do backend (app/Services/ChaveAcessoNfe.php).

const UF_POR_CODIGO = {
  '11': 'RO', '12': 'AC', '13': 'AM', '14': 'RR', '15': 'PA', '16': 'AP', '17': 'TO',
  '21': 'MA', '22': 'PI', '23': 'CE', '24': 'RN', '25': 'PB', '26': 'PE', '27': 'AL', '28': 'SE', '29': 'BA',
  '31': 'MG', '32': 'ES', '33': 'RJ', '35': 'SP',
  '41': 'PR', '42': 'SC', '43': 'RS',
  '50': 'MS', '51': 'MT', '52': 'GO', '53': 'DF',
}

// Remove separadores/espaços e deixa em maiúsculas (o leitor ou a colagem podem trazê-los)
export function normalizarChave(valor) {
  return String(valor ?? '').replace(/[^0-9A-Za-z]/g, '').toUpperCase()
}

// Módulo 11 (pesos 2 a 9, da direita para a esquerda) sobre ASCII - 48
export function calcularDv(base) {
  let soma = 0
  let peso = 2
  for (let i = base.length - 1; i >= 0; i--) {
    soma += (base.charCodeAt(i) - 48) * peso
    peso = peso === 9 ? 2 : peso + 1
  }
  const resto = soma % 11
  return resto < 2 ? 0 : 11 - resto
}

// Devolve a mensagem do problema, ou null se é uma chave de NF-e válida
export function validarChave(valor) {
  const chave = normalizarChave(valor)

  if (chave.length !== 44) {
    return 'A chave de acesso precisa ter 44 caracteres (recebi ' + chave.length + ').'
  }

  if (!/^[0-9]{6}[A-Z0-9]{12}[0-9]{26}$/.test(chave)) {
    return 'A chave de acesso tem caracteres inválidos: letras só são aceitas nas posições 7 a 18 (CNPJ do emitente).'
  }

  if (calcularDv(chave.slice(0, 43)) !== Number(chave[43])) {
    return 'Dígito verificador da chave inválido. A leitura pode ter falhado: bipe o código de barras de novo.'
  }

  const mes = Number(chave.slice(4, 6))
  if (mes < 1 || mes > 12) {
    return 'Mês de emissão inválido na chave de acesso.'
  }

  const modelo = chave.slice(20, 22)
  if (modelo !== '55') {
    return 'Esta chave é do modelo ' + modelo + ', e o recebimento aceita só NF-e (modelo 55).'
  }

  return null
}

// Só o que a chave informa de fato. Nome do fornecedor, itens, quantidades e valores NÃO estão na chave.
export function extrairDadosChave(valor) {
  const chave = normalizarChave(valor)
  const cnpj = chave.slice(6, 20)
  const codigoUf = chave.slice(0, 2)

  return {
    codigoUf,
    uf: UF_POR_CODIGO[codigoUf] || null,
    emissao: chave.slice(4, 6) + '/20' + chave.slice(2, 4),
    cnpj,
    cnpjFormatado: cnpj.slice(0, 2) + '.' + cnpj.slice(2, 5) + '.' + cnpj.slice(5, 8) + '/' + cnpj.slice(8, 12) + '-' + cnpj.slice(12, 14),
    modelo: chave.slice(20, 22),
    serie: String(Number(chave.slice(22, 25))),
    numero: String(Number(chave.slice(25, 34))),
  }
}
