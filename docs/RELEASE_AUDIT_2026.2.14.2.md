# Release Audit - 2026.2.14.2

## Ringkasan
Rilis ini menstabilkan alur PPPoE non-radius agar parameter plan dan pencatatan usage berjalan konsisten dari recharge sampai histori aktivasi.

## Perubahan Utama
- PPPoE plan sekarang punya parameter `pppoe_service` yang bisa dipilih dari data router.
- Device `MikrotikPppoe` sekarang menangani `/ppp/secret` dan `/interface/pppoe-server` binding secara bersamaan.
- Sistem menambahkan engine pencatatan usage per aktivasi (cycle + sample) untuk TX/RX.
- Cron menulis sample usage berkala, menangani reset counter, dan menutup cycle saat paket berakhir.
- Usage TX/RX ditampilkan di:
  - Widget customer active plan
  - Admin plan list (`/plan/list`)
  - Activation history (`/reports/activation`)
- Updater ditingkatkan agar migrasi enum lama yang obsolete tidak memblokir update database pada data modern.

## Dampak Database
Migrasi utama sudah terdaftar pada `system/updates.json`:
- `2026.2.14.1`
  - `tbl_plans.pppoe_service`
  - `tbl_user_recharges.usage_tx_bytes`
  - `tbl_user_recharges.usage_rx_bytes`
  - `tbl_recharge_usage_cycles`
  - `tbl_recharge_usage_samples`
- `2026.2.14.2`
  - Tidak ada perubahan schema (marker rilis metadata).

## Dampak File API/Docs
- `docs/openapi.yaml` version -> `2026.2.14.2`
- `docs/openapi.json` version -> `2026.2.14.2`

## Verifikasi Operasional
- Halaman admin utama (activation, PPPoE services, plan list, community/update, notifications, plugin manager) berhasil dimuat tanpa error console browser kritikal.
- Proses `Update Database` berhasil dijalankan hingga selesai setelah hardening migrasi updater.

## Catatan Deployment
- Jalankan update dari admin (`/update.php`) sampai Step 6 selesai.
- Pastikan cron aktif karena usage PPPoE per aktivasi bergantung pada sampling berkala.
- Jika router tidak punya PPPoE server/service, penyimpanan plan tetap boleh, tetapi sinkronisasi binding akan memberi warning.
