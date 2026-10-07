<?php
// admin/function/bus.php

include_once __DIR__ . '/../database/koneksi.php';

$aksi    = $_GET['aksi'] ?? '';
$kembali = '/e-ticket-bus/admin/index.php?page=bus';

// ---------- TAMBAH ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $plat  = strtoupper(trim($_POST['plat_nomor']));
    $kursi = (int) $_POST['kapasitas_kursi'];
    $kelas = $_POST['kelas'];

    // Cek plat sudah dipakai atau belum
    $cek = $koneksi->prepare("SELECT id FROM buses WHERE plat_nomor = ?");
    $cek->bind_param('s', $plat);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) {
        header("Location: $kembali&aksi=tambah&pesan=plat_ada");
        exit;
    }

    // Simpan
    $simpan = $koneksi->prepare("INSERT INTO buses (plat_nomor, kapasitas_kursi, kelas) VALUES (?, ?, ?)");
    $simpan->bind_param('sis', $plat, $kursi, $kelas);
    $simpan->execute();

    header("Location: $kembali&pesan=tambah_ok");
    exit;
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = (int) $_GET['id'];

    try {
        $hapus = $koneksi->prepare("DELETE FROM buses WHERE id = ?");
        $hapus->bind_param('i', $id);
        $hapus->execute();
    } catch (mysqli_sql_exception $e) {
        header("Location: $kembali&pesan=bus_dipakai");
        exit;
    }

    header("Location: $kembali&pesan=hapus_ok");
    exit;
}

// Aksi tidak dikenal
header("Location: $kembali");
exit;