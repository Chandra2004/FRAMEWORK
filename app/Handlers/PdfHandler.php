<?php

namespace TheFramework\Handlers;

use TheFramework\App\Http\View;
use Exception;

/**
 * 📄 PdfHandler — Framework v5.1 PDF Generator
 * 
 * Wrapper untuk Dompdf. Sangat mudah digunakan untuk mencetak tagihan,
 * tiket, atau laporan. Otomatis mendukung metode renderToString() dari Blade.
 * 
 * 📦 Persiapan:
 * composer require dompdf/dompdf
 */
class PdfHandler
{
    protected $dompdf;
    protected array $options = [];

    public function __construct()
    {
        if (!class_exists('\Dompdf\Dompdf') || !class_exists('\Dompdf\Options')) {
            throw new Exception("Dompdf tidak ditemukan! Silakan install dengan: composer require dompdf/dompdf");
        }

        // Trick instansiasi dinamis agar IDE tidak menganggapnya sebagai error "Undefined Class" 
        // jika dompdf belum di-install via Composer.
        $optionsClass = '\Dompdf\Options';
        $dompdfClass  = '\Dompdf\Dompdf';

        $options = new $optionsClass();
        $options->set('isRemoteEnabled', true); // Mengizinkan load gambar/CSS eksternal (CDN/URL)
        $options->set('isHtml5ParserEnabled', true);
        
        $this->dompdf = new $dompdfClass($options);
        $this->dompdf->setPaper('A4', 'portrait'); // Default ukuran A4
    }

    /**
     * Konfigurasi ukuran dan orientasi kertas (A4, Letter, legal, dll).
     */
    public function setPaper(string $size, string $orientation = 'portrait'): self
    {
        $this->dompdf->setPaper($size, $orientation);
        return $this;
    }

    /**
     * Setel Custom Options bawaan Dompdf.
     */
    public function setOption(string $key, $value): self
    {
        $options = $this->dompdf->getOptions();
        $options->set($key, $value);
        $this->dompdf->setOptions($options);
        return $this;
    }

    /**
     * Load dari string HTML murni.
     */
    public function loadHtml(string $html): self
    {
        $this->dompdf->loadHtml($html);
        return $this;
    }

    /**
     * Load dari View (Blade / Native PHP).
     * @param string $view Nama view (contoh: 'admin.invoice')
     * @param array $data Data yang dipassing ke view
     */
    public function loadView(string $view, array $data = []): self
    {
        $html = View::renderToString($view, $data);
        if ($html === false) {
            throw new Exception("Gagal memuat view untuk PDF: [{$view}]");
        }
        $this->dompdf->loadHtml($html);
        return $this;
    }

    /**
     * Tampilkan PDF langsung ke Browser (Preview / Cetak).
     * @param string $filename Nama file ketika user mengklik Save (Contoh: invoice.pdf)
     */
    public function stream(string $filename = 'document.pdf')
    {
        $this->dompdf->render();
        $this->dompdf->stream($filename, [
            "Attachment" => false // false = Preview di browser, true = Langsung download
        ]);
        exit;
    }

    /**
     * Paksa Download PDF.
     */
    public function download(string $filename = 'document.pdf')
    {
        $this->dompdf->render();
        $this->dompdf->stream($filename, [
            "Attachment" => true // Paksa download
        ]);
        exit;
    }

    /**
     * Simpan file PDF ke server (biasanya ke folder storage).
     * Berguna jika ingin dikirim sebagai lampiran email.
     * 
     * @param string $path Path penyimpanan relatif terhadap ROOT_DIR
     */
    public function save(string $path): bool
    {
        $this->dompdf->render();
        $output = $this->dompdf->output();

        $fullPath = defined('ROOT_DIR') ? ROOT_DIR . '/' . ltrim($path, '/') : $path;

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($fullPath, $output) !== false;
    }
}
