# PHPNuxBill - PHP Mikrotik Billing

![PHPNuxBill](install/img/logo.png)

## About This Fork

This repository is a personal fork that tracks and compares changes from the original project at:

- https://github.com/hotspotbilling/phpnuxbill

Update and maintenance in this fork are done by reviewing upstream changes and selectively merging or extending them to fit my operational needs. While it is curated for my environment, the work is open for everyone to use, contribute to, and support.

Important notes:

- Install and basic usage documentation continue to follow the original project (links below).
- The built-in updater in this fork downloads release ZIPs from this fork (see `install/update.php`), while database migrations are executed from `system/updates.json` within the codebase.

## What's New in This Fork

Enhancements and changes added on top of upstream:

- Plan visibility per customer
  - New column `tbl_plans.visibility` (enum: `all`, `custom`, `exclude`) and mapping table `tbl_plan_customers`.
  - Visibility applied in list/order flows so customers only see allowed plans.
  - Admin UI to configure visibility on Add/Edit for Hotspot, PPPoE, and VPN, including multi-select customer picker.
  - List pages show per-plan visibility badges: `{Lang::T('All')}`, `{Lang::T('Exclude')}`, `{Lang::T('Include')}` with counts.
  - Safety guard to ensure the `visibility` enum contains `exclude` even if a node missed a migration.

- UI refinements
  - Standardized visibility labels across forms and lists: `{Lang::T('All')}`, `{Lang::T('Exclude')}`, `{Lang::T('Include')}`.
  - Aligned column ordering and headers (fix swapped Visibility/Time/Manage columns across Hotspot/PPPoE/VPN lists).
  - Aligned position of the Visibility section in Hotspot Add/Edit to match PPPoE/VPN; adjusted selector sizing to match inputs.

- Authentication and recovery
  - Login with email or username; consistent case-insensitive lookup for admin.
  - “Forgot” flows expanded: recover password, recover all usernames by phone/email; clearer messages and redirects.
  - Cloudflare Turnstile integrated on login screens to reduce bot traffic (configurable in settings and templates).

- Registration and phone number handling
  - Phone numbers normalized via `Lang::phoneFormat` across flows; configurable `country_code_phone` respected.
  - Duplicate phone checks on registration and profile edits; optional WhatsApp-only phone updates.
  - Configurable OTP timing: `otp_wait` (request cooldown) and `otp_expiry` (lifetime).
  - Better error handling around OTP send failures and WhatsApp fallback.

- Billing and packages
  - Optional “welcome package” support (including inactive plans for welcome selection).
  - Voucher fixes: filtering, batch selection tracking, and stability improvements.

- Plugin Manager improvements
  - Three tabs (Plugins, Payment Gateway, Devices), cache refresh, and clearer source/install actions.
  - ZIP extension checks and safer prompts to avoid partial installs.

- Update process clarifications
  - Updater default ZIP source points to this fork (`robertrullyp/phpnuxbill-dev`), while DB updates run from `system/updates.json` (idempotent).

Compatibility:

- Fresh installs: schema included in `install/phpnuxbill.sql` matches fork features.
- Upgrades: run `install/update.php` to apply `system/updates.json` migrations; the fork includes a runtime guard to keep `tbl_plans.visibility` in sync.

---

## Perbedaan dari Repository Asli (Ringkas)

Ringkasan pembaruan perbaikan dibanding repo asli (hotspotbilling/phpnuxbill):

- Visibilitas paket per pelanggan: enum `visibility` (`all/custom/exclude`) + tabel relasi `tbl_plan_customers` dan badge pada daftar paket.
- Autentikasi lebih fleksibel: bisa login via email/username, pencarian case-insensitive untuk admin.
- Pemulihan akun lebih lengkap: lupa password, kirim daftar username via telepon/WhatsApp/email sesuai konfigurasi.
- Integrasi Cloudflare Turnstile pada halaman login untuk mitigasi bot (opsional).
- Penanganan nomor telepon konsisten: normalisasi dengan `Lang::phoneFormat`, cek duplikasi saat registrasi/edit, dukungan update khusus WhatsApp.
- OTP lebih dapat dikonfigurasi: `otp_wait` dan `otp_expiry`, serta penanganan kegagalan kirim OTP/WhatsApp yang lebih jelas.
- Paket & voucher: opsi “welcome package”, perbaikan filter voucher dan pelacakan batch, serta aneka bug-fix stabilitas.
- Plugin Manager: tab terpisah (Plugin/Payment Gateway/Devices), tombol refresh cache, pemeriksaan ekstensi ZIP, alur instal lebih aman.
- Proses update: sumber ZIP bawaan diarahkan ke fork ini; migrasi DB dari `system/updates.json` (idempotent).
- Peningkatan terjemahan (ID) dan banyak perbaikan kecil lain (validasi input, pesan error, pengalihan yang tepat, dsb.).

Lihat detail lengkap di CHANGELOG untuk daftar perubahan harian/mingguan.

## Feature

- Voucher Generator and Print
- [Freeradius](https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius)
- Self registration
- User Balance
- Auto Renewal Package using Balance
- Multi Router Mikrotik
- Hotspot & PPPOE
- Easy Installation
- Multi Language
- Payment Gateway
- SMS validation for login
- Whatsapp Notification to Consumer
- Telegram Notification for Admin

See [How it Works / Cara Kerja](https://github.com/hotspotbilling/phpnuxbill/wiki/How-It-Works---Cara-kerja)

## Payment Gateway And Plugin

- [Payment Gateway List](https://github.com/orgs/hotspotbilling/repositories?q=payment+gateway)
- [Plugin List](https://github.com/orgs/hotspotbilling/repositories?q=plugin)

You can download payment gateway and Plugin from Plugin Manager

## System Requirements

Most current web servers with PHP & MySQL installed will be capable of running PHPNuxBill

Minimum Requirements

- Linux or Windows OS
- Minimum PHP Version 8.2
- Both PDO & MySQLi Support
- PHP-GD2 Image Library
- PHP-CURL
- PHP-ZIP
- PHP-Mbstring
- MySQL Version 4.1.x and above

can be Installed in Raspberry Pi Device.

The problem with windows is hard to set cronjob, better Linux

## Changelog

[CHANGELOG.md](CHANGELOG.md)

## Installation

[Installation instructions](https://github.com/hotspotbilling/phpnuxbill/wiki)

## Configuration

OTP timing can be tuned through two settings available in Admin > Settings > Miscellaneous:

- `otp_wait`: seconds before a new OTP can be requested.
- `otp_expiry`: seconds before an OTP becomes invalid.

### Phone Number Formatting

Ensure the `country_code_phone` setting is configured in Admin > Settings > Localisation. All modules should normalize phone numbers using `Lang::phoneFormat` before storing or comparing values to maintain consistency across the system.

### Upstream Tracking

This fork tracks upstream and periodically syncs or cherry-picks changes.

- Upstream: https://github.com/hotspotbilling/phpnuxbill
- Fork: https://github.com/robertrullyp/phpnuxbill-dev

Suggested local setup to compare/sync:

```
git remote add upstream https://github.com/hotspotbilling/phpnuxbill.git
git fetch upstream --prune
# Show commits unique to this fork's dev branch
git log --oneline upstream/master..dev
```

## Freeradius

Support [Freeradius with Database](https://github.com/hotspotbilling/phpnuxbill/wiki/FreeRadius)

## Community Support

- [Github Discussion](https://github.com/hotspotbilling/phpnuxbill/discussions)
- [Telegram Group](https://t.me/phpmixbill)

## Technical Support

This Software is Free and Open Source, Without any Warranty.

Even if the software is free, but Technical Support is not,
Technical Support Start from Rp 500.000 or $50

If you chat me for any technical support,
you need to pay,

ask anything for free in the [discussion](/hotspotbilling/phpnuxbill/discussions) page or [Telegram Group](https://t.me/phpnuxbill)

Contact me at [Telegram](https://t.me/robertrullyp) about this fork or
Contact me at [Telegram](https://t.me/ibnux) for the upstream repository

## Contributing & Donations (Fork)

This fork is primarily maintained to satisfy personal/operational needs, but contributions and feedback from the community are welcome. Feel free to open issues or pull requests with improvements or fixes.

If you find this fork useful and want to support its continued maintenance, you can contribute via PRs or contact the maintainer for donation options. Your support helps keep the work sustainable for everyone.

## License

GNU General Public License version 2 or later

see [LICENSE](LICENSE) file


## Thanks
We appreciate all people who are participating in this project.

<a href="https://github.com/hotspotbilling/phpnuxbill/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=hotspotbilling/phpnuxbill" />
</a>

