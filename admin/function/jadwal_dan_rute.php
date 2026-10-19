<?php
require_once __DIR__ . '/../login/auth.php';
wajibRole(['admin']);   // hanya admin yang boleh masuk

// admin/function/jadwal_dan_rute.php
// Proses data jadwal & rute: simpan (Create) dan hapus (Delete).
// Proses ubah (Update) ada di pages/jadwal_dan_rute/update.php.

include_once __DIR__ . '/../database/koneksi.php';

$aksi    = param_enum('aksi', ['simpan', 'hapus']);

// Semua proses di file ini mengubah data: hanya POST + token CSRF (link yang disalin/dibuka di tab baru ditolak)
if ($aksi !== '') {
    wajibPostCsrf();
}
$kembali = BASE_URL . '/admin/index.php?page=jadwal_dan_rute';
$kelas_filter = param_str('kelas', '', 'POST');
if ($aksi === 'hapus' && in_array($kelas_filter, ['Eksekutif', 'Bisnis', 'Ekonomi'], true)) {
    $kembali .= '&kelas=' . urlencode($kelas_filter);
}
$tab_filter = param_enum('tab', ['akan_datang', 'sudah_berangkat', 'semua'], '', 'POST');
if ($aksi === 'hapus' && $tab_filter !== '') {
    $kembali .= '&tab=' . $tab_filter;
}

// Ambil & rapikan data dari form (dipakai oleh simpan)
function ambil_data_form()
{
    $bus_id = (int) ($_POST['bus_id'] ?? 0);
    $sopir  = trim($_POST['sopir'] ?? '');
    $asal   = trim($_POST['kota_asal'] ?? '');
    $tujuan = trim($_POST['kota_tujuan'] ?? '');
    $jam    = trim($_POST['jam_berangkat'] ?? '');
    $tiba   = trim($_POST['jam_tiba'] ?? '');

    // harga dikirim dalam bentuk "150.000" -> buang semua selain angka -> 150000
    $harga  = (int) preg_replace('/\D/', '', $_POST['harga'] ?? '');

    // input datetime-local formatnya 2026-10-20T08:00 -> ubah ke 2026-10-20 08:00:00
    $jam = str_replace('T', ' ', $jam);
    if (strlen($jam) === 16) {
        $jam .= ':00';
    }
    $tiba = str_replace('T', ' ', $tiba);
    if (strlen($tiba) === 16) {
        $tiba .= ':00';
    }

    $valid = $bus_id > 0
        && $sopir !== ''                          // sopir wajib dipilih
        && $asal !== '' && $tujuan !== ''
        && strcasecmp($asal, $tujuan) !== 0      // asal tidak boleh sama dengan tujuan
        && strtotime($jam) !== false
        && strtotime($tiba) !== false
        && strtotime($tiba) > strtotime($jam)    // jam tiba harus SETELAH jam berangkat
        && $harga > 0;

    return [$valid, $bus_id, $sopir, $asal, $tujuan, $jam, $tiba, $harga];
}

// ---------- TAMBAH ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    [$valid, $bus_id, $sopir, $asal, $tujuan, $jam, $tiba, $harga] = ambil_data_form();

    if (!$valid) {
        header("Location: $kembali&aksi=tambah&pesan=tidak_valid");
        exit;
    }

    // Jam berangkat tidak boleh sudah lewat. Dibandingkan dengan jam MySQL (NOW()), sama seperti tabel jadwal,
    // supaya tidak terpengaruh zona waktu PHP. Hasil NULL = tanggal tidak ada (mis. 31 Februari).
    $cek_jam = $koneksi->prepare("SELECT (? <= NOW()) AS lewat");
    $cek_jam->bind_param('s', $jam);
    $cek_jam->execute();
    $lewat = $cek_jam->get_result()->fetch_assoc()['lewat'];
    if ($lewat === null) {
        header("Location: $kembali&aksi=tambah&pesan=tidak_valid");
        exit;
    }
    if ((int) $lewat === 1) {
        header("Location: $kembali&aksi=tambah&pesan=jam_lewat");
        exit;
    }

    $simpan = $koneksi->prepare(
        "INSERT INTO schedules (bus_id, sopir, kota_asal, kota_tujuan, jam_berangkat, jam_tiba, harga) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $simpan->bind_param('isssssi', $bus_id, $sopir, $asal, $tujuan, $jam, $tiba, $harga);
    $simpan->execute();

    header("Location: $kembali&pesan=tambah_ok");
    exit;
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = param_id('id', 'POST');

    // 0. Jadwal yang sudah berangkat TIDAK boleh dihapus: datanya dipakai untuk riwayat & laporan.
    //    Dicek di server (bukan hanya tombol disembunyikan) dan dibandingkan dengan jam MySQL (NOW()).
    $cek_lewat = $koneksi->prepare("SELECT (jam_berangkat < NOW()) AS lewat FROM schedules WHERE id = ?");
    $cek_lewat->bind_param('i', $id);
    $cek_lewat->execute();
    $baris = $cek_lewat->get_result()->fetch_assoc();
    if ($baris && (int) $baris['lewat'] === 1) {
        header("Location: $kembali&pesan=hapus_lewat");
        exit;
    }

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