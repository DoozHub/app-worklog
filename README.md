# Dooz Worklog

Time tracking and work logging for teams: clients, projects, tasks, time logs,
reports and invoices. A Laravel package loaded by the Dooz host application
(`Dooz\Worklog\WorklogServiceProvider`).

## Authentication

- **API** (`/api/worklog/*`): every route requires an authenticated user through
  the host's `auth:api` guard (Laravel Passport). Send
  `Authorization: Bearer <access token>`; requests without a valid token get
  `401`. The only public route is `GET /api/worklog/health`, a liveness probe
  that returns a constant payload and reads no data. The host adds its app
  licence gate (`license:dooz-worklog`) on top.
- **Web** (`/worklog/*`): the `web` and `auth` (session) middleware.

## Checks

`php tests/check-api-auth.php` asserts that every API route carries `auth:api`
(except the health probe) and that no debug `test-*` route is registered. It
needs an autoloader with `illuminate/routing`; inside the host it uses the
host's `vendor/autoload.php`, otherwise pass `--autoload=<path>`.
