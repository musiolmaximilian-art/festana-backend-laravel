# Festana Backend (Laravel 11)

API-only Laravel 11 scaffold for Festana.

## Requirements

- PHP 8.2+
- Composer
- PostgreSQL 13+

## Local Setup

1. Install dependencies:
   ```bash
   composer install
   ```

2. Copy environment file and set your values:
   ```bash
   cp .env.example .env
   ```

3. Generate an application key:
   ```bash
   php artisan key:generate
   ```

4. Configure PostgreSQL credentials in `.env`:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=festana
   DB_USERNAME=postgres
   DB_PASSWORD=secret
   ```

## Running Migrations

```bash
php artisan migrate
```

## Starting the Server

```bash
php artisan serve
```

The health check endpoint is available at `GET /api/health` and returns:
```json
{ "status": "ok" }
```

## Authentication (Sanctum)

Register:
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Festana User","email":"user@example.com","password":"password123"}'
```

Login:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'
```

Current user:
```bash
curl http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Logout:
```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Events

List events:
```bash
curl http://localhost:8000/api/events \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Create an event:
```bash
curl -X POST http://localhost:8000/api/events \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "website_name": "festana-wedding",
    "title": "Festana Wedding",
    "wedding_date": "2030-06-01",
    "timezone": "Europe/Berlin",
    "public_settings": { "is_public": false }
  }'
```

Show an event:
```bash
curl http://localhost:8000/api/events/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Update an event:
```bash
curl -X PATCH http://localhost:8000/api/events/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "website_name": "festana-wedding",
    "title": "Updated Wedding Title",
    "public_settings": { "is_public": true }
  }'
```

Delete an event:
```bash
curl -X DELETE http://localhost:8000/api/events/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Public event (only when `is_public` is true):
```bash
curl http://localhost:8000/api/public/events/festana-wedding
```
