<?php
// admin/function/user.php
// Semua proses data user: simpan (Create), ubah (Update), hapus (Delete).

include_once __DIR__ . '/../database/koneksi.php';

$aksi    = $_GET['aksi'] ?? '';
$kembali = '/e-ticket-bus/admin/index.php?page=user';

$role_boleh = ['admin', 'kondektur', 'penumpang'];

// ---------- TAMBAH ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    $valid = $nama !== ''
        && filter_var($email, FILTER_VALIDATE_EMAIL)
        && strlen($password) >= 6
        && in_array($role, $role_boleh, true);

    if (!$valid) {
        header("Location: $kembali&aksi=tambah&pesan=tidak_valid");
        exit;
    }

    // Cek email sudah dipakai atau belum
    $cek = $koneksi->prepare("SELECT id FROM users WHERE email = ?");
    $cek->bind_param('s', $email);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) {
        header("Location: $kembali&aksi=tambah&pesan=email_ada");
        exit;
    }

    // Password disimpan dalam bentuk hash, bukan teks asli
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $simpan = $koneksi->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
    $simpan->bind_param('ssss', $nama, $email, $hash, $role);
    $simpan->execute();

    header("Location: $kembali&pesan=tambah_ok");
    exit;
}

// ---------- UBAH ----------
if ($aksi === 'ubah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int) ($_POST['id'] ?? 0);
    $nama     = trim($_POST['nama'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';      // boleh kosong = password tidak diganti
    $role     = $_POST['role'] ?? '';

    $valid = $id > 0
        && $nama !== ''
        && filter_var($email, FILTER_VALIDATE_EMAIL)
        && ($password === '' || strlen($password) >= 6)
        && in_array($role, $role_boleh, true);

    if (!$valid) {
        header("Location: $kembali&aksi=update&id=$id&pesan=tidak_valid");
        exit;
    }

    // Cek email dipakai user LAIN atau belum
    $cek = $koneksi->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
    $cek->bind_param('si', $email, $id);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) {
        header("Location: $kembali&aksi=update&id=$id&pesan=email_ada");
        exit;
    }

    if ($password === '') {
        $ubah = $koneksi->prepare("UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?");
        $ubah->bind_param('sssi', $nama, $email, $role, $id);
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ubah = $koneksi->prepare("UPDATE users SET nama = ?, email = ?, password = ?, role = ? WHERE id = ?");
        $ubah->bind_param('ssssi', $nama, $email, $hash, $role, $id);
    }
    $ubah->execute();

    header("Location: $kembali&pesan=update_ok");
    exit;
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = (int) ($_GET['id'] ?? 0);

    try {
        $hapus = $koneksi->prepare("DELETE FROM users WHERE id = ?");
        $hapus->bind_param('i', $id);
        $hapus->execute();
    } catch (mysqli_sql_exception $e) {
        // Gagal karena user ini sudah punya pemesanan di tabel bookings (foreign key)
        header("Location: $kembali&pesan=user_dipakai");
        exit;
    }

    header("Location: $kembali&pesan=hapus_ok");
    exit;
}

// Aksi tidak dikenal
header("Location: $kembali");
exit;