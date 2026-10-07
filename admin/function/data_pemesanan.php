<?php
// admin/function/data_pemesanan.php
// Proses data pemesanan: hapus / batalkan pemesanan.
// Hanya menerima POST supaya data tidak terhapus hanya karena link dibuka.

include_once __DIR__ . '/../database/koneksi.php';

$aksi    = $_GET['aksi'] ?? '';
$kembali = '/e-ticket-bus/admin/index.php?page=data_pemesanan';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aksi === 'hapus') {
    $id = (int) ($_POST['id'] ?? 0);

    // Cek status pembayaran terbaru. Yang sudah lunas tidak boleh dibatalkan.
    $cek = $koneksi->prepare(
        "SELECT COALESCE(p.status, 'belum') AS status_bayar
         FROM bookings b
         LEFT JOIN payments p ON p.id = (SELECT MAX(id) FROM payments WHERE booking_id = b.id)
         WHERE b.id = ?"
    );
    $cek->bind_param('i', $id);
    $cek->execute();
    $baris = $cek->get_result()->fetch_assoc();

    if (!$baris) {
        header("Location: $kembali");
        exit;
    }
    if ($baris['status_bayar'] === 'lunas') {
        header("Location: $kembali&pesan=sudah_lunas");
        exit;
    }

    // Hapus pembayaran dulu (foreign key), lalu booking-nya. Satu paket: gagal salah satu = batal semua.
    try {
        $koneksi->begin_transaction();

        $hapus_bayar = $koneksi->prepare("DELETE FROM payments WHERE booking_id = ?");
        $hapus_bayar->bind_param('i', $id);
        $hapus_bayar->execute();

        $hapus_booking = $koneksi->prepare("DELETE FROM bookings WHERE id = ?");
        $hapus_booking->bind_param('i', $id);
        $hapus_booking->execute();

        $koneksi->commit();
    } catch (mysqli_sql_exception $e) {
        $koneksi->rollback();
        header("Location: $kembali&pesan=gagal");
        exit;
    }

    header("Location: $kembali&pesan=hapus_ok");
    exit;
}

// Aksi tidak dikenal / bukan POST
header("Location: $kembali");
exit;