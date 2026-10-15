<?php
require_once __DIR__ . '/../login/auth.php';
wajibRole(['admin']);   // hanya admin yang boleh masuk

// admin/function/bus.php
// Proses data bus: simpan (Create) dan hapus (Delete).
// Proses ubah (Update) ada di pages/bus/update.php.

include_once __DIR__ . '/../database/koneksi.php';
require_once __DIR__ . '/../../includes/bus_helper.php';

$aksi    = param_enum('aksi', ['simpan', 'hapus']);

// Semua proses di file ini mengubah data: hanya POST + token CSRF (link yang disalin/dibuka di tab baru ditolak)
if ($aksi !== '') {
    wajibPostCsrf();
}
$kembali = '/e-ticket-bus/admin/index.php?page=bus';
$kelas_filter = param_str('kelas', '', 'POST');
if ($aksi === 'hapus' && in_array($kelas_filter, ['Eksekutif', 'Bisnis', 'Ekonomi'], true)) {
    $kembali .= '&kelas=' . urlencode($kelas_filter);
}

// ---------- TAMBAH ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = trim($_POST['nama_bus'] ?? '');
    $plat  = strtoupper(trim($_POST['plat_nomor'] ?? ''));
    $kursi = (int) ($_POST['kapasitas_kursi'] ?? 0);
    $kelas = $_POST['kelas'] ?? '';

    // Tata letak & fasilitas: hanya terima nilai yang ada di daftar resmi
    $tata_letak = in_array($_POST['tata_letak'] ?? '', TATA_LETAK_KURSI, true) ? $_POST['tata_letak'] : '';
    $fasilitas  = array_filter((array) ($_POST['fasilitas'] ?? []), fn($k) => is_string($k) && isset(FASILITAS_BUS[$k]));
    $fasilitas_teks = implode(',', $fasilitas);

    // Cek isiannya masuk akal (nama_bus tidak boleh kosong)
    if ($nama === '' || $plat === '' || $kursi < 1 || $kursi > 60 || !in_array($kelas, ['Ekonomi', 'Bisnis', 'Eksekutif'], true)) {
        header("Location: $kembali&aksi=tambah&pesan=tidak_valid");
        exit;
    }

    // Cek plat sudah dipakai atau belum
    $cek = $koneksi->prepare("SELECT id FROM buses WHERE plat_nomor = ?");
    $cek->bind_param('s', $plat);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) {
        header("Location: $kembali&aksi=tambah&pesan=plat_ada");
        exit;
    }

    // Simpan foto (kalau ada). Dilakukan setelah semua cek lolos supaya tidak ada file yatim.
    $pesan_foto = '';
    $foto = bus_simpan_foto($_FILES['foto'] ?? null, $pesan_foto);
    if ($foto === false) {
        header("Location: $kembali&aksi=tambah&pesan=foto_tidak_valid");
        exit;
    }
    $foto = $foto === '' ? null : $foto;

    $simpan = $koneksi->prepare(
        "INSERT INTO buses (nama_bus, plat_nomor, kapasitas_kursi, kelas, tata_letak, fasilitas, foto) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $simpan->bind_param('ssissss', $nama, $plat, $kursi, $kelas, $tata_letak, $fasilitas_teks, $foto);
    $simpan->execute();

    header("Location: $kembali&pesan=tambah_ok");
    exit;
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = param_id('id', 'POST');

    // Catat nama file foto dulu, supaya bisa ikut dihapus setelah bus terhapus
    $ambil = $koneksi->prepare("SELECT foto FROM buses WHERE id = ?");
    $ambil->bind_param('i', $id);
    $ambil->execute();
    $foto_lama = $ambil->get_result()->fetch_assoc()['foto'] ?? '';

    try {
        $hapus = $koneksi->prepare("DELETE FROM buses WHERE id = ?");
        $hapus->bind_param('i', $id);
        $hapus->execute();
    } catch (mysqli_sql_exception $e) {
        header("Location: $kembali&pesan=bus_dipakai");
        exit;
    }

    bus_hapus_foto($foto_lama);

    header("Location: $kembali&pesan=hapus_ok");
    exit;
}

// Aksi tidak dikenal
header("Location: $kembali");
exit;