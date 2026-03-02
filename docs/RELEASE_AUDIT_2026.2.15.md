# Release Audit - 2026.2.15

## Ringkasan
Rilis ini memfokuskan stabilitas siklus layanan setelah aktivasi dengan menambahkan alur refund kebalikan recharge, penyelarasan perilaku extend, dan kontrol notifikasi expiry edit yang lebih presisi.

## Perubahan Utama
- Menambahkan alur **Refund Customer** (`/plan/refund`) untuk paket aktif:
  - Mengurangi masa aktif sesuai validity plan.
  - Menonaktifkan layanan otomatis jika hasil expiry `<= now`.
  - Menjalankan sinkronisasi device (aktif/nonaktif) secara otomatis.
  - Mencatat transaksi refund bernilai negatif dan tetap menghasilkan invoice.
- Menambahkan reversal linked plan pada refund agar perilaku konsisten dengan recharge linked flow.
- Menyelaraskan integrasi PPPoE usage saat refund:
  - Membatalkan pending reset schedule lama.
  - Menutup usage cycle saat paket menjadi `off`.
  - Menjadwalkan reset baru bila paket tetap aktif dengan expiry baru.
- Menambahkan kontrol **Expiry Edit Notification** di Settings App untuk mengirim template `edit_expiry_message` saat extend berhasil.
- Menambahkan placeholder `[[extend_link]]` dan membatasi penggunaannya pada konteks notifikasi expired saja.
- Menyelaraskan UX customer dengan label `WiFi Setting` (menggantikan wording GenieACS pada widget customer).
- Menyinkronkan skema **fresh install** (`install/phpnuxbill.sql`) agar setara dengan runtime terbaru:
  - `tbl_plans.pppoe_service`
  - `tbl_user_recharges.usage_tx_bytes`, `tbl_user_recharges.usage_rx_bytes`
  - `tbl_recharge_usage_cycles`
  - `tbl_recharge_usage_samples`
  - Default appconfig: `extend_expiry`, `extend_expired`, `extend_allow_prepaid`, `notification_expiry_edit`

## Dampak Database
- Tidak ada perubahan schema baru pada rilis ini.
- Marker rilis ditambahkan ke `system/updates.json`:
  - `2026.2.15`: `[]`

## Dampak File API/Docs
- `docs/openapi.yaml` version -> `2026.2.15`
- `docs/openapi.json` version -> `2026.2.15`

## Verifikasi Operasional
- Metadata rilis telah sinkron pada:
  - `version.json`
  - `CHANGELOG.md`
  - `README.md`
  - `system/updates.json`
  - `docs/openapi.yaml`
  - `docs/openapi.json`
- Alur refund tersedia di menu admin (`Refund Customer`) dan route controller (`plan/refund`, `plan/refund-confirm`, `plan/refund-post`).

## Catatan Deployment
- Jalankan update database dari halaman Community/Updater agar marker versi tersimpan pada environment target.
- Pastikan cron tetap aktif untuk menjaga konsistensi proses usage sampling dan expiry workflow.
