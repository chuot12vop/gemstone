# Gemstone — Laravel (jewelry & feng shui storefront)

US-market storefront + admin: **USD base prices**, **multi-currency display**, mobile-first UI (white / soft gold). Built on **Laravel 9** (PHP **8.0.2+**).

## Requirements

- PHP 8.0.2+ (see `composer.json`)
- Composer 2
- MySQL 8 (or compatible)

## Quick start

```powershell
cd Gemstone
copy .env.example .env
php artisan key:generate
```

Edit `.env`: `APP_URL`, `DB_*`.

### Database (migrations + seed)

```powershell
php artisan migrate
php artisan db:seed
```

This creates:

- Admin user: **`admin@gemstone.local`** / **`admin123`**
- Demo categories, products, currency rates (USD / EUR / GBP)

### Run locally

```powershell
php artisan serve
```

- Storefront: `http://127.0.0.1:8000`
- Admin: `http://127.0.0.1:8000/admin/login`

Point Apache/nginx **document root** at `public/`.

## Common Artisan commands

| Task | Command |
|------|---------|
| Apply migrations | `php artisan migrate` |
| Roll back last batch | `php artisan migrate:rollback` |
| Fresh DB + migrate | `php artisan migrate:fresh` |
| Fresh + seed | `php artisan migrate:fresh --seed` |
| Seed only | `php artisan db:seed` |

## Authentication

- **Customers** (`users` table, guard `web`): sign in with **Google** — `/login` → “Continue with Google”. Set in `.env`: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and optional `GOOGLE_REDIRECT_URI` (default `{APP_URL}/auth/google/callback`). In [Google Cloud Console](https://console.cloud.google.com/) create OAuth 2.0 Client (Web) and add that redirect URI.
- **Staff** (`admins` table, guard `admin`): email + password at `/admin/login` (seed: `admin@gemstone.local` / `admin123`).

## Project notes

- **Shop routes**: `shop.*` + `login`, `auth.google.*`, `shop.logout`.
- **Admin routes**: prefix `/admin`, middleware `auth:admin` (model `App\Models\Admin`).
- **Currency**: `App\Services\CurrencyService`; `currency_rates` in admin.
- **Assets**: `public/assets/css`, `public/assets/js`, `public/assets/img`.

## License

MIT — Gemstone application layer; Laravel is MIT per upstream.
