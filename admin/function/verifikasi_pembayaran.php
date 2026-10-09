<?php
require_once __DIR__ . '/../login/auth.php';
wajibRole(['admin']);   // hanya admin yang boleh masuk

// admin/function/verifikasi_pembayaran.php
// Proses verifikasi pembayaran: setujui atau tolak.
// Hanya menerima POST supaya status tidak berubah hanya karena link dibuka/di-prefetch.

include_once __DIR__ . '/../database/koneksi.php';

$aksi    = $_GET['aksi'] ?? '';
$kembali = '/e-ticket-bus/admin/index.php?page=verifikasi_pembayaran';

// ===== Nilai status di kolom payments.status (SESUAIKAN dengan databasemu) =====
$STATUS_MENUNGGU = 'pending';
$STATUS_LUNAS    = 'lunas';
$STATUS_DITOLAK  = 'ditolak';
// ================================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($aksi, ['setujui', 'tolak'], true)) {
    $id = (int) ($_POST['id'] ?? 0);

    // Ambil data pembayaran
    $ambil = $koneksi->prepare("SELECT id, bukti_transfer, status FROM payments WHERE id = ?");
    $ambil->bind_param('i', $id);
    $ambil->execute();
    $bayar = $ambil->get_result()->fetch_assoc();

    if (!$bayar) {
        header("Location: $kembali");
        exit;
    }

    // Hanya pembayaran yang masih menunggu yang boleh diproses
    if ($bayar['status'] !== $STATUS_MENUNGGU) {
        header("Location: $kembali&pesan=sudah_diproses");
        exit;
    }

    if ($aksi === 'setujui') {
        // Tidak bisa disetujui kalau belum ada bukti transfer
        if (trim((string) $bayar['bukti_transfer']) === '') {
            header("Location: $kembali&aksi=detail&id=$id&pesan=tanpa_bukti");
            exit;
        }
        $status_baru = $STATUS_LUNAS;
        $pesan_ok    = 'setujui_ok';
    } else {
        $status_baru = $STATUS_DITOLAK;
        $pesan_ok    = 'tolak_ok';
    }

    // WHERE status = menunggu: mencegah dua admin memproses pembayaran yang sama bersamaan
    $ubah = $koneksi->prepare("UPDATE payments SET status = ? WHERE id = ? AND status = ?");
    $ubah->bind_param('sis', $status_baru, $id, $STATUS_MENUNGGU);
    $ubah->execute();

    header("Location: $kembali&pesan=$pesan_ok");
    exit;
}

// Aksi tidak dikenal / bukan POST
header("Location: $kembali");
exit;