# 🛡️ Data Encryption (v5.0.1)

Modul **Crypter** menyediakan layanan enkripsi dua arah (Authenticated Encryption) yang aman untuk melindungi data sensitif di database Anda. Berbeda dengan _hashing_ yang bersifat satu arah (seperti password), enkripsi memungkinkan Anda menyembunyikan data namun tetap bisa membacanya kembali di kemudian hari.

---

## 📑 Daftar Isi

- [Filosofi: Enkripsi vs Hashing](#-filosofi-enkripsi-vs-hashing)
- [Konfigurasi Awal](#-konfigurasi-awal)
- [Penggunaan Dasar](#-penggunaan-dasar)
- [Manfaat & Keunggulan](#-manfaat--keunggulan)
- [Praktik Keamanan Terbaik](#-praktik-keamanan-terbaik)
- [Spesifikasi Teknis](#-spesifikasi-teknis-v501)

---

## 🧠 Filosofi: Enkripsi vs Hashing

Penting untuk memahami kapan harus menggunakan enkripsi dan kapan harus menggunakan hashing:

| Fitur        | **Encryption (Crypter)**          | **Hashing (Helper::hash_password)** |
| :----------- | :-------------------------------- | :---------------------------------- |
| **Sifat**    | Dua Arah (Dapat dikembalikan)     | Satu Arah (Permanen)                |
| **Kegunaan** | API Keys, Secret Tokens, Data KTP | Password, PIN User                  |
| **Metode**   | AES-256-CBC + HMAC                | BCRYPT / Argon2                     |
| **Output**   | String terenkripsi panjang        | Hash tetap (60-255 karakter)        |

---

## ⚙️ Konfigurasi Awal

Layanan enkripsi sangat bergantung pada kunci rahasia aplikasi Anda (**`APP_KEY`**). Tanpa kunci ini, data tidak akan bisa dibuka.

### 1. Generate Key

Jalankan perintah berikut di terminal untuk mendapatkan kunci yang kuat:

```bash
php artisan key:generate
```

### 2. Setup .env

Kunci tersebut akan disimpan di file `.env` Anda:

```bash
APP_KEY=base64:7vFw+9JdQ3X9R7Y...
```

> [!CAUTION]
> **JANGAN PERNAH** membagikan `APP_KEY` Anda di Git atau ke publik. Jika kunci ini hilang, seluruh data terenkripsi di database Anda tidak akan pernah bisa dibuka lagi!

---

## 🚀 Penggunaan Dasar

Gunakan namespace `TheFramework\Helpers\Crypter` di controller atau service Anda.

### 1. Mengenkripsi Data

Enkripsi digunakan sebelum menyimpan data sensitif ke database.

```php
use TheFramework\Helpers\Crypter;

// Data asli
$apiKey = "SG.928jks0293js02938js02983j";

// Enkripsi
$encrypted = Crypter::encrypt($apiKey);

// Simpan ke database
User::where('id', 1)->update(['secret_key' => $encrypted]);
```

### 2. Mendekripsi Data

Gunakan saat Anda butuh membaca kembali nilai asli data tersebut.

```php
// Ambil dari database
$user = User::find(1);

try {
    // Dekripsi nilai
    $decrypted = Crypter::decrypt($user->secret_key);

    echo "API Key Anda: " . $decrypted;
} catch (Exception $e) {
    // Terjadi jika data rusak atau APP_KEY salah
    echo "Gagal mendekripsi data: " . $e->getMessage();
}
```

---

## 💎 Manfaat & Keunggulan

Modul **The Framework Crypter** didesain dengan standar militer untuk menjamin keamanan aplikasi Anda:

1.  **Authenticated Encryption**: Kami menggunakan **HMAC-SHA256** untuk memastikan data tidak dimanipulasi orang lain (Tamper-proof). Jika ada yang mencoba mengubah 1 karakter saja pada data terenkripsi, proses dekripsi akan langsung menolak.
2.  **Unbreakable Standard**: Menggunakan algoritma **AES-256-CBC**, standar enkripsi tercepat dan paling aman saat ini yang digunakan oleh pemerintah dan bank global.
3.  **Unique Output per Encryption**: Meskipun Anda mengenkripsi kata yang sama ("rahasia") berkali-kali, hasilnya akan selalu berbeda berkat penggunaan **IV (Initialization Vector)** acak. Ini mencegah serangan pola data (_Frequency Analysis_).
4.  **Timing-Attack Protection**: Verifikasi data menggunakan fungsi `hash_equals` untuk mencegah penyerang menebak isi data melalui kecepatan respon server.

---

## 🛡️ Praktik Keamanan Terbaik

- **Backup .env**: Selalu simpan cadangan `APP_KEY` Anda di tempat aman (Password Manager atau Vault).
- **Key Rotation**: Jika Anda menduga `APP_KEY` telah bocor, Anda harus segera melakukan rotasi kunci. Namun, ingatlah bahwa data lama harus didekripsi dengan kunci lama dan dienkripsi ulang dengan kunci baru.
- **Minimalisir Enkripsi**: Gunakan enkripsi hanya untuk field yang benar-benar sensitif. Mengenkripsi seluruh kolom database bisa sedikit membebani performa CPU server.
- **HTTPS (Wajib)**: Enkripsi di sisi server tidak berguna jika data dikirim melalui HTTP biasa. Pastikan SSL aktif agar data terlindungi saat perjalanan (in-transit).

---

## 📊 Spesifikasi Teknis (v5.0.1)

- **Library**: PHP OpenSSL
- **Cipher**: `AES-256-CBC`
- **Integrity**: `HMAC-SHA256` (Authenticated Encryption)
- **Key Length**: 32 Bytes (256-bit)
- **Encoding**: Base64 URL-Safe
- **Format Payload**: `base64(IV + EncryptedData) . "." . HMAC`

---

<div align="center">

**The Framework Security — Protect what matters most.**

[Kembali ke Dokumentasi](README.md)

</div>
