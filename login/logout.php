<?php
/**
 * login/logout.php — Keluar (hapus session sampai bersih).
 * Penumpang kembali ke beranda, sopir kembali ke halaman login.
 */
require_once __DIR__ . '/../includes/base_url.php';
session_start();

$tujuan = (($_SESSION['role'] ?? '') === 'penumpang')
    ? BASE_URL . '/penumpang/index.php'
    : BASE_URL . '/login/index.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: ' . $tujuan);
exit;