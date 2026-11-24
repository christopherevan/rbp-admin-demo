# Kinetra Admin — a sandboxed portfolio demo

A working replica of a sales and order-management system I built and run in
production for an industrial parts distributor. It covers quotation to cash,
web inquiries, inventory, and an AI copywriting pipeline with a person in the
loop. The company, people and figures are all fictional; the code paths are
real.

Every visitor gets **their own throwaway copy of the database**. They can
change anything, switch between the Admin and Sales roles, and reset to a
fresh copy whenever they like.

## What's inside

| Module                    | Highlights                                                                                                                                                                                                                                                                       |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Quote-to-cash**         | Draft → sent → customer PO → sales order (full or partial) → delivery order → invoice → payments, plus down-payment invoices. The server-side state machine and policies decide the "next step" the UI offers. Every step writes an audit log, and delivery orders deduct stock. |
| **Web inquiries**         | A public quote form feeds an inbox with status filters and counts. Triage runs assign → approve or reject (with a reason) → link a customer → draft the quotation in one click.                                                                                                  |
| **AI description review** | LLM-written product copy is checked by a guard for invented figures, compliance claims with no evidence, the wrong kind of product, and text copied from the manufacturer. A person then edits, approves or rejects it, with J/K/A/R keyboard review.                            |
| **Dashboard**             | Pipeline counts, most-quoted parts with a price and quantity history chart, and a live activity feed.                                                                                                                                                                            |
| **Master data**           | Customers with contacts and archiving, a parts master with stock adjustments, units of measure.                                                                                                                                                                                  |
| **Documents**             | Print-ready quotation, delivery order and invoices.                                                                                                                                                                                                                              |

Every page has an **About this feature** popover. It explains what the screen
does and what the production system does beyond the demo.

## Stack

Laravel 13 · Inertia 3 · React 19 · TypeScript · Tailwind CSS 4 · shadcn/ui ·
TanStack Table · Recharts · Wayfinder · SQLite · Pest · FrankenPHP · Docker

## How the sandbox works

- `php artisan demo:build-template` migrates and seeds
  `storage/demo/template.sqlite`. The seeder plans four months of history as
  dated events, then **replays them through the real workflow services** with
  the clock set to each event. Numbers, totals, stock and audit logs come out
  exactly as the app produces them.
- `POST /demo/enter` is the only route that creates a sandbox. It copies the
  template to `storage/demo/sandboxes/<id>.sqlite` and sets an encrypted
  `demo_sandbox` cookie. Crawlers and guests never touch the database.
- `App\Http\Middleware\DemoSandbox` runs right after the session starts and
  points the default connection at the visitor's file. That happens before
  authentication, route model binding and the shared Inertia props read
  anything.
- **Reset** moves the visitor to a new copy rather than overwriting the old
  one, so an in-flight request can never corrupt the fresh data.
- The scheduler prunes sandboxes idle for 3 hours (or older than 24) and
  rebuilds the template nightly, so dates stay current.
- FrankenPHP runs in **classic mode**, never worker mode. Switching the
  connection per request is only safe when every request boots fresh.

Quotations are addressed by ULID in every URL, including print links. The
integer id stays the primary key for foreign keys and the audit log.

## Running locally

Requirements: PHP 8.3+ with `pdo_sqlite`, `sqlite3` and `intl`; Composer;
Node 22+.

```bash
composer setup   # install, .env, app key, demo template, npm install, build
composer dev     # app server, Vite and logs
```

Open the app, pick a persona, and you're in. The username login is
`demo` / `demo1234`.

## Tests

```bash
php artisan test      # Pest: workflow, numbering, totals, guard, policies, sandbox isolation, seeder
npm run types:check
npm run check         # lint and formatting
```

The sandbox suite builds a real template and drives separate "visitors" with
their own cookies. It proves they can't see each other's data, including
through model-bound URLs, and that reset, role switching and expiry work.

## Deploying (VPS with Docker)

```bash
cp .env.docker.example .env      # set APP_KEY, APP_URL and SERVER_NAME
docker compose up -d --build
```

- `SERVER_NAME=demo.example.com` gives automatic HTTPS (ports 80 and 443 must
  reach the container).
- Behind an existing reverse proxy, use `SERVER_NAME=:80`,
  `HTTP_BIND=127.0.0.1:8080` and `TRUSTED_PROXIES=<proxy address>`.
- Two containers share one image: `app` (about 120 MB of RAM at rest) and
  `scheduler`. Sandboxes live on a tmpfs volume, so a deploy starts everyone
  afresh.
- Generate a key with `docker run --rm kinetra-admin-demo php artisan key:generate --show`.

### Automated CI/CD (GitHub Actions + GHCR + Tailscale SSH)

- `.github/workflows/tests.yml` runs on pull requests and via `deploy.yml`: builds the demo template and frontend assets, then runs `composer ci:check` (Vite+ lint/format, TypeScript, Pint, PHPStan, and Pest).
- `.github/workflows/deploy.yml` runs on pushes to `main` (and manual dispatch): waits for CI, builds and pushes the Docker image to `ghcr.io`, and — when repository variable `DEPLOY_ENABLED` is `true` — joins your tailnet as `tag:ci`, stages the image tag, and runs `sudo /usr/local/sbin/kinetra-demo-deploy` (installed from `docker/deploy.sh`, which pulls, restarts, checks `/up`, and rolls back on failure).

The deploy user (`kinetra-deploy`) has no `docker` group access and cannot edit `docker-compose.yml` or `.env`; it can only invoke the single root-owned script via sudoers.

Configure these repository secrets and variables in GitHub:

| Name                 | Kind     | Required     | Description                                           |
| -------------------- | -------- | ------------ | ----------------------------------------------------- |
| `TS_OAUTH_CLIENT_ID` | Secret   | Yes          | Tailscale OAuth client ID permitted to issue `tag:ci` |
| `TS_OAUTH_SECRET`    | Secret   | Yes          | Tailscale OAuth client secret                         |
| `VPS_HOST`           | Secret   | Yes          | VPS Tailscale IP or MagicDNS hostname                 |
| `VPS_USER`           | Secret   | Yes          | Dedicated deploy user on the VPS (`kinetra-deploy`)   |
| `VPS_KNOWN_HOSTS`    | Secret   | Yes          | Output of `ssh-keyscan` for the VPS host key          |
| `DEPLOY_ENABLED`     | Variable | To enable CD | Set to `true` to turn on automatic VPS deploys        |

## Code map

```
app/Services/QuotationWorkflow.php   state machine: one method per transition
app/Services/InquiryWorkflow.php     inquiry triage and conversion
app/Services/DescriptionGuard.php    fact-checks AI copy
app/Support/DocumentNumber.php       atomic per-day document counters
app/Support/QuotationTotals.php      integer-rupiah totals
app/Support/Sandbox/                 sandbox lifecycle
app/Policies/                        Sales vs Admin
database/seeders/DemoSeeder.php      plans history and replays it
resources/js/pages/quotations/show.tsx      the quote-to-cash workspace
resources/js/lib/feature-notes.ts           "About this feature" copy
```
