# Dokumentasi API PHPNuxBill (internal)

Dokumen ini merangkum endpoint API yang tersedia berdasarkan isi `system/api.php` dan seluruh `system/controllers/*.php`.
API ini bukan REST murni; ia memanggil controller yang sama dengan UI web, tetapi mengembalikan JSON (melalui `showResult`) ketika berjalan dalam mode API.

## Contoh Lengkap (Per Endpoint)

Untuk contoh request yang lengkap per endpoint (termasuk contoh beberapa cara auth: API key vs token), lihat:

- `docs/API_EXAMPLES.md` (generated dari `docs/openapi.json`)

Regenerate:

```bash
python3 scripts/generate_openapi.py
python3 scripts/generate_api_examples.py
```

## Base URL & Pola Route

- Base URL: `https://<domain>/system/api.php`
- Parameter route: `r`
- Pola: `r=<controller>[/<action>[/<param2>[/<param3>...]]]]`
  - Contoh: `r=customers/view/123/activation`

Alternatif (lebih ramah OpenAPI/Swagger): API juga mendukung gaya PATH_INFO:

- Pola: `/system/api.php/<controller>[/<action>[/<param2>[/<param3>...]]]]`
  - Contoh: `/system/api.php/customers/view/123/activation`

### Format Response JSON
Semua response berhasil atau gagal berbentuk:

```json
{
  "success": true,
  "message": "...",
  "result": { ... },
  "meta": { ... }
}
```

## Autentikasi

API memakai parameter `token` (bisa via query string atau POST). Untuk menghindari token bocor di URL/log, token juga bisa dikirim via header:

- `X-Token: <token>` (disarankan)
- `X-Auth-Token: <token>` (alias legacy)

Logika validasi token ada di `system/api.php`:

- Admin token: `a.<aid>.<time>.<sha1>`
- Customer token: `c.<uid>.<time>.<sha1>`
- `sha1` dihitung dari `sha1("<id>.<time>.<api_secret>")`.
- `api_secret` default = `db_pass` (lihat `init.php`).
- Token dianggap expired bila `time != 0` dan umur > 7,776,000 detik (~90 hari).

### Autentikasi via API key global (config)

Jika `token` sama dengan `config.api_key`, API akan otomatis memakai akun **SuperAdmin** (atau fallback **Admin** pertama) sebagai konteks.
Ini cocok untuk integrasi server‑to‑server tanpa perlu token yang berumur.

### Autentikasi via API key admin (header)

Selain `token`, API menerima **API key admin** via header berikut:
- `X-Admin-Api-Key: <key>`
- `X-API-Key: <key>`
- `Authorization: Bearer <key>`

Catatan:
- Header `Authorization` kadang tidak diteruskan ke PHP (tergantung setup Apache/Nginx/PHP-FPM/proxy). Jika `Authorization: Bearer ...` tidak bekerja, gunakan `X-Admin-Api-Key` atau konfigurasi server agar `HTTP_AUTHORIZATION` diteruskan ke PHP.
- API key bersifat **per-admin**. Saat disimpan, key akan di-hash (HMAC-SHA256) dan hanya `last4` yang disimpan. Key tidak bisa dibaca kembali.
- Jika dihapus/di-rotate, key lama langsung tidak berlaku.
- Secret hashing untuk admin API key memakai `$admin_api_key_secret` (lihat `init.php`). Disarankan set `$admin_api_key_secret` di `config.php` ke nilai yang stabil agar perubahan `db_pass` tidak memutus semua admin API key.
- API key mengidentifikasi **admin user tertentu** dan role/permission mengikuti `tbl_users.user_type` (RBAC). Jadi API key role `Agent`/`Sales`/`Report` akan otomatis dibatasi seperti akses UI mereka.

### Cara mendapatkan token

- Admin login: `POST r=admin/post` (lihat bagian `admin` di bawah). Response berisi `result.token` seperti `a.<aid>.<time>.<sha1>`.
- Customer login: `POST r=login/post`. Response berisi `result.token` seperti `c.<uid>.<time>.<sha1>`.

### Endpoint khusus di level API

Endpoint berikut di-handle langsung oleh `system/api.php` (bukan controller di `system/controllers/`).

#### `whoami`
- Route: `r=whoami`
- Method: GET
- Auth: admin API key atau token (admin/customer)
Example request:
```bash
# Admin via API key
curl -s -H "X-Admin-Api-Key: <ADMIN_API_KEY>" "https://<domain>/system/api.php?r=whoami"

# Customer via token header (lebih aman dari query token)
curl -s -H "X-Token: c.<uid>.<time>.<sha1>" "https://<domain>/system/api.php?r=whoami"
```

#### `whoami/permissions`
- Route: `r=whoami/permissions`
- Method: GET
- Auth: admin API key atau token (admin/customer)
Example request:
```bash
curl -s -H "X-Admin-Api-Key: <ADMIN_API_KEY>" "https://<domain>/system/api.php?r=whoami/permissions"
```

#### `isValid`
- Route: `r=isValid`
- Method: GET
- Auth: token (admin/customer)
Example request:
```bash
# Token via header
curl -s -H "X-Token: a.<aid>.<time>.<sha1>" "https://<domain>/system/api.php?r=isValid"

# Legacy token via query
curl -s "https://<domain>/system/api.php?r=isValid&token=a.<aid>.<time>.<sha1>"
```

#### `me`
- Route: `r=me`
- Method: GET
- Auth: token admin (`a.<...>`)
Example request:
```bash
curl -s -H "X-Token: a.<aid>.<time>.<sha1>" "https://<domain>/system/api.php?r=me"
```

## Contoh penggunaan (curl)

Login admin (ambil token):

```bash
curl -s -X POST "https://<domain>/system/api.php?r=admin/post"   -d "username=admin"   -d "password=admin"
```

Pakai token untuk akses endpoint admin:

```bash
curl -s "https://<domain>/system/api.php?r=dashboard&token=a.<aid>.<time>.<sha1>"
```

Pakai API key admin (tanpa token):

```bash
curl -s -H "X-Admin-Api-Key: <key>" "https://<domain>/system/api.php?r=dashboard"
```

Contoh cek identitas + permission adaptif:

```bash
curl -s -H "X-Admin-Api-Key: <key>" "https://<domain>/system/api.php?r=whoami/permissions"
```

Contoh akses data customer (list + pencarian):

```bash
curl -s "https://<domain>/system/api.php?r=customers&token=a.<aid>.<time>.<sha1>&search=andi"
```

## Catatan Penting

- CSRF: pengecekan CSRF dinonaktifkan saat `isApi=true` (lihat `Csrf::check`). Jadi endpoint POST via API tidak butuh `csrf_token`.
- Redaksi data sensitif: response API akan menghapus field sensitif (mis. `password`, `secret`, `api_key`, `admin_api_key_hash`, `login_token`) dan membatasi payload `_admin`/`_user` ke field yang aman. Jangan bergantung pada field sensitif muncul di JSON API.
- Output non-JSON: beberapa controller mengeluarkan CSV/PDF/HTML secara langsung. Untuk aksi seperti export PDF, response akan berupa file (`content-type: application/pdf`) dan **bukan** JSON.
- Metode HTTP: banyak aksi yang memaksa POST (lihat detail per action di bawah). Jika salah metode, response gagal.

## Rate Limiting

Secara default API menerapkan rate limit berbasis identitas:
- Kunci limit: admin id / customer id; jika tidak ada, fallback IP.
- Default: 120 request per 60 detik.
- Konfigurasi di `config.php`: `api_rate_limit_enabled` (`yes`/`no`), `api_rate_limit_max`, `api_rate_limit_window` (detik).
- Jika limit terlampaui: HTTP 429 + header `Retry-After` + `meta.rate_limit` di body JSON.
- Cache disimpan di `system/cache/api_rate_limit/` dan boleh dibersihkan.

Pengaturan backoff rotasi API key admin:
- `admin_api_key_backoff_enabled` (`yes`/`no`)
- `admin_api_key_backoff_base_delay` (detik)
- `admin_api_key_backoff_max_delay` (detik)
- `admin_api_key_backoff_reset_window` (detik)
- `admin_api_key_attempts_max` (jumlah gagal sebelum backoff)
- `admin_api_key_attempts_window` (detik untuk menghitung gagal)
- `admin_api_key_allowlist` (daftar IP/CIDR, satu per baris)

Backoff juga dipakai untuk menghambat brute force API key (invalid key berulang dari IP yang sama).
IP yang terblokir dapat dibuka kembali lewat Settings -> App -> API Key (daftar Blocked IPs).


## Detail Endpoint per Controller

Catatan: nilai "Default action" hanya terdeteksi jika controller memakai pola `if (empty($action))` atau `if (empty($do))`.

### `accounts` (access: customer)
- Default action: (tidak eksplisit / tergantung controller)

#### `change-password`
- Route: `r=accounts/change-password`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=accounts/change-password&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `change-password-post`
- Route: `r=accounts/change-password-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `cnpass`, `csrf_token`, `npass`, `password`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=accounts/change-password-post&token=c.<uid>.<time>.<sha1>" 
  -d "cnpass=<value>" 
  -d "csrf_token=<value>" 
  -d "npass=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `profile`
- Route: `r=accounts/profile`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=accounts/profile&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-profile-post`
- Route: `r=accounts/edit-profile-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `address`, `csrf_token`, `email`, `faceDetect`, `fullname`, `phonenumber`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=accounts/edit-profile-post&token=c.<uid>.<time>.<sha1>" 
  -d "address=<value>" 
  -d "csrf_token=<value>" 
  -d "email=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `phone-update`
- Route: `r=accounts/phone-update`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=accounts/phone-update&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `phone-update-otp`
- Route: `r=accounts/phone-update-otp`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `phone`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=accounts/phone-update-otp&token=c.<uid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "phone=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `phone-update-post`
- Route: `r=accounts/phone-update-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `otp`, `phone`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=accounts/phone-update-post&token=c.<uid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "otp=<value>" 
  -d "phone=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `email-update`
- Route: `r=accounts/email-update`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=accounts/email-update&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `email-update-otp`
- Route: `r=accounts/email-update-otp`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `email`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=accounts/email-update-otp&token=c.<uid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "email=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `email-update-post`
- Route: `r=accounts/email-update-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `email`, `otp`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=accounts/email-update-post&token=c.<uid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "email=<value>" 
  -d "otp=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `language-update-post`
- Route: `r=accounts/language-update-post`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `lang`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=accounts/language-update-post&token=c.<uid>.<time>.<sha1>&lang=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `admin` (access: public)
- Default action: (tidak eksplisit / tergantung controller)

#### `post`
- Route: `r=admin/post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `cf-turnstile-response`, `csrf_token`, `password`, `username`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=admin/post" 
  -d "cf-turnstile-response=<value>" 
  -d "csrf_token=<value>" 
  -d "password=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `autoload` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `pool`
- Route: `r=autoload/pool`
- Method: GET
- Path params: (tidak ada)
- GET params: `routers`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload/pool&token=a.<aid>.<time>.<sha1>&routers=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `bw_name`
- Route: `r=autoload/bw_name`
- Method: GET
- Path params: routes[2] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload/bw_name/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `balance`
- Route: `r=autoload/balance`
- Method: GET
- Path params: routes[2] => (direct); routes[3] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload/balance/1/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `server`
- Route: `r=autoload/server`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload/server&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `pppoe_service`
- Route: `r=autoload/pppoe_service`
- Method: GET
- Path params: (tidak ada)
- GET params: `routers`, `selected`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload/pppoe_service&token=a.<aid>.<time>.<sha1>&routers=<ROUTER_NAME>&selected=<SERVICE_NAME>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `pppoe_ip_used`
- Route: `r=autoload/pppoe_ip_used`
- Method: GET
- Path params: (tidak ada)
- GET params: `id`, `ip`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload/pppoe_ip_used&token=a.<aid>.<time>.<sha1>&id=<value>&ip=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `pppoe_username_used`
- Route: `r=autoload/pppoe_username_used`
- Method: GET
- Path params: (tidak ada)
- GET params: `id`, `u`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload/pppoe_username_used&token=a.<aid>.<time>.<sha1>&id=<value>&u=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `plan`
- Route: `r=autoload/plan`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `jenis`, `server`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=autoload/plan&token=a.<aid>.<time>.<sha1>" 
  -d "jenis=<value>" 
  -d "server=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `customer_is_active`
- Route: `r=autoload/customer_is_active`
- Method: GET
- Path params: routes[2] => (direct); routes[3] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload/customer_is_active/1/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `plan_is_active`
- Route: `r=autoload/plan_is_active`
- Method: GET
- Path params: routes[2] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload/plan_is_active/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `customer_select2`
- Route: `r=autoload/customer_select2`
- Method: GET
- Path params: (tidak ada)
- GET params: `s`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload/customer_select2&token=a.<aid>.<time>.<sha1>&s=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `autoload_user` (access: customer)
- Default action: (tidak eksplisit / tergantung controller)

#### `isLogin`
- Route: `r=autoload_user/isLogin`
- Method: GET
- Path params: routes[2] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload_user/isLogin/1&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `bw_name`
- Route: `r=autoload_user/bw_name`
- Method: GET
- Path params: routes[2] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload_user/bw_name/1&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `inbox_unread`
- Route: `r=autoload_user/inbox_unread`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload_user/inbox_unread&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `inbox`
- Route: `r=autoload_user/inbox`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload_user/inbox&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `language`
- Route: `r=autoload_user/language`
- Method: GET
- Path params: (tidak ada)
- GET params: `select`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=autoload_user/language&token=c.<uid>.<time>.<sha1>&select=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `bandwidth` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `list`
- Route: `r=bandwidth/list`
- Method: GET/POST (filter `name` via POST)
- Path params: (tidak ada)
- GET params: `p`
- POST params: `csrf_token`, `name`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=bandwidth/list&token=a.<aid>.<time>.<sha1>&p=1" 
  -d "csrf_token=<value>" 
  -d "name=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add`
- Route: `r=bandwidth/add`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=bandwidth/add&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit`
- Route: `r=bandwidth/edit`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=bandwidth/edit/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `delete`
- Route: `r=bandwidth/delete`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=bandwidth/delete/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add-post`
- Route: `r=bandwidth/add-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `name`, `rate_down`, `rate_down_unit`, `rate_up`, `rate_up_unit`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=bandwidth/add-post&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "name=<value>" 
  -d "rate_down=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-post`
- Route: `r=bandwidth/edit-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `id`, `name`, `rate_down`, `rate_down_unit`, `rate_up`, `rate_up_unit`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=bandwidth/edit-post&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "id=<value>" 
  -d "name=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `callback` (access: public)
- Default action: (gateway callback)
- Actions: (tidak ada switch/action eksplisit)

#### `payment-notification`
- Route: `r=callback`
- Method: GET/POST (tergantung gateway; umumnya POST)
- Path params: routes[1] => $gateway
- GET params: (tergantung gateway)
- POST params: (tergantung gateway)
- REQUEST params: (tergantung gateway)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=callback/<gateway>"
```
Example response (non-JSON, tergantung gateway):
```text
<binary/pdf/csv/html output>
```

### `community` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `rollback`
- Route: `r=community/rollback`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=community/rollback&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `coupons` (access: admin)
- Default action: `list`

#### `list` (default)
- Route: `r=coupons` (tanpa action)
- Method: GET/POST (list uses Paginator; search via POST)
- Path params: (tidak ada)
- GET params: `p`
- POST params: `csrf_token`, `search`, `filter`
- REQUEST params: (tidak ada)
Example requests:
```bash
# List (GET, paging)
curl -s "https://<domain>/system/api.php?r=coupons&token=a.<aid>.<time>.<sha1>&p=1"

# Search (POST; p tetap query param karena Paginator membaca dari GET)
curl -s -X POST "https://<domain>/system/api.php?r=coupons&p=1&token=a.<aid>.<time>.<sha1>"   -d "search=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

Catatan:
- UI kadang memakai `r=coupons/list`, tetapi action `list` tidak ada di switch dan akan jatuh ke default list yang sama.

#### `add`
- Route: `r=coupons/add`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=coupons/add&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add-post`
- Route: `r=coupons/add-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `code`, `csrf_token`, `description`, `end_date`, `max_discount_amount`, `max_usage`, `min_order_amount`, `start_date`, `status`, `type`, `value`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=coupons/add-post&token=a.<aid>.<time>.<sha1>" 
  -d "code=<value>" 
  -d "csrf_token=<value>" 
  -d "description=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit`
- Route: `r=coupons/edit`
- Method: POST (enforced)
- Path params: routes[2] => $coupon_id
- GET params: (tidak ada)
- POST params: `csrf_token`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=coupons/edit/1&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-post`
- Route: `r=coupons/edit-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `code`, `csrf_token`, `description`, `end_date`, `max_discount_amount`, `max_usage`, `min_order_amount`, `start_date`, `status`, `type`, `value`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=coupons/edit-post&token=a.<aid>.<time>.<sha1>" 
  -d "code=<value>" 
  -d "csrf_token=<value>" 
  -d "description=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `delete`
- Route: `r=coupons/delete`
- Method: POST (enforced; custom JSON)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `couponIds`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=coupons/delete&token=a.<aid>.<time>.<sha1>"   -d 'couponIds=[1,2,3]'
```
Example response (non-standard JSON):
```json
{"status": "success", "message": "..."}
```

#### `status`
- Route: `r=coupons/status`
- Method: POST (enforced)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `coupon_id`, `csrf_token`, `status`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=coupons/status&token=a.<aid>.<time>.<sha1>" 
  -d "coupon_id=<value>" 
  -d "csrf_token=<value>" 
  -d "status=active"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `csrf-refresh` (access: mixed)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

#### `refresh` (default)
- Route: `r=csrf-refresh`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=csrf-refresh&token=<admin_or_customer_token>"
```
Example response (JSON):
```json
{"success": true, "message": "ok", "result": {"csrf_token": "...", "csrf_token_logout": "..."}, "meta": {}}
```

### `customers` (access: admin)
- Default action: `list`

#### `list` (default)
- Route: `r=customers` (tanpa action)
- Method: GET
- Path params: (tidak ada)
- GET params: `p`, `filter`, `search`, `order`, `orderby`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=customers&token=a.<aid>.<time>.<sha1>&p=1&filter=Active&search=<query>&order=username&orderby=asc"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

Catatan:
- Endpoint ini paginated. Data ada di `result.d`, info paging ada di `result.paginator`.
- Default `per_page` biasanya 30 (tergantung cookie/config `customer_per_page`).

#### `csv`
- Route: `r=customers/csv`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=customers/csv&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>"
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

#### `csv-prepaid`
- Route: `r=customers/csv-prepaid`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=customers/csv-prepaid&token=a.<aid>.<time>.<sha1>"
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

#### `add`
- Route: `r=customers/add`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=customers/add&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

Catatan:
- Endpoint ini untuk menampilkan data form (UI). Untuk membuat customer gunakan `POST r=customers/add-post`.

#### `recharge`
- Route: `r=customers/recharge`
- Method: POST (enforced)
- Path params: routes[2] => $id_customer; routes[3] => $plan_id
- GET params: (tidak ada)
- POST params: `csrf_token`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=customers/recharge/1/1&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `deactivate`
- Route: `r=customers/deactivate`
- Method: POST (enforced)
- Path params: routes[2] => $id_customer; routes[3] => $plan_id
- GET params: (tidak ada)
- POST params: `csrf_token`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=customers/deactivate/1/1&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `sync`
- Route: `r=customers/sync`
- Method: POST (enforced)
- Path params: routes[2] => $id_customer
- GET params: (tidak ada)
- POST params: `csrf_token`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=customers/sync/1&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `login`
- Route: `r=customers/login`
- Method: POST (enforced)
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: `csrf_token`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=customers/login/1&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `viewu`
- Route: `r=customers/viewu`
- Method: GET
- Path params: routes[2] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=customers/viewu/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `view`
- Route: `r=customers/view`
- Method: GET
- Path params: routes[2] => $id; routes[3] => $v
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
- Sub-actions (routes[3]): `order`, `activation`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=customers/view/1/order&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit`
- Route: `r=customers/edit`
- Method: POST (enforced)
- Path params: routes[2] => $id; routes[3] => (direct)
- GET params: (tidak ada)
- POST params: `csrf_token`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=customers/edit/1/1&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `delete`
- Route: `r=customers/delete`
- Method: POST (enforced)
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: `csrf_token`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=customers/delete/1&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add-post`
- Route: `r=customers/add-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `account_type`, `address`, `city`, `coordinates`, `csrf_token`, `district`, `email`, `fullname`, `password`, `phonenumber`, `pppoe_ip`, `pppoe_password`, `pppoe_username`, `service_type`, `state`, `username`, `zip`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=customers/add-post&token=a.<aid>.<time>.<sha1>" 
  -d "account_type=<value>" 
  -d "address=<value>" 
  -d "city=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-post`
- Route: `r=customers/edit-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `account_type`, `address`, `city`, `coordinates`, `csrf_token`, `district`, `email`, `export`, `faceDetect`, `fullname`, `id`, `password`, `phonenumber`, `pppoe_ip`, `pppoe_password`, `pppoe_username`, `service_type`, `state`, `status`, `username`, `zip`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=customers/edit-post&token=a.<aid>.<time>.<sha1>" 
  -d "account_type=<value>" 
  -d "address=<value>" 
  -d "city=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `customfield` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `save`
- Route: `r=customfield/save`
- Method: POST (enforced)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `name[]`, `order[]`, `type[]`, `placeholder[]`, `value[]`, `register[]`, `required[]`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=customfield/save&token=a.<aid>.<time>.<sha1>" 
  -d "name[]=Address" 
  -d "type[]=text"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `dashboard` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

#### `dashboard` (default)
- Route: `r=dashboard`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=dashboard&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "dashboard", "result": { }, "meta": { }}
```

### `default` (access: public)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

#### `default` (default)
- Route: `r=default`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=default"
```
Example response (JSON):
```json
{"success": true, "message": "default", "result": { }, "meta": { }}
```

### `export` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `print-by-date`
- Route: `r=export/print-by-date`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `ed`, `sd`, `te`, `ts`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=export/print-by-date&token=a.<aid>.<time>.<sha1>&ed=<value>&sd=<value>&te=<value>"
# ... lihat daftar parameter di atas
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

#### `pdf-by-date`
- Route: `r=export/pdf-by-date`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `ed`, `sd`, `te`, `ts`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=export/pdf-by-date&token=a.<aid>.<time>.<sha1>&ed=<value>&sd=<value>&te=<value>"
# ... lihat daftar parameter di atas
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

#### `print-by-period`
- Route: `r=export/print-by-period`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `fdate`, `stype`, `tdate`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=export/print-by-period&token=a.<aid>.<time>.<sha1>" 
  -d "fdate=<value>" 
  -d "stype=<value>" 
  -d "tdate=<value>"
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

#### `pdf-by-period`
- Route: `r=export/pdf-by-period`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `fdate`, `stype`, `tdate`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=export/pdf-by-period&token=a.<aid>.<time>.<sha1>" 
  -d "fdate=<value>" 
  -d "stype=<value>" 
  -d "tdate=<value>"
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

### `forgot` (access: public)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

#### `forgot` (default)
- Route: `r=forgot`
- Method: GET/POST (flow via `step`)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `step`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=forgot&step=0"
```
Example response (JSON):
```json
{"success": true, "message": "forgot", "result": { }, "meta": { }}
```

### `home` (access: customer)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

#### `home` (default)
- Route: `r=home`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=home&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "home", "result": { }, "meta": { }}
```

### `invoices` (access: admin)
- Default action: `list`

#### `list`
- Route: `r=invoices/list`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=invoices/list&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `login` (access: public)
- Default action: (tidak eksplisit / tergantung controller)

#### `post`
- Route: `r=login/post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `cf-turnstile-response`, `csrf_token`, `password`, `username`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=login/post" 
  -d "cf-turnstile-response=<value>" 
  -d "csrf_token=<value>" 
  -d "password=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `activation`
- Route: `r=login/activation`
- Method: POST (enforced)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `username`, `voucher`, `voucher_only`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=login/activation" 
  -d "csrf_token=<value>" 
  -d "username=<value>" 
  -d "voucher=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `logout` (access: public)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

#### `logout` (default)
- Route: `r=logout`
- Method: POST (enforced)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=logout"
```
Example response (JSON):
```json
{"success": true, "message": "Logout Successful", "result": {}, "meta": {}}
```

Catatan:
- Saat dipanggil lewat API (`system/api.php`), logout tidak memerlukan CSRF dan bersifat best-effort (stateless).
- Untuk logout UI web (non-API), controller dapat meminta `csrf_token_logout` untuk mitigasi CSRF.

### `logs` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `list-csv`
- Route: `r=logs/list-csv`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=logs/list-csv&token=a.<aid>.<time>.<sha1>"
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

#### `radius-csv`
- Route: `r=logs/radius-csv`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=logs/radius-csv&token=a.<aid>.<time>.<sha1>"
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

#### `message-csv`
- Route: `r=logs/message-csv`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=logs/message-csv&token=a.<aid>.<time>.<sha1>"
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

#### `list`
- Route: `r=logs/list`
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: `p`, `q`
- POST params: `keep`, `q`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=logs/list&token=a.<aid>.<time>.<sha1>&p=1&q=<query>" 
  -d "keep=<value>" 
  -d "q=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `radius`
- Route: `r=logs/radius`
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: `p`, `q`
- POST params: `keep`, `q`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=logs/radius&token=a.<aid>.<time>.<sha1>&p=1&q=<query>" 
  -d "keep=<value>" 
  -d "q=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `message`
- Route: `r=logs/message`
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: `p`, `q`
- POST params: `keep`, `q`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=logs/message&token=a.<aid>.<time>.<sha1>&p=1&q=<query>" 
  -d "keep=<value>" 
  -d "q=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `mail` (access: customer)
- Default action: (tidak eksplisit / tergantung controller)

#### `list` (default)
- Route: `r=mail` (tanpa action)
- Method: GET
- Path params: (tidak ada)
- GET params: `p`, `q`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=mail&token=c.<uid>.<time>.<sha1>&p=0&q=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

Catatan:
- `p` pada endpoint ini 0-based (p=0 halaman pertama).

#### `view`
- Route: `r=mail/view`
- Method: GET
- Path params: routes[2] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=mail/view/1&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `delete`
- Route: `r=mail/delete`
- Method: GET
- Path params: routes[2] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `p`, `q`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=mail/delete/1&token=c.<uid>.<time>.<sha1>&p=<value>&q=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `maps` (access: admin)
- Default action: `customer`

#### `customer`
- Route: `r=maps/customer`
- Method: GET
- Path params: (tidak ada)
- GET params: `p`, `search`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=maps/customer&token=a.<aid>.<time>.<sha1>&p=1&search=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `routers`
- Route: `r=maps/routers`
- Method: GET/POST (filter via POST)
- Path params: (tidak ada)
- GET params: `p`
- POST params: `name`
- REQUEST params: (tidak ada)
Example requests:
```bash
# List (GET, paging)
curl -s "https://<domain>/system/api.php?r=maps/routers&token=a.<aid>.<time>.<sha1>&p=1"

# Filter by name (POST; p tetap query param karena Paginator membaca dari GET)
curl -s -X POST "https://<domain>/system/api.php?r=maps/routers&p=1&token=a.<aid>.<time>.<sha1>"   -d "name=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `message` (access: admin)
- Default action: `send`

#### `send`
- Route: `r=message/send`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=message/send/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `send-post`
- Route: `r=message/send-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `email`, `id_customer`, `inbox`, `message`, `sms`, `subject`, `wa`, `wa_queue`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=message/send-post&token=a.<aid>.<time>.<sha1>" 
  -d "id_customer=<value>" 
  -d "subject=<value>" 
  -d "message=<value>" 
  -d "inbox=1"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `wa_media_upload`
- Route: `r=message/wa_media_upload`
- Method: POST (enforced; multipart/form-data)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `media`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=message/wa_media_upload&token=a.<aid>.<time>.<sha1>" 
  -F "media=@/path/to/file.pdf"
```
Example response (non-standard JSON):
```json
{"ok": true, "media_id": "...", "url": "...", "mime": "application/pdf", "expires_at": "YYYY-mm-dd HH:MM:SS"}
```

#### `resend`
- Route: `r=message/resend`
- Method: GET
- Path params: routes[2] => $logId (direct; optional)
- GET params: `id` (alternative to path param)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=message/resend/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `resend-post`
- Route: `r=message/resend-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `channel`, `log_id`, `message`, `recipient`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=message/resend-post&token=a.<aid>.<time>.<sha1>" 
  -d "log_id=<value>" 
  -d "channel=wa" 
  -d "recipient=<value>" 
  -d "message=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `send_bulk`
- Route: `r=message/send_bulk`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=message/send_bulk&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `send_bulk_ajax`
- Route: `r=message/send_bulk_ajax`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=message/send_bulk_ajax&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `send_bulk_selected`
- Route: `r=message/send_bulk_selected`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=message/send_bulk_selected&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `order` (access: customer)
- Default action: (tidak eksplisit / tergantung controller)

#### `voucher`
- Route: `r=order/voucher`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=order/voucher&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `history`
- Route: `r=order/history`
- Method: GET
- Path params: (tidak ada)
- GET params: `p`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=order/history&token=c.<uid>.<time>.<sha1>&p=1"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `balance`
- Route: `r=order/balance`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=order/balance&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `package`
- Route: `r=order/package`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=order/package&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `unpaid`
- Route: `r=order/unpaid`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=order/unpaid&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `view`
- Route: `r=order/view`
- Method: GET
- Path params: routes[2] => $trxid; routes[3] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=order/view/1/1&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `pay`
- Route: `r=order/pay`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=order/pay&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `send`
- Route: `r=order/send`
- Method: POST (implicit; uses POST params)
- Path params: routes[2] => (direct); routes[3] => (direct)
- GET params: (tidak ada)
- POST params: `csrf_token`, `username`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=order/send/1/1&token=c.<uid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "username=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `gateway`
- Route: `r=order/gateway`
- Method: POST (implicit; uses POST params)
- Path params: routes[3] => (direct)
- GET params: (tidak ada)
- POST params: `amount`, `coupon`, `csrf_token`, `custom`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=order/gateway/1/1&token=c.<uid>.<time>.<sha1>" 
  -d "amount=<value>" 
  -d "coupon=<value>" 
  -d "csrf_token=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `buy`
- Route: `r=order/buy`
- Method: POST (implicit; uses POST params)
- Path params: routes[2] => (direct); routes[3] => (direct)
- GET params: (tidak ada)
- POST params: `amount`, `csrf_token`, `custom`, `discount`, `gateway`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=order/buy/1/1&token=c.<uid>.<time>.<sha1>" 
  -d "amount=<value>" 
  -d "csrf_token=<value>" 
  -d "custom=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `page` (access: customer)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

#### `view`
- Route: `r=page`
- Method: GET
- Path params: routes[1] => $page
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=page/Terms&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "page/Terms", "result": { }, "meta": { }}
```

### `pages` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

#### `page-action`
- Route: `r=pages`
- Method: GET/POST (dynamic action; lihat catatan)
- Path params: routes[1] => $action
- GET params: (tidak ada)
- POST params: `html`, `template_name`, `template_save`
- REQUEST params: (tidak ada)
Example requests:
```bash
# View/edit page (GET)
curl -s "https://<domain>/system/api.php?r=pages/Voucher&token=a.<aid>.<time>.<sha1>"

# Save page (POST) -> action suffix harus "-post"
curl -s -X POST "https://<domain>/system/api.php?r=pages/Voucher-post&token=a.<aid>.<time>.<sha1>"   -d "html=<value>"   -d "template_save=<value>"   -d "template_name=<value>"

# Reset page from template (GET) -> action suffix harus "-reset"
curl -s "https://<domain>/system/api.php?r=pages/Voucher-reset&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "pages/...", "result": { }, "meta": { }}
```

Catatan:
- Controller `pages` menginterpretasi nilai `routes[1]` sebagai nama page file, dengan suffix `-post` dan `-reset` sebagai aksi khusus.
- Aksi `save` dan `reset` bersifat state-changing (unsafe); sebaiknya tidak dipakai untuk automation tanpa guard ekstra.

### `paymentgateway` (access: admin)
- Default action: `list`

Catatan:
- Akses dibatasi ke `SuperAdmin` / `Admin` (role lain akan ditolak).
- Selain endpoint di bawah, controller juga mendukung konfigurasi dinamis per gateway via `r=paymentgateway/<gateway>`:
  - GET: tampilkan config (`<gateway>_show_config`)
  - POST: simpan config (`<gateway>_save_config`)
  - Parameter POST bergantung pada gateway.

#### `list` (default)
- Route: `r=paymentgateway` (tanpa action)
- Method: GET/POST (POST untuk simpan daftar active gateway)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `save`, `pgs[]`
- REQUEST params: (tidak ada)
Example requests:
```bash
# List (GET)
curl -s "https://<domain>/system/api.php?r=paymentgateway&token=a.<aid>.<time>.<sha1>"

# Save active gateways (POST)
curl -s -X POST "https://<domain>/system/api.php?r=paymentgateway&token=a.<aid>.<time>.<sha1>"   -d "save=actives"   -d "pgs[]=xendit"   -d "pgs[]=midtrans"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `gateway-config` (dynamic)
- Route: `r=paymentgateway`
- Method: GET/POST (dynamic gateway handler)
- Path params: routes[1] => $gateway
- GET params: (tidak ada)
- POST params: (tergantung gateway)
- REQUEST params: (tergantung gateway)
Example requests:
```bash
# Show gateway config (GET)
curl -s "https://<domain>/system/api.php?r=paymentgateway/xendit&token=a.<aid>.<time>.<sha1>"

# Save gateway config (POST; fields depend on gateway implementation)
curl -s -X POST "https://<domain>/system/api.php?r=paymentgateway/xendit&token=a.<aid>.<time>.<sha1>" \
  -d "key=<value>" \
  -d "secret=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```
Catatan:
- Action `delete`, `audit`, `auditview` ditangani endpoint khusus (lihat bagian di bawah).
- `gateway` harus sesuai nama file gateway di `system/paymentgateway/<gateway>.php`.

#### `delete`
- Route: `r=paymentgateway/delete`
- Method: GET
- Path params: routes[2] => $pg
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=paymentgateway/delete/xendit&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `audit`
- Route: `r=paymentgateway/audit`
- Method: GET
- Path params: routes[2] => $pg
- GET params: `p`
- POST params: (tidak ada)
- REQUEST params: `q`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=paymentgateway/audit/xendit&token=a.<aid>.<time>.<sha1>&p=1&q=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `auditview`
- Route: `r=paymentgateway/auditview`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=paymentgateway/auditview/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `plan` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `list` (default)
- Route: `r=plan` (tanpa action)
- Method: GET/POST (filter via query; POST di UI biasanya redirect)
- Path params: (tidak ada)
- GET params: `p`, `search`, `status`, `router`, `plan`
- POST params: `csrf_token`, `search`, `status`, `router`, `plan`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan&token=a.<aid>.<time>.<sha1>&p=1&search=<query>&status=on&router=<ROUTER_NAME>&plan=<PLAN_ID>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `sync`
- Route: `r=plan/sync`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/sync&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `recharge`
- Route: `r=plan/recharge`
- Method: GET
- Path params: routes[2] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/recharge/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `recharge-confirm`
- Route: `r=plan/recharge-confirm`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `id_customer`, `plan`, `server`, `using`, `note` (opsional, max 256 karakter)
- REQUEST params: (tidak ada)
- Catatan: pada mode API, `csrf_token` diabaikan (CSRF bypass saat `isApi=true`).
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/recharge-confirm&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "id_customer=<value>" 
  -d "plan=<value>" 
  -d "server=<value>" 
  -d "using=<value>" 
  -d "note=<optional_note>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `recharge-post`
- Route: `r=plan/recharge-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `id_customer`, `plan`, `server`, `svoucher`, `using`, `note` (opsional, max 256 karakter)
- REQUEST params: (tidak ada)
- Catatan: pada mode API, `csrf_token` diabaikan (CSRF bypass saat `isApi=true`).
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/recharge-post&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "id_customer=<value>" 
  -d "plan=<value>" 
  -d "server=<value>" 
  -d "svoucher=<value>" 
  -d "using=<value>" 
  -d "note=<optional_note>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `refund`
- Route: `r=plan/refund`
- Method: GET
- Path params: routes[2] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/refund/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `refund-confirm`
- Route: `r=plan/refund-confirm`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `id_customer`, `plan`, `server`, `using`, `note` (opsional, max 256 karakter)
- REQUEST params: (tidak ada)
- Catatan: pada mode API, `csrf_token` diabaikan (CSRF bypass saat `isApi=true`).
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/refund-confirm&token=a.<aid>.<time>.<sha1>" \
  -d "csrf_token=<value>" \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `refund-post`
- Route: `r=plan/refund-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `id_customer`, `plan`, `server`, `using`, `note` (opsional, max 256 karakter)
- REQUEST params: (tidak ada)
- Catatan: pada mode API, `csrf_token` diabaikan (CSRF bypass saat `isApi=true`).
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/refund-post&token=a.<aid>.<time>.<sha1>" \
  -d "csrf_token=<value>" \
  -d "id_customer=<value>" \
  -d "plan=<value>" \
  -d "server=<value>" \
  -d "using=<value>" \
  -d "note=<optional_note>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `view`
- Route: `r=plan/view`
- Method: GET
- Path params: routes[2] => $id; routes[3] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/view/1/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `print`
- Route: `r=plan/print`
- Method: POST (implicit; uses POST params)
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: `csrf_token`, `id`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/print/1&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "id=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit`
- Route: `r=plan/edit`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/edit/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `delete`
- Route: `r=plan/delete`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/delete/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-post`
- Route: `r=plan/edit-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `expiration`, `id`, `id_plan`, `recharged_on`, `time`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/edit-post&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "expiration=<value>" 
  -d "id=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `voucher`
- Route: `r=plan/voucher`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `batch_name`, `customer`, `plan`, `router`, `search`, `status`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/voucher&token=a.<aid>.<time>.<sha1>&batch_name=<value>&customer=<value>&plan=<value>"
# ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add-voucher`
- Route: `r=plan/add-voucher`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/add-voucher&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `remove-voucher`
- Route: `r=plan/remove-voucher`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/remove-voucher&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `print-voucher`
- Route: `r=plan/print-voucher`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `batch`, `from_id`, `group`, `limit`, `pagebreak`, `planid`, `selected_datetime`, `vpl`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/print-voucher&token=a.<aid>.<time>.<sha1>" 
  -d "batch=<value>" 
  -d "from_id=<value>" 
  -d "group=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `voucher-post`
- Route: `r=plan/voucher-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `batch_name`, `lengthcode`, `numbervoucher`, `plan`, `prefix`, `print_now`, `server`, `type`, `voucher_format`, `voucher_per_page`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/voucher-post&token=a.<aid>.<time>.<sha1>" 
  -d "batch_name=<value>" 
  -d "lengthcode=<value>" 
  -d "numbervoucher=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `voucher-delete-many`
- Route: `r=plan/voucher-delete-many`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/voucher-delete-many&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `voucher-view`
- Route: `r=plan/voucher-view`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/voucher-view&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `voucher-delete`
- Route: `r=plan/voucher-delete`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/voucher-delete/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `refill`
- Route: `r=plan/refill`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/refill&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `refill-post`
- Route: `r=plan/refill-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `code`, `csrf_token`, `id_customer`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/refill-post&token=a.<aid>.<time>.<sha1>" 
  -d "code=<value>" 
  -d "csrf_token=<value>" 
  -d "id_customer=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `deposit`
- Route: `r=plan/deposit`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/deposit&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `deposit-post`
- Route: `r=plan/deposit-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `amount`, `csrf_token`, `id_customer`, `id_plan`, `note`
- REQUEST params: `svoucher`
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/deposit-post&token=a.<aid>.<time>.<sha1>&svoucher=<value>" 
  -d "amount=<value>" 
  -d "csrf_token=<value>" 
  -d "id_customer=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `extend`
- Route: `r=plan/extend`
- Method: GET (state-changing; unsafe)
- Path params: routes[2] => $id (direct); routes[3] => $days (direct)
- GET params: `svoucher`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plan/extend/1/30&token=a.<aid>.<time>.<sha1>&svoucher=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `plugin` (access: mixed)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

#### `run`
- Route: `r=plugin`
- Method: GET/POST (dynamic; calls a PHP function)
- Path params: routes[1] => $function
- GET params: (tidak ada)
- POST params: (tergantung plugin)
- REQUEST params: (tergantung plugin)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plugin/<function>&token=<admin_or_customer_token>"
```
Example response (JSON / non-JSON tergantung plugin):
```text
<binary/pdf/csv/html output>
```

Catatan:
- Endpoint ini memanggil `call_user_func($function)` jika fungsi tersebut terdaftar (biasanya dari file plugin).
- Setiap fungsi plugin harus mengimplementasikan validasi permission sendiri (karena endpoint ini bersifat dinamis).

### `pluginmanager` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `refresh`
- Route: `r=pluginmanager/refresh`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pluginmanager/refresh&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `dlinstall`
- Route: `r=pluginmanager/dlinstall`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `gh_url`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=pluginmanager/dlinstall&token=a.<aid>.<time>.<sha1>" 
  -d "gh_url=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `delete`
- Route: `r=pluginmanager/delete`
- Method: GET
- Path params: routes[2] => $tipe; routes[3] => $plugin
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pluginmanager/delete/1/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `install`
- Route: `r=pluginmanager/install`
- Method: GET
- Path params: routes[2] => $tipe; routes[3] => $plugin
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pluginmanager/install/1/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `pool` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `list`
- Route: `r=pool/list`
- Method: GET/POST (filter `name` via POST)
- Path params: (tidak ada)
- GET params: `p`
- POST params: `name`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=pool/list&token=a.<aid>.<time>.<sha1>&p=1" 
  -d "name=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add`
- Route: `r=pool/add`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pool/add&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit`
- Route: `r=pool/edit`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pool/edit/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `delete`
- Route: `r=pool/delete`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pool/delete/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `sync`
- Route: `r=pool/sync`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pool/sync&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add-post`
- Route: `r=pool/add-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `ip_address`, `local_ip`, `name`, `routers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=pool/add-post&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "ip_address=<value>" 
  -d "local_ip=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-post`
- Route: `r=pool/edit-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `id`, `ip_address`, `local_ip`, `routers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=pool/edit-post&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "id=<value>" 
  -d "ip_address=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `port`
- Route: `r=pool/port`
- Method: GET/POST (filter `name` via POST)
- Path params: (tidak ada)
- GET params: `p`
- POST params: `name`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=pool/port&token=a.<aid>.<time>.<sha1>&p=1" 
  -d "name=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add-port`
- Route: `r=pool/add-port`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pool/add-port&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-port`
- Route: `r=pool/edit-port`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pool/edit-port/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `delete-port`
- Route: `r=pool/delete-port`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pool/delete-port/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `sync`
- Route: `r=pool/sync`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pool/sync&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add-port-post`
- Route: `r=pool/add-port-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `name`, `port_range`, `public_ip`, `routers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=pool/add-port-post&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "name=<value>" 
  -d "port_range=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-port-post`
- Route: `r=pool/edit-port-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `id`, `name`, `public_ip`, `range_port`, `routers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=pool/edit-port-post&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "id=<value>" 
  -d "name=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `radius` (access: admin)
- Default action: `list`

Catatan:
- Module Radius memerlukan koneksi DB `radius` + tabel terkait. Jika belum dikonfigurasi, endpoint akan mengembalikan error `Radius database is not configured`.

#### `list` (default)
- Route: `r=radius` (tanpa action)
- Method: GET/POST (filter via POST)
- Path params: (tidak ada)
- GET params: `p`
- POST params: `csrf_token`, `name`
- REQUEST params: (tidak ada)
Example requests:
```bash
# List (GET, paging)
curl -s "https://<domain>/system/api.php?r=radius&token=a.<aid>.<time>.<sha1>&p=1"

# Filter by name (POST; p tetap query param karena Paginator membaca dari GET)
curl -s -X POST "https://<domain>/system/api.php?r=radius&p=1&token=a.<aid>.<time>.<sha1>"   -d "name=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `nas-add`
- Route: `r=radius/nas-add`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=radius/nas-add&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `nas-add-post`
- Route: `r=radius/nas-add-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `community`, `csrf_token`, `description`, `nasname`, `ports`, `routers`, `secret`, `server`, `shortname`, `type`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=radius/nas-add-post&token=a.<aid>.<time>.<sha1>" 
  -d "community=<value>" 
  -d "csrf_token=<value>" 
  -d "description=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `nas-edit`
- Route: `r=radius/nas-edit`
- Method: GET
- Path params: routes[2] => $id
- GET params: `name`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=radius/nas-edit/1&token=a.<aid>.<time>.<sha1>&name=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `nas-edit-post`
- Route: `r=radius/nas-edit-post`
- Method: POST (implicit; uses POST params)
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: `community`, `csrf_token`, `description`, `nasname`, `ports`, `routers`, `secret`, `server`, `shortname`, `type`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=radius/nas-edit-post/1&token=a.<aid>.<time>.<sha1>" 
  -d "community=<value>" 
  -d "csrf_token=<value>" 
  -d "description=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `nas-delete`
- Route: `r=radius/nas-delete`
- Method: POST (enforced)
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: `csrf_token`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=radius/nas-delete/1&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `register` (access: public)
- Default action: (tidak eksplisit / tergantung controller)

#### `post`
- Route: `r=register/post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `address`, `cpassword`, `csrf_token`, `email`, `fullname`, `otp_code`, `password`, `phone_number`, `username`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=register/post" 
  -d "address=<value>" 
  -d "cpassword=<value>" 
  -d "csrf_token=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `reports` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `ajax`
- Route: `r=reports/ajax`
- Method: GET
- Path params: routes[2] => $data
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `ed`, `sd`, `te`, `ts`
- Sub-actions (routes[2]): `type`, `plan`, `method`, `router`, `line`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=reports/ajax/type&token=a.<aid>.<time>.<sha1>&ed=<value>&sd=<value>&te=<value>"
# ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `by-date`
- Route: `r=reports/by-date`
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: `p`, `q`
- POST params: `keep`, `q`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=reports/by-date&token=a.<aid>.<time>.<sha1>&p=1&q=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `activation`
- Route: `r=reports/activation`
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: `p`, `q`
- POST params: `keep`, `q`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=reports/activation&token=a.<aid>.<time>.<sha1>&p=1&q=<query>" 
  -d "keep=<value>" 
  -d "q=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `by-period`
- Route: `r=reports/by-period`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=reports/by-period&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `period-view`
- Route: `r=reports/period-view`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `fdate`, `stype`, `tdate`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=reports/period-view&token=a.<aid>.<time>.<sha1>" 
  -d "fdate=<value>" 
  -d "stype=<value>" 
  -d "tdate=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `daily-report`
- Route: `r=reports/daily-report`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `ed`, `sd`, `te`, `ts`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=reports/daily-report&token=a.<aid>.<time>.<sha1>&ed=<value>&sd=<value>&te=<value>"
# ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `routers` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `list` (default)
- Route: `r=routers` (tanpa action)
- Method: GET/POST (filter `name` via POST)
- Path params: (tidak ada)
- GET params: `p`
- POST params: `name`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=routers&token=a.<aid>.<time>.<sha1>&p=1"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add`
- Route: `r=routers/add`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=routers/add&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit`
- Route: `r=routers/edit`
- Method: GET
- Path params: routes[2] => $id
- GET params: `name`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=routers/edit/1&token=a.<aid>.<time>.<sha1>&name=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `delete`
- Route: `r=routers/delete`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=routers/delete/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add-post`
- Route: `r=routers/add-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `description`, `enabled`, `ip_address`, `name`, `password`, `testIt`, `username`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=routers/add-post&token=a.<aid>.<time>.<sha1>" 
  -d "description=<value>" 
  -d "enabled=<value>" 
  -d "ip_address=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-post`
- Route: `r=routers/edit-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `coordinates`, `coverage`, `description`, `id`, `ip_address`, `name`, `password`, `testIt`, `username`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=routers/edit-post&token=a.<aid>.<time>.<sha1>" 
  -d "coordinates=<value>" 
  -d "coverage=<value>" 
  -d "description=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `search_user` (access: public)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

#### `search`
- Route: `r=search_user`
- Method: GET (raw HTML output)
- Path params: (tidak ada)
- GET params: `query`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=search_user&query=<value>"
```
Example response (non-JSON, HTML):
```text
<binary/pdf/csv/html output>
```

### `services` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `sync`
- Route: `r=services/sync`
- Method: GET
- Path params: routes[2] => $target
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
- Sub-actions (routes[2]): `hotspot`, `pppoe`, `vpn`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/sync/hotspot&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `hotspot`
- Route: `r=services/hotspot`
- Method: GET
- Path params: (tidak ada)
- GET params: `p`
- POST params: (tidak ada)
- REQUEST params: `bandwidth`, `device`, `name`, `router`, `status`, `type1`, `type2`, `type3`, `valid`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/hotspot&token=a.<aid>.<time>.<sha1>&p=1&bandwidth=<value>&device=<value>&name=<value>"
# ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add`
- Route: `r=services/add`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/add&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit`
- Route: `r=services/edit`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/edit/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `delete`
- Route: `r=services/delete`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/delete/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `add-post`
- Route: `r=services/add-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `data_limit`, `data_unit`, `device`, `enabled`, `expired_date`, `id_bw`, `invoice_notification`, `limit_type`, `linked_plans`, `name`, `plan_type`, `prepaid`, `price`, `radius`, `reminder_enabled`, `routers`, `sharedusers`, `time_limit`, `time_unit`, `typebp`, `validity`, `validity_unit`, `visibility`, `visible_customers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=services/add-post&token=a.<aid>.<time>.<sha1>" 
  -d "data_limit=<value>" 
  -d "data_unit=<value>" 
  -d "device=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-post`
- Route: `r=services/edit-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `data_limit`, `data_unit`, `device`, `enabled`, `expired_date`, `id`, `id_bw`, `invoice_notification`, `limit_type`, `linked_plans`, `name`, `on_login`, `on_logout`, `plan_expired`, `plan_type`, `prepaid`, `price`, `price_old`, `reminder_enabled`, `routers`, `sharedusers`, `time_limit`, `time_unit`, `typebp`, `validity`, `validity_unit`, `visibility`, `visible_customers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=services/edit-post&token=a.<aid>.<time>.<sha1>" 
  -d "data_limit=<value>" 
  -d "data_unit=<value>" 
  -d "device=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `pppoe`
- Route: `r=services/pppoe`
- Method: GET
- Path params: (tidak ada)
- GET params: `p`
- POST params: (tidak ada)
- REQUEST params: `bandwidth`, `device`, `name`, `router`, `status`, `type1`, `type2`, `type3`, `valid`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/pppoe&token=a.<aid>.<time>.<sha1>&p=1&bandwidth=<value>&device=<value>&name=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `pppoe-add`
- Route: `r=services/pppoe-add`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/pppoe-add&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `pppoe-edit`
- Route: `r=services/pppoe-edit`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/pppoe-edit/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `pppoe-delete`
- Route: `r=services/pppoe-delete`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/pppoe-delete/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `pppoe-add-post`
- Route: `r=services/pppoe-add-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `device`, `enabled`, `expired_date`, `id_bw`, `invoice_notification`, `linked_plans`, `name_plan`, `plan_type`, `pool_name`, `prepaid`, `price`, `radius`, `reminder_enabled`, `routers`, `validity`, `validity_unit`, `visibility`, `visible_customers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=services/pppoe-add-post&token=a.<aid>.<time>.<sha1>" 
  -d "device=<value>" 
  -d "enabled=<value>" 
  -d "expired_date=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-pppoe-post`
- Route: `r=services/edit-pppoe-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `device`, `enabled`, `expired_date`, `id`, `id_bw`, `invoice_notification`, `linked_plans`, `name_plan`, `on_login`, `on_logout`, `plan_expired`, `plan_type`, `pool_name`, `prepaid`, `price`, `price_old`, `reminder_enabled`, `routers`, `validity`, `validity_unit`, `visibility`, `visible_customers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=services/edit-pppoe-post&token=a.<aid>.<time>.<sha1>" 
  -d "device=<value>" 
  -d "enabled=<value>" 
  -d "expired_date=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `balance`
- Route: `r=services/balance`
- Method: GET/POST (filter via POST)
- Path params: (tidak ada)
- GET params: `p`
- POST params: `name`
- REQUEST params: (tidak ada)
Example requests:
```bash
# List (GET, paging)
curl -s "https://<domain>/system/api.php?r=services/balance&token=a.<aid>.<time>.<sha1>&p=1"

# Filter by name (POST; p tetap query param karena Paginator membaca dari GET)
curl -s -X POST "https://<domain>/system/api.php?r=services/balance&p=1&token=a.<aid>.<time>.<sha1>"   -d "name=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `balance-add`
- Route: `r=services/balance-add`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/balance-add&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `balance-edit`
- Route: `r=services/balance-edit`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/balance-edit/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `balance-delete`
- Route: `r=services/balance-delete`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/balance-delete/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `balance-edit-post`
- Route: `r=services/balance-edit-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `enabled`, `id`, `invoice_notification`, `linked_plans`, `name`, `prepaid`, `price`, `price_old`, `reminder_enabled`, `visibility`, `visible_customers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=services/balance-edit-post&token=a.<aid>.<time>.<sha1>" 
  -d "enabled=<value>" 
  -d "id=<value>" 
  -d "name=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `balance-add-post`
- Route: `r=services/balance-add-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `enabled`, `invoice_notification`, `linked_plans`, `name`, `price`, `reminder_enabled`, `visibility`, `visible_customers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=services/balance-add-post&token=a.<aid>.<time>.<sha1>" 
  -d "enabled=<value>" 
  -d "name=<value>" 
  -d "price=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `vpn`
- Route: `r=services/vpn`
- Method: GET
- Path params: (tidak ada)
- GET params: `p`
- POST params: (tidak ada)
- REQUEST params: `bandwidth`, `device`, `name`, `router`, `status`, `type1`, `type2`, `type3`, `valid`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/vpn&token=a.<aid>.<time>.<sha1>&p=1&bandwidth=<value>&device=<value>&name=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `vpn-add`
- Route: `r=services/vpn-add`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/vpn-add&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `vpn-edit`
- Route: `r=services/vpn-edit`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/vpn-edit/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `vpn-delete`
- Route: `r=services/vpn-delete`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/vpn-delete/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `vpn-add-post`
- Route: `r=services/vpn-add-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `device`, `enabled`, `expired_date`, `id_bw`, `invoice_notification`, `linked_plans`, `name_plan`, `plan_type`, `pool_name`, `prepaid`, `price`, `radius`, `reminder_enabled`, `routers`, `validity`, `validity_unit`, `visibility`, `visible_customers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=services/vpn-add-post&token=a.<aid>.<time>.<sha1>" 
  -d "device=<value>" 
  -d "enabled=<value>" 
  -d "expired_date=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `edit-vpn-post`
- Route: `r=services/edit-vpn-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `device`, `enabled`, `expired_date`, `id`, `id_bw`, `invoice_notification`, `linked_plans`, `name_plan`, `on_login`, `on_logout`, `plan_expired`, `plan_type`, `pool_name`, `prepaid`, `price`, `price_old`, `reminder_enabled`, `routers`, `validity`, `validity_unit`, `visibility`, `visible_customers`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=services/edit-vpn-post&token=a.<aid>.<time>.<sha1>" 
  -d "device=<value>" 
  -d "enabled=<value>" 
  -d "expired_date=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `settings` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `docs`
- Route: `r=settings/docs`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/docs&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `devices`
- Route: `r=settings/devices`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/devices&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `app`
- Route: `r=settings/app`
- Method: GET
- Path params: (tidak ada)
- GET params: `testEmail`, `testSms`, `testTg`, `testWa`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/app&token=a.<aid>.<time>.<sha1>&testEmail=<value>&testSms=<value>&testTg=<value>"
# ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `app-post`
- Route: `r=settings/app-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `CompanyName`, `csrf_token`, `custom_tax_rate`, `hide_al`, `hide_aui`, `hide_mrc`, `hide_pg`, `hide_tms`, `hide_uet`, `hide_vs`, `login_Page_template`, `login_page_description`, `login_page_head`, `login_page_type`, `turnstile_admin_enabled`, `turnstile_client_enabled`, `turnstile_secret_key`, `turnstile_site_key`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/app-post&token=a.<aid>.<time>.<sha1>" 
  -d "CompanyName=<value>" 
  -d "csrf_token=<value>" 
  -d "custom_tax_rate=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `api-unblock`
- Route: `r=settings/api-unblock`
- Method: GET (state-changing; unsafe)
- Path params: (tidak ada)
- GET params: `csrf_token`, `ip`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/api-unblock&token=a.<aid>.<time>.<sha1>&ip=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `api-block-add`
- Route: `r=settings/api-block-add`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `api_block_add_ip`, `api_block_add_blocked_until`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/api-block-add&token=a.<aid>.<time>.<sha1>" \
  -d "csrf_token=<value>" \
  -d "api_block_add_ip=<IP_ADDRESS>" \
  -d "api_block_add_blocked_until=<YYYY-mm-dd HH:MM:SS>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `api-block-edit`
- Route: `r=settings/api-block-edit`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `api_block_edit_ip`, `api_block_edit_blocked_until`, `api_block_edit_fail_count`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/api-block-edit&token=a.<aid>.<time>.<sha1>" \
  -d "csrf_token=<value>" \
  -d "api_block_edit_ip=<IP_ADDRESS>" \
  -d "api_block_edit_blocked_until=<YYYY-mm-dd HH:MM:SS>" \
  -d "api_block_edit_fail_count=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `localisation`
- Route: `r=settings/localisation`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/localisation&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `localisation-post`
- Route: `r=settings/localisation-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `country_code_phone`, `csrf_token`, `date_format`, `hotspot_plan`, `lan`, `pppoe_plan`, `radius_plan`, `tzone`, `vpn_plan`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/localisation-post&token=a.<aid>.<time>.<sha1>" 
  -d "country_code_phone=<value>" 
  -d "csrf_token=<value>" 
  -d "date_format=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `users`
- Route: `r=settings/users`
- Method: GET
- Path params: (tidak ada)
- GET params: `p`
- POST params: (tidak ada)
- REQUEST params: `search`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/users&token=a.<aid>.<time>.<sha1>&p=1&search=<query>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `users-add`
- Route: `r=settings/users-add`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/users-add&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `users-view`
- Route: `r=settings/users-view`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/users-view/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `users-edit`
- Route: `r=settings/users-edit`
- Method: GET
- Path params: routes[2] => $id; routes[3] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/users-edit/1/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `users-delete`
- Route: `r=settings/users-delete`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/users-delete/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `users-post`
- Route: `r=settings/users-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `city`, `csrf_token`, `email`, `fullname`, `password`, `phone`, `root`, `send_notif`, `subdistrict`, `user_type`, `username`, `ward`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/users-post&token=a.<aid>.<time>.<sha1>" 
  -d "city=<value>" 
  -d "csrf_token=<value>" 
  -d "email=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `users-edit-post`
- Route: `r=settings/users-edit-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `city`, `cpassword`, `csrf_token`, `email`, `faceDetect`, `fullname`, `id`, `password`, `phone`, `root`, `status`, `subdistrict`, `user_type`, `username`, `ward`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/users-edit-post&token=a.<aid>.<time>.<sha1>" 
  -d "city=<value>" 
  -d "cpassword=<value>" 
  -d "csrf_token=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `change-password`
- Route: `r=settings/change-password`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/change-password&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `change-password-post`
- Route: `r=settings/change-password-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `cnpass`, `csrf_token`, `npass`, `password`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/change-password-post&token=a.<aid>.<time>.<sha1>" 
  -d "cnpass=<value>" 
  -d "csrf_token=<value>" 
  -d "npass=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `notifications`
- Route: `r=settings/notifications`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/notifications&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `notifications-post`
- Route: `r=settings/notifications-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/notifications-post&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `notifications-override-type-post`
- Route: `r=settings/notifications-override-type-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `template_key`, `type_key`, `message`
- REQUEST params: (tidak ada)
- Catatan: `type_key` menerima `HOTSPOT`, `PPPOE`, `VPN`.
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/notifications-override-type-post&token=a.<aid>.<time>.<sha1>" \
  -d "csrf_token=<value>" \
  -d "template_key=<value>" \
  -d "type_key=HOTSPOT" \
  -d "message=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `notifications-override-plan-post`
- Route: `r=settings/notifications-override-plan-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `plan_id`, `template_key`, `message`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/notifications-override-plan-post&token=a.<aid>.<time>.<sha1>" \
  -d "csrf_token=<value>" \
  -d "plan_id=<value>" \
  -d "template_key=<value>" \
  -d "message=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `notifications-override-purpose-post`
- Route: `r=settings/notifications-override-purpose-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `template_key`, `purpose_key`, `message`
- REQUEST params: (tidak ada)
- Catatan: `purpose_key` tergantung `template_key`:
  - `otp_message`: `register`, `verify`, `forgot`
  - `welcome_message`: `admin_register`, `self_register`
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/notifications-override-purpose-post&token=a.<aid>.<time>.<sha1>" \
  -d "csrf_token=<value>" \
  -d "template_key=<value>" \
  -d "purpose_key=<value>" \
  -d "message=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `notifications-test`
- Route: `r=settings/notifications-test`
- Method: POST (implicit; returns non-standard JSON)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `message`, `phone`, `template`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/notifications-test&token=a.<aid>.<time>.<sha1>" 
  -d "phone=<value>" 
  -d "template=<value>" 
  -d "message=<value>"
```
Example response (non-standard JSON):
```json
{"ok": true, "message": "...", "csrf_token": "..."}
```

#### `dbstatus`
- Route: `r=settings/dbstatus`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/dbstatus&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `dbbackup`
- Route: `r=settings/dbbackup`
- Method: POST (implicit; download file)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `tables[]`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/dbbackup&token=a.<aid>.<time>.<sha1>"   -d "tables[]=tbl_customers"   -d "tables[]=tbl_transactions"
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

#### `dbrestore`
- Route: `r=settings/dbrestore`
- Method: POST (multipart/form-data)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `json`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/dbrestore&token=a.<aid>.<time>.<sha1>"   -F "json=@/path/to/phpnuxbill_backup.json"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `language`
- Route: `r=settings/language`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/language&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `lang-post`
- Route: `r=settings/lang-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/lang-post&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `maintenance`
- Route: `r=settings/maintenance`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `save`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/maintenance&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "save=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `miscellaneous`
- Route: `r=settings/miscellaneous`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `save`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=settings/miscellaneous&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "save=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `voucher` (access: customer)
- Default action: (tidak eksplisit / tergantung controller)

#### `activation`
- Route: `r=voucher/activation`
- Method: GET
- Path params: (tidak ada)
- GET params: `code`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=voucher/activation&token=<admin_or_customer_token>&code=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `activation-post`
- Route: `r=voucher/activation-post`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `code`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=voucher/activation-post&token=<admin_or_customer_token>" 
  -d "code=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `list-activated`
- Route: `r=voucher/list-activated`
- Method: GET
- Path params: (tidak ada)
- GET params: `p`
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=voucher/list-activated&token=<admin_or_customer_token>&p=1"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `invoice`
- Route: `r=voucher/invoice`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=voucher/invoice&token=<admin_or_customer_token>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `invoice-view`
- Route: `r=voucher/invoice`
- Method: GET
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=voucher/invoice/123&token=<admin_or_customer_token>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

Catatan:
- UI juga punya public invoice link: `r=voucher/invoice/<id>/<sign>` tanpa login, dengan `sign=md5(<id>.<db_pass>)`.

### `widgets` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

#### `list` (default)
- Route: `r=widgets`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `user`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=widgets&token=a.<aid>.<time>.<sha1>&user=Admin"
```
Example response (JSON):
```json
{"success": true, "message": "widgets", "result": { }, "meta": { }}
```

#### `add`
- Route: `r=widgets/add`
- Method: GET/POST (state-changing; unsafe)
- Path params: routes[2] => $position
- GET params: (tidak ada)
- POST params: `content`, `enabled`, `orders`, `position`, `tipeUser`, `title`, `widget`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=widgets/add/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "widgets/add", "result": { }, "meta": { }}
```

#### `edit`
- Route: `r=widgets/edit`
- Method: GET/POST (state-changing; unsafe)
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: `content`, `enabled`, `id`, `orders`, `position`, `tipeUser`, `title`, `widget`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=widgets/edit/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "widgets/edit", "result": { }, "meta": { }}
```

#### `delete`
- Route: `r=widgets/delete`
- Method: GET (state-changing; unsafe)
- Path params: routes[2] => $id
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=widgets/delete/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "widgets/delete", "result": { }, "meta": { }}
```

#### `pos`
- Route: `r=widgets/pos`
- Method: POST (state-changing; unsafe)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `id[]`, `orders[]`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=widgets/pos&token=a.<aid>.<time>.<sha1>"   -d "id[]=1"   -d "orders[]=1"
```
Example response (JSON):
```json
{"success": true, "message": "widgets/pos", "result": { }, "meta": { }}
```

#### `widget-command` (dynamic)
- Route: `r=widgets`
- Method: GET/POST (dynamic widget command)
- Path params: routes[1] => $widget; routes[2] => $command
- GET params: (tidak ada)
- POST params: (tergantung widget command)
- REQUEST params: `user`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=widgets/traffic/reload&token=a.<aid>.<time>.<sha1>&user=Admin"
```
Example response (JSON / non-JSON tergantung widget):
```text
<binary/pdf/csv/html output>
```

Catatan:
- Controller `widgets` punya mode tambahan untuk menjalankan widget command: `r=widgets/<widget>/<command>`.
- Semua aksi di controller ini bersifat administratif dan dapat mengubah data; jangan expose ke publik.
