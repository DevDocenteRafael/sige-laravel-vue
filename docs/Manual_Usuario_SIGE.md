# Manual do usuário — SIGE

**Sistema de Gestão de Estoque**  
**Edição:** 1.0  
**Data:** 12 de novembro de 2026

Este manual orienta o uso das funções disponíveis no SIGE. Os nomes dos botões podem variar ligeiramente conforme o perfil de acesso e a versão instalada.

## 1. Acesso ao sistema

1. Abra o endereço do SIGE fornecido pelo responsável pelo sistema.
2. Na tela de login, informe seu e-mail e sua senha e selecione **Entrar**.
3. Se os dados estiverem incorretos, confira o e-mail e tente novamente. Se a conta estiver inativa ou você não souber a senha, procure um administrador.
4. Para sair, abra as opções da sua conta no menu lateral e use a opção de sair.

O acesso é individual. Não compartilhe sua senha nem deixe a sessão aberta em um computador compartilhado. O administrador deve fornecer as credenciais iniciais. No primeiro acesso, atualize sua senha em **Editar Perfil**.

## 2. Perfis e permissões

| Perfil | O que pode fazer |
|---|---|
| **Visualizador** | Consultar dashboard, produtos e relatórios e atualizar o próprio perfil. Não registra alterações de estoque. |
| **Operador** | Executar tarefas operacionais, como cadastrar e movimentar estoque, registrar perdas, trabalhar com pedidos/recebimentos e usar importações disponíveis. |
| **Administrador** | Usar as funções do sistema, gerenciar usuários e acessar auditoria e backup/restauração. Algumas exclusões também são exclusivas do administrador. |

O menu mostra somente as áreas permitidas para a conta. Se uma ação ou página não aparecer, peça ao administrador para confirmar seu perfil.

## 3. Conhecendo a tela

- **Menu lateral:** navega entre Dashboard, Estoque, Análises e Sistema. Use o botão no cabeçalho do menu para expandi-lo ou recolhê-lo; grupos podem ser abertos e fechados.
- **Dashboard:** apresenta indicadores e atalhos para lotes, produtos, vencimentos, estoque baixo e pendências. Selecione **Personalizar** para reorganizar os cartões e **Concluir** para terminar.
- **Busca e filtros:** refine listas por texto, status, datas, lote, categoria, saldo ou centro de custo, conforme a tela. Use **Limpar Filtros** para recomeçar.
- **Modais:** formulários de inclusão e edição abrem sobre a página. Preencha os campos obrigatórios indicados por `*` e confira antes de salvar.
- **Confirmações:** ações sensíveis, como baixa ou exclusão, podem solicitar confirmação. Leia a mensagem e confira quantidade e item antes de confirmar.

## 4. Consultar produtos e validade

1. Abra **Estoque > Produtos**.
2. Pesquise pelo nome ou SKU. Use os filtros de estoque baixo, categoria, centro de custo e saldo para restringir a lista.
3. A lista mostra quantidade total, estoque mínimo, próxima validade, lotes e status.
4. Clique em um produto para consultar os detalhes dos lotes e validades.
5. Se desejar, ajuste o período do aviso de vencimento; o seletor oferece períodos predefinidos e opção personalizada.

Os indicadores ajudam a localizar itens sem estoque, críticos, em atenção, vencidos ou próximos do vencimento. Verifique a data e o saldo no detalhe antes de movimentar o item.

## 5. Cadastrar lotes e itens

No SIGE, o lote agrupa itens de estoque. Cada item pode ter quantidade, unidade, validade, localização e centro de custo.

### Criar um lote

1. Abra **Estoque > Lotes** e selecione **Novo Lote**.
2. Informe o número do lote e a data de entrada. A descrição é opcional.
3. Selecione **Salvar Lote**.

### Adicionar um item ao lote

1. Abra a aba do lote desejado e selecione **Adicionar Item**.
2. Informe o SKU/código. Se o produto já existir, o sistema o identifica; caso contrário, preencha os dados solicitados para cadastrá-lo.
3. Informe nome, quantidade, unidade, validade e os demais campos solicitados. Para um produto novo, também são solicitados estoque mínimo e categoria.
4. Se aplicável, informe fornecedor, localização, centro de custo e prioridade manual.
5. Selecione **Adicionar** e confira se o item apareceu no lote.

Um mesmo produto pode aparecer em itens de lotes diferentes. O saldo total do produto considera as quantidades dos seus itens de lote. Portanto, prefira registrar uma entrada ou transferência no item correto em vez de criar um cadastro duplicado.

### Editar, organizar ou excluir

Abra o lote para editar seus dados, reordenar itens ou usar as opções de seleção múltipla. A exclusão pode afetar dados associados; confira a confirmação cuidadosamente. A disponibilidade depende do perfil.

## 6. Registrar entrada, baixa e transferência

As operações são feitas a partir do item no lote, não pela tela **Movimentos**. Essa tela serve para consultar o histórico.

### Entrada

1. Em **Estoque > Lotes**, localize o item e escolha a ação de entrada.
2. Informe a quantidade positiva e, se desejar, o motivo.
3. Confira o saldo que será atualizado e selecione **Confirmar Entrada**.

### Baixa (saída)

1. Localize o item e escolha a ação de baixa.
2. Informe a quantidade, que não pode exceder o saldo disponível, e registre o motivo quando aplicável.
3. Confira o saldo previsto e selecione **Confirmar Baixa**.

### Transferência

1. Escolha a ação de transferência do item.
2. Selecione o lote de destino e informe a quantidade.
3. Confira o limite disponível e confirme.

A quantidade transferida sai do item de origem e passa para o destino. Para cada operação, confira produto, unidade e lote antes de confirmar.

## 7. Pedidos e recebimentos de notas

### Consultar ou cadastrar pedidos

1. Abra **Estoque > Pedidos**.
2. Pesquise por número ou fornecedor e filtre pelo status (aberto, parcial, concluído ou cancelado).
3. Para criar, selecione **Novo pedido** e preencha as informações e itens solicitados.
4. Também é possível importar pedidos pela opção **Importar**, quando disponível.
5. Abra um pedido para conferir itens, quantidades pedidas/recebidas e saldo. Use **Exportar planilha** para baixar o pedido.

Pedidos que já têm nota vinculada ou itens recebidos podem não permitir edição. O sistema informa quando o pedido não está editável.

### Importar e conferir uma nota fiscal

1. Abra **Estoque > Recebimentos** e selecione **Importar XML**.
2. Escolha o arquivo XML da NF-e. O limite exibido é 5 MB. Se a nota não informar o pedido, preencha o número do pedido quando solicitado.
3. Abra a nota importada e confirme fornecedor, pedido e itens.
4. Itens sem vínculo podem ser associados a um item do pedido pela opção **Vincular**. Confira descrição, SKU e unidade antes de escolher.
5. Resolva avisos de divergência. Se as unidades forem diferentes, confira a equivalência apresentada antes de prosseguir.
6. Nos itens aptos à entrada, confira quantidade e preencha, quando necessário, lote, prioridade, localização e validade.
7. Selecione os itens que deseja receber e use a ação de entrada disponível na nota. Confirme somente depois de comparar o registro com o material físico recebido.

Uma nota pode ficar pendente ou parcial se faltar vínculo, houver divergência, item não conferido ou ainda restar recebimento. Consulte **Estoque > Pendências** para ver os motivos e retornar à nota ou ao pedido. Não registre como recebido um item que ainda não foi conferido fisicamente.

## 8. Registrar perdas

1. Abra **Estoque > Perdas**.
2. Pesquise por produto, SKU ou lote. Refine por lote, centro de custo ou disponibilidade de estoque.
3. Para um item, escolha a ação de registrar perda e informe a quantidade e os dados solicitados, incluindo o motivo.
4. Revise as informações antes de confirmar.
5. Para vários itens, use **Selecionar**, marque os itens e selecione **Registrar Perdas Selecionadas**.

Itens sem saldo não podem ser selecionados para registrar perda. A tela apresenta totais de registros, unidades perdidas e perdas do mês.

## 9. Consultar histórico e relatórios

- **Histórico:** consulte as entradas e saídas registradas. Use os filtros de tipo e período e selecione **Limpar** para remover os filtros.
- **Relatórios:** filtre por lote, vencimento, texto, período e centro de custo. A tela mostra itens encontrados e indicadores de vencimento e permite exportar CSV, Excel ou PDF.
- **Relatórios avançados:** alterne entre análise de perdas e classificação ABC; filtre por centro de custo e período e use **Exportar** para obter os formatos oferecidos.
- **Dashboard:** use os cartões como atalhos para as listas de produtos, lotes e pendências. Os indicadores são uma visão resumida; confira os registros antes de tomar decisões.

Os resultados refletem os filtros atualmente selecionados. Antes de compartilhar um arquivo, confirme o período, o lote e o centro de custo incluídos.

## 10. Importar e exportar dados

Abra **Sistema > Import/Export**.

### Importar uma planilha de estoque

1. Baixe a planilha modelo e preencha as colunas sem alterar o cabeçalho.
2. Descrição e saldo são obrigatórios. Código, unidade, estoque mínimo, validade e centro de custo podem ser opcionais conforme as instruções da própria planilha.
3. Selecione **Importar Planilha**, escolha o arquivo `.xlsx` ou `.xls` e selecione **Analisar Planilha**.
4. Revise os produtos encontrados, linhas ignoradas e totais.
5. Escolha entre criar um lote único ou distribuir os itens em lotes separados. Ao distribuir, confira as quantidades de cada lote; unidades não distribuídas não serão importadas.
6. Confirme a importação e aguarde a mensagem de conclusão.

Se um código já existir, a importação pode incrementar o estoque desse produto em vez de criar outro. Confira a prévia e mantenha uma cópia da planilha original.

### Exportar e fazer backup

- Produtos e movimentações podem ser exportados em CSV ou Excel.
- O backup de estoque é um arquivo JSON e inclui dados de produtos, lotes, itens e movimentações.
- A restauração substitui dados de estoque. Ela é uma ação administrativa: confirme o arquivo e o ambiente antes de prosseguir e nunca use um backup desconhecido.

Faça backups periódicos e guarde-os em local autorizado. Não compartilhe arquivos que contenham dados internos sem autorização.

## 11. Perfil, usuários e centros de custo

### Editar o próprio perfil

Abra o menu com seu nome e selecione **Editar Perfil**. Atualize nome, e-mail ou foto (limite de 2 MB). Para trocar a senha, informe a senha atual, a nova senha (mínimo de 6 caracteres) e a confirmação; depois selecione **Salvar Alterações**.

### Gerenciar usuários (administrador)

Em **Sistema > Usuários**, o administrador pode pesquisar, cadastrar, editar e ativar/inativar contas. Ao cadastrar, informe nome, e-mail, perfil e senha; forneça a senha inicial ao usuário por um canal seguro. Ao editar, deixe a senha vazia para mantê-la. Não inative sua própria conta.

### Centros de custo

Em **Estoque > Centros de Custo**, usuários autorizados podem pesquisar, filtrar por status, cadastrar ou editar centros e ativá-los/desativá-los. Atribua o centro correto aos itens para facilitar consultas e relatórios. A exclusão é restrita ao administrador e pode estar indisponível quando há vínculos.

## 12. Dúvidas e boas práticas

- **Não consigo entrar:** confira e-mail e senha; se a conta estiver inativa ou bloqueada, contate o administrador.
- **Não vejo uma função:** confirme se o perfil tem permissão para aquela área.
- **Um saldo parece errado:** confira os itens e lotes do produto e seu histórico. Não registre uma movimentação corretiva sem autorização.
- **A nota ficou pendente:** abra **Pendências**, leia o motivo e resolva o vínculo ou a divergência na nota.
- **A planilha não foi aceita:** use o modelo fornecido, mantenha os cabeçalhos e corrija os campos indicados na mensagem.
- Confira produto, unidade, quantidade, validade e lote antes de salvar qualquer operação.
- Registre motivos claros para entradas, baixas e perdas; eles ajudam na conferência posterior.
- Não exclua dados para corrigir uma movimentação sem consultar o responsável pelo almoxarifado.
- Ao terminar, saia da conta, especialmente em equipamentos compartilhados.

