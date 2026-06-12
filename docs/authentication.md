# SyncFlow — Authentication (Sanctum)

## Overview

Token-based API auth using Laravel Sanctum. Tokens passed via `Authorization: Bearer` header. Svelte SPA stores token in localStorage.

---

## Implementation Steps

### 1. Install Sanctum

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 2. Add HasApiTokens to User Model

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

### 3. Configure API Routes

File: `routes/api.php` — 21 routes total.

| Method | Path | Controller | Auth |
|--------|------|------------|------|
| POST | `/api/register` | AuthController@register | Public |
| POST | `/api/login` | AuthController@login | Public |
| POST | `/api/logout` | AuthController@logout | Sanctum |
| GET | `/api/user` | AuthController@user | Sanctum |

Remaining routes (projects, tasks, attachments, comments, dashboard) all under `auth:sanctum` middleware.

### 4. Bootstrap API Routing

File: `bootstrap/app.php`

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',     // <-- added
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

### 5. Base Controller

File: `app/Http/Controllers/Controller.php`

Added `AuthorizesRequests` and `ValidatesRequests` traits so all API controllers can use `$this->authorize()` and `$this->validate()`.

```php
abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;
}
```

---

## Endpoints

### `POST /api/register`

- **Request:** `{ name, email, password, password_confirmation }`
- **Validation:** name required string max:255, email required unique:users, password required min:8 confirmed
- **Response 201:** `{ user: { id, name, email, avatar_url, timezone, created_at }, token: "1|abc..." }`
- **Errors 422:** `{ message: "Validation failed", errors: { email: ["This email is already registered."] } }`

### `POST /api/login`

- **Request:** `{ email, password }`
- **Response 200:** `{ user: {...}, token: "1|abc..." }`
- **Response 401:** `{ message: "Invalid credentials" }`

### `POST /api/logout`

- **Headers:** `Authorization: Bearer <token>`
- **Response 200:** `{ message: "Logged out" }`
- Revokes current token via `$request->user()->currentAccessToken()->delete()`

### `GET /api/user`

- **Headers:** `Authorization: Bearer <token>`
- **Response 200:** `{ data: { id, name, email, avatar_url, timezone, created_at } }`

---

## Files Created

```
app/Http/
├── Controllers/
│   └── Api/
│       └── AuthController.php
├── Requests/
│   ├── LoginRequest.php
│   └── RegisterRequest.php
└── Resources/
    └── UserResource.php
```

### AuthController

```php
class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([...]);
        $token = $user->createToken('auth-token')->plainTextToken;
        return response()->json(['user' => new UserResource($user), 'token' => $token], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $token = $user->createToken('auth-token')->plainTextToken;
        return response()->json(['user' => new UserResource($user), 'token' => $token]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }
}
```

---

## Tests: AuthTest (7 tests, all passing)

| Test | What it verifies |
|------|------------------|
| `test_user_can_register` | 201 status, JSON structure, DB has user |
| `test_user_cannot_register_with_existing_email` | 422 validation error on duplicate email |
| `test_user_can_login` | 200, user + token in response |
| `test_user_cannot_login_with_invalid_credentials` | 401 with error message |
| `test_user_can_logout` | 200, token deleted from DB |
| `test_can_get_authenticated_user` | 200, correct user data returned |
| `test_unauthenticated_user_cannot_access_protected_routes` | 401 on protected endpoint |

All pass: `✓ 7 tests, 24 assertions`
