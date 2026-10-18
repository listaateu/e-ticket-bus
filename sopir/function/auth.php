<?php
/**
 * ============================================================
 * FILE         : sopir/function/auth.php
 * FUNGSI       : Cek login sopir/kondektur. Login-nya satu form dengan penumpang (login/index.php);
 *                di database sopir memakai role 'kondektur'.
 * DIPANGGIL DARI: sopir/index.php
 * CATATAN      : Memakai session yang diisi login/index.php: $_SESSION['user_id'], ['role'], ['nama'].
 * ============================================================
 */
require_once __DIR__ . '/../../includes/base_url.php';

const LOGIN_URL  = BASE_URL . '/login/index.php';
const LOGOUT_URL = BASE_URL . '/login/logout.php';
const ROLE_SOPIR = 'kondektur';

// Session aman (cookie HttpOnly + SameSite, header anti-iframe). Lihat includes/session_aman.php
require_once __DIR__ . '/../../includes/session_aman.php';
mulai_session_pengguna();

function user_id_login(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function role_login(): string
{
    return (string) ($_SESSION['role'] ?? '');
}

/** Hanya role 'kondektur' yang boleh masuk. Selain itu dilempar ke halaman login. */
function wajib_sopir(): void
{
    if (user_id_login() > 0 && role_login() === ROLE_SOPIR) {
        return;
    }
    header('Location: ' . LOGIN_URL);
    exit;
}

/** Data sopir yang sedang login (null kalau akunnya sudah dihapus admin). */
function get_sopir(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT id, nama, username, email, no_hp FROM users WHERE id = ? AND role = 'kondektur'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}