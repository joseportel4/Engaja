# 📌 Engaja — Sistema de Gestão de Participação e Engajamento

O **Engaja** é uma aplicação desenvolvida em **Laravel + Bootstrap** para gerenciar eventos educacionais, inscrições, presenças e relatórios de engajamento.  
Ele foi projetado para atender instituições que precisam organizar **formações, oficinas, reuniões, lives** e outros momento, oferecendo:

- Gestão de **usuários** e papéis (roles)
- Cadastro de **eventos** vinculados a eixos temáticos
- **Momento** associados aos eventos (programação)
- **Inscrições** de participantes via cadastro manual ou importação `.xlsx`
- Controle de **presenças**
- Relatórios para acompanhamento de engajamento educacional

---

## 🛠️ Tecnologias

- **Backend:** [Laravel 12](https://laravel.com/)
- **Frontend:** [Bootstrap 5](https://getbootstrap.com/) + Blade Templates
- **Autenticação:** [Laravel Breeze](https://laravel.com/docs/12.x/starter-kits#laravel-breeze)
- **Banco de dados:** PostgreSQL
- **Gerenciamento de dependências:** Composer & NPM

---

## 🚀 Como Rodar o Projeto

### 🔧 Pré-requisitos
- PHP 8.2+
- Composer
- Node.js (versão 20+)
- NPM ou Yarn
- Banco de dados PostgreSQL

### ⚡ Passo a passo

1. **Clonar o repositório**
   ```bash
   git clone https://github.com/seu-usuario/engaja.git
   cd engaja
2. **Instalar dependências**
   ```bash
   composer install
   npm install
3. **Configurar o ambiente**
   ```bash
   cp .env.example .env
   php artisan key:generate
4. **Edite o .env com suas credenciais de banco de dados:**
   ```bash
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=engaja
   DB_USERNAME=postgres
   DB_PASSWORD=secret
5. **Edite o .env com suas credenciais de banco de dados:**
   ```bash
   php artisan migrate --seed
6. **Compilar assets (modo dev)**
   ```bash
   npm run dev
7. **Iniciar servidor Laravel**
   ```bash
   php artisan serve
8. **Acesse o sistema em:**
👉 http://localhost:8000

---

## 🧠 graphify (opcional — grafo de conhecimento do código)

O projeto está integrado ao [graphify](https://github.com/safishamsi/graphify), que transforma o código num grafo de conhecimento consultável via skill `/graphify` no Claude Code.

É **opcional**: o sistema roda sem ele. Para habilitar, instale uma vez (requer Python 3.10+):

```bash
# uv é o instalador recomendado
uv tool install graphifyy        # instala o binário `graphify`
graphify update .                # constrói o grafo inicial (AST-only, sem custo de API)
graphify hook install            # opcional: reconstrói o grafo a cada commit
```

A pasta `graphify-out/` é um artefato reconstruível e **não** é versionada (cada dev gera a sua).
