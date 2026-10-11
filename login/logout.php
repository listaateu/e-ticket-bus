<?php
/**
 * login/logout.php — Keluar (hapus session sampai bersih).
 * Penumpang kembali ke beranda, sopir kembali ke halaman login.
 */
session_start();

$tujuan = (($_SESSION['role'] ?? '') === 'penumpang')
    ? '/e-ticket-bus/penumpang/index.php'
    : '/e-ticket-bus/login/index.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: ' . $tujuan);
exit;