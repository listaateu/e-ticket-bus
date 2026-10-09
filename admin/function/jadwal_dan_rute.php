<?php
require_once __DIR__ . '/../login/auth.php';
wajibRole(['admin']);   // hanya admin yang boleh masuk

// admin/function/jadwal_dan_rute.php
// Proses data jadwal & rute: simpan (Create) dan hapus (Delete).
// Proses ubah (Update) ada di pages/jadwal_dan_rute/update.php.

include_once __DIR__ . '/../database/koneksi.php';

$aksi    = $_GET['aksi'] ?? '';
$kembali = '/e-ticket-bus/admin/index.php?page=jadwal_dan_rute';

// Ambil & rapikan data dari form (dipakai oleh simpan)
function ambil_data_form()
{
    $bus_id = (int) ($_POST['bus_id'] ?? 0);
    $sopir  = trim($_POST['sopir'] ?? '');
    $asal   = trim($_POST['kota_asal'] ?? '');
    $tujuan = trim($_POST['kota_tujuan'] ?? '');
    $jam    = trim($_POST['jam_berangkat'] ?? '');

    // harga dikirim dalam bentuk "150.000" -> buang semua selain angka -> 150000
    $harga  = (int) preg_replace('/\D/', '', $_POST['harga'] ?? '');

    // input datetime-local formatnya 2026-10-20T08:00 -> ubah ke 2026-10-20 08:00:00
    $jam = str_replace('T', ' ', $jam);
    if (strlen($jam) === 16) {
        $jam .= ':00';
    }

    $valid = $bus_id > 0
        && $sopir !== ''                          // sopir wajib dipilih
        && $asal !== '' && $tujuan !== ''
        && strcasecmp($asal, $tujuan) !== 0      // asal tidak boleh sama dengan tujuan
        && strtotime($jam) !== false
        && $harga > 0;

    return [$valid, $bus_id, $sopir, $asal, $tujuan, $jam, $harga];
}

// ---------- TAMBAH ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    [$valid, $bus_id, $sopir, $asal, $tujuan, $jam, $harga] = ambil_data_form();

    if (!$valid) {
        header("Location: $kembali&aksi=tambah&pesan=tidak_valid");
        exit;
    }

    $simpan = $koneksi->prepare(
        "INSERT INTO schedules (bus_id, sopir, kota_asal, kota_tujuan, jam_berangkat, harga) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $simpan->bind_param('issssi', $bus_id, $sopir, $asal, $tujuan, $jam, $harga);
    $simpan->execute();

    header("Location: $kembali&pesan=tambah_ok");
    exit;
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = (int) ($_GET['id'] ?? 0);

    // 1. Hitung ada berapa pemesanan di jadwal ini
    $cek = $koneksi->prepare("SELECT COUNT(*) AS total FROM bookings WHERE schedule_id = ?");
    $cek->bind_param('i', $id);
    $cek->execute();
    $total = (int) $cek->get_result()->fetch_assoc()['total'];

    // 2. Kalau sudah ada pemesanan, batalkan hapus dan kasih tahu admin
    if ($total > 0) {
        header("Location: $kembali&pesan=jadwal_dipakai");
        exit;
    }

    // 3. Belum ada yang pesan, aman untuk dihapus
    $hapus = $koneksi->prepare("DELETE FROM schedules WHERE id = ?");
    $hapus->bind_param('i', $id);
    $hapus->execute();

    header("Location: $kembali&pesan=hapus_ok");
    exit;
}

// Aksi tidak dikenal
header("Location: $kembali");
exit;