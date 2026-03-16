# UCC DEMO

Unified Communication Center — Laravel + Vue.js demo with real-time chat, agent monitor, events, and optional LLM (Ollama) and MFA.

---

## Prerequisites

- **Docker** and **Docker Compose**
- **Git**
- (Optional) **Ollama** installed locally if you run the app without Docker and want LLM replies

---

## What is not in the repository (.gitignore)

The following are **not** committed to GitHub. You must create or configure them locally:

| Item | Description |
|------|-------------|
| **`.env`** | Environment variables (copy from `.env.example` and fill in). Contains secrets and local settings. |
| **`docker/nginx/certs/*.crt`** and **`*.key`** | TLS certificates for nginx HTTPS. The repo does **not** include these; you must generate your own (see below). |
| **`/vendor`** | PHP dependencies (installed by Composer, e.g. inside the container). |
| **`/node_modules`** | Node dependencies (installed by pnpm; in Docker the `node` service does this). |
| **`/docs`** | Documentation folder (if present, kept local). |
| **`/public/build`**, **`/public/hot`**, **`/public/storage`** | Build artifacts and symlinks. |
| **`/storage/*.key`**, **`/storage/pail`** | Laravel storage keys and pail data. |
| **IDE/project config** | `.idea`, `.vscode`, `.fleet`, `.zed`, etc. |

---

## Running the project locally (step-by-step)

Follow these steps in order.

### 1. Clone the repository

```bash
git clone <repository-url>
cd UCC-DEMO
```

### 2. Create your environment file

```bash
cp .env.example .env
```

Edit `.env` and set at least:

- **`APP_KEY`** — leave empty for now; you will generate it after the first `docker compose up` (see step 6).
- For **Docker**, use MySQL and the backend/API behind nginx HTTPS (see step 4).

### 3. Generate TLS certificates for nginx (required for Docker)

The nginx config in `docker/nginx/nginx-site.conf` is set up for **HTTPS**. Certificates are **not** in the repo (see `.gitignore`). You must create your own and place them in `docker/nginx/certs/`.

Create the directory if it does not exist:

```bash
mkdir -p docker/nginx/certs
```

Generate a self-signed certificate for localhost (valid e.g. 365 days):

```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/certs/localhost.key \
  -out docker/nginx/certs/localhost.crt \
  -subj "/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"
```

The filenames must be **`localhost.crt`** and **`localhost.key`** so they match the paths in `docker/nginx/nginx-site.conf`.

Your browser will show a security warning for the self-signed cert; you can accept it for local development.

### 4. Configure `.env` for Docker

Edit `.env` and set values suitable for the Docker stack:

- **Database (MySQL in Docker):**

  ```env
  DB_CONNECTION=mysql
  DB_HOST=mysql
  DB_PORT=3306
  DB_DATABASE=ucc_demo
  DB_USERNAME=ucc_demo
  DB_PASSWORD=secret
  DB_ROOT_PASSWORD=root_secret
  ```

- **Application URL (HTTPS via nginx):**

  ```env
  APP_URL=https://localhost:8443
  ```

- **Sanctum stateful domains** (so the SPA on port 5173 can use cookie auth when calling the API on 8443):

  ```env
  SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
  ```

- **Reverb (WebSocket)** — backend and frontend must use the same host/port/scheme as the API (nginx proxies Reverb under the same origin):

  ```env
  BROADCAST_CONNECTION=reverb
  REVERB_APP_ID=ucc-demo
  REVERB_APP_KEY=ucc-demo-key
  REVERB_APP_SECRET=ucc-demo-secret
  REVERB_HOST=localhost
  REVERB_PORT=8443
  REVERB_SCHEME=https
  VITE_REVERB_APP_KEY=ucc-demo-key
  VITE_REVERB_HOST=localhost
  VITE_REVERB_PORT=8443
  VITE_REVERB_SCHEME=https
  ```

- **Ollama** is overridden in `docker-compose.local.yml` to `http://ollama:11434/api/generate` for the backend; the `node` service and frontend use the host. No change needed in `.env` for Ollama if you only run via Docker.

### 5. Build and start the stack

From the project root:

```bash
docker compose -f docker-compose.local.yml build
docker compose -f docker-compose.local.yml up -d
```

This starts:

- **nginx** — reverse proxy (HTTP 8080 → redirect to HTTPS 8443; HTTPS on 8443), using `docker/nginx/certs`.
- **backend** — PHP-FPM (Laravel); entrypoint creates `storage`/`bootstrap/cache` dirs and runs `composer install` if needed.
- **node** — pnpm install + Vite dev server on port **5173**.
- **reverb** — WebSocket server (proxied by nginx at `/app`).
- **queue-worker-mail**, **queue-worker-bot-messages-1/2/3** — Laravel queue workers.
- **scheduler** — `php artisan schedule:work`.
- **mysql** — MySQL 8.4 with encryption (keyring); healthcheck before backend starts.
- **ollama** — Ollama for LLM (optional for bot replies).
- **mailpit** — Local mail UI (SMTP 1025, Web UI 8025).

### 6. Generate app key and run migrations

If `APP_KEY` in `.env` is empty:

```bash
docker compose -f docker-compose.local.yml exec backend php artisan key:generate
```

Run migrations:

```bash
docker compose -f docker-compose.local.yml exec backend php artisan migrate
```

(Optional) Seed or create an admin user as needed for your setup.

### 7. Access the application

- **Frontend (Vue SPA):** [http://localhost:5173](http://localhost:5173) (Vite dev server).
- **Backend API (HTTPS):** [https://localhost:8443](https://localhost:8443) (accept the self-signed cert in the browser if prompted).
- **Mailpit (outgoing mail):** [http://localhost:8025](http://localhost:8025).

The SPA on port 5173 will send API and Reverb requests to `https://localhost:8443` when configured as above (via `APP_URL`, `SANCTUM_STATEFUL_DOMAINS`, and the Reverb `VITE_*` variables).

---

## Stopping and cleaning up

```bash
docker compose -f docker-compose.local.yml down
```

To remove volumes as well (database and Ollama data will be lost):

```bash
docker compose -f docker-compose.local.yml down -v
```

---

## Docker overview

| Service | Role | Ports (host) |
|---------|------|--------------|
| nginx | Reverse proxy, TLS termination, Reverb proxy | 8080 (HTTP), 8443 (HTTPS) |
| backend | Laravel (PHP-FPM) | — |
| node | pnpm + Vite dev server | 5173 |
| reverb | Laravel Reverb WebSocket | Proxied via nginx `/app` |
| mysql | MySQL 8.4 (encryption enabled) | 3308 → 3306 |
| ollama | LLM for bot replies | 11434 |
| mailpit | Local mail catcher | 1025 (SMTP), 8025 (Web UI) |
| queue-worker-* | Laravel queue workers | — |
| scheduler | Laravel schedule:work | — |

Config and scripts:

- **docker/nginx/nginx-site.conf** — nginx server block (HTTPS, PHP, Reverb proxy); expects certs in `docker/nginx/certs/`.
- **docker/php/Dockerfile** — PHP 8.3-FPM image; **docker/php/entrypoint.sh** — creates dirs, `composer install` if needed, then starts FPM.
- **docker/mysql/** — MySQL image with keyring and encryption config; **docker/mysql/conf.d/my.cnf** and **docker/mysql/keyring-files/** are mounted read-only.

---

## License

Laravel and other dependencies have their own licenses. This project is open-sourced as indicated in the repository.
