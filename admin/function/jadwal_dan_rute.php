<?php
// admin/function/jadwal_dan_rute.php
// Semua proses data jadwal & rute: simpan (Create), ubah (Update), hapus (Delete).

include_once __DIR__ . '/../database/koneksi.php';

$aksi    = $_GET['aksi'] ?? '';
$kembali = '/e-ticket-bus/admin/index.php?page=jadwal_dan_rute';

// Ambil & rapikan data dari form (dipakai oleh simpan dan ubah)
function ambil_data_form()
{
    $bus_id = (int) ($_POST['bus_id'] ?? 0);
    $asal   = trim($_POST['kota_asal'] ?? '');
    $tujuan = trim($_POST['kota_tujuan'] ?? '');
    $jam    = trim($_POST['jam_berangkat'] ?? '');
    $harga  = (int) ($_POST['harga'] ?? 0);

    // input datetime-local formatnya 2026-10-20T08:00 -> ubah ke 2026-10-20 08:00:00
    $jam = str_replace('T', ' ', $jam);
    if (strlen($jam) === 16) {
        $jam .= ':00';
    }

    $valid = $bus_id > 0
        && $asal !== '' && $tujuan !== ''
        && strcasecmp($asal, $tujuan) !== 0      // asal tidak boleh sama dengan tujuan
        && strtotime($jam) !== false
        && $harga > 0;

    return [$valid, $bus_id, $asal, $tujuan, $jam, $harga];
}

// ---------- TAMBAH ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    [$valid, $bus_id, $asal, $tujuan, $jam, $harga] = ambil_data_form();

    if (!$valid) {
        header("Location: $kembali&aksi=tambah&pesan=tidak_valid");
        exit;
    }

    $simpan = $koneksi->prepare(
        "INSERT INTO schedules (bus_id, kota_asal, kota_tujuan, jam_berangkat, harga) VALUES (?, ?, ?, ?, ?)"
    );
    $simpan->bind_param('isssi', $bus_id, $asal, $tujuan, $jam, $harga);
    $simpan->execute();

    header("Location: $kembali&pesan=tambah_ok");
    exit;
}

// ---------- UBAH ----------
if ($aksi === 'ubah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    [$valid, $bus_id, $asal, $tujuan, $jam, $harga] = ambil_data_form();

    if (!$valid || $id <= 0) {
        header("Location: $kembali&aksi=update&id=$id&pesan=tidak_valid");
        exit;
    }

    $ubah = $koneksi->prepare(
        "UPDATE schedules SET bus_id = ?, kota_asal = ?, kota_tujuan = ?, jam_berangkat = ?, harga = ? WHERE id = ?"
    );
    $ubah->bind_param('isssii', $bus_id, $asal, $tujuan, $jam, $harga, $id);
    $ubah->execute();

    header("Location: $kembali&pesan=update_ok");
    exit;
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = (int) ($_GET['id'] ?? 0);

    try {
        $hapus = $koneksi->prepare("DELETE FROM schedules WHERE id = ?");
        $hapus->bind_param('i', $id);
        $hapus->execute();
    } catch (mysqli_sql_exception $e) {
        // Gagal karena jadwal ini sudah dipakai di tabel bookings (foreign key)
        header("Location: $kembali&pesan=jadwal_dipakai");
        exit;
    }

    header("Location: $kembali&pesan=hapus_ok");
    exit;
}

// Aksi tidak dikenal
header("Location: $kembali");
exit;