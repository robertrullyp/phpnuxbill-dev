# Standar API (Internal)

Dokumen ini mendefinisikan standar praktis untuk API PHPNuxBill di repo ini (controller-based), sekaligus rekomendasi upgrade keamanan tanpa memutus kompatibilitas.

## 1) Kontrak Dasar

### 1.1 Routing

Saat ini API di-handle oleh `system/api.php` dan route ditentukan oleh string `<route>`:

- Legacy: `/system/api.php?r=<route>`
- PATH_INFO (direkomendasikan untuk tooling/OpenAPI): `/system/api.php/<route>`

`<route>` mengikuti pola:

- `<controller>[/<action>[/<param2>[/<param3>...]]]]`

### 1.2 Response JSON (default)

Untuk endpoint yang menggunakan `showResult(...)`, response berbentuk:

```json
{
  "success": true,
  "message": "string",
  "result": { "any": "object" },
  "meta": { "any": "object" }
}
```

Catatan: sebagian endpoint adalah “raw output” (PDF/CSV/HTML/text) dan tidak memakai envelope JSON.

### 1.3 HTTP Status

Konvensi implementasi saat ini:

- Banyak error tetap memakai HTTP 200 dengan `success=false`.
- Rate limit / API key throttle menggunakan HTTP 429 + `Retry-After`.

Standar yang disarankan untuk endpoint baru:

- 2xx untuk sukses.
- 4xx untuk error client (401/403/404/422).
- 429 untuk rate limit.
- 5xx untuk error server.

Tetap boleh mengirim envelope JSON pada 4xx/5xx agar agent mudah mem-parse.

## 2) Autentikasi & Otorisasi

### 2.1 Admin API Key (direkomendasikan)

Gunakan header:

- `X-Admin-Api-Key: <ADMIN_API_KEY>`

Kompatibilitas:

- `X-API-Key: <ADMIN_API_KEY>`
- `Authorization: Bearer <ADMIN_API_KEY>`

Catatan keamanan:

- Header `Authorization` kadang tidak diteruskan ke PHP (tergantung setup web server/proxy). Jika `Authorization: Bearer ...` tidak bekerja, gunakan `X-Admin-Api-Key` atau konfigurasi server agar `HTTP_AUTHORIZATION` diteruskan ke PHP.
- Key disimpan hashed (HMAC-SHA256) + `last4` (tidak bisa dibaca balik).
- Kombinasikan dengan allowlist IP/CIDR jika memungkinkan.
- Set secret hashing yang stabil untuk admin API key. Disarankan set `$admin_api_key_secret` di `config.php`
  (jangan bergantung pada `db_pass` yang bisa kosong/berubah). Setelah mengganti secret ini, regenerate API key
  untuk memastikan hash tersimpan memakai secret terbaru.

### 2.1.1 Admin API Key Per Role (RBAC)

Admin API key selalu terikat ke **1 user admin** di `tbl_users` dan otomatis mewarisi role (`user_type`) user tsb.
Artinya:

- Jika key milik user `Admin`/`SuperAdmin`: request akan punya akses admin penuh (sesuai guard per-controller).
- Jika key milik user `Agent`/`Sales`: request hanya bisa mengakses endpoint yang memang diizinkan untuk role tsb.
- Jika key milik user `Report`: request umumnya hanya bisa akses laporan (read-only), dan akan `blocked` untuk aksi operasional.

Cara validasi cepat (disarankan sebelum eksekusi automation):

```bash
curl -s -H "X-Admin-Api-Key: <ADMIN_API_KEY>" \
  "https://<domain>/system/api.php?r=whoami/permissions"
```

Yang dicek dari JSON:

- `result.identity.user_type` menunjukkan role key tersebut.
- `result.permissions.auth.via_api_key == true` memastikan auth benar-benar via API key.

Rekomendasi operasional:

- Buat **service account** terpisah per kebutuhan integrasi (mis. `api_agent`, `api_report`) dan set role minimal (least privilege).
- Jangan reuse 1 key untuk banyak integrasi/role; lebih mudah audit, rotasi, dan revokasi.

### 2.2 Token (legacy)

Token dipakai untuk sesi admin/customer:

- Admin: `a.<aid>.<time>.<sha1>`
- Customer: `c.<uid>.<time>.<sha1>`

Transport yang diterima:

- Query: `token=<token>`
- Body (POST): `token=<token>`
- Header (lebih aman dari sisi log): `X-Auth-Token: <token>` (alias `X-Token`)

Rekomendasi:

- Hindari menaruh token di URL untuk production.
- Untuk integrasi server-to-server, utamakan Admin API key.

### 2.3 Rate Limit & Backoff

Standar handling client:

- Jika HTTP 429: wajib backoff sesuai `Retry-After`.
- Hindari retry agresif (gunakan exponential backoff + jitter).

## 3) Standar Request

### 3.1 Content-Type

Mayoritas endpoint POST menggunakan form fields:

- `application/x-www-form-urlencoded`
- (kadang) `multipart/form-data`

Untuk endpoint baru, jika memungkinkan:

- gunakan JSON (`application/json`) untuk payload kompleks,
- gunakan form-data hanya untuk upload file.

### 3.2 Idempotency (disarankan untuk aksi finansial)

Untuk aksi yang membuat transaksi/recharge, standar yang disarankan:

- Terima header `Idempotency-Key` (unik per actor) dan simpan hasilnya untuk window tertentu.
- Jika request diulang dengan key yang sama, kembalikan hasil yang sama (tanpa double charge).

(Belum diimplementasikan global; perlu langkah lanjutan jika ingin diterapkan penuh.)

## 4) OpenAPI / Swagger

- Spec tersedia di `docs/openapi.yaml` dan `docs/openapi.json`.
- Contoh request per endpoint tersedia di `docs/API_EXAMPLES.md` (generated dari OpenAPI).
- Generator: `python3 scripts/generate_openapi.py` (sumber utama tetap `docs/api.md`).
- Generator contoh: `python3 scripts/generate_api_examples.py`.

## 5) Rekomendasi Upgrade Keamanan Lanjutan (opsional)

Item di bawah ini butuh waktu/QA karena menyentuh kompatibilitas:

- Deprecate `config.api_key` global yang auto-impersonate SuperAdmin (lebih aman pakai per-admin API key + allowlist).
- Pisahkan secret signing token dari `db_pass` (jangan pakai password DB sebagai `api_secret`).
- Upgrade algoritma token dari `sha1` ke HMAC-SHA256, tambah opsi expiry lebih pendek + mekanisme revoke token.
- Tambahkan audit log untuk aksi sensitif (recharge, refund, perubahan API key) dengan request id/actor id.
