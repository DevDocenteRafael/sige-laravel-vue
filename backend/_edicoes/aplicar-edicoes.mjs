#!/usr/bin/env node
// Aplica as edições do fluxo "bipar chave -> achar nota -> (se não existir) enviar XML da mesma chave".
//
// Uso:
//   node aplicar-edicoes.mjs <RecebimentoController.php> <pagina-de-recebimento.vue> --dry-run
//   node aplicar-edicoes.mjs <RecebimentoController.php> <pagina-de-recebimento.vue>
//
// Só grava se TODAS as âncoras forem encontradas exatamente o número esperado de vezes.
// Se qualquer uma falhar, nada é alterado. Antes de gravar, cria <arquivo>.bak (se ainda não existir).
// Aceita arquivos com fim de linha LF ou CRLF.

import fs from 'node:fs'

const r = String.raw
const args = process.argv.slice(2)
const dryRun = args.includes('--dry-run')
const [caminhoController, caminhoPagina] = args.filter((a) => !a.startsWith('--'))

if (!caminhoController || !caminhoPagina) {
  console.error('Uso: node aplicar-edicoes.mjs <RecebimentoController.php> <pagina.vue> [--dry-run]')
  process.exit(1)
}

const ALVOS = [
  {
    nome: 'RecebimentoController',
    caminho: caminhoController,
    marcador: 'ChaveAcessoNfe',
    edicoes: [
      {
        nome: 'use do servico',
        vezes: 1,
        de: r`use App\Services\DescricaoMatcher;`,
        para: r`use App\Services\ChaveAcessoNfe;
use App\Services\DescricaoMatcher;`,
      },
      {
        nome: 'porChave: normalizar, validar e sinalizar nota nao importada',
        vezes: 1,
        de: r`    // Usado quando o conferente bipa o código de barras da nota (chave de 44 dígitos)
    public function porChave(string $chave)
    {
        $chave = preg_replace('/\D/', '', $chave);

        if (strlen($chave) !== 44) {
            return response()->json(['message' => 'A chave de acesso precisa ter 44 dígitos.'], 422);
        }

        $nota = NotaRecebimento::where('chave_acesso', $chave)->first();

        if (!$nota) {
            return response()->json([
                'message' => 'Esta nota ainda não foi importada. Envie o XML dela primeiro.',
            ], 404);
        }`,
        para: r`    // Usado quando o conferente bipa o código de barras da nota (chave de 44 caracteres)
    public function porChave(string $chave)
    {
        $chave = ChaveAcessoNfe::normalizar($chave);

        if ($erro = ChaveAcessoNfe::validar($chave)) {
            return response()->json(['message' => $erro], 422);
        }

        $nota = NotaRecebimento::where('chave_acesso', $chave)->first();

        if (!$nota) {
            return response()->json([
                'message'       => 'Esta nota ainda não foi importada. Envie o XML dela primeiro.',
                'nao_importada' => true,
            ], 404);
        }`,
      },
      {
        nome: 'importarXml: aceitar chave_esperada na validacao',
        vezes: 1,
        de: r`            'numero_pedido' => 'nullable|string|max:30',`,
        para: r`            'numero_pedido' => 'nullable|string|max:30',
            'chave_esperada' => 'nullable|string|max:60',`,
      },
      {
        nome: 'importarXml: recusar XML de outra chave',
        vezes: 1,
        de: r`        $existente = NotaRecebimento::where('chave_acesso', $dados['chave'])->first();`,
        para: r`        // Importação iniciada a partir de uma chave bipada: o XML precisa ser dessa mesma nota
        $chaveEsperada = ChaveAcessoNfe::normalizar($request->input('chave_esperada'));
        if ($chaveEsperada !== '' && ChaveAcessoNfe::normalizar($dados['chave']) !== $chaveEsperada) {
            return response()->json([
                'message' => "O XML enviado é da chave {$dados['chave']}, diferente da chave bipada ({$chaveEsperada}). Nada foi importado.",
            ], 422);
        }

        $existente = NotaRecebimento::where('chave_acesso', $dados['chave'])->first();`,
      },
    ],
  },
  {
    nome: 'Página de Recebimento de Notas',
    caminho: caminhoPagina,
    marcador: 'NotaNaoImportadaModal',
    edicoes: [
      {
        nome: 'imports',
        vezes: 1,
        de: r`import { formatarData } from '@/utils/date'`,
        para: r`import { formatarData } from '@/utils/date'
import { normalizarChave, validarChave, extrairDadosChave } from '@/utils/chaveAcesso'
import NotaNaoImportadaModal from '@/componentes/NotaNaoImportadaModal.vue'`,
      },
      {
        nome: 'estado novo',
        vezes: 1,
        de: r`// ===== Estado do detalhe =====`,
        para: r`const notaNaoImportada = ref(null)   // { chave, dados } quando a chave bipada não tem nota cadastrada
const chaveEsperada    = ref('')     // chave bipada que o XML importado precisa ter

// ===== Estado do detalhe =====`,
      },
      {
        nome: 'buscarPorChave: validar a chave antes de chamar a API',
        vezes: 1,
        de: r`  const chave = chaveBusca.value.replace(/\D/g, '')

  // Limpa sempre, para a próxima bipada não se somar à anterior
  chaveBusca.value = ''

  if (chave.length !== 44) {
    erro('A chave de acesso precisa ter 44 dígitos.')
    focarCampoChave()
    return
  }`,
        para: r`  const chave = normalizarChave(chaveBusca.value)

  // Limpa sempre, para a próxima bipada não se somar à anterior
  chaveBusca.value = ''

  const problema = validarChave(chave)
  if (problema) {
    erro(problema)
    focarCampoChave()
    return
  }`,
      },
      {
        nome: 'buscarPorChave: nota nao cadastrada abre o painel guiado',
        vezes: 1,
        de: r`  } catch (e) {
    console.error(e)
    erro(e.response?.data?.message || 'Não foi possível buscar a nota.')
  } finally {
    buscandoChave.value = false`,
        para: r`  } catch (e) {
    if (e.response?.status === 404 && e.response.data?.nao_importada) {
      // Nota ainda não cadastrada: mostra o que a chave informa e oferece o envio do XML
      notaNaoImportada.value = { chave, dados: extrairDadosChave(chave) }
    } else {
      console.error(e)
      erro(e.response?.data?.message || 'Não foi possível buscar a nota.')
    }
  } finally {
    buscandoChave.value = false`,
      },
      {
        nome: 'fecharImportar + funcoes do painel',
        vezes: 1,
        de: r`function fecharImportar() {
  modalImportarAberto.value = false
  arquivoXml.value = null
  numeroPedido.value = ''
}`,
        para: r`function fecharImportar() {
  modalImportarAberto.value = false
  arquivoXml.value = null
  numeroPedido.value = ''
  chaveEsperada.value = ''
}

// Chave bipada sem nota cadastrada: abre a importação já presa a essa chave
function enviarXmlDaChave() {
  chaveEsperada.value = notaNaoImportada.value.chave
  notaNaoImportada.value = null
  modalImportarAberto.value = true
}

function fecharNaoImportada() {
  notaNaoImportada.value = null
  focarCampoChave()
}`,
      },
      {
        nome: 'importarXml: enviar a chave esperada',
        vezes: 1,
        de: r`  if (numeroPedido.value.trim()) form.append('numero_pedido', numeroPedido.value.trim())`,
        para: r`  if (numeroPedido.value.trim()) form.append('numero_pedido', numeroPedido.value.trim())
  if (chaveEsperada.value) form.append('chave_esperada', chaveEsperada.value)`,
      },
      {
        nome: 'modal de importar: mostrar a chave esperada',
        vezes: 1,
        de: r`        <div class="space-y-4 mb-6">`,
        para: r`        <div class="space-y-4 mb-6">
          <p v-if="chaveEsperada" class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 p-3 text-xs text-blue-800 dark:text-blue-200">
            O XML precisa ser da chave <span class="font-mono break-all">{{ chaveEsperada }}</span>.
            Se for de outra nota, a importação é recusada.
          </p>`,
      },
      {
        nome: 'painel de nota nao cadastrada no template',
        vezes: 1,
        de: r`    <!-- ===== MODAL EXCLUIR NOTA ===== -->`,
        para: r`    <!-- ===== PAINEL: CHAVE BIPADA SEM NOTA CADASTRADA ===== -->
    <NotaNaoImportadaModal
      v-if="notaNaoImportada"
      :chave="notaNaoImportada.chave"
      :dados="notaNaoImportada.dados"
      :pode-importar="autenticacao.podeCadastrar"
      @enviar-xml="enviarXmlDaChave"
      @fechar="fecharNaoImportada"
    />

    <!-- ===== MODAL EXCLUIR NOTA ===== -->`,
      },
      {
        nome: 'campos do bipador aceitam letras (CNPJ alfanumerico)',
        vezes: 2,
        de: r`inputmode="numeric"`,
        para: r`inputmode="text"`,
      },
      {
        nome: 'placeholder da lista',
        vezes: 1,
        de: r`placeholder="Bipar chave de acesso (44 dígitos)"`,
        para: r`placeholder="Bipar chave de acesso (44 caracteres)"`,
      },
      {
        nome: 'placeholder do detalhe',
        vezes: 1,
        de: r`placeholder="Bipar outra nota (44 dígitos)"`,
        para: r`placeholder="Bipar outra nota (44 caracteres)"`,
      },
    ],
  },
]

const resultados = []
let falhou = false

for (const alvo of ALVOS) {
  if (!fs.existsSync(alvo.caminho)) {
    console.error('[ERRO] Arquivo não encontrado: ' + alvo.caminho)
    process.exit(1)
  }

  const original = fs.readFileSync(alvo.caminho, 'utf8')

  if (original.includes(alvo.marcador)) {
    console.error('[ERRO] ' + alvo.nome + ': parece que as edições já foram aplicadas (achei "' + alvo.marcador + '"). Nada foi alterado.')
    process.exit(1)
  }

  const eol = original.includes('\r\n') ? '\r\n' : '\n'
  let novo = original

  for (const e of alvo.edicoes) {
    const de = e.de.replace(/\n/g, eol)
    const para = e.para.replace(/\n/g, eol)
    const achadas = novo.split(de).length - 1

    if (achadas !== e.vezes) {
      console.error('[ERRO] ' + alvo.nome + ' / "' + e.nome + '": esperava ' + e.vezes + ' ocorrência(s) do trecho original, achei ' + achadas + '.')
      falhou = true
      continue
    }

    novo = novo.split(de).join(para)
  }

  resultados.push({ alvo, novo })
}

if (falhou) {
  console.error('\nNada foi alterado. Mande esta saída e o trecho do arquivo que não bateu.')
  process.exit(1)
}

if (dryRun) {
  for (const { alvo } of resultados) console.log('[OK - dry-run] ' + alvo.nome + ': ' + alvo.edicoes.length + ' edições aplicáveis.')
  console.log('\nNada foi gravado (dry-run).')
  process.exit(0)
}

for (const { alvo, novo } of resultados) {
  const bak = alvo.caminho + '.bak'
  if (!fs.existsSync(bak)) fs.copyFileSync(alvo.caminho, bak)
  fs.writeFileSync(alvo.caminho, novo, 'utf8')
  console.log('[OK] ' + alvo.nome + ': ' + alvo.edicoes.length + ' edições gravadas (backup em ' + bak + ').')
}
