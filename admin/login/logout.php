<?php
/**
 * admin/login/logout.php — Keluar dari admin.
 * Hanya menerima POST + token CSRF (tombol "Keluar" di topbar), jadi orang lain tidak bisa
 * memaksa admin keluar lewat link/gambar tersembunyi di situs lain.
 */
require_once __DIR__ . '/auth.php';

header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_valid()) {
    header('Location: ' . ADMIN_HOME_URL);   // dibuka lewat alamat biasa / token salah: tidak berbuat apa-apa
    exit;
}

admin_hapus_sesi();
header('Location: ' . AUTH_LOGIN_URL . '?keluar=1');
exit;