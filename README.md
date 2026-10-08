# SIGE — Sistema de Gestão de Estoque

Sistema de almoxarifado com **Laravel** (backend), **Vue.js** (frontend) e **Docker**.

---

## 📁 Estrutura do projeto

```
sige-laravel-vue/
│
├── docker-compose.yml          ← Sobe tudo com um comando
├── docker/
│   ├── nginx/default.conf      ← Configuração do servidor web
│   └── mysql/init.sql          ← Script inicial do banco
│
├── backend/                    ← API Laravel (PHP)
│   ├── Dockerfile
│   ├── .env.example            ← Copie para .env e configure
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/    ← Lógica de cada rota da API
│   │   │   └── Middleware/     ← Verificação de perfil de acesso
│   │   └── Models/             ← Representação das tabelas do banco
│   ├── database/
│   │   ├── migrations/         ← Criação das tabelas
│   │   └── seeders/            ← Dados iniciais (admin, categorias)
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

## Como rodar o projeto (instalação limpa)

### Pré-requisitos

- PHP >= 8.1
- Composer
- Node.js >= 18
- MySQL (recomendado: XAMPP no Windows ou MySQL Server)
- Git

### Passo a passo

```bash
# 1. Configurar PHP.INI
 Exclua o ponto e vírgula (;) da ";extension gd" e ";extension zip", depois salve (Abrir com bloco de notas)

# 2. Criar cache do bootstrap dentro do Backend
Comando: "mkdir bootstrap\cache" 

# 3. Instale as dependencias do Backend (PHP)
cd Backend
composer install
composer require barryvdh/laravel-dompdf

# 4. Crie o arquivo de ambiente a partir do template
copy .env.example .env

# 5. Gere a chave da aplicacao
php artisan key:generate

# 6. Configure o banco de dados no arquivo .env
#    Edite o arquivo Backend/.env com as seguintes configuracoes:
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3307
#    DB_DATABASE=sige
#    DB_USERNAME=root
#    DB_PASSWORD=       (deixe vazio se for XAMPP padrao)

# 7. Crie o banco de dados no MySQL
#    Acesse o MySQL e execute: CREATE DATABASE sige;

# 8. Limpe o cache de configuracao (importante em ambientes novos)
php artisan config:clear
php artisan cache:clear

# 9. Execute as migrations (cria as tabelas)
php artisan migrate

# 10. Execute os seeders (popula o banco com dados de desenvolvimento) - OBRIGATORIO!
php artisan db:seed

# 11. Inicie o servidor Backend
php artisan serve
#    O servidor ira rodar em: http://localhost:8000

# 12. Em outro terminal, instale as dependencias do Frontend
cd ../Frontend
npm install
npm install lucide-vue-next marked dompurify exceljs grid-layout-plus
# 13. Inicie o servidor de desenvolvimento do Frontend
npm run dev
#    O frontend ira rodar em: http://localhost:5173
```


**5. Acesse o sistema**

| Serviço   | Endereço                  |
|-----------|---------------------------|
| Frontend  | http://localhost:3000     |
| Backend   | http://localhost:8000/api |
| Banco     | localhost:3306            |

---

## 👤 Usuários para teste

| Perfil       | Email                  | Senha          |
|--------------|-------------------------|----------------|
| Admin        | admin@sige.com          | Admin@2024     |
| Admin        | angel@sige.com          | Angel@2024     |
| Admin        | natan@sige.com          | Natan@2024     |
| Operador     | amanda@sige.com         | Operador@2024  |
| Operador     | daniela@sige.com        | Operador@2024  |
| Operador     | miguel@sige.com         | Operador@2024  |
| Visualizador | visualizador@sige.com   | Visual@2024    |

---

## 🔐 Perfis de acesso

| Ação                          | Admin | Operador | Visualizador |
|-------------------------------|:-----:|:--------:|:------------:|
| Ver produtos/lotes/movimentos |  ✅   |    ✅    |      ✅      |
| Criar/editar produtos         |  ✅   |    ✅    |      ❌      |
| Registrar entrada/saída       |  ✅   |    ✅    |      ❌      |
| Gerenciar usuários            |  ✅   |    ❌    |      ❌      |
| Remover produtos/categorias   |  ✅   |    ❌    |      ❌      |

---

## 🌐 Rotas da API

### Públicas (sem login)
| Método | Rota         | Descrição        |
|--------|--------------|------------------|
| POST   | /api/login   | Fazer login      |

### Protegidas (precisam do token no header `Authorization: Bearer {token}`)

| Método | Rota                        | Descrição                    |
|--------|-----------------------------|------------------------------|
| POST   | /api/logout                 | Fazer logout                 |
| GET    | /api/me                     | Dados do usuário logado      |
| GET    | /api/dashboard              | Resumo geral                 |
| GET    | /api/produtos               | Listar produtos              |
| POST   | /api/produtos               | Criar produto                |
| PUT    | /api/produtos/{id}          | Editar produto               |
| DELETE | /api/produtos/{id}          | Desativar produto            |
| GET    | /api/lotes                  | Listar lotes com itens       |
| POST   | /api/lotes                  | Criar lote com itens         |
| GET    | /api/lotes/itens            | Itens disponíveis (p/ select)|
| GET    | /api/movimentos             | Histórico de movimentos      |
| POST   | /api/movimentos/entrada     | Registrar entrada            |
| POST   | /api/movimentos/saida       | Registrar saída              |
| GET    | /api/relatorios/estoque     | Relatório de estoque         |
| GET    | /api/relatorios/vencimentos | Itens vencidos/vencendo      |
| GET    | /api/relatorios/auditoria   | Log de auditoria             |
| GET    | /api/usuarios               | Listar usuários (admin)      |
| POST   | /api/usuarios               | Criar usuário (admin)        |
| PUT    | /api/usuarios/{id}          | Editar usuário (admin)       |
| DELETE | /api/usuarios/{id}          | Desativar usuário (admin)    |

---

## 🛑 Parar o projeto

```bash
docker compose down
```

Para parar **e apagar os dados do banco**:
```bash
docker compose down -v
```

---

## 🧰 Tecnologias usadas

| Camada     | Tecnologia                          |
|------------|-------------------------------------|
| Backend    | PHP 8.2 + Laravel 11                |
| Auth       | Laravel Sanctum (tokens)            |
| Frontend   | Vue.js 3 + Vite                     |
| Estado     | Pinia                               |
| Rotas      | Vue Router 4                        |
| HTTP       | Axios                               |
| Estilo     | Tailwind CSS                        |
| Banco      | MySQL 8                             |
| Servidor   | Nginx + PHP-FPM                     |
| Container  | Docker + Docker Compose             |
