<?php
/**
 * ============================================================
 * FILE         : penumpang/function/pembayaran.php
 * FUNGSI       : Daftar metode bayar (QRIS & transfer bank, tanpa tunai) + simpan bukti pembayaran ke tabel payments.
 * DIPANGGIL DARI: index.php; dipakai pages/tiket/pembayaran.php
 * CATATAN      : Nomor rekening di DETAIL_METODE hanya contoh, ganti dengan rekening asli.
 *                Bukti disimpan di folder uploads/bukti/ (di root e-ticket-bus), dibaca juga oleh halaman admin.
 * ============================================================
 */
/** Pembayaran & upload bukti transfer */

/**
 * Metode pembayaran untuk PENUMPANG (semuanya non-tunai, bukti diunggah lalu dicek admin).
 * Nama metode (kunci array) disimpan apa adanya ke kolom payments.metode_pembayaran,
 * jadi namanya sengaja dibiarkan sama seperti sebelumnya supaya database tidak perlu diubah.
 *
 * Mau ganti rekening? Cukup ubah 'rek' dan 'an' di bawah. Mau tambah bank baru? Salin satu blok 'bank'.
 *   grup  : 'qris' | 'bank'  (menentukan judul kelompok & isi panel)
 *   ikon  : kelas FontAwesome untuk ikon di daftar
 *   warna : warna lingkaran ikon
 */
const DETAIL_METODE = [
    'QRIS' => [
        'grup'  => 'qris',
        'label' => 'QRIS',
        'sub'   => 'GoPay, OVO, DANA, ShopeePay, atau m-banking apa pun',
        'ikon'  => 'fa-qrcode',
        'warna' => '#e11d48',
        'badge' => 'Paling praktis',
    ],
    'Transfer BCA' => [
        'grup'  => 'bank',
        'label' => 'Bank BCA',
        'sub'   => 'Transfer manual lewat m-banking / ATM',
        'ikon'  => 'fa-building-columns',
        'warna' => '#0060af',
        'bank'  => 'BCA',
        'rek'   => '1234567890',
        'an'    => 'E-Ticket Bus',
    ],
    'Transfer BRI' => [
        'grup'  => 'bank',
        'label' => 'Bank BRI',
        'sub'   => 'Transfer manual lewat BRImo / ATM',
        'ikon'  => 'fa-building-columns',
        'warna' => '#00529c',
        'bank'  => 'BRI',
        'rek'   => '0987654321',
        'an'    => 'E-Ticket Bus',
    ],
    'Transfer Mandiri' => [
        'grup'  => 'bank',
        'label' => 'Bank Mandiri',
        'sub'   => 'Transfer manual lewat Livin\' / ATM',
        'ikon'  => 'fa-building-columns',
        'warna' => '#f5a800',
        'bank'  => 'Mandiri',
        'rek'   => '1122334455',
        'an'    => 'E-Ticket Bus',
    ],
];

// Daftar sederhana (nama => teks) dipakai untuk validasi metode di simpan_pembayaran()
const METODE_BAYAR = [
    'QRIS'             => 'Scan kode QRIS (bisa dari m-banking, DANA, GoPay, OVO, ShopeePay, dll.)',
    'Transfer BCA'     => 'BCA 1234567890 a.n. E-Ticket Bus',
    'Transfer BRI'     => 'BRI 0987654321 a.n. E-Ticket Bus',
    'Transfer Mandiri' => 'Mandiri 1122334455 a.n. E-Ticket Bus',
];

// Judul kelompok di halaman pembayaran
const GRUP_METODE = [
    'qris' => 'Dompet digital & QRIS',
    'bank' => 'Transfer bank',
];

/** 'qris' (tampil gambar QR) atau 'rekening' (tampil nomor rekening) */
function jenis_metode(string $metode): string
{
    return (DETAIL_METODE[$metode]['grup'] ?? 'bank') === 'qris' ? 'qris' : 'rekening';
}

// Gambar QRIS toko/perusahaan. Letakkan file QRIS aslimu di penumpang/assets/img/qris.png
const FILE_QRIS = __DIR__ . '/../assets/img/qris.png';
const URL_QRIS  = '/e-ticket-bus/penumpang/assets/img/qris.png';

const FOLDER_BUKTI = __DIR__ . '/../../uploads/bukti/';   // lokasi folder di komputer/server
const URL_BUKTI    = '/e-ticket-bus/uploads/bukti/';       // alamat folder di browser

/** Simpan bukti pembayaran. Mengembalikan [berhasil, pesan]. */
function simpan_pembayaran(mysqli $conn, array $booking, string $metode, array $file): array
{
    if (!isset(METODE_BAYAR[$metode])) {
        return [false, 'Metode pembayaran tidak valid.'];
    }
    if (in_array($booking['status_bayar'] ?? '', ['lunas', 'pending'], true)) {
        return [false, 'Pembayaran untuk tiket ini sudah dikirim.'];
    }
    // Bus sudah berangkat: tiket tidak berguna lagi (biasanya sudah dihapus otomatis sebelum sampai sini)
    if (!empty($booking['sudah_berangkat'])) {
        return [false, 'Jadwal ini sudah berangkat, jadi pembayaran tidak bisa dikirim lagi.'];
    }
    // Pesanan yang lewat batas waktu biasanya sudah dihapus otomatis sebelum sampai sini; ini pengaman tambahan
    if (isset($booking['sisa_detik']) && (int) $booking['sisa_detik'] <= 0) {
        return [false, 'Batas waktu pembayaran sudah habis. Silakan pesan kursi lagi.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, 'Silakan unggah bukti pembayaran.'];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return [false, 'Ukuran bukti maksimal 2 MB.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ekstensi = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if ($ekstensi === null) {
        return [false, 'Bukti harus berupa gambar JPG, PNG, atau WEBP.'];
    }

    if (!is_dir(FOLDER_BUKTI)) {
        mkdir(FOLDER_BUKTI, 0775, true);
    }
    $nama = $booking['kode_booking'] . '-' . bin2hex(random_bytes(4)) . '.' . $ekstensi;
    if (!move_uploaded_file($file['tmp_name'], FOLDER_BUKTI . $nama)) {
        return [false, 'Gagal menyimpan file bukti.'];
    }

    $bid = (int) $booking['id'];
    try {
        $conn->begin_transaction();

        if (!empty($booking['payment_id'])) {            // sebelumnya ditolak admin -> kirim ulang
            $pid  = (int) $booking['payment_id'];
            // alasan_penolakan dikosongkan lagi karena bukti baru sudah dikirim
            $stmt = $conn->prepare("UPDATE payments SET metode_pembayaran = ?, bukti_transfer = ?, status = 'pending', alasan_penolakan = NULL WHERE id = ?");
            $stmt->bind_param('ssi', $metode, $nama, $pid);
        } else {
            $stmt = $conn->prepare("INSERT INTO payments (booking_id, metode_pembayaran, bukti_transfer, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param('iss', $bid, $metode, $nama);
        }
        $stmt->execute();

        // Bukti sudah masuk -> hentikan hitung mundur. Mulai saat ini giliran admin memeriksa,
        // dan pesanan tidak akan dibatalkan otomatis walau admin belum sempat memverifikasi.
        $stop = $conn->prepare('UPDATE bookings SET batas_bayar = NULL WHERE id = ?');
        $stop->bind_param('i', $bid);
        $stop->execute();

        $conn->commit();
    } catch (Throwable $e) {
        // Paling sering: pesanan baru saja dibatalkan otomatis (batas waktu habis) saat bukti dikirim
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        @unlink(FOLDER_BUKTI . $nama);
        return [false, 'Pemesanan ini sudah dibatalkan karena melewati batas waktu pembayaran. Silakan pesan kursi lagi.'];
    }
    return [true, 'Bukti pembayaran terkirim. Admin akan memverifikasi sebentar lagi.'];
}