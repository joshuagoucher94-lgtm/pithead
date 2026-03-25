# PITHEAD ROASTWORKS

Production-oriented static marketing site (Astro + Tailwind) with PHP 8 + MySQL commerce for **Hostinger shared hosting** (no Node on the server).

## Stack

- **Frontend:** Astro (static `output`), Tailwind, Alpine.js on the homepage featured grid only
- **Backend:** PHP under `php/public/` (app code in `php/public/_inc/`)
- **Payments:** Stripe **Embedded Checkout** (`ui_mode: embedded`) via `stripe-php` bundled in `php/public/_inc/vendor/stripe-php/`
- **Database:** MySQL (see `sql/schema.sql`)

## GitHub + Hostinger (skip local PHP/MySQL)

You can treat **GitHub as source of truth** and **Hostinger as where the site runs**. Shared hosting does **not** run `npm run build`, so do **not** point Hostinger’s Git deploy at the raw repo expecting a working site—the live tree must be the **merged** output (Astro `dist` + `php/public`), same as the `deploy/` folder from `./scripts/build-deploy.sh`.

### Automatic deploy (FTP from GitHub Actions)

Workflow: [`.github/workflows/deploy-hostinger-ftp.yml`](.github/workflows/deploy-hostinger-ftp.yml)

On every push to **`main`** or **`master`** it builds that bundle, uploads **`pithead-public_html`** as a workflow artifact (zip), then deploys the same files to Hostinger over **FTP** (FTPS on port 21).

1. **Repository secrets** (GitHub → **Settings** → **Secrets and variables** → **Actions**):

   | Secret | Value |
   |--------|--------|
   | `HOSTINGER_FTP_HOST` | FTP hostname from hPanel (**Files** → **FTP Accounts**), often `ftp.yourdomain.com` |
   | `HOSTINGER_FTP_USERNAME` | FTP username |
   | `HOSTINGER_FTP_PASSWORD` | FTP password |

2. **Remote path** is set to **`public_html/`** in the workflow. If your account uses a different web root, edit `server-dir` in that YAML file.

3. **FTPS vs FTP** — the workflow uses `protocol: ftps-legacy` and `port: 21`. If the job fails to connect, change `protocol` to `ftp` in [`.github/workflows/deploy-hostinger-ftp.yml`](.github/workflows/deploy-hostinger-ftp.yml) and push again (Hostinger’s docs vary by plan).

4. **Server-only files** are excluded from delete/sync rules: `_inc/config.local.php`, `_inc/logs/`, `uploads/`. Create **`public_html/_inc/config.local.php`** once on the server (see below); it will not be removed on later deploys.

5. **Until the three FTP secrets exist**, the **ftp** job will fail on each push (the **build** job and artifact still succeed). You can temporarily **disable** the workflow under **Actions** if you are not ready.

### One-time server setup

- **Database** — hPanel → **MySQL** → create database and user → **phpMyAdmin** → import [`sql/schema.sql`](sql/schema.sql).
- **`config.local.php`** — on Hostinger, create `public_html/_inc/config.local.php` from [`php/public/_inc/config.example.php`](php/public/_inc/config.example.php). Set DB credentials, `app.base_url` to `https://your-domain.com`, Stripe keys. Never commit this file.
- **Stripe webhook** — `https://your-domain.com/api/stripe-webhook.php`.

### Manual zip only (no FTP)

Run **Actions** → **Build site for Hostinger (artifact only)** → **Run workflow**, then download **pithead-public_html** and upload/extract into `public_html`. See [`.github/workflows/build-site.yml`](.github/workflows/build-site.yml).

**Hostinger “Deploy from Git”** that clones this repo **without** a build step will not produce the merged site. Use the FTP workflow above or the manual artifact flow.

**Editing the site:** push to `main`; the FTP workflow redeploys automatically once secrets are set.

## Local development

### `npm run dev` (Astro only)

```bash
cd astro
npm install
npm run dev
```

Use this for **editing Tailwind/marketing pages** quickly. Routes: `/`, `/about/`, `/cacao/`, `/drinks/`, `/wholesale/`.

It does **not** run PHP. Calls like `/api/products.php`, `/shop/`, checkout, and `/admin` will **not** work here (wrong server / 404).

### Full site preview (matches production)

Do this before go-live: same layout as Hostinger — static Astro output **and** PHP in **one** document root.

1. **MySQL** — create DB and import:

   ```bash
   mysql -u root -p pithead < sql/schema.sql
   ```

2. **Config** — copy and edit (DB, Stripe **test** keys, **exact local URL**):

   ```bash
   cp php/public/_inc/config.example.php php/public/_inc/config.local.php
   ```

   Set `'base_url' => 'http://127.0.0.1:8080'` (or whatever port you use) under `app` so Stripe `return_url` and redirects are correct.

3. **Build merged site** into `deploy/`:

   ```bash
   ./scripts/build-deploy.sh
   ```

4. **Serve with PHP** (PHP 8.1+). The built-in server **ignores `.htaccess`**, so use the dev router (handles `/admin/`, `/shop/your-slug`, and Astro `*/index.html`):

   ```bash
   cd deploy
   php -S 127.0.0.1:8080 ../scripts/dev-router.php
   ```

   Open **http://127.0.0.1:8080/** — same origin for marketing pages, `/api/*`, `/shop/`, checkout, and admin.

   If you use **Apache locally** with `AllowOverride` and the merged tree as `DocumentRoot`, you can skip the router; `.htaccess` will apply.

   **`zsh: command not found: php`** — Apple’s macOS often ships without a `php` binary in your PATH. Install PHP 8.1+ locally, for example with [Homebrew](https://brew.sh):

   ```bash
   brew install php
   php -v
   ```

   Then run `cd deploy` and the `php -S 127.0.0.1:8080 ../scripts/dev-router.php` line again. Other options: **Laravel Herd**, **MAMP**, or test PHP only on Hostinger after upload (no local PHP required for `npm run dev` / Astro work).

5. **Stripe webhooks locally** (optional but useful):

   ```bash
   stripe listen --forward-to http://127.0.0.1:8080/api/stripe-webhook.php
   ```

   Put the CLI signing secret into `config.local.php` as `stripe.webhook_secret` while testing.

**Admin (local):** http://127.0.0.1:8080/admin/ — default seed user:

- **Email:** `admin@pithead.local`
- **Password:** `password` (rotate before production; see `sql/schema.sql`)

**`SQLSTATE[HY000] [2002] Connection refused`** — MySQL is not accepting connections. Start the server (e.g. Homebrew: `brew services start mysql` or `mariadb`), create the `pithead` database, and run `mysql … < sql/schema.sql`. If it still fails on macOS, set `'unix_socket' => '/tmp/mysql.sock'` (or your Homebrew socket path) in `db` inside `config.local.php` instead of relying on `host`/`port`. After changing config, run `./scripts/build-deploy.sh` again so `deploy/_inc/config.local.php` is updated, or copy the file into `deploy/_inc/` manually.

## Production build + deploy (Hostinger)

```bash
chmod +x scripts/build-deploy.sh
./scripts/build-deploy.sh
```

This runs `astro build`, copies the main CSS bundle to `php/public/assets/app.css`, and merges `astro/dist/*` with `php/public/*` into `deploy/`.

Upload **everything inside** `deploy/` to `public_html/`:

- Astro pages: `index.html`, `about/`, `cacao/`, etc.
- PHP: `api/`, `shop/`, `admin/`, `partials/`, `_inc/` (protected by `.htaccess`), `contact.php`, `wholesale-apply.php`, `uploads/`, `assets/`

The `_inc/` directory holds config, auth, Stripe SDK, and libraries. Apache should deny direct HTTP access (`.htaccess` with `Require all denied` is included).

## Stripe

1. **Keys** in `config.local.php`: `stripe.secret_key`, `stripe.publishable_key`, `stripe.webhook_secret`.
2. **Webhook endpoint:** `https://your-domain.com/api/stripe-webhook.php`
3. Listen for at least: `checkout.session.completed`, `checkout.session.async_payment_failed`, `checkout.session.expired` (optional but supported).

Test locally with [Stripe CLI](https://stripe.com/docs/stripe-cli):

```bash
stripe listen --forward-to http://localhost:8000/api/stripe-webhook.php
```

## Commerce flow

1. Session cart (`$_SESSION['cart']`) via `api/cart-add.php` and `api/cart-update.php`.
2. Checkout collects email/name, then `POST /api/create-embedded-checkout-session.php` creates an **order** (`pending_payment`) and a **Checkout Session** (`ui_mode: embedded`).
3. `shop/checkout.php` mounts Embedded Checkout with Stripe.js.
4. `return_url` → `shop/thank-you.php` clears the cart when payment is confirmed; webhooks update `orders.status`.

## Optional Composer

`php/composer.json` is optional. The Stripe PHP SDK is bundled at `php/public/_inc/vendor/stripe-php/`.

## Licence

Proprietary — PITHEAD ROASTWORKS.
