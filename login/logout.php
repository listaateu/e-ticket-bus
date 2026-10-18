<?php
/**
 * login/logout.php — Keluar (hapus session sampai bersih).
 * Penumpang kembali ke beranda, sopir kembali ke halaman login.
 *
 * Hanya menerima POST + token CSRF (tombol "Keluar" di penumpang/ dan sopir/). Dibuka lewat alamat biasa
 * (link, gambar tersembunyi, atau tab baru dari situs lain) tidak mengeluarkan siapa pun.
 */
require_once __DIR__ . '/../includes/base_url.php';
require_once __DIR__ . '/../includes/session_aman.php';
mulai_session_pengguna();

header('Cache-Control: no-store');

$role = (string) ($_SESSION['role'] ?? '');
if ($role === 'kondektur') {
    $beranda = BASE_URL . '/sopir/index.php';
} elseif ($role === 'penumpang') {
    $beranda = BASE_URL . '/penumpang/index.php';
} else {
    $beranda = BASE_URL . '/login/index.php';
}

$boleh_keluar = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && isset($_POST['csrf'], $_SESSION['csrf'])
    && is_string($_POST['csrf']) && is_string($_SESSION['csrf'])
    && hash_equals($_SESSION['csrf'], $_POST['csrf'])
    && permintaan_satu_situs();

if (!$boleh_keluar) {
    header('Location: ' . $beranda);   // bukan POST / token salah: tidak berbuat apa-apa
    exit;
}

$tujuan = ($role === 'penumpang')
    ? BASE_URL . '/penumpang/index.php'
    : BASE_URL . '/login/index.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $p['path'],
        'domain'   => $p['domain'],
        'secure'   => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => $p['samesite'] ?: 'Lax',
    ]);
}
session_destroy();

header('Location: ' . $tujuan);
exit;