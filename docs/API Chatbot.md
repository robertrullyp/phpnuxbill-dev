# Wire Chatbot (WhatsApp) - Integrasi dan Prosedur

> Last updated: 31 Jan 2026 (release 2026.01.31). Terminologi dan field mengikuti UI terbaru.

Dokumen ini menjelaskan syarat dan prosedur agar integrasi **Wire Chatbot** berfungsi
dengan benar. Fokusnya adalah apa yang harus dilakukan di sisi **chatbot** (webhook eksternal).

## 1) Ringkas Fungsi
- Wire Chatbot menghubungkan **kontak WA tertentu** (personal/grup) ke **webhook eksternal**.
- Saat kontak **membalas (reply)** ke pesan bot, pesan diteruskan ke webhook eksternal.
- Balasan dari webhook akan dikirim kembali ke WhatsApp sebagai **reply** ke pesan pengguna.
- Kontak yang di-wire **bypass** aturan `reply_only/mention_only`, dan **selalu memakai** webhook eksternal.
> Catatan: Webhook ini **khusus wire chatbot** (gateway → chatbot) dan **terpisah** dari webhook AI external untuk auto‑reply.

## 2) Endpoint yang Dipakai
- **Chatbot -> Gateway (inbound)**: `POST /ext/{secret}/ai`
  - Kirim `route: "wa"` + `session_id` + `chatInput`/`text`.
- **Gateway -> Chatbot (outbound)**: **Chatbot Webhook** di UI WhatsApp.
  - URL ini **terpisah** dari AI external webhook.

## 3) Syarat & Prasyarat
1) **WA Device sudah terhubung** dan kontak sudah tersinkron.
2) **Chatbot Webhook** di halaman WhatsApp sudah diisi (gateway → chatbot khusus wire).
3) **Kontak/group yang di-wire ada di daftar kontak** (handle WA valid).
4) Untuk grup, **pesan harus reply ke pesan bot** agar diteruskan.
5) Jika chatbot punya banyak session browser, kirim **session_id** agar balasan user bisa dipetakan dengan tepat.

## 4) Prosedur Aktivasi (Admin/User)
1) Buka halaman **WhatsApp** di panel.
2) Pilih **device** WA.
3) Isi **Chatbot Webhook** (gateway → chatbot khusus wire).
4) Di bagian **External Endpoints**, cari input **Wire Contact**.
5) Ketik nama/nomor/grup (auto-complete) lalu klik **Wire Chatbot**.
6) Status akan tersimpan di server dan tampil di daftar wire aktif.

## 5) Alur Pesan (End-to-End)

### A) Inbound: WhatsApp -> Chatbot
1) User WA **reply** ke pesan bot (wajib ada reply).
2) Server cek apakah contact ini **wired** untuk device tersebut.
3) Jika wired, server **mengirim payload ke Chatbot Webhook** (khusus wire, terpisah dari AI external webhook).
4) Chatbot merespons **text**.
5) Server mengirim balasan ke WA sebagai **reply**.

Jika **tidak reply**, pesan **tidak diteruskan** ke chatbot.

### B) Outbound: Chatbot -> WhatsApp
Ada 2 cara umum:
1) **Balasan webhook** (otomatis):
   - Chatbot merespons request dari gateway (payload masuk).
   - Gateway langsung mengirim ke WA.
2) **Proaktif via /ext/:secret/ai**:
   - Chatbot memanggil endpoint **AI external** dan memberi `route: "wa"`.
   - Gateway meneruskan ke kontak WA yang di-wire.

## 6) Format Payload dari Gateway ke Chatbot (Wire Webhook)
Webhook menerima payload dengan dua format:
1) **JSON** (default, tanpa file attachment).
2) **multipart/form-data** ketika ada `attachments` (teks + file dikirim bersama).

Format JSON utamanya seperti berikut:

```json
{
  "system": "system prompt (optional)",
  "messages": [
    { "role": "system", "content": "..." },
    { "role": "user", "content": "..." }
  ],
  "temperature": 0.7,
  "max_tokens": 512,
  "chatInput": "Pesan user",
  "inputType": "text|image|document|pdf|voice",
  "attachments": [],
  "body": {
    "chatInput": "Pesan user",
    "inputType": "text|image|document|pdf|voice",
    "attachments": []
  },
  "extra": {
    "wire_chatbot": true,
    "contact_id": "uuid",
    "contact_jid": "62812...@s.whatsapp.net",
    "session_id": "session-123"
  },
  "metadata": { "ownerId": "...", "scope": "wa", "entityId": "...", "requestId": "..." }
}
```

### Header penting
- `X-AI-Request-ID` / `X-Idempotency-Key`: id request
- `X-AI-Scope`, `X-AI-Entity`, `X-AI-Owner`: info konteks

Catatan:
- `body.chatInput` adalah bentuk **seragam**; nilai top-level (`chatInput`, `inputType`, `attachments`) tetap dikirim untuk kompatibilitas.

## 6.1) Format Payload dari Chatbot -> Gateway (Outbound)
Chatbot **hanya** mengirim ke endpoint AI external:

**POST** `/ext/{secret}/ai`
```json
{
  "route": "wa",
  "chatInput": "Halo!",
  "session_id": "session-123"
}
```
> Alternatif: gunakan `text` jika belum memakai `chatInput`.

Kunci `route: "wa"` (atau `action: "wa_send"`) membuat gateway **meneruskan ke WA** alih‑alih AI provider.
Secara default, route ini **mengaktifkan handoff** untuk kontak tujuan (agar auto‑reply AI berhenti).
Jika tidak ingin handoff, kirim `"handoff": false`.
Jika `to`/`contact_id` **tidak** dikirim, gateway memakai **kontak wire aktif** (dipilih di UI Wire Contact).

Catatan penting:
- Chatbot **tidak perlu** mengetahui `contact_id` maupun `entity_id`.
- Mapping ke kontak WA dilakukan gateway berdasarkan **Wire Contact** di UI.
- Jika ada lebih dari satu wire aktif, gateway memakai yang **terakhir di‑update**.

## 7) Format Attachments (Vision/Dokumen/PDF/Voice)
Jika `inputType` bukan `text`, gateway mengirim `attachments`.

### 7.1) JSON (legacy)
Jika webhook menerima **JSON**, setiap attachment berisi `base64`:

**Image**
```json
{
  "type": "image",
  "mimetype": "image/jpeg",
  "size": 12345,
  "base64": "...",
  "caption": "optional caption",
  "width": 1080,
  "height": 720,
  "sha256": "base64sha"
}
```

**Document / PDF**
```json
{
  "type": "document",
  "mimetype": "application/pdf",
  "size": 23456,
  "base64": "...",
  "filename": "file.pdf",
  "caption": "optional"
}
```

**Voice**
```json
{
  "type": "voice",
  "mimetype": "audio/ogg; codecs=opus",
  "size": 34567,
  "base64": "...",
  "duration": 12,
  "caption": "optional"
}
```

### 7.2) multipart/form-data (disarankan)
Jika gateway mendeteksi `attachments`, ia mengirim **multipart/form-data** dengan pola:
- Field `payload` berisi JSON string (struktur sama dengan di atas).
- File attachment dikirim sebagai part terpisah dengan nama `file0`, `file1`, dst.
- Pada array `attachments`, properti `base64` **tidak dikirim** dan diganti dengan `fileField` yang menunjuk nama file part.
- `sessionid` selalu disertakan di payload, dan header `X-AI-Session` dikirim jika tersedia.

Contoh potongan `attachments` di dalam `payload`:
```json
[
  {
    "type": "image",
    "mimetype": "image/jpeg",
    "size": 12345,
    "caption": "optional caption",
    "width": 1080,
    "height": 720,
    "fileField": "file0"
  }
]
```
Di sisi webhook, gunakan `fileField` untuk mengambil file dari part multipart.

## 8) Format Balasan dari Chatbot
Webhook harus mengembalikan **teks**. Gateway akan mengambil:
- `responseKey` jika diset di UI (misal `output`), atau
- `message` jika ada, atau
- body text langsung.

**Jika response kosong**, gateway akan menganggapnya **error**. Untuk menghentikan auto-reply,
gunakan **mode handoff** (lihat di bawah).

## 9) Aturan Penting (Wajib untuk Wire)
- **Reply wajib**: pesan harus reply ke bot agar diteruskan.
- **Group**: hanya reply ke bot yang akan diproses.
- **Bypass aturan**: `reply_only`, `mention_only`, `reply_group` tidak berlaku untuk kontak wired.
- **Mode paksa wire webhook**: kontak wired selalu diproses via Chatbot Webhook.
- **Mapping session**: `session_id` disimpan dari outbound chatbot agar reply user kembali ke session yang benar.

## 10) Mode "Chat dengan Admin" (Handoff)
Untuk mencegah bentrok antara **AI** dan **admin**, chatbot **wajib** mengirim
flag/route khusus ketika user memilih **chat dengan admin**. Saat handoff aktif,
gateway **menghentikan auto reply AI** untuk kontak tersebut agar admin mengambil alih.

### Aktifkan handoff
Chatbot cukup mengirim `route: "handoff"` dan **session_id** (tanpa `contact_id`/`entity_id`).
Gateway akan mencari device + contact dari **pesan terakhir** milik session tersebut.

```json
POST /ext/{secret}/ai
{
  "route": "handoff",
  "session_id": "session-123",
  "handoff_timeout_sec": 600,
  "handoff_reason": "chat_dengan_admin"
}
```

Alternatif:
```json
{ "route": "admin", "handoff": true, "session_id": "session-123" }
```

### Nonaktifkan handoff
```json
POST /ext/{secret}/ai
{
  "route": "handoff_off",
  "session_id": "session-123"
}
```

### Timeout (sinkronisasi state)
Jika chatbot mendeteksi timeout, **kirim sinyal ke gateway** agar state sinkron:
```json
POST /ext/{secret}/ai
{
  "route": "handoff_timeout",
  "session_id": "session-123",
  "handoff_reason": "timeout"
}
```
Alternatif: kirim `handoff_status: "timeout"`.
Gateway akan mencari pesan WA terakhir berdasarkan `session_id` untuk menentukan device & kontak yang harus di‑clear.

### Timeout
- Default: **600 detik (10 menit)**
- Disarankan: **300–600 detik**
- Atur via `handoff_timeout_sec`

### Notifikasi jika admin tidak merespons
Gateway **tidak** mengirim pesan otomatis saat timeout.
Chatbot **harus** menjadwalkan sendiri pesan fallback, contoh:
> “Maaf, admin sedang tidak tersedia. Silakan coba lagi nanti.”

### Prefix/Marker (opsional)
Jika ingin menandai pesan internal, gunakan marker seperti:
- `[[ADMIN]]` atau `#handoff`

**Rekomendasi**: tetap gunakan **route/flag** agar gateway jelas mengaktifkan handoff.

## 11) Tips Implementasi di Chatbot
1) **Validasi `extra.wire_chatbot === true`** untuk membedakan traffic wire.
2) Gunakan `contact_jid` untuk route/identifikasi session.
3) Gunakan `requestId` untuk idempotensi.
4) Simpan state/chat history per contact jika diperlukan.

## 11) Contoh Handler Sederhana (Pseudo)
```js
app.post('/ai-webhook', (req, res) => {
  const { chatInput, extra } = req.body;
  if (extra?.wire_chatbot) {
    // handle wired contact
  }
  const reply = `Halo, kamu bilang: ${chatInput}`;
  res.json({ output: reply });
});
```

---
Jika butuh contoh integrasi ke platform chatbot tertentu (n8n, Botpress, Rasa),
beri tahu saya dan saya buatkan contoh khusus.
