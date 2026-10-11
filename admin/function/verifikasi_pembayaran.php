<?php
require_once __DIR__ . '/../login/auth.php';
wajibRole(['admin']);   // hanya admin yang boleh masuk
require_once __DIR__ . '/../../includes/alasan_tolak.php';   // daftar & penyusun alasan penolakan

// admin/function/verifikasi_pembayaran.php
// Proses verifikasi pembayaran: setujui atau tolak.
// Menolak WAJIB disertai alasan (dikirim dari form di halaman detail).
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

    $alasan = null;   // hanya diisi kalau pembayaran ditolak

    if ($aksi === 'setujui') {
        // Tidak bisa disetujui kalau belum ada bukti transfer
        if (trim((string) $bayar['bukti_transfer']) === '') {
            header("Location: $kembali&aksi=detail&id=$id&pesan=tanpa_bukti");
            exit;
        }
        $status_baru = $STATUS_LUNAS;
        $pesan_ok    = 'setujui_ok';
    } else {
        // Menolak: alasan wajib dipilih ("lainnya" wajib disertai catatan)
        $alasan = susun_alasan_tolak(
            (string) ($_POST['alasan'] ?? ''),
            (string) ($_POST['catatan'] ?? '')
        );
        if ($alasan === null) {
            header("Location: $kembali&aksi=detail&id=$id&pesan=alasan_kosong");
            exit;
        }
        $status_baru = $STATUS_DITOLAK;
        $pesan_ok    = 'tolak_ok';
    }

    // WHERE status = menunggu: mencegah dua admin memproses pembayaran yang sama bersamaan.
    // Saat disetujui, $alasan bernilai null sehingga kolom alasan_penolakan dikosongkan.
    $ubah = $koneksi->prepare("UPDATE payments SET status = ?, alasan_penolakan = ? WHERE id = ? AND status = ?");
    $ubah->bind_param('ssis', $status_baru, $alasan, $id, $STATUS_MENUNGGU);
    $ubah->execute();

    header("Location: $kembali&pesan=$pesan_ok");
    exit;
}

// Aksi tidak dikenal / bukan POST
header("Location: $kembali");
exit;