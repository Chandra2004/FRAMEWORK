# 📄 PDF Handler (DomPDF)

The Framework menyediakan class `PdfHandler` sebagai wrapper elegan dan kuat untuk membuat file PDF dinamis langsung dari **HTML** atau **Blade Templates**. Sangat cocok untuk mencetak kwitansi, nomor antrean, resi pengiriman, hingga laporan bulanan.

---

## 📦 Instalasi

Engine ini dibackup oleh engine dari Dompdf, Anda wajib menginstalnya via Composer terlebih dahulu:

```bash
composer require dompdf/dompdf
```

> **Catatan IDE-Safe:** Framework menggunakan teknik instansiasi dinamis. Editor Anda (VS Code, dll) **tidak akan menampilkan error merah** meskipun Anda belum menginstal package ini. Error informatif baru akan muncul saat Anda mencoba menjalankan fungsi PDF di browser.

---

## 🚀 Cara Penggunaan

Fitur utamanya memungkinkan Anda merender sebuah `.blade.php` atau `.php` langsung menjadi bentuk cetakan PDF dengan opsi preview, unduh otomatis, atau simpan ke server.

### 1. Basic Contoh Kasus: Render dari Blade (Struk Pembayaran)

Anggap kita punya file `resources/views/reports/invoice.blade.php`:
```html
<h1>Invoice #{{ $id }}</h1>
<p>Halo, {{ $name }}. Terima kasih telah berbelanja senilai Rp {{ number_format($total) }}.</p>
```

Di Controller, Anda cukup panggil `PdfHandler`:

```php
use TheFramework\Handlers\PdfHandler;

public function cetakInvoice($id)
{
    $data = [
        'id' => $id,
        'name' => 'Budi',
        'total' => 150000
    ];

    $pdf = new PdfHandler();

    // Set Ukuran dan Orientasi (Opsional, Default = A4 Portrait)
    $pdf->setPaper('A4', 'landscape');

    // Memuat view Blade
    $pdf->loadView('reports.invoice', $data);

    // Tampilkan / Buka file PDF di tab baru
    $pdf->stream('Invoice_' . $id . '.pdf');
}
```

---

### 2. Opsi Mengeluarkan Hasil PDF (Output Types)

Ada tiga bentuk *output* yang bisa dilakukan. Gunakan salah satunya di bagian akhir eksekusi kode Anda:

#### a. Tampilkan Preview di Browser (`stream()`)
Paling umum digunakan. Saat tombol cetak di-klik, browser membuka tab baru yang menampilkan si PDF. 
User bisa klik icon print 🖨️ / save 💾 secara manual.
```php
$pdf->stream('Laporan_Tahunan.pdf');
```

#### b. Paksa Download (`download()`)
Begitu link diklik, file PDF langsung tersimpan di komputer user. Tidak nampil/menggantung di tab browser.
```php
$pdf->download('Data_Karyawan.pdf');
```

#### c. Simpan ke Server (`save()`)
Fungsi yang wajib digunakan saat Anda ingin men-*generate* sertifikat / invoice untuk dikirim via **Email (Attachment)**, tanpa pernah membuka jendela PDF ke monitor. File fisik akan diamankankan ke Path Server tujuan.
```php
$path = 'storage/app/invoices/INV_0021.pdf';
$pdf->save($path);

// Logika Lanjutannya:
// TheFramework\Handlers\MailHandler::to('user@mail.com')->send('E-Ticket Anda', '...', ['attachments' => [$path]]);
```

---

### 3. Load dari String / HTML Mentah

Anda tidak diwajibkan selalu pakai file View Blade/PHP. Jika teks HTML-nya sangat pendek, Anda bisa merender tipe plain text:

```php
$pdf = new PdfHandler();
$pdf->loadHtml('<h1>Struk Nomor Antrian #56</h1>');
$pdf->stream('antrian.pdf');
```

---

## 🎨 Menangani Styling & Gambar Eksternal

Engine Dompdf dapat mem-parsing elemen CSS standar dan tag `<img>`. The Framework bahkan sudah membuka limitnya dengan menambahkan flag:
- `$options->set('isRemoteEnabled', true);` 

Yang berarti **Anda bisa memasukkan CDN Gambar / CSS eksternal** tanpa error DOM!

```html
<!-- Cdn style langsung bekerja tanpa pusing -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<!-- Gambar URL berjalan mulus -->
<img src="https://upload.wikimedia.org/wikipedia/commons/logo.png" width="100">
```

> **TIPS CSS UNTUK DOMPDF:**
> Sistem parser Dompdf lebih menyukai style inline (`<h1 style="color:red">`) atau internal block `<style>` dibanding file css raksasa, karena sistem dom-render butuh kompresi. Sedapat mungkin sederhanakan desain jika laporan *stuck* loading terlalu lama.

---

## 🛠 Konfigurasi Manual

Bagi Anda yang sudah terbiasa dengan Dompdf dan butuh menyuntikkan setting kustom tingkat dewa, silakan injeksikan config Anda melewati `setOption($key, $value)`.

```php
$pdf = new PdfHandler();
$pdf->setOption('defaultFont', 'Courier');
$pdf->setOption('isFontSubsettingEnabled', true);
```
