<?php
require_once __DIR__ . '/../login/auth.php';
wajibRole(['admin']);   // hanya admin yang boleh masuk

// admin/function/user.php
// Proses hapus penumpang (Delete).
// Proses tambah (Create) ada di pages/user/tambah.php lewat function/registrasi.php.
// Proses ubah (Update) ada di pages/user/update.php.
// Semua disimpan di tabel users dengan role 'penumpang'.
// Role DIKUNCI di sini (bukan dari form), jadi tidak bisa diubah lewat inspect element.

include_once __DIR__ . '/../database/koneksi.php';

$aksi    = $_GET['aksi'] ?? '';
$kembali = '/e-ticket-bus/admin/index.php?page=user';
$role    = 'penumpang';

// ---------- HAPUS ----------
if ($aksi === 'hapus') {
    $id = (int) ($_GET['id'] ?? 0);

    try {
        // "AND role = ?" supaya halaman ini hanya bisa menghapus penumpang, bukan admin
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