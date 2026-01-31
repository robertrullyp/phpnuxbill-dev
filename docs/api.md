# Dokumentasi API PHPNuxBill (internal)

> Last updated: 31 Jan 2026 (release 2026.01.31). Untuk integrasi WhatsApp Gateway eksternal, lihat `docs/API WA Gateway.md`.

Dokumen ini merangkum endpoint API yang tersedia berdasarkan isi `system/api.php` dan seluruh `system/controllers/*.php`.
API ini bukan REST murni; ia memanggil controller yang sama dengan UI web, tetapi mengembalikan JSON (melalui `showResult`) ketika berjalan dalam mode API.

## Base URL & Pola Route

- Base URL: `https://<domain>/system/api.php`
- Parameter route: `r`
- Pola: `r=<controller>[/<action>[/<param2>[/<param3>...]]]]`
  - Contoh: `r=customers/view/123/activation`

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

API memakai parameter `token` (bisa via query string atau POST). Logika validasi token ada di `system/api.php`:

- Admin token: `a.<aid>.<time>.<sha1>`
- Customer token: `c.<uid>.<time>.<sha1>`
- `sha1` dihitung dari `sha1("<id>.<time>.<api_secret>")`.
- `api_secret` default = `db_pass` (lihat `init.php`).
- Token dianggap expired bila `time != 0` dan umur > 7,776,000 detik (~90 hari).

### Cara mendapatkan token

- Admin login: `POST r=admin/post` (lihat bagian `admin` di bawah). Response berisi `result.token` seperti `a.<aid>.<time>.<sha1>`.
- Customer login: `POST r=login/post`. Saat ini controller mengembalikan token dengan prefix `u.` (lihat `system/controllers/login.php`).
  Catatan: `system/api.php` mengharapkan prefix `c.` untuk customer. Ini tidak konsisten dan berpotensi membuat token customer tidak diterima tanpa penyesuaian.

### Endpoint khusus di level API

- `r=isValid` -> cek token valid.
- `r=me` -> info admin dari token.

## Contoh penggunaan (curl)

Login admin (ambil token):

```bash
curl -s -X POST "https://<domain>/system/api.php?r=admin/post"   -d "username=admin"   -d "password=admin"
```

Pakai token untuk akses endpoint admin:

```bash
curl -s "https://<domain>/system/api.php?r=dashboard&token=a.<aid>.<time>.<sha1>"
```

Contoh akses data customer (list + pencarian):

```bash
curl -s "https://<domain>/system/api.php?r=customers&token=a.<aid>.<time>.<sha1>&search=andi"
```

## Catatan Penting

- CSRF: pengecekan CSRF dinonaktifkan saat `isApi=true` (lihat `Csrf::check`). Jadi endpoint POST via API tidak butuh `csrf_token`.
- Output non-JSON: beberapa controller mengeluarkan CSV/PDF/HTML secara langsung (bukan melalui `$ui->display`). Aksi seperti itu tetap mengirim output asli.
- Metode HTTP: banyak aksi yang memaksa POST (lihat detail per action di bawah). Jika salah metode, response gagal.


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
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `csrf_token`, `name`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=bandwidth/list&token=a.<aid>.<time>.<sha1>" 
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
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=callback"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
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
- Default action: (tidak eksplisit / tergantung controller)

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
- Path params: routes[2] => (direct)
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
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=coupons/delete&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `status`
- Route: `r=coupons/status`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `coupon_id`, `csrf_token`, `filter`, `search`, `status`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=coupons/status&token=a.<aid>.<time>.<sha1>" 
  -d "coupon_id=<value>" 
  -d "csrf_token=<value>" 
  -d "filter=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `csrf-refresh` (access: mixed)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=csrf-refresh&token=<admin_or_customer_token>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `customers` (access: admin)
- Default action: `list`

#### `list` (default)
- Route: `r=customers` (tanpa action)
- Method: GET (umumnya)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=customers&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

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
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `account_type`, `address`, `city`, `coordinates`, `csrf_token`, `district`, `email`, `export`, `faceDetect`, `fullname`, `id`, `password`, `phonenumber`, `pppoe_ip`, `pppoe_password`, `pppoe_username`, `service_type`, `state`, `status`, `username`, `zip`
- REQUEST params: `filter`, `order`, `orderby`, `search`
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=customers/edit-post&token=a.<aid>.<time>.<sha1>&filter=<value>&order=<value>&orderby=<value>" 
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
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=customfield/save&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `dashboard` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=dashboard&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `default` (access: public)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=default"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
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

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=forgot"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `home` (access: customer)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=home&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
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
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
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
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: `code`
- POST params: `csrf_token`, `username`, `voucher`, `voucher_only`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=login/activation&code=<value>" 
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

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=logout"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

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
- GET params: `q`
- POST params: `keep`, `q`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=logs/list&token=a.<aid>.<time>.<sha1>&q=<value>" 
  -d "keep=<value>" 
  -d "q=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `radius`
- Route: `r=logs/radius`
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: `q`
- POST params: `keep`, `q`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=logs/radius&token=a.<aid>.<time>.<sha1>&q=<value>" 
  -d "keep=<value>" 
  -d "q=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `message`
- Route: `r=logs/message`
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: `q`
- POST params: `keep`, `q`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=logs/message&token=a.<aid>.<time>.<sha1>&q=<value>" 
  -d "keep=<value>" 
  -d "q=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `mail` (access: customer)
- Default action: (tidak eksplisit / tergantung controller)

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
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `search`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=maps/customer&token=a.<aid>.<time>.<sha1>&search=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `routers`
- Route: `r=maps/routers`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `name`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=maps/routers&token=a.<aid>.<time>.<sha1>" 
  -d "name=<value>"
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
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=message/send-post&token=a.<aid>.<time>.<sha1>"
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
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=order/history&token=c.<uid>.<time>.<sha1>"
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

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=page&token=c.<uid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `pages` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=pages&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `paymentgateway` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `delete`
- Route: `r=paymentgateway/delete`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=paymentgateway/delete&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `audit`
- Route: `r=paymentgateway/audit`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `q`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=paymentgateway/audit&token=a.<aid>.<time>.<sha1>&q=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `auditview`
- Route: `r=paymentgateway/auditview`
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `save`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=paymentgateway/auditview&token=a.<aid>.<time>.<sha1>" 
  -d "save=<value>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `plan` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

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
- POST params: `csrf_token`, `id_customer`, `plan`, `server`, `using`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/recharge-confirm&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "id_customer=<value>" 
  -d "plan=<value>" 
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
- POST params: `csrf_token`, `id_customer`, `plan`, `server`, `svoucher`, `using`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/recharge-post&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "id_customer=<value>" 
  -d "plan=<value>" 
  # ... lihat daftar parameter di atas
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
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
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
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
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
- Method: GET/POST (uses POST and GET/REQUEST params)
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
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `plan`, `router`, `search`, `status`
- REQUEST params: `plan`, `router`, `search`, `status`
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=plan/extend&token=a.<aid>.<time>.<sha1>&plan=<value>&router=<value>&search=<value>" 
  -d "plan=<value>" 
  -d "router=<value>" 
  -d "search=<value>" 
  # ... lihat daftar parameter di atas
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `plugin` (access: public)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=plugin"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

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
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `name`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=pool/list&token=a.<aid>.<time>.<sha1>" 
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
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `name`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=pool/port&token=a.<aid>.<time>.<sha1>" 
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
- Default action: (tidak eksplisit / tergantung controller)

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
- POST params: `csrf_token`, `name`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=radius/nas-delete/1&token=a.<aid>.<time>.<sha1>" 
  -d "csrf_token=<value>" 
  -d "name=<value>"
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
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=reports/by-date&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `activation`
- Route: `r=reports/activation`
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: `q`
- POST params: `keep`, `q`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=reports/activation&token=a.<aid>.<time>.<sha1>&q=<value>" 
  -d "keep=<value>" 
  -d "q=<value>"
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

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=search_user"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

### `services` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)

#### `sync`
- Route: `r=services/sync`
- Method: GET
- Path params: routes[2] => (direct)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/sync/1&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```

#### `hotspot`
- Route: `r=services/hotspot`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `bandwidth`, `device`, `name`, `router`, `status`, `type1`, `type2`, `type3`, `valid`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=services/hotspot&token=a.<aid>.<time>.<sha1>&bandwidth=<value>&device=<value>&name=<value>"
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
- POST params: `data_limit`, `data_unit`, `device`, `enabled`, `expired_date`, `id_bw`, `limit_type`, `name`, `plan_type`, `prepaid`, `price`, `radius`, `routers`, `sharedusers`, `time_limit`, `time_unit`, `typebp`, `validity`, `validity_unit`, `visibility`
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
- POST params: `data_limit`, `data_unit`, `device`, `enabled`, `expired_date`, `id`, `id_bw`, `limit_type`, `name`, `on_login`, `on_logout`, `plan_expired`, `plan_type`, `prepaid`, `price`, `price_old`, `routers`, `sharedusers`, `time_limit`, `time_unit`, `typebp`, `validity`, `validity_unit`, `visibility`
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
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `name`
- REQUEST params: `bandwidth`, `device`, `name`, `router`, `status`, `type1`, `type2`, `type3`, `valid`
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=services/pppoe&token=a.<aid>.<time>.<sha1>&bandwidth=<value>&device=<value>&name=<value>" 
  -d "name=<value>" 
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
- POST params: `device`, `enabled`, `expired_date`, `id_bw`, `name_plan`, `plan_type`, `pool_name`, `prepaid`, `price`, `radius`, `routers`, `validity`, `validity_unit`, `visibility`
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
- POST params: `device`, `enabled`, `expired_date`, `id`, `id_bw`, `name_plan`, `on_login`, `on_logout`, `plan_expired`, `plan_type`, `pool_name`, `prepaid`, `price`, `price_old`, `routers`, `validity`, `validity_unit`, `visibility`
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
- Method: POST (implicit; uses POST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `name`
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=services/balance&token=a.<aid>.<time>.<sha1>" 
  -d "name=<value>"
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
- POST params: `enabled`, `id`, `name`, `prepaid`, `price`, `price_old`, `visibility`
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
- POST params: `enabled`, `name`, `price`, `visibility`
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
- Method: GET/POST (uses POST and GET/REQUEST params)
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: `name`
- REQUEST params: `bandwidth`, `device`, `name`, `router`, `status`, `type1`, `type2`, `type3`, `valid`
Example request:
```bash
curl -s -X POST "https://<domain>/system/api.php?r=services/vpn&token=a.<aid>.<time>.<sha1>&bandwidth=<value>&device=<value>&name=<value>" 
  -d "name=<value>" 
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
- POST params: `device`, `enabled`, `expired_date`, `id_bw`, `name_plan`, `plan_type`, `pool_name`, `prepaid`, `price`, `radius`, `routers`, `validity`, `validity_unit`, `visibility`
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
- POST params: `device`, `enabled`, `expired_date`, `id`, `id_bw`, `name_plan`, `on_login`, `on_logout`, `plan_expired`, `plan_type`, `pool_name`, `prepaid`, `price`, `price_old`, `routers`, `validity`, `validity_unit`, `visibility`
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
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: `search`
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/users&token=a.<aid>.<time>.<sha1>&search=<value>"
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
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/dbbackup&token=a.<aid>.<time>.<sha1>"
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

#### `dbrestore`
- Route: `r=settings/dbrestore`
- Method: GET
- Path params: (tidak ada)
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=settings/dbrestore&token=a.<aid>.<time>.<sha1>"
```
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
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

### `voucher` (access: mixed)
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
- GET params: (tidak ada)
- POST params: (tidak ada)
- REQUEST params: (tidak ada)
Example request:
```bash
curl -s "https://<domain>/system/api.php?r=voucher/list-activated&token=<admin_or_customer_token>"
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
Example response (non-JSON, tergantung aksi):
```text
<binary/pdf/csv/html output>
```

### `widgets` (access: admin)
- Default action: (tidak eksplisit / tergantung controller)
- Actions: (tidak ada switch/action eksplisit)

Example request:
```bash
curl -s "https://<domain>/system/api.php?r=widgets&token=a.<aid>.<time>.<sha1>"
```
Example response (JSON):
```json
{"success": true, "message": "OK", "result": {}, "meta": {}}
```
