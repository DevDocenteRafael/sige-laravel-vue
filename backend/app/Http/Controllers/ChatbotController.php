<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    private const MODELO = 'claude-sonnet-5';

    public function perguntar(Request $request)
    {
        $request->validate([
            'mensagem' => 'required|string|max:800',
        ]);

        $mensagemOriginal = trim($request->input('mensagem'));

        if (config('services.anthropic.api_key')) {
            try {
                $resposta = $this->perguntarComIA($mensagemOriginal);
                if ($resposta !== null) {
                    return $this->responder($resposta);
                }
            } catch (\Throwable $e) {
                Log::warning('Chatbot IA falhou, usando fallback de regras', ['erro' => $e->getMessage()]);
            }
        }

        return $this->responder($this->gerarRespostaRegras($mensagemOriginal));
    }


    public function perguntarStream(Request $request)
    {
        $request->validate([
            'mensagem' => 'required|string|max:800',
        ]);

        $mensagemOriginal = trim($request->input('mensagem'));

        return response()->stream(function () use ($mensagemOriginal) {
            if (config('services.anthropic.api_key')) {
                try {
                    $this->responderStreamComIA($mensagemOriginal);
                    $this->enviarFimStream();
                    return;
                } catch (\Throwable $e) {
                    Log::warning('Chatbot stream IA falhou, usando fallback', ['erro' => $e->getMessage()]);
                }
            }

            $this->streamTextoSimulado($this->gerarRespostaRegras($mensagemOriginal));
            $this->enviarFimStream();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    private function responderStreamComIA(string $mensagem): void
    {
        $ferramentas = $this->ferramentasDisponiveis();
        $systemPrompt = $this->systemPromptPadrao();
        $messages = [['role' => 'user', 'content' => $mensagem]];

        $resposta = $this->chamarAnthropic($systemPrompt, $messages, $ferramentas);

        if ($resposta === null) {
            throw new \RuntimeException('Sem resposta da IA na etapa de decisão');
        }

        if ($resposta['stop_reason'] !== 'tool_use') {
            $texto = $this->extrairTexto($resposta) ?? 'Não consegui gerar uma resposta agora.';
            $this->streamTextoSimulado($texto);
            return;
        }

        $blocosDeUso = array_filter($resposta['content'], fn($b) => $b['type'] === 'tool_use');

        $resultadosFerramentas = [];
        foreach ($blocosDeUso as $bloco) {
            $dados = $this->executarFerramenta($bloco['name'], $bloco['input'] ?? []);
            $resultadosFerramentas[] = [
                'type' => 'tool_result',
                'tool_use_id' => $bloco['id'],
                'content' => json_encode($dados, JSON_UNESCAPED_UNICODE),
            ];
        }

        $messages[] = ['role' => 'assistant', 'content' => $resposta['content']];
        $messages[] = ['role' => 'user', 'content' => $resultadosFerramentas];

        $this->chamarAnthropicStream($systemPrompt, $messages, $ferramentas);
    }

    private function chamarAnthropicStream(string $systemPrompt, array $messages, array $tools): void
    {
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->withOptions(['stream' => true])
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => self::MODELO,
                'max_tokens' => 1024,
                'system' => $systemPrompt,
                'messages' => $messages,
                'tools' => $tools,
                'stream' => true,
            ]);

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';
        $textoRecebido = false;

        while (!$body->eof()) {
            $buffer .= $body->read(1024);

            while (($posicao = strpos($buffer, "\n")) !== false) {
                $linha = trim(substr($buffer, 0, $posicao));
                $buffer = substr($buffer, $posicao + 1);

                if (!str_starts_with($linha, 'data:')) {
                    continue;
                }

                $json = trim(substr($linha, 5));
                if ($json === '' || $json === '[DONE]') {
                    continue;
                }

                $evento = json_decode($json, true);
                if (!$evento) {
                    continue;
                }

                if (($evento['type'] ?? '') === 'content_block_delta'
                    && ($evento['delta']['type'] ?? '') === 'text_delta'
                ) {
                    $textoRecebido = true;
                    $this->enviarChunkStream($evento['delta']['text']);
                }
            }
        }

        if (!$textoRecebido) {
            $this->enviarChunkStream('Não consegui montar uma resposta com os dados encontrados.');
        }
    }

    private function enviarChunkStream(string $texto): void
    {
        echo 'data: ' . json_encode(['delta' => $texto], JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) {
            @ob_flush();
        }
        flush();
    }

    private function enviarFimStream(): void
    {
        echo 'data: ' . json_encode(['done' => true]) . "\n\n";
        if (ob_get_level() > 0) {
            @ob_flush();
        }
        flush();
    }

    private function streamTextoSimulado(string $texto): void
    {
        $partes = preg_split('/(\s+)/u', $texto, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$texto];
        foreach ($partes as $parte) {
            if ($parte === '') continue;
            $this->enviarChunkStream($parte);
            usleep(16000);
        }
    }


    private function perguntarComIA(string $mensagem): ?string
    {
        $ferramentas = $this->ferramentasDisponiveis();
        $systemPrompt = $this->systemPromptPadrao();
        $messages = [['role' => 'user', 'content' => $mensagem]];

        $resposta = $this->chamarAnthropic($systemPrompt, $messages, $ferramentas);
        if ($resposta === null) return null;

        if ($resposta['stop_reason'] !== 'tool_use') {
            return $this->extrairTexto($resposta);
        }

        $blocosDeUso = array_filter($resposta['content'], fn($b) => $b['type'] === 'tool_use');

        $resultadosFerramentas = [];
        foreach ($blocosDeUso as $bloco) {
            $dados = $this->executarFerramenta($bloco['name'], $bloco['input'] ?? []);
            $resultadosFerramentas[] = [
                'type' => 'tool_result',
                'tool_use_id' => $bloco['id'],
                'content' => json_encode($dados, JSON_UNESCAPED_UNICODE),
            ];
        }

        $messages[] = ['role' => 'assistant', 'content' => $resposta['content']];
        $messages[] = ['role' => 'user', 'content' => $resultadosFerramentas];

        $respostaFinal = $this->chamarAnthropic($systemPrompt, $messages, $ferramentas);
        if ($respostaFinal === null) return null;

        return $this->extrairTexto($respostaFinal);
    }

    private function chamarAnthropic(string $systemPrompt, array $messages, array $tools): ?array
    {
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout(20)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => self::MODELO,
                'max_tokens' => 1024,
                'system' => $systemPrompt,
                'messages' => $messages,
                'tools' => $tools,
            ]);

        if (!$response->successful()) {
            Log::warning('Anthropic API retornou erro', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        }

        return $response->json();
    }

    private function extrairTexto(array $resposta): ?string
    {
        foreach ($resposta['content'] ?? [] as $bloco) {
            if ($bloco['type'] === 'text') {
                return trim($bloco['text']);
            }
        }
        return null;
    }

    private function executarFerramenta(string $nome, array $input): array
    {
        return match ($nome) {
            'buscar_produto' => $this->dadosBuscarProduto($input['termo'] ?? ''),
            'listar_produtos' => $this->dadosListarProdutos(),
            'contar_produtos' => $this->dadosTotalProdutos(),
            'consultar_vencimentos' => $this->dadosVencimentos(),
            'consultar_estoque_critico' => $this->dadosEstoqueCritico(),
            'consultar_perdas' => $this->dadosPerdas(),
            'consultar_movimentacoes' => $this->dadosMovimentacoes(),
            default => ['erro' => 'Ferramenta desconhecida'],
        };
    }


    private function systemPromptPadrao(): string
    {
        return <<<PROMPT
Você é o Assistente SIGE, um assistente de IA completo integrado ao sistema de gestão de estoque do almoxarifado.

Você tem duas frentes:
1. Consultar dados reais do estoque (quantidades, validades, perdas, movimentações, lista de produtos) usando as ferramentas disponíveis. Nunca invente números de estoque — sempre use a ferramenta correta.
2. Para qualquer outro assunto (dúvidas gerais, explicações, ajuda com texto, conversa casual, etc.), responda normalmente com seu próprio conhecimento, como uma IA completa e útil, sem se limitar apenas a estoque.

Regras de formatação e tom:
- Formate listas de itens com marcadores "•", incluindo nome, quantidade e unidade de medida quando fizer sentido.
- Pode usar markdown (negrito, listas, títulos curtos) quando isso deixar a resposta mais organizada.
- Seja direto, natural e simpático. Responda em português do Brasil.
- Nunca mencione nomes de ferramentas, tabelas do banco de dados ou detalhes técnicos internos na resposta ao usuário.
PROMPT;
    }

    private function ferramentasDisponiveis(): array
    {
        return [
            [
                'name' => 'buscar_produto',
                'description' => 'Busca a quantidade em estoque de um ou mais produtos específicos pelo nome. Use quando o usuário perguntar sobre um item em particular (ex: "quantas luvas temos", "cadê o álcool em gel", "tem máscara?").',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'termo' => [
                            'type' => 'string',
                            'description' => 'O nome ou parte do nome do produto que o usuário quer consultar, já isolado da pergunta (ex: "luvas", "álcool em gel").',
                        ],
                    ],
                    'required' => ['termo'],
                ],
            ],
            [
                'name' => 'listar_produtos',
                'description' => 'Lista todos os produtos cadastrados no estoque. Use quando o usuário pedir uma visão geral de tudo que existe no estoque, sem especificar um item.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name' => 'contar_produtos',
                'description' => 'Retorna o número total de produtos distintos cadastrados no sistema. Use quando o usuário perguntar quantos produtos existem/tem cadastrado no total (ex: "quantos produtos tem no sistema", "quantos itens cadastrados").',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name' => 'consultar_vencimentos',
                'description' => 'Lista os itens que vencem nos próximos 30 dias, ordenados do vencimento mais próximo para o mais distante. Use para perguntas sobre validade, vencimento, prazo de itens.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name' => 'consultar_estoque_critico',
                'description' => 'Lista os itens cuja quantidade em estoque está no mínimo ou abaixo dele. Use para perguntas sobre estoque baixo, itens acabando, itens que precisam de reposição.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name' => 'consultar_perdas',
                'description' => 'Lista as perdas de produtos registradas nos últimos 30 dias, com motivo e quantidade. Use para perguntas sobre desperdício, perdas, itens descartados ou estragados.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name' => 'consultar_movimentacoes',
                'description' => 'Lista as últimas movimentações de entrada e saída de estoque. Use para perguntas sobre histórico recente, quem mexeu no estoque, últimas entradas/saídas.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
        ];
    }


    private function dadosListarProdutos(): array
    {
        return DB::table('item_lote')
            ->join('produto', 'item_lote.id_produto', '=', 'produto.id_produto')
            ->select('produto.nome', 'item_lote.quantidade', 'item_lote.unidade_medida')
            ->orderBy('produto.nome')
            ->limit(30)
            ->get()
            ->toArray();
    }

    private function dadosTotalProdutos(): array
    {
        return ['total' => DB::table('produto')->count()];
    }

    private function dadosVencimentos(): array
    {
        return DB::table('item_lote')
            ->join('produto', 'item_lote.id_produto', '=', 'produto.id_produto')
            ->whereNotNull('item_lote.data_validade')
            ->whereBetween('item_lote.data_validade', [now()->startOfDay(), now()->addDays(30)->endOfDay()])
            ->orderBy('item_lote.data_validade')
            ->select('produto.nome', 'item_lote.quantidade', 'item_lote.unidade_medida', 'item_lote.data_validade')
            ->limit(50)
            ->get()
            ->toArray();
    }

    private function dadosEstoqueCritico(): array
    {
        return DB::table('item_lote')
            ->join('produto', 'item_lote.id_produto', '=', 'produto.id_produto')
            ->whereColumn('item_lote.quantidade', '<=', 'produto.estoque_minimo')
            ->select('produto.nome', 'item_lote.quantidade', 'produto.estoque_minimo', 'item_lote.unidade_medida')
            ->limit(15)
            ->get()
            ->toArray();
    }

    private function dadosPerdas(): array
    {
        return DB::table('movimentacao')
            ->join('item_lote', 'movimentacao.id_item', '=', 'item_lote.id_item')
            ->join('produto', 'item_lote.id_produto', '=', 'produto.id_produto')
            ->where('movimentacao.tipo', 'PERDA')
            ->where('movimentacao.data_movimentacao', '>=', now()->subDays(30))
            ->select(
                'produto.nome',
                'movimentacao.quantidade',
                'movimentacao.observacao as razao',
                'movimentacao.data_movimentacao as data_perda'
            )
            ->orderByDesc('movimentacao.data_movimentacao')
            ->limit(15)
            ->get()
            ->toArray();
    }

    private function dadosMovimentacoes(): array
    {
        return DB::table('movimentacao')
            ->join('item_lote', 'movimentacao.id_item', '=', 'item_lote.id_item')
            ->join('produto', 'item_lote.id_produto', '=', 'produto.id_produto')
            ->select('produto.nome', 'movimentacao.tipo', 'movimentacao.quantidade', 'movimentacao.data_movimentacao')
            ->orderByDesc('movimentacao.data_movimentacao')
            ->limit(15)
            ->get()
            ->toArray();
    }

    private function dadosBuscarProduto(string $termo): array
    {
        if (strlen(trim($termo)) < 2) {
            return [];
        }

        $itens = DB::table('item_lote')
            ->join('produto', 'item_lote.id_produto', '=', 'produto.id_produto')
            ->where('produto.nome', 'like', "%{$termo}%")
            ->select('produto.nome', 'item_lote.quantidade', 'item_lote.unidade_medida', 'produto.estoque_minimo')
            ->limit(10)
            ->get();

        if ($itens->isNotEmpty()) {
            return $itens->toArray();
        }

        return $this->buscarProdutoFuzzy($termo)->toArray();
    }

    private function buscarProdutoFuzzy(string $termo)
    {
        $termoNorm = $this->singularizar($this->normalizar($termo));

        $todos = DB::table('item_lote')
            ->join('produto', 'item_lote.id_produto', '=', 'produto.id_produto')
            ->select('produto.nome', 'item_lote.quantidade', 'item_lote.unidade_medida', 'produto.estoque_minimo')
            ->get();

        $comDistancia = $todos->map(function ($item) use ($termoNorm) {
            $nomeNorm = $this->singularizar($this->normalizar($item->nome));
            $palavrasDoNome = explode(' ', $nomeNorm);
            $distancia = min(array_map(
                fn($palavra) => levenshtein($termoNorm, $palavra),
                $palavrasDoNome
            ));
            $item->distancia = $distancia;
            return $item;
        });

        $limiar = max(1, (int) floor(strlen($termoNorm) * 0.4));

        return $comDistancia
            ->filter(fn($item) => $item->distancia <= $limiar)
            ->sortBy('distancia')
            ->take(5)
            ->values();
    }


    private function responderComRegras(string $mensagemOriginal)
    {
        return $this->responder($this->gerarRespostaRegras($mensagemOriginal));
    }

    private function gerarRespostaRegras(string $mensagemOriginal): string
    {
        $pergunta = $this->normalizar($mensagemOriginal);

        if ($this->contem($pergunta, [
            'obrigado',
            'obrigada',
            'valeu',
            'vlw',
            'brigado',
            'brigada',
            'thanks',
            'tchau',
            'ate mais',
            'ate logo',
            'falou',
            'flw',
        ])) {
            return 'De nada! Qualquer coisa é só chamar. 😊';
        }

        if ($this->contem($pergunta, [
            'oi',
            'ola',
            'bom dia',
            'boa tarde',
            'boa noite',
            'eae',
            'e ai',
            'tudo bem',
            'blz',
            'beleza',
            'salve',
            'opa',
            'fala',
            'como vc esta',
            'como voce esta',
            'como vc ta',
            'como voce ta',
            'tudo certo',
            'tudo joia',
            'tudo tranquilo',
            'suave',
            'quem e vc',
            'quem e voce',
            'o que vc faz',
            'o que voce faz',
            'me ajuda',
            'pode me ajudar',
            'preciso de ajuda',
            'o que vc sabe fazer',
            'quais comandos',
            'como funciona',
        ])) {
            return 'Oi! Posso te ajudar com informações sobre estoque, validades, perdas, movimentações — e também com qualquer outra dúvida. O que você quer saber?';
        }

        if ($this->contem($pergunta, [
            'quantos produtos',
            'quantos itens',
            'total de produtos',
            'quantidade de produtos',
            'numero de produtos',
            'quantos produtos cadastrados',
            'quantos produtos existem',
        ])) {
            $total = DB::table('produto')->count();
            return "Atualmente há {$total} produtos cadastrados no sistema.";
        }

        if ($this->contem($pergunta, [
            'vence',
            'vencendo',
            'vencimento',
            'validade',
            'expirando',
            'expira',
            'prazo',
            'venc',
            'vai vencer',
        ])) {
            $itens = $this->dadosVencimentos();
            if (empty($itens)) return 'Nenhum item vencendo nos próximos 30 dias. 👍';
            $linhas = collect($itens)->map(function ($i) {
                $data = \Carbon\Carbon::parse($i->data_validade)->format('d/m/Y');
                return "• {$i->nome} — {$i->quantidade} {$i->unidade_medida} (vence em {$data})";
            })->implode("\n");
            return "Itens vencendo nos próximos 30 dias:\n\n{$linhas}";
        }

        if ($this->contem($pergunta, [
            'critico',
            'criticos',
            'acabando',
            'minimo',
            'baixo estoque',
            'estoque baixo',
            'faltando',
            'em falta',
            'zerado',
            'no vermelho',
        ])) {
            $itens = $this->dadosEstoqueCritico();
            if (empty($itens)) return 'Nenhum item em estoque crítico no momento. 👍';
            $linhas = collect($itens)->map(fn($i) => "• {$i->nome} — {$i->quantidade} {$i->unidade_medida} (mínimo: {$i->estoque_minimo})")->implode("\n");
            return "Itens com estoque crítico:\n\n{$linhas}";
        }

        if ($this->contem($pergunta, ['perda', 'perdas', 'perdi', 'descarte', 'desperdicio', 'quebra', 'estragou'])) {
            $perdas = $this->dadosPerdas();
            if (empty($perdas)) return 'Nenhuma perda registrada nos últimos 30 dias.';
            $total = collect($perdas)->sum('quantidade');
            $linhas = collect($perdas)->map(function ($p) {
                $data = \Carbon\Carbon::parse($p->data_perda)->format('d/m/Y');
                return "• {$p->nome} — {$p->quantidade} un ({$p->razao}, {$data})";
            })->implode("\n");
            return "Perdas nos últimos 30 dias ({$total} unidades no total):\n\n{$linhas}";
        }

        if ($this->contem($pergunta, ['movimenta', 'entrada', 'saida', 'ultima', 'historico', 'quem mexeu'])) {
            $movs = $this->dadosMovimentacoes();
            if (empty($movs)) return 'Nenhuma movimentação registrada ainda.';
            $linhas = collect($movs)->map(function ($m) {
                $data = \Carbon\Carbon::parse($m->data_movimentacao)->format('d/m/Y H:i');
                return "• {$m->tipo} — {$m->nome} ({$m->quantidade} un, {$data})";
            })->implode("\n");
            return "Últimas movimentações:\n\n{$linhas}";
        }

        if ($this->contem($pergunta, [
            'quais sao os produtos',
            'quais os produtos',
            'liste os produtos',
            'listar produtos',
            'lista de produtos',
            'todos os produtos',
            'quais produtos',
            'me mostra os produtos',
            'produtos cadastrados',
            'quais itens',
            'lista de itens',
            'o que tem no estoque',
            'o que tem em estoque',
        ])) {
            $itens = $this->dadosListarProdutos();
            if (empty($itens)) return 'Nenhum produto cadastrado no estoque ainda.';
            $linhas = collect($itens)->map(fn($i) => "• {$i->nome} — {$i->quantidade} {$i->unidade_medida}")->implode("\n");
            return "Produtos no estoque:\n\n{$linhas}";
        }

        if (preg_match('/\b(quant[ao]s?|quantidade|estoque de|tem |tenho |temos|possui|existe|disponivel|onde esta|cade)\b/u', $pergunta)) {
            $termo = $this->extrairTermoBusca($pergunta);
            if (strlen($termo) < 2) return 'Qual produto você quer consultar?';
            $itens = $this->dadosBuscarProduto($termo);
            if (empty($itens)) return "Não encontrei nenhum item parecido com \"{$termo}\".";
            $linhas = collect($itens)->map(fn($i) => "• {$i->nome} — {$i->quantidade} {$i->unidade_medida} (mínimo: {$i->estoque_minimo})")->implode("\n");
            return "Encontrei:\n\n{$linhas}";
        }

        return "Não entendi bem. Você pode perguntar coisas como:\n" .
            "• \"o que vence essa semana?\"\n" .
            "• \"estoque crítico\"\n" .
            "• \"perdas do mês\"\n" .
            "• \"quantas luvas temos?\"";
    }

    private function extrairTermoBusca(string $pergunta): string
    {
        $palavras = [
            'quantas?',
            'quantidade de',
            'quantidade',
            'estoque de',
            'estoque',
            'tenho',
            'temos',
            'tem',
            'possui',
            'existe',
            'ha',
            'disponivel',
            'onde esta',
            'onde estao',
            'cade',
            'de',
            'do',
            'da',
            'no',
            'na',
            'o',
            'a',
            'os',
            'as',
        ];
        $padrao = '/\b(' . implode('|', $palavras) . ')\b/u';
        $termo = preg_replace($padrao, '', $pergunta);
        $termo = str_replace('?', '', $termo);
        return trim(preg_replace('/\s+/', ' ', $termo));
    }


    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = str_replace(
            ['á', 'à', 'â', 'ã', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'ô', 'õ', 'ö', 'ú', 'ù', 'û', 'ü', 'ç'],
            ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c'],
            $texto
        );
        $texto = str_replace(['"', "'", "\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"], '', $texto);
        return trim(preg_replace('/\s+/', ' ', $texto));
    }

    private function singularizar(string $texto): string
    {
        return preg_replace('/s\b/u', '', $texto);
    }

    private function contem(string $texto, array $palavras): bool
    {
        foreach ($palavras as $p) {
            if (str_contains($texto, $p)) return true;
        }
        return false;
    }

    private function responder(string $texto)
    {
        return response()->json(['resposta' => $texto]);
    }
}
