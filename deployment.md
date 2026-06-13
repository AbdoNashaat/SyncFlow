# SyncFlow Deployment Guide

Full-stack deployment — Laravel 11 backend + Svelte 5 frontend.

## Prerequisites

- PHP 8.2+
- Composer 2
- Node.js 18+
- SQLite (dev) or MySQL 8+ (production)
- Git

---

## Local Development

### 1. Clone and prepare

```bash
git clone <repo-url> SyncFlow
cd SyncFlow
```

### 2. Backend setup

```bash
# Install PHP dependencies
composer install

# Environment
cp .env.example .env
php artisan key:generate

# Database (SQLite for dev)
touch database/database.sqlite
# Edit .env: DB_CONNECTION=sqlite, DB_DATABASE=/absolute/path/database/database.sqlite

# Migrate + seed
php artisan migrate --seed

# Storage link (for file uploads)
php artisan storage:link
```

### 3. Frontend setup

```bash
cd frontend
npm install
cd ..
```

### 4. Run both servers

**Terminal 1 — Backend API:**
```bash
php artisan serve --port=8001
```

**Terminal 2 — Frontend dev server:**
```bash
cd frontend && npx vite
```

Open `http://localhost:5173`. Register an account, then create projects and tasks.

---

## Production Deployment

### Option A: Separate servers

#### Backend (Laravel)

```bash
# Production environment
composer install --optimize-autoloader --no-dev

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrate
php artisan migrate --force

# Queue (if using async jobs)
php artisan queue:work --daemon &
```

Serve via Nginx + PHP-FPM:

```nginx
server {
    listen 80;
    server_name api.syncflow.example.com;
    root /var/www/SyncFlow/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Frontend (Svelte SPA)

```bash
cd frontend
npm install
npx vite build
```

Serve the `frontend/dist/` directory with any static server:

```nginx
server {
    listen 80;
    server_name app.syncflow.example.com;
    root /var/www/SyncFlow/frontend/dist;

    # Hash routing — serve index.html for all SPA routes
    # (not needed since we use hash routing, but good practice)
    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

**Update proxy target** in `frontend/vite.config.js` for production:

```js
// Before building for production, set the API URL in .env or directly:
VITE_API_URL=https://api.syncflow.example.com
```

### Option B: Serve frontend from Laravel's public directory

```bash
cd frontend
npx vite build
cp -r dist/* ../public/
```

Then configure Laravel to serve the SPA. The root Vite config (`package.json` root) handles this automatically via `laravel-vite-plugin`.

---

## Environment Variables

### Backend (.env)

```
APP_URL=https://app.syncflow.example.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=syncflow
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public   # or s3 for production
```

### Frontend

Frontend API URL is configured via Vite proxy in `vite.config.js`. For production, set `VITE_API_URL` or adjust the proxy target before building.

---

## Database

### SQLite (dev)

```bash
touch database/database.sqlite
# .env: DB_CONNECTION=sqlite, DB_DATABASE=/full/path/database.sqlite
```

### MySQL (production)

```bash
mysql -u root -p
CREATE DATABASE syncflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=syncflow
DB_USERNAME=root
DB_PASSWORD=your_password
```

Migrate:

```bash
php artisan migrate --force
```

---

## File Storage

### Local (dev)

```bash
php artisan storage:link
```

Files stored in `storage/app/public/`, accessible at `/storage/*`.

### S3 (production)

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
AWS_URL=https://your-bucket.s3.amazonaws.com
```

Requires `league/flysystem-aws-s3-v3` (included via `composer.json`).

---

## Testing

```bash
# Backend tests (65 tests)
php artisan test

# Frontend build check
cd frontend && npx vite build
```

---

## Deployment Walkthrough (Fresh Server)

```bash
# 1. System dependencies
sudo apt update
sudo apt install -y git nginx php8.2 php8.2-fpm php8.2-mysql php8.2-sqlite \
  php8.2-xml php8.2-mbstring php8.2-curl php8.2-gd php8.2-zip composer nodejs npm

# 2. Clone project
git clone <repo-url> /var/www/SyncFlow

# 3. Backend
cd /var/www/SyncFlow
composer install --optimize-autoloader --no-dev
cp .env.example .env
# Edit .env with your DB credentials
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan storage:link

# 4. Frontend
cd frontend
npm install
npx vite build

# 5. Permissions
sudo chown -R www-data:www-data /var/www/SyncFlow/storage
sudo chown -R www-data:www-data /var/www/SyncFlow/bootstrap/cache

# 6. Nginx config (see above)
sudo nano /etc/nginx/sites-available/syncflow
sudo ln -s /etc/nginx/sites-available/syncflow /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# 7. Serve frontend from Laravel public directory
cp -r frontend/dist/* public/
```

---

## Quick Reference

| Action | Command |
|--------|---------|
| Start backend | `php artisan serve --port=8001` |
| Start frontend | `cd frontend && npx vite` |
| Build frontend | `cd frontend && npx vite build` |
| Run tests | `php artisan test` |
| Clear cache | `php artisan optimize:clear` |
| Fresh migrate | `php artisan migrate:fresh --seed` |
