# Como Executar Local

Guia para rodar o projeto localmente, sem Docker e sem Redis.

## 1) Preparar ambiente

```bash
cd C:\laragon\www\ocr-laravel
copy .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan optimize:clear
```

## 2) Rodar backend Laravel

```bash
php artisan serve --host=127.0.0.1 --port=8080
```

## 3) Rodar fila (desenvolvimento)

```bash
php artisan queue:listen --queue=ocr,default --tries=1 --timeout=420
```

## 4) Rodar fila (mais performatico)

```bash
php artisan queue:work --queue=ocr,default --tries=3 --timeout=420 --sleep=1
```

Se alterar codigo de Job/Service com `queue:work`, rode:

```bash
php artisan queue:restart
```

## 5) Frontend

Modo desenvolvimento:

```bash
npm run dev
```

Build de producao local:

```bash
npm run build
```

## 6) Rodar OCR Service Python (externo)

Projeto recomendado do OCR separado:
- `C:\laragon\www\ocr-service-python`

No `.env` do Laravel:

```bash
OCR_SERVICE_URL=http://127.0.0.1:8001
```

## 7) URLs

- App: `http://127.0.0.1:8080`
- OCR health: endpoint do seu OCR externo (ex.: `http://127.0.0.1:8001/health`)

## 8) Credencial inicial

- Email: `admin@ocr.local`
- Senha: `password`

---

## Atalho (serve + queue + vite)

Depois da instalacao inicial, rode tudo com um unico comando:

```bash
composer run dev-local
```
