<div align="center">

<h1 align="center">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/php/php-original.svg" width="28" alt="PHP" />
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/laravel/laravel-original.svg" width="28" alt="Laravel" />
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/postgresql/postgresql-original.svg" width="28" alt="PostgreSQL" />
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/redis/redis-original.svg" width="28" alt="Redis" />
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/alpinejs/alpinejs-original.svg" width="28" alt="Alpine.js" />
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/tailwindcss/tailwindcss-original.svg" width="28" alt="Tailwind" />
  OCR Laravel Platform
</h1>

<p align="center">
  Painel OCR fullstack em Laravel com filas assíncronas, histórico por lote e integração com serviço OCR Python externo.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-%5E8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/Laravel-%5E13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel" />
  <img src="https://img.shields.io/badge/Node-%5E20.19-339933?style=for-the-badge&logo=node.js&logoColor=white" alt="Node" />
  <img src="https://img.shields.io/badge/Vite-%5E8-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite" />
  <img src="https://img.shields.io/badge/TailwindCSS-%5E4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind" />
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License" />
</p>

</div>

## :memo: Descrição
Sistema OCR operacional com foco em produtividade: upload de documentos, processamento assíncrono, monitoramento de fila/logs, visualização do resultado extraído e reprocessamento.

<cite>Painel Laravel para orquestrar o pipeline OCR e consolidar o fluxo documental ponta a ponta.</cite>

## :vertical_traffic_light: Status do Projeto
<h4 align="center"> :white_check_mark: OCR Laravel Platform :rocket: Em desenvolvimento :gear: </h4>

## :building_construction: Arquitetura do Projeto
- **Tipo:** :bricks: **Monólito (fullstack web + API no mesmo app Laravel)**
- **Como está organizado:** backend, views Blade, filas e API REST dentro do mesmo projeto, com **integração externa** para OCR em serviço Python separado (`ocr-service-python`).

## :fire: Pré-requisitos
- **PHP 8.3+**
- **Node.js 20.19+** (compatível com Vite 8)
- **Composer 2+**
- **NPM 10+**
- **Docker + Docker Compose** (modo Docker)
- **Banco local (opcional):** SQLite/MySQL para execução sem Docker

## :rocket: Tecnologias Utilizadas
- **Linguagem principal:** PHP (Laravel)
- **Framework:** Laravel 13
- **Frontend:** Blade, Alpine.js, Tailwind CSS 4, Vite 8
- **UI/UX libs:** Chart.js, FilePond, Tippy.js, Toastr, NProgress
- **Banco de dados:** PostgreSQL 16 (Docker), MySQL/SQLite (local)
- **Fila e cache:** Redis 7 (Docker) com fallback local configurável
- **OCR:** integração HTTP com serviço Python externo
- **Padrões:** MVC, services, jobs, policies, logs estruturados

## :hammer_and_wrench: Funcionalidades
- Login e controle de acesso por perfil
- Upload de arquivo único e múltiplo (`PDF`, `PNG`, `JPG`, `JPEG`)
- Criação de **lote de upload** para múltiplos arquivos
- Histórico com visualização em **collapse por lote**
- Status do documento e etapa de processamento
- Reprocessamento manual de documentos com falha
- Painel com métricas operacionais e gráficos
- Monitoramento da fila e jobs recentes
- Auditoria de logs por nível/etapa/mensagem
- API REST autenticada para documentos e logs
- Healthcheck interno (`/api/health`) e status do OCR externo

## :dart: Sobre o Projeto
Sistema desenvolvido demonstrando boas práticas de desenvolvimento, arquitetura limpa e organização de código, com foco em escalabilidade e manutenção.

## :camera_flash: Preview do Projeto
:construction: Preview não disponível no projeto.

## :bar_chart: Documentação da API
### :file_folder: Documentação do Projeto
A pasta [`docs/`](docs) contém:
- [`docs/como-executar-local.md`](docs/como-executar-local.md)
- [`docs/como-executar-docker.md`](docs/como-executar-docker.md)
- [`docs/fluxo-principal-e-regras-negocio.md`](docs/fluxo-principal-e-regras-negocio.md)
- [`docs/ocr-test-files/README_TEST_FILES.md`](docs/ocr-test-files/README_TEST_FILES.md)

Também há pacote de arquivos de teste OCR:
- [`docs/ocr-test-files.zip`](docs/ocr-test-files.zip)

### :mailbox_with_mail: Postman / Collections
Collection disponível em:
- [`docs/postman/ocr-laravel-api.postman_collection.json`](docs/postman/ocr-laravel-api.postman_collection.json)

Como importar no Postman:
1. Abra o Postman
2. Clique em **Import**
3. Selecione o JSON da collection
4. Configure `base_url`, `token` e `document_uuid` nas variáveis

### :globe_with_meridians: Swagger
:construction: O projeto não possui Swagger/OpenAPI configurado.

### :framed_picture: Prints / Imagens
Não há screenshots de interface no repositório no momento.

## :computer: Comandos
### :arrow_forward: Execução local
```bash
cd C:\laragon\www\ocr-laravel
copy .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan optimize:clear
composer run dev-local
```

### :whale: Execução com Docker
```bash
cd C:\laragon\www\ocr-laravel
copy .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm install
docker compose exec app npm run build
```

### :test_tube: Qualidade de código
```bash
composer run lint
composer run analyse
composer run test
```

### :closed_lock_with_key: Credencial inicial
- `admin@ocr.local`
- `password`

> ⚠️ Estes são comandos básicos. Verifique no projeto arquivos como:
> README.md, COMO_EXECUTAR.md ou docs/ para instruções completas.

## :bricks: Estrutura do Projeto
```text
ocr-laravel/
├── app/
│   ├── Http/Controllers/
│   ├── Jobs/
│   ├── Models/
│   ├── Policies/
│   ├── Services/
│   └── Support/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── docker/
├── docs/
│   ├── postman/
│   └── ocr-test-files/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
│   ├── web.php
│   └── api.php
├── storage/
├── tests/
├── composer.json
├── docker-compose.yml
└── package.json
```

## :memo: Melhorias Futuras
- [ ] Adicionar documentação OpenAPI/Swagger
- [ ] Adicionar preview visual (GIF/screenshots) no repositório
- [ ] Expandir testes E2E de fluxo de upload e fila
- [ ] Publicar dashboard de observabilidade (tempo por etapa OCR)

## :bulb: Dicas
- Para desenvolvimento local rápido: use `composer run dev-local`.
- Para cenário mais próximo de produção local: use Docker + Redis + Postgres.
- O OCR roda em serviço externo: valide `OCR_SERVICE_URL` e `/health` antes de testar uploads.

<div align="center">

Feito com ❤️ por Gabriel Martins 🚀

</div>
