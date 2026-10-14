<?php
/**
 * ============================================================
 * FILE         : admin/function/notif_pembayaran.php
 * FUNGSI       : Endpoint JSON kecil: daftar pembayaran yang MENUNGGU verifikasi.
 *                Dipanggil otomatis tiap beberapa detik oleh assets/js/notif_pembayaran.js
 *                supaya admin tahu ada pembayaran masuk tanpa memuat ulang halaman.
 * DIPANGGIL DARI: assets/js/notif_pembayaran.js (bukan dibuka manual)
 * ============================================================
 */
require_once __DIR__ . '/../login/auth.php';
wajibRole(['admin']);
session_write_close();   // lepas kunci session, supaya request berkala ini tidak memperlambat halaman lain

include_once __DIR__ . '/../database/koneksi.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $jumlah = (int) $koneksi->query("SELECT COUNT(*) AS n FROM payments WHERE status = 'pending'")->fetch_assoc()['n'];

    // 8 pembayaran menunggu terbaru (untuk daftar di ikon lonceng)
    $hasil = $koneksi->query(
        "SELECT p.id, p.metode_pembayaran, b.kode_booking, u.nama,
                s.kota_asal, s.kota_tujuan, s.harga * b.jumlah_dewasa + b.kode_unik AS total
         FROM payments p
         JOIN bookings  b ON b.id = p.booking_id
         JOIN users     u ON u.id = b.user_id
         JOIN schedules s ON s.id = b.schedule_id
         WHERE p.status = 'pending'
         ORDER BY p.id DESC
         LIMIT 8"
    );

    $daftar = [];
    foreach ($hasil->fetch_all(MYSQLI_ASSOC) as $r) {
        $daftar[] = [
            'id'     => (int) $r['id'],
            'kode'   => $r['kode_booking'],
            'nama'   => $r['nama'],
            'rute'   => $r['kota_asal'] . ' → ' . $r['kota_tujuan'],
            'metode' => $r['metode_pembayaran'],
            'total'  => (int) round((float) $r['total']),
        ];
    }

    // Semua id yang menunggu, dipakai browser untuk mendeteksi pembayaran BARU
    $ids = array_map('intval', array_column(
        $koneksi->query("SELECT id FROM payments WHERE status = 'pending'")->fetch_all(MYSQLI_ASSOC),
        'id'
    ));

    echo json_encode(['jumlah' => $jumlah, 'ids' => $ids, 'daftar' => $daftar], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('notif_pembayaran: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => true]);
}