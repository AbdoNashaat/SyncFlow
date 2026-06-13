# SyncFlow Frontend

Svelte 5 SPA with Tailwind CSS 4 and Vite 8.

## Architecture

```
frontend/
├── index.html              # Entry HTML (loads jQuery CDN)
├── src/
│   ├── main.js             # App bootstrap
│   ├── app.css             # Tailwind imports
│   ├── App.svelte          # Router + auth guard + layout
│   ├── pages/              # Route pages
│   │   ├── Dashboard.svelte
│   │   ├── Login.svelte
│   │   ├── Register.svelte
│   │   ├── ProjectList.svelte
│   │   ├── ProjectBoard.svelte  # Kanban board
│   │   └── NotFound.svelte
│   └── lib/
│       ├── api/            # Axios API wrappers
│       │   ├── client.js   # Axios instance (token interceptor + 401 logout)
│       │   ├── auth.js
│       │   ├── projects.js
│       │   ├── tasks.js
│       │   ├── attachments.js
│       │   ├── comments.js
│       │   └── dashboard.js
│       ├── stores/         # Svelte writable stores
│       │   ├── auth.js     # Auth state (persisted to localStorage)
│       │   └── ui.js       # Toast notifications
│       └── components/
│           ├── auth/
│           │   ├── LoginForm.svelte
│           │   └── RegisterForm.svelte
│           ├── layout/
│           │   └── Sidebar.svelte
│           └── common/
│               ├── Toast.svelte
│               ├── LoadingSpinner.svelte
│               ├── EmptyState.svelte
│               ├── ErrorMessage.svelte
│               ├── Modal.svelte
│               └── Datepicker.svelte  # Legacy jQuery widget
```

## Key Decisions

- **Hash-based routing** via `svelte-spa-router` — no server-side URL handling needed
- **Sanctum token auth** — Bearer token in Axios interceptor, stored in localStorage
- **Tailwind CSS 4** via `@tailwindcss/vite` plugin (no config file needed)
- **Legacy widget showcase** — jQuery-UI datepicker loaded from CDN, wrapped in Svelte component with native `<input type="date">` fallback

## How to Run

### Prerequisites

- Node.js 18+
- Backend server running (see [backend.md](./backend.md))

### Setup

```bash
cd frontend
npm install
```

### Development

```bash
cd frontend
npx vite
```

Opens at `http://localhost:5173`. API proxy forwards `/api/*` to the Laravel backend at `http://localhost:8001`.

### Production Build

```bash
cd frontend
npx vite build
```

Output goes to `frontend/dist/`. Serve with any static file server. The SPA uses hash routing so no server-side fallback config is needed.

### Full Stack

```bash
# Terminal 1 — Backend
php artisan serve --port=8001

# Terminal 2 — Frontend
cd frontend && npx vite
```

Visit `http://localhost:5173`, register an account, then create projects and tasks.

## Route Map

| Path | Page | Auth Required |
|------|------|--------------|
| `#/` | Dashboard | Yes |
| `#/login` | Login | No (redirects to home if logged in) |
| `#/register` | Register | No (redirects to home if logged in) |
| `#/projects` | Project List | Yes |
| `#/projects/:id` | Project Board (Kanban) | Yes |
| `*` | 404 | N/A |

## Components

### Common
- **Modal** — `show` prop + `on:close` event. Sizes: sm/md/lg
- **Toast** — Fixed top-right. Types: success/error/info/warning. Auto-dismissed on click
- **LoadingSpinner** — `size` prop (sm/md/lg)
- **ErrorMessage** — Displays error with retry button
- **EmptyState** — Title, description, optional action button
- **Datepicker** — jQuery-UI datepicker with native `<input type="date">` fallback

### Auth
- **LoginForm** — Email + password → `POST /api/login` → saves token → redirects to `/`
- **RegisterForm** — Name + email + password + confirm → `POST /api/register` → saves token → redirects to `/`
