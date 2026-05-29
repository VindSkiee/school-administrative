# Setup Project School Administrative

Panduan ini menjelaskan cara menjalankan project dari nol di Windows dengan Laragon, mulai dari menyalakan service, membuat database, menjalankan backend Laravel, sampai membuka web frontend.

Repo: https://github.com/VindSkiee/school-administrative.git

## 1. Kebutuhan Awal

- Laragon
- PHP 8.3
- Composer
- Node.js dan npm
- MySQL dari Laragon

## 2. Jalankan Laragon

1. Buka Laragon.
2. Klik **Start All** supaya Apache dan MySQL aktif.
3. Pastikan status service hijau.

Catatan: backend project ini paling aman dijalankan dengan `php artisan serve` di port `8000`, karena frontend Vite sudah mengarah ke `http://localhost:8000` lewat proxy API.

## 3. Buat Database

1. Buka **Laragon > Database > phpMyAdmin** atau tools database lain yang biasa dipakai.
2. Buat database baru dengan nama:

```text
school_administrative
```

3. Jika memakai setting default Laragon, gunakan konfigurasi berikut di backend:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_administrative
DB_USERNAME=root
DB_PASSWORD=
```

## 4. Setup Backend Laravel

Buka terminal di folder `backend`, lalu jalankan:

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
```

Kalau file `.env` sudah ada, cukup pastikan nilai berikut sesuai:

```env
APP_URL=http://localhost:8000
DB_DATABASE=school_administrative
DB_USERNAME=root
DB_PASSWORD=
```

### Kenapa perlu `jwt:secret`?

Project ini memakai JWT untuk autentikasi, jadi secret token harus dibuat sebelum aplikasi dipakai.

### Kenapa perlu `migrate --seed`?

Seeder project ini akan mengisi data awal seperti admin, principal, guru, siswa, kelas, dan mapel. Password default untuk akun seed adalah:

```text
password123
```

Contoh akun awal:

- `euis.herlina@sekolah.com`
- `principal@sekolah.com`

## 5. Setup Frontend Vue

Buka terminal baru di folder `sms-frontend`, lalu jalankan:

```bash
npm install
npm run dev
```

Frontend memakai Vite dev server dan secara default membaca API dari `/api`. Karena konfigurasi Vite mem-proxy `/api` ke `http://localhost:8000`, backend harus tetap berjalan di port itu.

## 6. Jalankan Backend

Di terminal lain, dari folder `backend`, jalankan:

```bash
php artisan serve
```

Backend biasanya akan aktif di:

```text
http://localhost:8000
```

## 7. Akses Web

1. Buka frontend di browser:

```text
http://localhost:5173
```

2. Jika ingin cek backend, buka:

```text
http://localhost:8000/health
```

3. Login menggunakan akun hasil seeder dengan password `password123`.

## 8. Urutan Singkat

Kalau ingin versi paling cepat, jalankan urutan ini:

1. Start Laragon.
2. Buat database `school_administrative`.
3. Masuk ke folder `backend` lalu jalankan `composer install`, copy `.env`, `php artisan key:generate`, `php artisan jwt:secret`, dan `php artisan migrate --seed`.
4. Masuk ke folder `sms-frontend` lalu jalankan `npm install` dan `npm run dev`.
5. Jalankan `php artisan serve` dari folder `backend`.
6. Buka `http://localhost:5173`.

## 9. Kalau Ada Masalah

- Jika login gagal, pastikan seed sudah dijalankan dan coba password `password123`.
- Jika API tidak terbaca dari frontend, pastikan backend berjalan di `http://localhost:8000`.
- Jika database tidak terhubung, cek kembali nama database, username, dan password MySQL di `.env`.