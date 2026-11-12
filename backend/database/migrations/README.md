# Índice das migrations

Os 44 arquivos usam nomes descritivos por tabela e finalidade. Prefixos numéricos sequenciais, de `001` a `044`, preservam a ordem de execução sem datas nos nomes.

## Base do Laravel e autenticação

| Tabela(s) | Migration | Finalidade |
|---|---|---|
| `users`, `password_reset_tokens`, `sessions` | `001_criar_tabelas_usuarios_sessoes_e_tokens_senha.php` | Criar tabelas de usuários, sessões e redefinição de senha |
| `cache`, `cache_locks` | `002_criar_tabelas_cache_e_cache_locks.php` | Criar tabelas de cache |
| `jobs`, `job_batches`, `failed_jobs` | `003_criar_tabelas_jobs_lotes_e_falhas.php` | Criar tabelas de filas |
| `personal_access_tokens` | `004_criar_tabela_personal_access_tokens.php` | Criar tabela de tokens do Sanctum |
| `users` | `005_adicionar_perfil_a_usuarios.php` | Adicionar perfil |
| `users` | `020_adicionar_foto_url_a_usuarios.php` | Adicionar URL da foto |
| `users` | `021_ampliar_foto_url_em_usuarios.php` | Ampliar o campo da foto |
| `users` | `024_adicionar_pin_a_usuarios.php` | Adicionar PIN |
| `users` | `028_adicionar_status_ativo_a_usuarios.php` | Adicionar status ativo |
| `users` | `029_adicionar_expiracao_do_pin_a_usuarios.php` | Adicionar expiração do PIN |

## Cadastros e estoque

| Tabela(s) | Migration | Finalidade |
|---|---|---|
| `categoria` | `006_criar_tabela_categoria.php` | Criar categorias |
| `fornecedor` | `007_criar_tabela_fornecedor.php` | Criar fornecedores |
| `localizacao` | `008_criar_tabela_localizacao.php` | Criar localizações |
| `usuario` | `009_criar_tabela_usuario_legado.php` | Criar tabela legada de usuários |
| `produto` | `010_criar_tabela_produto.php` | Criar produtos |
| `lote` | `011_criar_tabela_lote.php` | Criar lotes |
| `item_lote` | `012_criar_tabela_item_lote.php` | Criar itens de lote |
| `movimentacao` | `013_criar_tabela_movimentacao.php` | Criar movimentações |
| `entrada_estoque` | `014_criar_tabela_entrada_estoque.php` | Criar registros de entrada |
| `saida_estoque` | `015_criar_tabela_saida_estoque.php` | Criar registros de saída |
| `perda` | `016_criar_tabela_perda.php` | Criar registros de perdas |
| `log_de_auditoria` | `017_criar_tabela_log_de_auditoria_legado.php` | Criar log legado de auditoria |
| `item_lote` | `018_adicionar_prioridade_manual_a_item_lote.php` | Adicionar prioridade manual |
| `movimentacao` | `019_adicionar_id_item_a_movimentacao.php` | Relacionar movimentação ao item |
| `produto` | `022_permitir_categoria_e_fornecedor_nulos_em_produto.php` | Tornar categoria e fornecedor opcionais |
| `audit_logs` | `023_criar_tabela_audit_logs.php` | Criar auditoria |
| `item_lote` | `025_permitir_prioridade_manual_nula_em_item_lote.php` | Permitir prioridade manual nula |
| `movimentacao` | `026_ajustar_chave_estrangeira_lote_movimentacao.php` | Ajustar exclusão em cascata do lote |
| `item_lote` | `027_adicionar_ordem_a_itens_lote.php` | Adicionar ordem dos itens |
| `item_lote`, `movimentacao` | `030_adicionar_indices_a_item_lote_e_movimentacao.php` | Adicionar índices de consulta |
| `item_lote` | `031_adicionar_id_produto_a_item_lote.php` | Relacionar item ao produto |
| `produto` | `032_tornar_sku_unico_em_produto.php` | Tornar SKU único |
| `item_lote` | `033_remover_colunas_obsoletas_de_item_lote.php` | Remover campos legados |
| `movimentacao` | `034_adicionar_transferencia_ao_tipo_movimentacao.php` | Permitir tipo de transferência |
| `categoria` | `035_adicionar_timestamps_a_categoria.php` | Adicionar datas de criação e alteração |
| `categoria`, `fornecedor` | `036_adicionar_timestamps_a_categoria_e_fornecedor.php` | Adicionar datas de criação e alteração |
| `fornecedor` | `037_permitir_cnpj_nulo_em_fornecedor.php` | Tornar CNPJ opcional |
| `produto` | `038_adicionar_percentual_alerta_a_produto.php` | Adicionar percentual de alerta |
| `produto` | `039_ajustar_precisao_do_estoque_atual_em_produto.php` | Ajustar precisão do estoque |
| `movimentacao` | `040_ajustar_precisao_da_quantidade_em_movimentacao.php` | Ajustar precisão da quantidade |
| `item_lote` | `041_alterar_quantidade_em_item_lote_para_bigint.php` | Ajustar tipo da quantidade |
| `produto` | `042_alterar_estoque_atual_em_produto_para_bigint.php` | Ajustar tipo do saldo |
| `centro_custo`, `item_lote` | `043_criar_centro_custo_e_vincular_a_item_lote.php` | Criar centros de custo e relacioná-los aos itens |

## Pedidos e recebimentos

| Tabela(s) | Migration | Finalidade |
|---|---|---|
| `pedido`, `pedido_item`, `fornecedor_produto`, `nota_recebimento`, `nota_item` | `044_criar_tabelas_pedidos_e_recebimentos.php` | Criar pedidos, itens e recebimentos |

## Atualização segura de bancos existentes

O Laravel registra o nome de cada migration na tabela `migrations`. Antes de atualizar o código de um banco que já executou as versões anteriores, faça um backup e execute o sincronizador antes de `php artisan migrate --force`:

```powershell
php artisan migrations:sync-renamed-history
php artisan migrate:status
php artisan migrate --force
```

O comando atualiza apenas os nomes no histórico, preserva os lotes, usa uma transação e não altera as tabelas de negócio. Ele reconhece os nomes originais e os nomes com timestamp usados anteriormente, é seguro para executar novamente e interrompe a operação se detectar registros conflitantes. Em uma instalação nova, sem a tabela `migrations`, não faz alterações e o Laravel executa normalmente as migrations atuais.
