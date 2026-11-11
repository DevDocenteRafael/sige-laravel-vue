# Índice das migrations

Os 44 arquivos usam nomes descritivos por tabela e finalidade. Os timestamps foram mantidos para preservar a ordem de execução.

## Base do Laravel e autenticação

| Tabela(s) | Migration | Finalidade |
|---|---|---|
| `users`, `password_reset_tokens`, `sessions` | `0001_01_01_000000_criar_tabelas_usuarios_sessoes_e_tokens_senha.php` | Criar tabelas de usuários, sessões e redefinição de senha |
| `cache`, `cache_locks` | `0001_01_01_000001_criar_tabelas_cache_e_cache_locks.php` | Criar tabelas de cache |
| `jobs`, `job_batches`, `failed_jobs` | `0001_01_01_000002_criar_tabelas_jobs_lotes_e_falhas.php` | Criar tabelas de filas |
| `personal_access_tokens` | `2026_06_18_173455_criar_tabela_personal_access_tokens.php` | Criar tabela de tokens do Sanctum |
| `users` | `2026_06_18_175006_adicionar_perfil_a_usuarios.php` | Adicionar perfil |
| `users` | `2026_06_23_181421_adicionar_foto_url_a_usuarios.php` | Adicionar URL da foto |
| `users` | `2026_06_23_183508_ampliar_foto_url_em_usuarios.php` | Ampliar o campo da foto |
| `users` | `2026_07_02_141504_adicionar_pin_a_usuarios.php` | Adicionar PIN |
| `users` | `2026_07_22_150520_adicionar_status_ativo_a_usuarios.php` | Adicionar status ativo |
| `users` | `2026_08_14_154601_adicionar_expiracao_do_pin_a_usuarios.php` | Adicionar expiração do PIN |

## Cadastros e estoque

| Tabela(s) | Migration | Finalidade |
|---|---|---|
| `categoria` | `2026_06_22_180001_criar_tabela_categoria.php` | Criar categorias |
| `fornecedor` | `2026_06_22_180002_criar_tabela_fornecedor.php` | Criar fornecedores |
| `localizacao` | `2026_06_22_180003_criar_tabela_localizacao.php` | Criar localizações |
| `usuario` | `2026_06_22_180004_criar_tabela_usuario_legado.php` | Criar tabela legada de usuários |
| `produto` | `2026_06_22_180005_criar_tabela_produto.php` | Criar produtos |
| `lote` | `2026_06_22_180006_criar_tabela_lote.php` | Criar lotes |
| `item_lote` | `2026_06_22_180424_criar_tabela_item_lote.php` | Criar itens de lote |
| `movimentacao` | `2026_06_22_180500_criar_tabela_movimentacao.php` | Criar movimentações |
| `entrada_estoque` | `2026_06_22_180501_criar_tabela_entrada_estoque.php` | Criar registros de entrada |
| `saida_estoque` | `2026_06_22_180502_criar_tabela_saida_estoque.php` | Criar registros de saída |
| `perda` | `2026_06_22_180503_criar_tabela_perda.php` | Criar registros de perdas |
| `log_de_auditoria` | `2026_06_22_180504_criar_tabela_log_de_auditoria_legado.php` | Criar log legado de auditoria |
| `item_lote` | `2026_06_23_170914_adicionar_prioridade_manual_a_item_lote.php` | Adicionar prioridade manual |
| `movimentacao` | `2026_06_23_172636_adicionar_id_item_a_movimentacao.php` | Relacionar movimentação ao item |
| `produto` | `2026_06_26_154013_permitir_categoria_e_fornecedor_nulos_em_produto.php` | Tornar categoria e fornecedor opcionais |
| `audit_logs` | `2026_06_26_155750_criar_tabela_audit_logs.php` | Criar auditoria |
| `item_lote` | `2026_07_06_141941_permitir_prioridade_manual_nula_em_item_lote.php` | Permitir prioridade manual nula |
| `movimentacao` | `2026_07_06_145151_ajustar_chave_estrangeira_lote_movimentacao.php` | Ajustar exclusão em cascata do lote |
| `item_lote` | `2026_07_09_153227_adicionar_ordem_a_itens_lote.php` | Adicionar ordem dos itens |
| `item_lote`, `movimentacao` | `2026_08_28_150502_adicionar_indices_a_item_lote_e_movimentacao.php` | Adicionar índices de consulta |
| `item_lote` | `2026_09_01_145109_adicionar_id_produto_a_item_lote.php` | Relacionar item ao produto |
| `produto` | `2026_09_01_145916_tornar_sku_unico_em_produto.php` | Tornar SKU único |
| `item_lote` | `2026_09_01_154101_remover_colunas_obsoletas_de_item_lote.php` | Remover campos legados |
| `movimentacao` | `2026_09_01_180000_adicionar_transferencia_ao_tipo_movimentacao.php` | Permitir tipo de transferência |
| `categoria` | `2026_09_03_142342_adicionar_timestamps_a_categoria.php` | Adicionar datas de criação e alteração |
| `categoria`, `fornecedor` | `2026_09_03_142459_adicionar_timestamps_a_categoria_e_fornecedor.php` | Adicionar datas de criação e alteração |
| `fornecedor` | `2026_09_03_142608_permitir_cnpj_nulo_em_fornecedor.php` | Tornar CNPJ opcional |
| `produto` | `2026_09_14_150710_adicionar_percentual_alerta_a_produto.php` | Adicionar percentual de alerta |
| `produto` | `2026_09_16_161904_ajustar_precisao_do_estoque_atual_em_produto.php` | Ajustar precisão do estoque |
| `movimentacao` | `2026_09_16_162117_ajustar_precisao_da_quantidade_em_movimentacao.php` | Ajustar precisão da quantidade |
| `item_lote` | `2026_09_17_170841_alterar_quantidade_em_item_lote_para_bigint.php` | Ajustar tipo da quantidade |
| `produto` | `2026_09_17_170855_alterar_estoque_atual_em_produto_para_bigint.php` | Ajustar tipo do saldo |
| `centro_custo`, `item_lote` | `2026_10_01_170909_criar_centro_custo_e_vincular_a_item_lote.php` | Criar centros de custo e relacioná-los aos itens |

## Pedidos e recebimentos

| Tabela(s) | Migration | Finalidade |
|---|---|---|
| `pedido`, `pedido_item`, `fornecedor_produto`, `nota_recebimento`, `nota_item` | `2026_10_19_160903_criar_tabelas_pedidos_e_recebimentos.php` | Criar pedidos, itens e recebimentos |

## Atualização segura de bancos existentes

O Laravel identifica migrations pelo nome e registra esse nome na tabela `migrations`. Antes de atualizar o código de um banco que já executou as migrations antigas, faça um backup e execute o comando abaixo **antes** de `php artisan migrate --force`:

```powershell
php artisan migrations:sync-renamed-history
php artisan migrate:status
php artisan migrate --force
```

O comando atualiza apenas os nomes correspondentes na tabela `migrations`, preserva os lotes, executa a atualização em transação e não altera as tabelas de negócio. Ele é idempotente; interrompe a operação se detectar registros antigos e novos conflitantes. Em uma instalação nova, sem a tabela `migrations`, não faz alterações e o Laravel executa normalmente as migrations atuais.
