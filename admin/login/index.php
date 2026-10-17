<?php
/**
 * admin/login/index.php — Halaman login ADMIN.
 * Hanya akun dengan role 'admin' yang bisa masuk lewat sini (penumpang & sopir login di /login/).
 *
 * Pengamannya:
 *  - Token CSRF di form
 *  - Query memakai prepared statement (anti SQL injection)
 *  - Password dicek dengan password_verify (hash bcrypt), dan tetap "dihitung" walau akun tidak ada
 *    supaya waktu respon tidak membocorkan akun mana yang ada
 *  - Pesan error satu macam saja ("salah") + jeda kecil tiap gagal
 *  - Dikunci sementara setelah terlalu banyak percobaan salah (lihat throttle.php)
 *  - session_regenerate_id() setelah login berhasil (anti session fixation)
 *  - Redirect setelah login hanya ke halaman di dalam /admin/ (anti open redirect)
 *  - Header keamanan: tidak bisa dibuka di iframe, tidak di-cache
 */
define('ADMIN_LOGIN', true);
require_once __DIR__ . '/auth.php';                  // session, csrf_token(), csrf_valid(), BASE_URL
require_once __DIR__ . '/throttle.php';
require_once __DIR__ . '/../database/koneksi.php';   // membuat $koneksi

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

// Sudah login sebagai admin & sesi masih berlaku? Langsung ke dashboard.
if (admin_sudah_login()) {
    header('Location: ' . ADMIN_HOME_URL);
    exit;
}

/** Hash palsu, hanya untuk menyamakan waktu proses ketika akun tidak ditemukan. */
const HASH_PALSU = '$2y$10$.vGA1O9wmRjrwAVXD98HNOgsNpDczlqm3Jq7KnEd1rVAGv3Fykk1a';

/** Hanya terima alamat internal di dalam /admin/ sebagai tujuan setelah login. */
function tujuan_aman(string $alamat): string
{
    $awal = BASE_URL . '/admin/';
    if ($alamat === ''
        || $alamat[0] !== '/'
        || str_starts_with($alamat, '//')
        || strpbrk($alamat, "\\\r\n\0") !== false
        || !str_starts_with($alamat, $awal)
        || str_starts_with($alamat, $awal . 'login/')) {
        return ADMIN_HOME_URL;
    }
    return $alamat;
}

$info  = '';
$error = '';
$login = '';

if (isset($_GET['keluar'])) {
    $info = 'Kamu sudah keluar.';
} elseif (isset($_GET['habis'])) {
    $info = 'Sesi berakhir karena lama tidak aktif. Silakan masuk lagi.';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $login    = is_string($_POST['login'] ?? null) ? mb_substr(trim($_POST['login']), 0, 100, 'UTF-8') : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    $ip         = $_SERVER['REMOTE_ADDR'] ?? '0';
    $kunci_user = 'u:' . $ip . '|' . mb_strtolower($login, 'UTF-8');
    $kunci_ip   = 'i:' . $ip;
    $tunggu     = max(thr_sisa($kunci_user), thr_sisa($kunci_ip));

    if (!csrf_valid()) {
        $error = 'Sesi form habis. Muat ulang halaman lalu coba lagi.';
    } elseif ($tunggu > 0) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . max(1, (int) ceil($tunggu / 60)) . ' menit.';
    } elseif ($login === '' || $password === '') {
        $error = 'Email/username dan password wajib diisi.';
    } elseif (strlen($password) > 200) {
        $error = 'Email/username atau password salah.';
    } else {
        $stmt = $koneksi->prepare(
            "SELECT id, nama, username, email, password, role FROM users
             WHERE (email = ? OR BINARY username = ?) AND role = 'admin'
             LIMIT 1"
        );
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // username harus persis (huruf besar/kecil dihitung); email tidak membedakan besar/kecil
        $akun_cocok = $user && (
            hash_equals((string) $user['username'], $login)
            || strcasecmp((string) $user['email'], $login) === 0
        );

        $password_benar = password_verify($password, $akun_cocok ? (string) $user['password'] : HASH_PALSU);

        if ($akun_cocok && $password_benar) {
            thr_reset($kunci_user);
            thr_reset($kunci_ip);

            // Ambil tujuan SEBELUM session diganti
            $tujuan = tujuan_aman((string) ($_SESSION['redirect_setelah_login'] ?? ''));

            session_regenerate_id(true);               // cegah session fixation
            $_SESSION = [];
            $_SESSION['user_id']        = (int) $user['id'];
            $_SESSION['nama']           = (string) $user['nama'];
            $_SESSION['role']           = 'admin';
            $_SESSION['admin_login_at'] = time();
            $_SESSION['admin_terakhir'] = time();

            // Hash lama? Perbarui ke standar PHP terbaru (diam-diam, tidak mengganggu login kalau gagal)
            if (password_needs_rehash((string) $user['password'], PASSWORD_DEFAULT)) {
                try {
                    $hash_baru = password_hash($password, PASSWORD_DEFAULT);
                    $up = $koneksi->prepare('UPDATE users SET password = ? WHERE id = ?');
                    $id = (int) $user['id'];
                    $up->bind_param('si', $hash_baru, $id);
                    $up->execute();
                } catch (Throwable $e) {
                    error_log('Rehash password admin gagal: ' . $e->getMessage());
                }
            }

            header('Location: ' . $tujuan);
            exit;
        }

        thr_gagal($kunci_user, THR_MAKS_USER);
        thr_gagal($kunci_ip, THR_MAKS_IP);
        usleep(random_int(250000, 600000));            // jeda kecil: memperlambat tebak-tebakan
        $error = 'Email/username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Masuk Admin - E-Ticket Bus</title>
  <link rel="icon" href="<?= BASE_URL ?>/admin/assets/images/favicon.ico">
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/libs/bootstrap-icons/bootstrap-icons.css">
  <style>
    body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;
         background:linear-gradient(135deg,#0070d6 0%,#2fa8ff 100%)}
    .kartu-login{width:100%;max-width:420px;border:0;border-radius:16px;box-shadow:0 20px 50px rgba(0,40,90,.25)}
    .logo-login{width:56px;height:56px;border-radius:14px;background:#e8f3ff;color:#0070d6;
                display:flex;align-items:center;justify-content:center;font-size:1.7rem;margin:0 auto 1rem}
  </style>
</head>
<body>
  <main class="card kartu-login p-4 p-md-5">
    <div class="logo-login"><i class="bi bi-bus-front-fill"></i></div>
    <h1 class="h4 text-center mb-1">Masuk Admin</h1>
    <p class="text-center text-muted small mb-4">E-Ticket Bus</p>

    <?php if ($info !== ''): ?>
      <div class="alert alert-success py-2 small" role="status"><?= htmlspecialchars($info, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="alert alert-danger py-2 small" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" id="formLogin" autocomplete="on" novalidate>
      <?= csrf_field() ?>

      <div class="mb-3">
        <label for="login" class="form-label">Email atau username</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" class="form-control" id="login" name="login" maxlength="100"
                 value="<?= htmlspecialchars($login, ENT_QUOTES, 'UTF-8') ?>"
                 autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
        </div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" class="form-control" id="password" name="password" maxlength="200"
                 autocomplete="current-password" required>
          <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Tampilkan password">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" id="tombolMasuk" class="btn btn-primary w-100 py-2">Masuk</button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
      Penumpang atau sopir? <a href="<?= BASE_URL ?>/login/index.php">Masuk di sini</a>
    </p>
  </main>

  <script src="<?= BASE_URL ?>/admin/assets/js/login.js"></script>
</body>
</html>