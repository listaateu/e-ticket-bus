<?php
require_once __DIR__ . '/../login/auth.php';
wajibRole(['admin']);   // hanya admin yang boleh masuk
require_once __DIR__ . '/../../includes/alasan_tolak.php';   // daftar & penyusun alasan penolakan
require_once __DIR__ . '/../../includes/batas_bayar.php';    // batas waktu bayar (dimulai ulang saat ditolak)

// admin/function/verifikasi_pembayaran.php
// Proses verifikasi pembayaran: setujui atau tolak.
// Menolak WAJIB disertai alasan (dikirim dari form di halaman detail).
// Hanya menerima POST supaya status tidak berubah hanya karena link dibuka/di-prefetch.

include_once __DIR__ . '/../database/koneksi.php';

$aksi    = param_enum('aksi', ['setujui', 'tolak']);

// Hanya POST + token CSRF
if ($aksi !== '') {
    wajibPostCsrf();
}
$kembali = '/e-ticket-bus/admin/index.php?page=verifikasi_pembayaran';

// ===== Nilai status di kolom payments.status (SESUAIKAN dengan databasemu) =====
$STATUS_MENUNGGU = 'pending';
$STATUS_LUNAS    = 'lunas';
$STATUS_DITOLAK  = 'ditolak';
// ================================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($aksi, ['setujui', 'tolak'], true)) {
    $id = param_id('id', 'POST');

    // Ambil data pembayaran
    $ambil = $koneksi->prepare("SELECT id, booking_id, bukti_transfer, status FROM payments WHERE id = ?");
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

    // Bus sudah berangkat? Pembayaran seperti ini TIDAK boleh disetujui (tiketnya sudah tidak berguna).
    // Dicek di server juga, bukan hanya dengan mematikan tombol di halaman detail.
    $cek_berangkat = $koneksi->prepare(
        "SELECT (s.jam_berangkat < NOW()) AS sudah_berangkat
         FROM payments p
         JOIN bookings  b ON b.id = p.booking_id
         JOIN schedules s ON s.id = b.schedule_id
         WHERE p.id = ?"
    );
    $cek_berangkat->bind_param('i', $id);
    $cek_berangkat->execute();
    $sudah_berangkat = (int) ($cek_berangkat->get_result()->fetch_assoc()['sudah_berangkat'] ?? 0) === 1;

    if ($aksi === 'setujui' && $sudah_berangkat) {
        header("Location: $kembali&aksi=detail&id=$id&pesan=sudah_berangkat");
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
            param_str('alasan', '', 'POST', 50),
            param_str('catatan', '', 'POST', 500)
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

    // Ditolak -> penumpang diberi waktu baru (sama dengan batas bayar awal) untuk mengunggah bukti yang benar.
    // Kalau tidak, hitung mundur lamanya sudah dihentikan saat ia mengirim bukti, dan pesanannya akan
    // tertahan selamanya. Hanya dijalankan kalau baris ini memang berhasil diubah oleh admin ini.
    // (Kalau bus sudah berangkat, tidak ada waktu baru: pesanan ikut dibersihkan sistem setelah ditolak.)
    if ($aksi === 'tolak' && $ubah->affected_rows === 1 && !$sudah_berangkat) {
        atur_batas_bayar($koneksi, (int) $bayar['booking_id']);
    }

    header("Location: $kembali&pesan=$pesan_ok");
    exit;
}

// Aksi tidak dikenal / bukan POST
header("Location: $kembali");
exit;