# Estampille Back

Backend Laravel de `estampille-app`, preconfigure pour tourner en Docker avec:

- `app` (PHP-FPM Laravel)
- `nginx`
- `postgres`
- `redis`
- `worker` (queue)
- `scheduler` (cron Laravel)
- `horizon` (monitoring des jobs)

## Prerequis

- Docker Desktop demarre
- Docker Compose v2

## Demarrage rapide

1. Demarrer Docker Desktop.
2. Depuis `estampille-back`, lancer:

```bash
docker compose up -d --build
```

3. Installer les dependances PHP dans le conteneur:

```bash
docker compose exec app composer install
```

4. Generer la cle application:

```bash
docker compose exec app php artisan key:generate
```

5. Migrer la base:

```bash
docker compose exec app php artisan migrate
```

6. (Optionnel) Demarrer Horizon:

```bash
docker compose --profile horizon up -d horizon
```

## Acces

- API Laravel: `http://localhost:8080`
- PostgreSQL: `localhost:5432` (`estampille/estampille`)
- Redis: `localhost:6379`

## Endpoints QR (MVP)

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me` (Bearer token)
- `POST /api/v1/auth/logout` (Bearer token)
- `POST /api/v1/companies`
- `POST /api/v1/products`
- `POST /api/v1/certificates`
- `POST /api/v1/qr-batches` (creer un lot, asynchrone)
- `GET /api/v1/qr-batches/{batch}` (statut de generation)
- `GET /api/v1/qr-batches/{batch}/codes` (tokens + URLs de verification, pagine)
- `PATCH /api/v1/qr-codes/{qrCode}/revoke`
- `GET /api/v1/verify/{token}` (verification d'un QR)

## Structure des donnees (alignee au front)

- `companies`
  - `name`, `registration_number`, `email`, `phone`
  - `website`, `country`, `province`, `province_code`, `address`, `status`
- `products`
  - `company_id`, `name`, `description`, `sku`, `image_url`, `status`
- `certificates`
  - `product_id`, `certificate_number`, `standard`
  - `issued_at`, `expires_at`, `file_name`, `file_url`
- `qr_batches`
  - `company_id`, `product_id`, `certificate_id`, `quantity`, `prefix`
- `qr_codes`
  - `code`, `verification_token`, `status`, `revoked_at`, `revocation_reason`

Filtres pour `GET /api/v1/qr-batches/{batch}/codes`:

- `page` (defaut 1)
- `per_page` (defaut 50, max 500)
- `status` (`active` ou `revoked`)
- `search` (code ou id)
- `serial_from`, `serial_to`

## Notes

- La configuration applicative Docker est dans `.env`.
- Si `horizon` n'est pas encore installe, executer:

```bash
docker compose exec app composer require laravel/horizon
docker compose exec app php artisan horizon:install
```

Voir les workers Horizon:

```bash
docker compose logs -f horizon
```

Exemples Horizon utiles:

```bash
docker compose exec app php artisan horizon:status
docker compose exec app php artisan queue:work --once
docker compose logs -f worker
```
