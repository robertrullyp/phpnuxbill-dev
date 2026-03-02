# Release Audit - 2026.3.2

## Ringkasan
Rilis ini memfokuskan konsistensi boundary usage cycle saat recharge/aktivasi serta standarisasi engine usage menjadi `PlanUsage` untuk alur lintas PPPoE dan Hotspot (direct MikroTik).

## Perubahan Utama
- Standarisasi engine usage lifecycle ke `PlanUsage` pada alur recharge, cron collector, scheduled reset, dan controller terkait.
- Penyelarasan boundary recharge/aktivasi:
  - Aktivasi/recharge membuka cycle baru.
  - App usage (`usage_tx_bytes`, `usage_rx_bytes`) mengikuti kebijakan boundary agar tidak bercampur antar periode.
  - Reset app usage dieksekusi konsisten pada momen scheduled expiry reset.
- Penguatan eksekusi scheduled reset di cron:
  - Menggunakan method device generik `resetUsageBindingCounters`.
  - Setelah reset device, sistem menyinkronkan app-side totals dan membuka cycle baru di titik waktu reset.
- Riwayat usage per aktivasi tetap menjadi sumber utama agregasi UL/DL untuk tampilan history/aktivasi.

## Dampak Database
- Tidak ada perubahan schema baru pada rilis ini.
- Marker rilis ditambahkan ke `system/updates.json`:
  - `2026.3.2`: `[]`

## Dampak File API/Docs
- `docs/openapi.yaml` version -> `2026.3.2`
- `docs/openapi.json` version -> `2026.3.2`

## Verifikasi Operasional
- Metadata rilis telah sinkron pada:
  - `version.json`
  - `CHANGELOG.md`
  - `README.md`
  - `system/updates.json`
  - `docs/openapi.yaml`
  - `docs/openapi.json`
- Engine usage aktif pada class `PlanUsage` dan cron scheduled reset tetap berjalan via collector/reset loop.

## Catatan Deployment
- Jalankan updater database dari menu update/admin agar marker versi tersimpan pada environment target.
- Pastikan cron aktif; proses usage sampling dan scheduled reset bergantung pada cron yang berjalan stabil.
