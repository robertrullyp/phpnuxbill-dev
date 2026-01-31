# WhatsApp Gateway (POST/GET) — Integrasi PHPNuxBill

> Last updated: 31 Jan 2026 (release 2026.01.31).

Dokumentasi ini menjelaskan cara PHPNuxBill mengirim WhatsApp ke server gateway eksternal, format payload POST, format human‑friendly `[[wa]]` (builder), serta perilaku legacy GET.

## 1) Ringkas
PHPNuxBill mendukung dua mode pengiriman WA:
1) **POST (disarankan)** — kirim JSON ke **WA Gateway** (mendukung pesan interaktif, header media, request nomor, idempotency, dll).
2) **GET (legacy)** — kirim ke URL dengan placeholder `[number]` dan `[text]` (hanya teks).

Mode **POST** dipakai untuk integrasi gateway modern. Mode **GET** dipakai jika gateway hanya mendukung teks.

---

## 2) Konfigurasi (Admin > Settings > App > Whatsapp Notification)

### A) Method
- **POST** → aktifkan WA Gateway (JSON).
- **GET** → gunakan URL legacy.

### B) WhatsApp Gateway URL (POST)
Masukkan URL endpoint gateway. Contoh:
- `https://your-host/ext/SECRET/wa`
- `https://your-host/ext/{secret}/wa`
- `https://your-host/ext/:secret/wa`
- `https://your-host/ext/[secret]/wa`

**Catatan URL & secret:**
- Jika URL berisi `{secret}` / `:secret` / `[secret]`, PHPNuxBill akan mengganti dengan `wa_gateway_secret` (jika tersimpan di DB/config).
- Jika URL **tidak** mengandung `/ext/` atau `/wa`, sistem akan menambahkan `.../ext/{secret}/wa` (butuh secret).
- **UI saat ini tidak menampilkan field secret** → rekomendasi: **tempel URL lengkap yang sudah berisi secret** (mis. `https://host/ext/SECRET/wa`).

### C) Auth untuk POST
Auth type:
- **None**
- **Basic** → isi `Auth Username` + `Auth Password`
- **Header** → isi `Auth Header Name` + `Auth Token`
- **JWT** → isi `Auth Token` (dikirim sebagai `Authorization: Bearer ...`)

### D) WhatsApp Gateway URL (GET / Legacy)
Masukkan URL legacy yang **wajib** berisi placeholder:
- `[number]` → nomor tujuan
- `[text]` → isi pesan

Contoh:
```
https://domain/?param_number=[number]&param_text=[text]&secret=...
```

### E) WA Queue (opsional)
- **WA Queue Max Retries**: jumlah retry otomatis (min 1).
- **WA Queue Retry Interval (seconds)**: jeda antar retry (min 10 detik).
- Diproses oleh **cron** (`system/cron.php`).

---

## 3) Payload POST (ke WA Gateway)

PHPNuxBill akan mengirim JSON POST ke gateway. `action` otomatis diisi `send` bila tidak ada.

```json
{
  "action": "send | upsert | delete",
  "to": "62812xxxxxxx",
  "text": "string",
  "body": "string",
  "message": { "any": "object" },
  "interactive": { "type": "buttons | list | template", "...": "..." },
  "requestPhoneNumber": false,
  "allowEmptyText": false,
  "session_id": "string",
  "idempotency_key": "string",
  "queue": false,
  "auto_retry": false,
  "contacts": [ { "...": "..." } ]
}
```

**Catatan:**
- `text` atau `body` dipakai untuk teks biasa.
- `interactive` untuk pesan interaktif.
- `message` untuk raw payload (sesuai format gateway WA Anda).
- `requestPhoneNumber: true` untuk meminta nomor (jika gateway mendukung).
- `idempotency_key` juga bisa dikirim via header `Idempotency-Key`.
- Field `queue`/`auto_retry` **akan diteruskan** ke gateway jika Anda mengisinya.

### Idempotency/Dedup
PHPNuxBill **selalu membuat** `idempotency_key` jika kosong, dan mengirim header `Idempotency-Key`. Ini membantu mencegah pengiriman ganda saat retry.

---

## 4) Struktur `interactive`

### A) Buttons
```json
{
  "type": "buttons",
  "text": "Pilih menu",
  "footer": "Footer opsional",
  "headerType": 1,
  "headerText": "Header opsional",
  "headerMedia": { "type": "image", "url": "https://..." },
  "buttons": [
    { "id": "1", "text": "Menu 1" },
    { "id": "2", "text": "Menu 2" }
  ]
}
```

### B) List (multi‑section)
```json
{
  "type": "list",
  "text": "Pilih item",
  "title": "Judul list",
  "buttonText": "Lihat",
  "sections": [
    {
      "title": "Kategori A",
      "rows": [
        { "id": "a1", "title": "Item A1", "description": "Keterangan" }
      ]
    },
    {
      "title": "Kategori B",
      "rows": [
        { "id": "b1", "title": "Item B1" }
      ]
    }
  ]
}
```

### C) Template
```json
{
  "type": "template",
  "text": "Konfirmasi tindakan",
  "buttons": [
    { "type": "quick", "text": "Ya", "id": "yes" },
    { "type": "url", "text": "Buka", "url": "https://example.com" },
    { "type": "call", "text": "Telepon", "phoneNumber": "62812xxxxxxx" }
  ]
}
```

### HeaderType
- `1` = text
- `2` = image
- `3` = video
- `4` = document

Gunakan `headerText` untuk tipe 1, dan `headerMedia.url` untuk tipe 2/3/4.

---

## 5) Format Human‑Friendly (`[[wa]]` Builder)

Builder menghasilkan blok `[[wa]] ... [[/wa]]` agar mudah ditulis di UI.
Format utama: **`[key](value)`**, tetapi **`key=value`** atau **`key: value`** masih didukung.

**Contoh (template + tombol):**
```
[[wa]]
[type](template)
Hallooo [[name]], ini test...

test lagi,
[[name]]
last test
[button](quick|1|ya)
[button](url|home|https://example.com)
[button](call|drnet|08119806333)
[[/wa]]
```

**Contoh (list dengan multi‑section):**
```
[[wa]]
[type](list)
Pilih menu berikut:
[title](Menu Utama)
[buttonText](Lihat)
[section](Paket)
[row](p1|Paket 1|Deskripsi)
[row](p2|Paket 2)
[section](Support)
[row](cs|Hubungi CS)
[[/wa]]
```

### Kunci yang didukung
- `type` → `buttons | list | template`
- `text` / `body` → teks utama (bisa multi‑line). Anda juga bisa menulis langsung baris teks di dalam blok.
- `headerType` → `1|2|3|4`
- `headerText` → teks header
- `headerMedia` → URL media header
- `footer` → footer teks
- `allowEmptyText` → `true/false`
- `title`, `buttonText` → khusus list
- `section` → judul section list (boleh lebih dari satu)
- `row` → format `id|title|description`
- `button`:
  - buttons: `id|text`
  - template: `quick|id|text`, `url|text|url`, `call|text|phone`
- `requestPhoneNumber` → `true/false`
- `to`, `session_id` (opsional)

**Catatan fallback:** jika gateway hanya mendukung teks (GET/legacy), sistem akan mengambil teks dari blok `[[wa]]`.

---

## 6) Header Media Upload (PHPNuxBill)

Builder mendukung **upload media** untuk header (image/video/document). File akan:
- Diunggah ke `system/uploads/wa_tmp/<media_id>/filename`
- Diberi **URL publik sementara**
- Disimpan maksimal **7 hari** (dibersihkan otomatis)
- Diproses oleh cron melalui `system/cron.php`

**Batasan upload:**
- Maks 16 MB
- Tipe: `image/*`, `video/mp4`, `video/3gpp`, `application/pdf`

Gateway eksternal harus bisa **mengunduh URL** tersebut.

---

## 7) Queue & Retry (PHPNuxBill)

PHPNuxBill punya **antrian WA** internal:
- Toggle tersedia di **Settings > Notifications**, **Send Message**, dan **Send Bulk**.
- Retry & interval diatur di **Settings > App > Whatsapp Notification**.
- Diproses cron (`system/cron.php`).

Jika gateway eksternal juga punya retry, **potensi kirim ganda** bisa terjadi. Gunakan **idempotency key** atau nonaktifkan salah satu mekanisme retry.

---

## 8) Logging & Resend

- Log mencatat **payload** dan **response** (success/error).
- Pesan gagal bisa **Edit & Resend** dan builder akan **auto‑fill** dari payload.

---

## 9) Legacy GET

Jika `Method = GET`:
- Sistem mengirim **teks saja** ke `wa_url`.
- Placeholder wajib: `[number]` dan `[text]`.
- Pesan interaktif akan **fallback ke teks**.

---

## 10) Contoh cURL (POST)

### 1) Teks biasa
```bash
curl -X POST "https://your-host/ext/SECRET/wa" \
  -H "Content-Type: application/json" \
  -d '{
    "action": "send",
    "to": "62812xxxxxxx",
    "text": "Halo dari PHPNuxBill"
  }'
```

### 2) Interactive buttons
```bash
curl -X POST "https://your-host/ext/SECRET/wa" \
  -H "Content-Type: application/json" \
  -d '{
    "action": "send",
    "to": "62812xxxxxxx",
    "interactive": {
      "type": "buttons",
      "text": "Pilih menu",
      "buttons": [
        { "id": "1", "text": "Info" },
        { "id": "2", "text": "Bantuan" }
      ]
    }
  }'
```

### 3) Idempotency
```bash
curl -X POST "https://your-host/ext/SECRET/wa" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: order-INV-2026-0001" \
  -d '{
    "action": "send",
    "to": "62812xxxxxxx",
    "text": "Pembayaran berhasil"
  }'
```

---

## Appendix: Mutasi Kontak (opsional, jika gateway mendukung)

### Upsert
```json
{
  "action": "upsert",
  "contacts": [
    { "name": "Budi", "phone": "62812xxxxxxx" }
  ]
}
```

### Delete
```json
{
  "action": "delete",
  "contacts": [
    { "id": "contact-uuid" },
    { "phone": "62812xxxxxxx" }
  ]
}
```
