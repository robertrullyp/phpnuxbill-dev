# Panduan Mudah Setup FreeRADIUS untuk PHPNuxBill (Ubuntu 24.04)

## Tujuan dokumen ini
Panduan ini dibuat untuk user awam agar bisa:
1. Install FreeRADIUS.
2. Menghubungkan FreeRADIUS ke database sistem ini.
3. Memastikan service benar-benar berjalan.
4. Mengaktifkan mode FreeRADIUS di panel admin.

Jika semua langkah di bawah dilakukan, backend `freeradius_mysql` siap dipakai.

## Ringkasan cepat
- FreeRADIUS = server autentikasi.
- Database radius yang dipakai sistem ini = database yang diset di `config.php`.
- Port utama RADIUS:
  - `1812/UDP` untuk login (auth)
  - `1813/UDP` untuk accounting (usage/session)

## Catatan keamanan penting
- Jangan tulis password asli ke dokumentasi publik.
- Gunakan placeholder seperti `<RADIUS_DB_PASSWORD>` saat membuat panduan internal.
- Pastikan file `config.php` hanya bisa diakses admin/server.

## Sebelum mulai
Pastikan Anda punya:
- akses `sudo` ke server
- MySQL/MariaDB aktif
- file `config.php` aplikasi sudah berisi setting radius yang benar

Gunakan parameter dari `config.php`:
- `<RADIUS_DB_HOST>`
- `<RADIUS_DB_NAME>`
- `<RADIUS_DB_USER>`
- `<RADIUS_DB_PASSWORD>`

## Langkah 1 - Install paket
Jalankan:

```bash
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y freeradius freeradius-mysql freeradius-utils
```

## Langkah 2 - Aktifkan modul SQL
Aktifkan modul SQL agar FreeRADIUS membaca data dari MySQL.

```bash
sudo ln -s ../mods-available/sql /etc/freeradius/3.0/mods-enabled/sql
```

Jika muncul `File exists`, artinya modul sudah aktif.

## Langkah 3 - Edit konfigurasi SQL FreeRADIUS
File yang diedit:
- `/etc/freeradius/3.0/mods-available/sql`

Pastikan nilai penting ini benar:

```text
dialect = "mysql"
driver = "rlm_sql_${dialect}"

server = "<RADIUS_DB_HOST>"
port = 3306
login = "<RADIUS_DB_USER>"
password = "<RADIUS_DB_PASSWORD>"
radius_db = "<RADIUS_DB_NAME>"

read_clients = yes
```

Catatan:
- Jika MySQL lokal tidak memakai SSL, `tls_required` bisa diset `no`.

## Langkah 4 - Validasi konfigurasi
Jalankan:

```bash
sudo freeradius -XC
```

Jika benar, akan muncul:
- `Configuration appears to be OK`

## Langkah 5 - Jalankan service
Jalankan:

```bash
sudo systemctl enable --now freeradius
sudo systemctl status freeradius
```

Status yang diharapkan:
- `active (running)`

## Langkah 6 - Cek port RADIUS
Jalankan:

```bash
ss -lunp | grep -E ':1812|:1813'
```

Harus terlihat port `1812` dan `1813` dalam keadaan listen.

## Langkah 7 - Cek tabel radius di database
Jalankan:

```bash
mysql -h<RADIUS_DB_HOST> -u<RADIUS_DB_USER> -p'<RADIUS_DB_PASSWORD>' -D <RADIUS_DB_NAME> \
  -e "SHOW TABLES LIKE 'rad%'; SHOW TABLES LIKE 'nas';"
```

Minimal harus ada tabel:
- `radcheck`, `radreply`, `radgroupreply`, `radusergroup`, `radacct`, `nas`

## Langkah 8 - Uji autentikasi cepat (opsional)
Contoh test local menggunakan `radtest`:

```bash
radtest -x <TEST_USER> <TEST_PASSWORD> 127.0.0.1 0 <RADIUS_CLIENT_SECRET>
```

Jika sukses, hasilnya `Access-Accept`.

## Langkah 9 - Aktifkan mode FreeRADIUS di aplikasi
Di panel admin aplikasi:
1. Buka `Settings -> App`.
2. Set:
   - `Radius Enable = ON`
   - `Radius Backend = FreeRADIUS (MySQL)`
3. Simpan.

## Langkah 10 - Tambahkan NAS router
Agar router bisa autentikasi ke server RADIUS:
1. Buka menu `Radius -> NAS List`.
2. Tambahkan data router (NAS):
   - NAS IP (`nasname`) = IP router
   - Secret = harus sama persis dengan secret di router
3. Simpan.

Catatan:
- Jika tabel `nas` masih kosong, router belum bisa auth ke FreeRADIUS.

## Checklist akhir
- [ ] `freeradius -XC` -> `Configuration appears to be OK`
- [ ] `systemctl status freeradius` -> `active (running)`
- [ ] Port `1812/1813` listen
- [ ] Tabel `rad*` dan `nas` tersedia
- [ ] Backend aplikasi sudah `freeradius_mysql`
- [ ] NAS router sudah ditambahkan dan secret cocok

## Troubleshooting singkat

### 1) `Access-Reject`
Cek:
- user ada di `radcheck`
- password user benar
- secret client cocok

### 2) Service tidak mau start
Cek:
```bash
sudo freeradius -XC
sudo journalctl -u freeradius -n 100 --no-pager
```

### 3) Router tidak bisa auth
Cek:
- NAS IP dan secret di tabel `nas`
- firewall server membuka UDP 1812/1813
- router diarahkan ke IP server FreeRADIUS yang benar

## Rollback konfigurasi SQL
Jika Anda punya backup file sql module:

```bash
sudo cp /etc/freeradius/3.0/mods-available/sql.bak-<timestamp> /etc/freeradius/3.0/mods-available/sql
sudo systemctl restart freeradius
```
