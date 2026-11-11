# SIGE — Sistema de Gestão de Estoque

Sistema de almoxarifado com **Laravel** (backend), **Vue.js** (frontend) e **Docker**.

---

## Estrutura do projeto

```
sige-laravel-vue/
│
├── docker-compose.yml          ← Sobe tudo com um comando
├── docker/
│   ├── nginx/default.conf      ← Configuração do servidor web
│   └── mysql/init.sql          ← Script inicial do banco
│
├── backend/                    ← Aplicação oficial da API Laravel (PHP)
│   ├── Dockerfile
│   ├── .env.example            ← Copie para .env e configure
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/    ← Lógica de cada rota da API
│   │   │   └── Middleware/     ← Verificação de perfil de acesso
│   │   └── Models/             ← Representação das tabelas do banco
│   ├── database/
│   │   ├── migrations/         ← Criação das tabelas
│   │   └── seeders/            ← Dados iniciais (admin)
│   └── routes/api.php          ← Todas as rotas da API
│
└── frontend/                   ← Interface Vue.js
    ├── index.html
    ├── vite.config.js
    └── src/
        ├── main.js             ← Ponto de entrada do Vue
        ├── App.vue             ← Componente raiz
        ├── router.js           ← Rotas do frontend
        ├── paginas/            ← Uma pasta por tela do sistema
        ├── componentes/
        │   ├── layout/         ← Sidebar, header
        │   ├── ui/             ← Componentes genéricos (cards, modais)
        │   ├── produtos/       ← Modais e tabelas de produtos
        │   └── movimentos/     ← Modais de entrada/saída
        ├── servicos/           ← API (axios) e stores (Pinia)
        └── estilos/            ← CSS global
```

`backend_backup/` é uma cópia de referência legada e não faz parte da aplicação em execução. Os manifestos oficiais são `backend/composer.json` e `frontend/package.json`; os manifestos na raiz são arquivos legados e não devem ser usados para instalar dependências. Eles e a cópia de referência só devem ser removidos após validação com a equipe.

O índice das migrations está em [`backend/database/migrations/README.md`](./backend/database/migrations/README.md). Todas foram renomeadas por tabela e finalidade. Em bancos que já executaram os nomes antigos, faça backup e rode `php artisan migrations:sync-renamed-history` antes de `php artisan migrate`; o comando atualiza apenas o histórico, preservando os lotes, para evitar a reexecução das migrations.

---

## Como rodar o projeto

### Pré-requisitos

- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL (recomendado: XAMPP no Windows ou MySQL Server)
- Git

### Opção 1: rodar localmente

```bash
# 1. Configurar o PHP.INI
#    Remova o ponto e vírgula (;) de ";extension=gd" e ";extension=zip" e salve

# 2. Criar a pasta de cache do bootstrap dentro do backend
cd backend
mkdir bootstrap\cache

# 3. Instalar as dependências travadas do backend
composer install

# 4. Criar o arquivo de ambiente a partir do template
copy .env.example .env

# 5. Gerar a chave da aplicação
php artisan key:generate

# 6. Configurar o banco no backend/.env
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3307
#    DB_DATABASE=sige
#    DB_USERNAME=root
#    DB_PASSWORD=

# 7. Criar o banco no MySQL
#    CREATE DATABASE sige;

# 8. Limpar o cache de configuração
php artisan config:clear
php artisan cache:clear

# 9. Sincronizar nomes antigos no histórico (necessário em bancos já migrados)
php artisan migrations:sync-renamed-history

# 10. Criar as tabelas
php artisan migrate

# 11. Criar o usuário admin inicial (obrigatório)
php artisan db:seed

# 12. Iniciar o backend
php artisan serve
#    Roda em http://localhost:8000

# 13. Em outro terminal, instalar as dependências travadas do frontend
cd ../frontend
npm ci

# 14. Iniciar o frontend
npm run dev
#    Roda em http://localhost:3000
```

### Opção 2: Docker (somente desenvolvimento)

Crie o arquivo `.env` da raiz a partir de `.env.example` e defina valores aleatórios para `APP_KEY`, `DB_PASSWORD` e `DB_ROOT_PASSWORD` antes de iniciar os serviços. O `.env` do backend permanece separado e local.

```bash
docker compose up -d --build

# Sincronizar nomes antigos (não altera instalações novas)
docker compose exec backend php artisan migrations:sync-renamed-history

# Criar as tabelas e o admin
docker compose exec backend php artisan migrate --seed
```

No `backend/.env`, use `DB_HOST=banco` quando rodar pelo Docker.

O `docker-compose.yml` é exclusivamente para desenvolvimento e executa o Vite. Debug fica desligado por padrão e o banco não publica uma porta para a rede. Não use essa configuração em produção. Em produção, use imagens de aplicação próprias, HTTPS, cookies seguros (`SESSION_SECURE_COOKIE=true`), uma CSP restritiva com nonce/hash, chaves e senhas fornecidas pelo gerenciador de segredos, rede privada para o banco e `APP_DEBUG=false`.

### Distribuição do projeto

Não compacte a pasta de trabalho, pois ela pode conter `.env` e logs locais ignorados pelo Git. Gere um pacote apenas com arquivos versionados:

```bash
git archive --format=zip --output=sige-release.zip HEAD
```

Rotacione qualquer chave ou senha que já tenha sido incluída em um pacote compartilhado.

### Endereços

| Serviço  | Endereço                  |
|----------|---------------------------|
| Frontend | http://localhost:3000     |
| Backend  | http://localhost:8000/api |
| Banco    | localhost:3307 (Docker, somente loopback) |

---

## Primeiro acesso

O `php artisan db:seed` cria apenas o usuário **admin** inicial (e-mail e senha definidos em `backend/database/seeders/DatabaseSeeder.php`).

**Troque a senha do admin no primeiro login.** Os demais usuários (operadores e visualizadores) são criados pelo admin na tela de usuários.

---

## Perfis de acesso

| Ação                                           | Admin | Operador | Visualizador |
|------------------------------------------------|:-----:|:--------:|:------------:|
| Ver produtos, lotes, itens, pedidos, relatórios|  ✅   |    ✅    |      ✅      |
| Criar/editar lotes, itens, pedidos, perdas     |  ✅   |    ✅    |      ❌      |
| Registrar entrada, baixa e transferência       |  ✅   |    ✅    |      ❌      |
| Importar planilhas e XML de recebimento        |  ✅   |    ✅    |      ❌      |
| Remover produtos, itens, lotes e pedidos       |  ✅   |    ✅    |      ❌      |
| Remover centros de custo e movimentações       |  ✅   |    ❌    |      ❌      |
| Exportar e restaurar backup                    |  ✅   |    ❌    |      ❌      |
| Logs de auditoria                              |  ✅   |    ❌    |      ❌      |
| Gerenciar usuários                             |  ✅   |    ❌    |      ❌      |

---

## Rotas da API

### Públicas

| Método | Rota       | Descrição                         |
|--------|------------|-----------------------------------|
| POST   | /api/login | Fazer login (limite: 5 por minuto)|

### Protegidas

O navegador envia o cookie de sessão HttpOnly automaticamente; operações de escrita usam o cookie de proteção CSRF.

| Grupo           | Rotas principais                                                        |
|-----------------|-------------------------------------------------------------------------|
| Sessão          | `POST /logout`, `GET /user`                                             |
| Dashboard       | `GET /dashboard`, `GET /dashboard/top-produtos`                         |
| Produtos        | `GET /produtos`, `GET /produtos/buscar-por-sku`, `DELETE /produtos/{id}`|
| Lotes           | `/lotes` (CRUD), `GET /lotes/{id}/itens`, `POST /lotes/{id}/itens`      |
| Itens           | `GET /itens`, `PUT /itens/{id}`, `PATCH /itens/{id}/baixa`, `/entrada`, `/transferir`, `GET /itens/{id}/historico` |
| Centros de custo| `/centros-custo`                                                        |
| Pedidos         | `/pedidos` (CRUD, itens, importação e exportação)                       |
| Recebimentos    | `/recebimentos` (importar XML, vincular itens, dar entrada)             |
| Perdas          | `/perdas`, `/perdas/estatisticas`                                       |
| Movimentações   | `GET /movimentacoes`                                                    |
| Relatórios      | `/relatorios/*`, `/relatorios-avancados/*`                              |
| Importação/Exportação | `/importacao-exportacao/*` (templates, preview, confirmar, CSV, XLSX, backup) |
| Auditoria       | `GET /audit-logs`, `GET /audit-logs/export` (admin)                     |
| Usuários        | `/usuarios` (admin)                                                     |
| Chatbot         | `POST /chatbot`, `POST /chatbot/stream` (limite: 20 por minuto)         |

A lista completa e as permissões de cada rota estão em `backend/routes/api.php`.

---

## Testes

No backend, execute `php artisan test` dentro de `backend/`. Para a verificação de sanitização do chatbot, execute `npm test` dentro de `frontend/`.

## Parar o projeto

```bash
docker compose down
```

Para parar **e apagar os dados do banco**:

```bash
docker compose down -v
```

---

## Tecnologias usadas

| Camada    | Tecnologia                |
|-----------|---------------------------|
| Backend   | PHP 8.2 + Laravel 12      |
| Auth      | Laravel Sanctum (sessão e cookie HttpOnly) |
| Frontend  | Vue.js 3 + Vite           |
| Estado    | Pinia                     |
| Rotas     | Vue Router 4              |
| HTTP      | Axios                     |
| Estilo    | Tailwind CSS              |
| Banco     | MySQL 8                   |
| Servidor  | Nginx + PHP-FPM           |
| Container | Docker + Docker Compose   |