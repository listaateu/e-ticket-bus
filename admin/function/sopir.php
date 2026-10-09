<?php
require_once __DIR__ . '/../login/auth.php';
wajibRole(['admin']);   // hanya admin yang boleh masuk

// admin/function/sopir.php
// Proses data sopir: simpan (Create) dan hapus (Delete).
// Proses ubah (Update) ada di pages/sopir/update.php.
// Semua disimpan di tabel users dengan role 'kondektur'.
// Role DIKUNCI di sini (bukan dari form), jadi tidak bisa diubah lewat inspect element.

include_once __DIR__ . '/../database/koneksi.php';

$aksi    = $_GET['aksi'] ?? '';
$kembali = '/e-ticket-bus/admin/index.php?page=sopir';
$role    = 'kondektur';

// ---------- TAMBAH ----------
if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // Cek isiannya masuk akal
    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
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

    // Password diacak dulu (hash), jangan pernah disimpan apa adanya
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $simpan = $koneksi->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
    $simpan->bind_param('ssss', $nama, $email, $hash, $role);
    $simpan->execute();

    header("Location: $kembali&pesan=tambah_ok");
    exit;
}

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = (int) ($_GET['id'] ?? 0);

    try {
        // "AND role = ?" supaya halaman ini hanya bisa menghapus sopir, bukan admin
        $hapus = $koneksi->prepare("DELETE FROM users WHERE id = ? AND role = ?");
        $hapus->bind_param('is', $id, $role);
        $hapus->execute();
    } catch (mysqli_sql_exception $e) {
        // gagal karena data ini masih dipakai tabel lain (foreign key)
        header("Location: $kembali&pesan=dipakai");
        exit;
    }

    header("Location: $kembali&pesan=hapus_ok");
    exit;
}

// Aksi tidak dikenal
header("Location: $kembali");
exit;