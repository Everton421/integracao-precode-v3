# Integração Precode (integrador)

Projeto PHP que integra seu ERP com a API Precode/Replicade. Envia produtos, preços e estoques para a loja e recebe pedidos aprovados para lançamento no ERP.

Principais scripts
- [index.php](index.php) — interface web simples com listagem de produtos e botões para ações.
- [src/atualizar-preco.php](src/atualizar-preco.php) — envia preços para a API.
- [src/atualizar-estoque.php](src/atualizar-estoque.php) — envia saldo de estoque para a API.
- [src/receber-pedidos.php](src/receber-pedidos.php) — wrapper que instancia a classe de recebimento.
- [src/pedidos.php](src/pedidos.php) — classe principal de recebimento: [`recebePrecode`](src/pedidos.php) (contém também `recebePrecode::formatCnpjCpf`) que cadastra clientes e pedidos.
- [src/enviar-nota.php](src/enviar-nota.php) — envia XML / chave da NFe para a API.
- [src/produtos.php](src/produtos.php) — endpoint para envio manual de produto selecionado pela UI.

Conexão com banco
- As conexões com os bancos são encapsuladas nas classes:
  - [`CONEXAOPUBLICO`](src/database/conexao_publico.php) — [src/database/conexao_publico.php](src/database/conexao_publico.php)
  - [`CONEXAOVENDAS`](src/database/conexao_vendas.php) — [src/database/conexao_vendas.php](src/database/conexao_vendas.php)
  - [`CONEXAOESTOQUE`](src/database/conexao_estoque.php) — [src/database/conexao_estoque.php](src/database/conexao_estoque.php)

Configuração (obrigatória)
- Copie e preencha o arquivo de exemplo: [conexao-example.ini](conexao-example.ini) -> criar `conexao.ini` na raiz do projeto.
- Campos mínimos obrigatórios em `[conexao]`:
  - host, login, senha, porta
  - banco_publico, banco_vendas, banco_estoque
  - token (app token para API) — obrigatório
  - tabelaPreco, setor, filial
  - vendedor_pedido, tipo_recebimento_pedido, forma_pagamento

Como usar
1. Preencha `conexao.ini` baseado em [conexao-example.ini](conexao-example.ini).
2. Coloque o projeto no servidor web (ex.: XAMPP) e abra [index.php](index.php) para operações manuais.
3. Execute os scripts diretamente (cron/jobs) se desejar automação:
   - Atualizar preços: acionar [src/atualizar-preco.php](src/atualizar-preco.php)
   - Atualizar estoque: acionar [src/atualizar-estoque.php](src/atualizar-estoque.php)
   - Receber pedidos: acionar [src/receber-pedidos.php](src/receber-pedidos.php)
   - Enviar notas: acionar [src/enviar-nota.php](src/enviar-nota.php)

Observações e boas práticas
- Verifique permissões e tempo máximo de execução (scripts usam `max_execution_time=0`).
- Teste em ambiente de homologação antes de produção.
- Campos sensíveis (tokens, senhas) não devem ser versionados.

Arquivos relevantes
- [conexao-example.ini](conexao-example.ini)
- [index.php](index.php)
- [src/atualizar-preco.php](src/atualizar-preco.php)
- [src/atualizar-estoque.php](src/atualizar-estoque.php)
- [src/receber-pedidos.php](src/receber-pedidos.php)
- [src/pedidos.php](src/pedidos.php)
- [src/enviar-nota.php](src/enviar-nota.php)
- [src/produtos.php](src/produtos.php)
- [src/database/conexao_publico.php](src/database/conexao_publico.php)
- [src/database/conexao_vendas.php](src/database/conexao_vendas.php)
- [src/database/conexao_estoque.php](src/database/conexao_estoque.php)

Licença
- Ajuste conforme sua necessidade (não incluída).