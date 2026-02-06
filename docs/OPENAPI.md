# OpenAPI / Swagger (Internal)

Repo ini sudah punya dokumentasi endpoint di `docs/api.md`. Untuk kebutuhan standardisasi dan tooling (Swagger UI, codegen), spesifikasi OpenAPI disediakan di:

- `docs/openapi.yaml`
- `docs/openapi.json`

## Cara Pakai

### Base URL

API legacy memakai:

- `GET/POST /system/api.php?r=<route>`

Agar lebih kompatibel dengan OpenAPI, API juga mendukung gaya PATH_INFO:

- `GET/POST /system/api.php/<route>`

Contoh yang ekuivalen:

- Legacy: `/system/api.php?r=whoami/permissions`
- PATH_INFO: `/system/api.php/whoami/permissions`

Catatan server:

- Jika memakai Apache + rewrite ke `index.php`, pastikan request `^system/api.php(/.*)?$` tidak ikut di-rewrite.
  - Di repo ini sudah ditambahkan rule di `.htaccess`: `RewriteRule ^system/api\.php(/.*)?$ - [L]`

### Auth yang Direkomendasikan

Untuk akses admin, gunakan **Admin API key** (per admin) via header:

- `X-Admin-Api-Key: <ADMIN_API_KEY>`

Kompatibilitas (tetap diterima oleh sistem):

- `X-API-Key: <ADMIN_API_KEY>`
- `Authorization: Bearer <ADMIN_API_KEY>`

Untuk token login (admin/customer), selain query `token=...`, sekarang juga bisa lewat header:

- `X-Auth-Token: a.<...>` atau `X-Auth-Token: c.<...>`

Catatan: token via header ini dibuat untuk menghindari token bocor di URL/log.

## Lihat di Swagger UI (opsional)

Jalankan server lokal dari root repo:

```bash
php -S localhost:8080 -t .
```

Lalu buka:

- `/docs/swagger-ui.html`

## Regenerate Spec

Jika `docs/api.md` berubah, regenerate OpenAPI:

```bash
python3 scripts/generate_openapi.py
```
