<?php
/**
 * ============================================================
 * FILE         : penumpang/function/pembayaran.php
 * FUNGSI       : Daftar metode bayar + simpan bukti pembayaran (upload gambar) ke tabel payments.
 * DIPANGGIL DARI: index.php; dipakai pages/tiket/pembayaran.php
 * CATATAN      : Nomor rekening di METODE_BAYAR hanya contoh, ganti dengan rekening asli.
 *                Bukti disimpan di folder uploads/bukti/ (di root e-ticket-bus), dibaca juga oleh halaman admin.
 * ============================================================
 */
/** Pembayaran & upload bukti transfer */

const METODE_BAYAR = [
    'Transfer BCA'     => 'BCA 1234567890 a.n. E-Ticket Bus',
    'Transfer BRI'     => 'BRI 0987654321 a.n. E-Ticket Bus',
    'Transfer Mandiri' => 'Mandiri 1122334455 a.n. E-Ticket Bus',
    'QRIS'             => 'Scan QRIS di loket / kirim ke nomor 0812-0000-0000',
    'DANA'             => 'DANA 0812-0000-0000 a.n. E-Ticket Bus',
];

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
    return [true, 'Bukti pembayaran terkirim. Admin akan memverifikasi sebentar lagi.'];
}