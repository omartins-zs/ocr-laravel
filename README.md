# OCR Platform - Laravel (Painel)

Painel administrativo OCR em Laravel, com processamento assincrono e integracao com servico OCR Python externo.

## Arquitetura
- Projeto 1: `C:\laragon\www\ocr-laravel` (este repo)
- Projeto 2: `C:\laragon\www\ocr-service-python` (servico OCR separado)

Fluxo:
1. Usuario faz upload.
2. Laravel salva e cria job.
3. Worker chama OCR externo via `OCR_SERVICE_URL`.
4. Resultado OCR volta em JSON.
5. Laravel persiste extracao, campos, logs e status.

## Modulos ativos
- Autenticacao
- Dashboard
- Documentos (upload, listagem, detalhe)
- Fila e status
- Logs de processamento
- Relatorios
- Configuracoes
- Perfil

## Documentacao operacional
- Execucao local: [`docs/como-executar-local.md`](docs/como-executar-local.md)
- Execucao Docker: [`docs/como-executar-docker.md`](docs/como-executar-docker.md)
- Fluxo principal e regras: [`docs/fluxo-principal-e-regras-negocio.md`](docs/fluxo-principal-e-regras-negocio.md)

## Stack
- Laravel 13 / PHP 8.4
- Blade + Alpine.js + Tailwind CSS
- Redis (docker) ou queue `database` (local)
- PostgreSQL (docker) ou MySQL (local)
- OCR externo por HTTP (FastAPI/Python)

## Comandos rapidos

### Local
```bash
cd C:\laragon\www\ocr-laravel
copy .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
composer run dev-local
```

### Docker
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

## Credencial inicial
- Email: `admin@ocr.local`
- Senha: `password`
