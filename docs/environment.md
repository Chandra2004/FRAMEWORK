> **Version**: 4.0.0 | **Author**: Chandra Tri A | **Updated**: 2025

# 🌍 Environment & Error Handling

Framework ini memiliki sistem environment yang canggih untuk memastikan keamanan dan kenyamanan developer.
Anda dapat mengatur environment melalui file `.env` pada variabel `APP_ENV`.

## 4 Level Environment

### 1. Local (`APP_ENV=local`)

- **Fungsi**: Untuk development di komputer lokal.
- **Error**: Menampilkan **Full Stack Trace** (pesan error lengkap, baris kode, variabel).
- **WAF**: Hanya mencatat log (Log Warning), tidak memblokir agar coding lancar.

### 2. Development (`APP_ENV=development`)

- **Fungsi**: Untuk server development tim.
- **Error**: Menampilkan **Short Error Message** (hanya pesan inti, tanpa stack trace).
- **WAF**: Mulai aktif (Strict Mode).

### 3. Testing / Staging (`APP_ENV=testing`)

- **Fungsi**: Untuk QA Tester sebelum rilis.
- **Error**: Menampilkan **Error ID Unik** (contoh: `#A1B2C3`). User melapor kode ini ke developer, developer cek log server. Detail error **tidak** muncul di layar.

### 4. Production (`APP_ENV=production`)

- **Fungsi**: Untuk User Asli (Live).
- **Error**: Menampilkan **Halaman Cantik** (Error 500) yang sopan ("We are currently experiencing issues"). Tidak ada detail teknis sama sekali.
- **Security**: Strict HTTPS, Secure Cookies, WAF Blocking Mode.

---

## 🎨 Halaman Error (Custom Error Pages)

Framework sudah dilengkapi halaman error estetik di `resources/views/errors/`:

- **403 Forbidden**: Tema "Cyber Security / Terminal Block".
- **404 Not Found**: Tema "Lost in Space".
- **500 Server Error**: Tema "System Failure" (Dark Minimalist).

Anda dapat mengubah desain halaman ini sesuai branding aplikasi Anda.
