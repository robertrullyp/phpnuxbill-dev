# Repository Guidelines

## Project Structure & Module Organization
- `index.php`, `init.php`, `config.php`: entry/bootstrap and configuration.
- `system/`: core app (controllers, vendor libs, cache, plugins, widgets).
  - `system/controllers/`: route handlers (e.g., `customers.php`, `invoices.php`).
  - `system/vendor/`: Composer dependencies (defined in `system/composer.json`).
  - `system/cache/`, `ui/compiled/`: runtime caches; safe to clear.
- `ui/`: Smarty templates and assets (`ui/themes/`, `ui/ui_custom/`).
- `admin/`, `pages/`, `pages_template/`, `qrcode/`, `scan/`: feature modules and assets.
- `docs/`, `.github/`: documentation and workflows.

## Build, Test, and Development Commands
- Install deps: `cd system && composer install`
- Rebuild autoload: `cd system && composer dump-autoload -o`
- Run locally (PHP built‑in): `php -S localhost:8080 -t .` (serve repo root)
- Docker (example): `docker build -t phpnuxbill .` then `docker compose -f docker-compose.example.yml up`
- Lint PHP quickly: `php -l path/to/file.php`

## Coding Style & Naming Conventions
- PHP: PSR‑12, 4‑space indentation, UTF‑8.
- Filenames in `system/controllers/`: lowercase with underscores (e.g., `paymentgateway.php`).
- Templates: keep logic minimal; place PHP in controllers, presentation in `ui/themes/`.
- Configuration: do not hardcode secrets; use `config.php` (copy from `config.sample.php`).

## Testing Guidelines
- No formal PHPUnit suite in repo; prioritize manual smoke tests:
  - Auth (login/logout), customer CRUD, invoice creation, voucher/plan flows.
  - Router and payment callbacks (use sandbox/test mode).
- Clear caches when tweaking views: delete `ui/compiled/*` and `system/cache/*`.
- Prefer small, testable controller functions; add guards and input validation.

## Commit & Pull Request Guidelines
- Use Conventional Commits: `feat:`, `fix:`, `chore:`, `docs:`, `refactor:`.
- Subject ≤ 72 chars; body explains why, steps to reproduce, and risks.
- PRs: describe changes, link issues, include screenshots for UI, note DB/config impacts, and test steps.

## Security & Configuration Tips
- Copy `config.sample.php` to `config.php` and set DB/keys before running.
- Never commit secrets or private keys. Review `.gitignore` before adding files.
- Keep `.htaccess_firewall` enabled when deploying; restrict `system/` write access to caches only.
