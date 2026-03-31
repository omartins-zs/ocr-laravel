# Como Executar Docker

Guia para rodar o projeto com Docker e Redis.

## 1) Preparar ambiente

```bash
cd C:\laragon\www\ocr-laravel
copy .env.example .env
```

No `.env`, ative o bloco `Docker` e comente o bloco `Local` para:
- `APP_URL`
- `LOG_CHANNEL` e `LOG_STACK`
- `DB_*`
- `QUEUE_CONNECTION`, `CACHE_STORE`, `REDIS_HOST`
- `OCR_SERVICE_URL`

Use `OCR_SERVICE_URL` apontando para OCR externo (recomendado no Docker Desktop):
- `OCR_SERVICE_URL=http://host.docker.internal:8001`

Projeto recomendado do OCR separado:
- `C:\laragon\www\ocr-service-python`

## 2) Subir containers

```bash
docker compose up -d --build
```

## 3) Inicializar app

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app npm install
docker compose exec app npm run build
```

## 4) Acessos

- App: `http://localhost:8080`
- OCR health: endpoint do seu servico OCR externo (ex.: `http://localhost:8001/health`)

## 5) Logs

```bash
docker compose logs -f app worker scheduler
```

## 6) Credencial inicial

- Email: `admin@ocr.local`
- Senha: `password`
