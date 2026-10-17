<?php
/**
 * ============================================================
 * FILE         : e-ticket-bus/includes/bus_helper.php
 * FUNGSI       : Data bersama admin & penumpang untuk bus: daftar fasilitas, tata letak kursi,
 *                lokasi foto, serta fungsi simpan/hapus foto.
 * DIPANGGIL DARI: admin/function/bus.php, admin/pages/bus/tambah.php & update.php,
 *                penumpang/function/komponen_card_jadwal.php
 * CATATAN      : Mau tambah fasilitas baru? Cukup tambah satu baris di FASILITAS_BUS.
 * ============================================================
 */

require_once __DIR__ . '/base_url.php';

const DIR_FOTO_BUS = __DIR__ . '/../uploads/bus/';    // lokasi folder foto di komputer/server
const URL_FOTO_BUS = BASE_URL . '/uploads/bus/';    // alamat folder foto di browser

// kode => [nama yang tampil, ikon FontAwesome]
const FASILITAS_BUS = [
    'ac'          => ['AC', 'fa-snowflake'],
    'kursi_rebah' => ['Kursi Rebah', 'fa-chair'],
    'usb'         => ['Colokan USB', 'fa-plug'],
    'bagasi'      => ['Bagasi', 'fa-suitcase-rolling'],
    'toilet'      => ['Toilet', 'fa-restroom'],
    'wifi'        => ['WiFi', 'fa-wifi'],
    'tv'          => ['TV / Hiburan', 'fa-tv'],
    'selimut'     => ['Selimut', 'fa-bed'],
    'snack'       => ['Snack & Minum', 'fa-utensils'],
    'istirahat'   => ['Tempat Istirahat', 'fa-mug-hot'],
];

const TATA_LETAK_KURSI = ['1-1', '2-1', '2-2', '2-3'];

/** "ac,usb" -> ['ac', 'usb'] (kode yang tidak dikenal dibuang) */
function bus_fasilitas_dari_teks(?string $teks): array
{
    $hasil = [];
    foreach (explode(',', (string) $teks) as $kode) {
        $kode = trim($kode);
        if (isset(FASILITAS_BUS[$kode])) {
            $hasil[] = $kode;
        }
    }
    return $hasil;
}

/** Alamat foto untuk browser. Kosong kalau bus belum punya foto / filenya hilang. */
function bus_url_foto(?string $nama): string
{
    $nama = basename((string) $nama);
    if ($nama === '' || !is_file(DIR_FOTO_BUS . $nama)) {
        return '';
    }
    return URL_FOTO_BUS . rawurlencode($nama);
}

/**
 * Simpan foto bus yang diunggah ke folder uploads/bus.
 * Hasil: nama file baru (sukses) | '' (tidak ada file dipilih) | false (ada kesalahan, alasan di $pesan)
 */
function bus_simpan_foto(?array $berkas, string &$pesan)
{
    $pesan = '';
    if (!$berkas || ($berkas['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($berkas['error'] !== UPLOAD_ERR_OK) {
        $pesan = 'Foto gagal diunggah. Coba lagi.';
        return false;
    }
    if ($berkas['size'] > 2 * 1024 * 1024) {
        $pesan = 'Ukuran foto maksimal 2 MB.';
        return false;
    }

    // Cek ISI file benar-benar gambar (bukan hanya melihat ekstensinya)
    $mime     = (new finfo(FILEINFO_MIME_TYPE))->file($berkas['tmp_name']);
    $ekstensi = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if ($ekstensi === null) {
        $pesan = 'Format foto harus JPG, PNG, atau WEBP.';
        return false;
    }

    if (!is_dir(DIR_FOTO_BUS) && !mkdir(DIR_FOTO_BUS, 0775, true)) {
        $pesan = 'Folder uploads/bus belum ada dan gagal dibuat. Buat manual dulu.';
        return false;
    }

    $nama = 'bus_' . bin2hex(random_bytes(8)) . '.' . $ekstensi;   // nama acak
    if (!move_uploaded_file($berkas['tmp_name'], DIR_FOTO_BUS . $nama)) {
        $pesan = 'Foto gagal disimpan ke folder uploads/bus.';
        return false;
    }
    return $nama;
}

/** Hapus file foto dari folder (aman dipanggil walau file tidak ada) */
function bus_hapus_foto(?string $nama): void
{
    $nama = basename((string) $nama);
    if ($nama !== '' && is_file(DIR_FOTO_BUS . $nama)) {
        @unlink(DIR_FOTO_BUS . $nama);
    }
}