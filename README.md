# Viações App — PHP MVC

Aplicação web de cadastro e gerenciamento de viações (empresas de transporte), construída com PHP puro seguindo o padrão **MVC artesanal** — sem framework externo.

## Funcionalidades

- Cadastro, edição e exclusão de viações (com upload de logo)
- Busca por nome e cidade
- Autenticação de usuários com sessão
- Histórico de auditoria (quem criou/editou/deletou e o quê)
- API REST em JSON para consulta de tasks
- Página pública com viações ativas

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.1+ |
| Banco de dados | MySQL 8.0 |
| Servidor | Apache (mod_rewrite) |
| Containerização | Docker + Docker Compose |
| Gerenciador de pacotes | Composer |

## Pré-requisitos

- [Docker](https://www.docker.com/) e Docker Compose instalados
- Portas **8081** e **3308** disponíveis na máquina

## Instalação e execução

bash
# 1. Clone o repositório
git clone <url-do-repositorio>
cd php-task-app

# 2. Suba os containers
docker-compose up -d

# 3. Acesse no browser
http://localhost:8081


O banco de dados é criado automaticamente na primeira execução a partir do arquivo `src/database/init.sql`.

---

## Acesso padrão

| Campo | Valor |
|---|---|
| URL | http://localhost:8081 |
| E-mail | admin@admin.com |
| Senha | password |


## Estrutura do projeto

src/
├── public/
│   ├── index.php          # Front controller — porta de entrada de todas as requisições
│   ├── api.php            # Entrada alternativa da API REST
│   └── .htaccess          # Redireciona todas as URLs para index.php
│
├── routes/
│   ├── web.php            # Mapa de URLs → Controllers (interface web)
│   └── api.php            # Mapa de URLs → Controllers (API JSON)
│
├── Core/
│   ├── Router.php         # Interpreta URLs, faz dispatch para o controller correto
│   └── View.php           # Renderiza templates PHP dentro do _layout.php
│
├── Controllers/
│   ├── ViacaoController.php        # CRUD de viações + auditoria
│   ├── AutenticadorController.php  # Login e logout
│   ├── HomeController.php          # Página pública
│   ├── TaskController.php          # CRUD de tasks
│   └── Api/
│       └── TaskApiController.php   # Endpoints JSON da API
│
├── Services/
│   ├── ViacaoService.php       # Queries SQL de viações
│   ├── HistoricoService.php    # Gravação e leitura do histórico de auditoria
│   ├── AutenticatorService.php # Validação de login com bcrypt
│   └── TaskService.php         # Queries SQL de tasks
│
├── Models/
│   ├── Viacao.php      # Objeto de dados da viação
│   ├── Usuario.php     # Objeto de dados do usuário
│   ├── Historico.php   # Objeto de dados do histórico
│   └── Task.php        # Objeto de dados da task
│
├── views/
│   ├── _layout.php              # Esqueleto HTML global (menu, head, flash messages)
│   ├── viacoes/
│   │   ├── index.php            # Listagem com busca
│   │   ├── create.php           # Tela de criação
│   │   ├── edit.php             # Tela de edição
│   │   └── form.php             # Formulário reutilizado por create e edit
│   ├── home/
│   │   └── index.php            # Página pública
│   └── autenticator/
│       └── login.php            # Formulário de login
│
└── database/
├── db.php      # Função getPdo() — conexão singleton com o banco
└── init.sql    # Criação das tabelas e usuário admin padrão



## Como o fluxo MVC funciona

Browser → .htaccess → public/index.php
↓
Router (routes/web.php)
↓
Controller
/         \
Service         View::render()
↓                  ↓
Model           views/arquivo.php
↓                  ↓
Banco            _layout.php
↓
HTML → Browser
```

1. Toda requisição HTTP chega ao `public/index.php` (pelo `.htaccess`)
2. O `Router` lê as rotas de `routes/web.php` e encontra qual Controller chamar
3. O Controller delega consultas/alterações ao **Service**
4. O Service executa queries SQL e retorna objetos **Model**
5. O Controller chama `View::render('caminho/view', $dados)` para exibir o resultado
6. O `Core/View.php` executa o template e o injeta no `_layout.php`

---

## API REST

A API retorna dados em JSON e aceita apenas requisições `GET`.

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/tasks` | Lista todas as tasks |
| GET | `/api/tasks/{id}` | Retorna uma task por ID |

**Exemplo de resposta:**
```json
{
  "ok": true,
  "count": 2,
  "data": [
    {
      "id": 1,
      "title": "Minha task",
      "cidade": "Curitiba",
      "is_done": false,
      "data_criacao": "2025-01-15 10:30:00"
    }
  ]
}
```

---

## Banco de dados

Três tabelas principais:

- **`usuarios`** — usuários do sistema com senha em hash bcrypt
- **`viacoes`** — cadastro de viações com nome, URL, cidade, logo e status
- **`historico_viacoes`** — auditoria de todas as alterações, com snapshot JSON do estado antes e depois de cada operação

---

## Variáveis de ambiente

Definidas no `docker-compose.yml` e lidas pela função `env()` em `database/db.php`:

| Variável | Padrão | Descrição |
|---|---|---|
| `DB_HOST` | `db` | Host do banco de dados |
| `DB_NAME` | `viacoes` | Nome do banco |
| `DB_USER` | `root` | Usuário do banco |
| `DB_PASSWORD` | `root123` | Senha do banco |

---

## Comandos úteis

```bash
# Subir os containers
docker-compose up -d

# Ver logs em tempo real
docker-compose logs -f app

# Parar os containers
docker-compose down

# Resetar o banco (apaga todos os dados)
docker-compose down -v
docker-compose up -d
```
