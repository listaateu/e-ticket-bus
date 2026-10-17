<?php
/**
 * login/index.php — Login PENUMPANG & SOPIR (satu form).
 * Sopir di database memakai role 'kondektur' (dibuat admin lewat admin/function/sopir.php).
 */
require_once __DIR__ . '/../includes/base_url.php';
require_once __DIR__ . '/../penumpang/database/koneksi.php';   // membuat $conn
require_once __DIR__ . '/../penumpang/function/auth.php';       // session + ambil_redirect_setelah_login()

// role di database => halaman tujuan setelah login
const TUJUAN_ROLE = [
    'penumpang' => BASE_URL . '/penumpang/index.php',
    'kondektur' => BASE_URL . '/sopir/index.php',
];

// Sudah login? Langsung arahkan, tidak perlu lihat form lagi.
if (user_id_login() > 0 && isset(TUJUAN_ROLE[role_login()])) {
    header('Location: ' . TUJUAN_ROLE[role_login()]);
    exit;
}

// Token anti-CSRF sederhana
if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(16));
}

/**
 * Cocokkan yang diketik dengan akun hasil query.
 * - Username: HARUS persis sama, huruf besar/kecil dihitung ("kaka" tidak sama dengan "KakA").
 *   (MySQL secara bawaan tidak membedakan besar/kecil, jadi dicek ulang di sini.)
 * - Email: tidak membedakan besar/kecil (memang begitu aturan email).
 */
function login_cocok(array $user, string $login): bool
{
    $username = (string) ($user['username'] ?? '');
    $email    = (string) ($user['email'] ?? '');
    return ($username !== '' && hash_equals($username, $login))
        || ($email !== '' && strcasecmp($email, $login) === 0);
}

$pesan = $_SESSION['pesan_login'] ?? '';   // mis. "Pendaftaran berhasil"
unset($_SESSION['pesan_login']);
$error = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hanya terima teks (bukan array seperti login[]=x yang bisa membuat error)
    $login    = is_string($_POST['login'] ?? null) ? trim($_POST['login']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf_login'], $_POST['csrf'])) {
        $error = 'Sesi form habis, silakan coba lagi.';
    } elseif ($login === '' || $password === '') {
        $error = 'Email/username dan password wajib diisi.';
    } else {
        // Hanya role penumpang & kondektur yang boleh lewat sini (admin punya login sendiri)
        $stmt = $conn->prepare(
            "SELECT id, nama, username, email, password, role FROM users
             WHERE (email = ? OR BINARY username = ?) AND role IN ('penumpang', 'kondektur')
             LIMIT 1"
        );
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && login_cocok($user, $login) && password_verify($password, $user['password'])) {
            session_regenerate_id(true);               // cegah session fixation
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role'];

            if ($user['role'] === 'penumpang') {
                // balik ke halaman yang tadi dituju (kalau ada), kalau tidak ke beranda
                $tujuan = ambil_redirect_setelah_login(TUJUAN_ROLE['penumpang']);
            } else {
                unset($_SESSION['redirect_setelah_login']);
                $tujuan = TUJUAN_ROLE[$user['role']];
            }
            header('Location: ' . $tujuan);
            exit;
        }

        // Satu pesan umum, supaya orang tidak bisa menebak akun mana yang ada
        $error = 'Email/username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk - E-Ticket Bus</title>
  <!-- Sementara memakai bootstrap milik admin; nanti boleh dipindah ke login/assets -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/libs/bootstrap/css/bootstrap.min.css">
  <style>body{background:#f1f5f9}.kartu{max-width:420px;margin:8vh auto}</style>
</head>
<body>
<div class="kartu card shadow-sm border-0 p-4">
  <h4 class="mb-1">Masuk</h4>
  <p class="text-muted small mb-3">Untuk penumpang dan sopir</p>

  <?php if ($pesan): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($pesan) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_login'] ?>">
    <div class="mb-3">
      <label class="form-label" for="login">Email atau username</label>
      <input class="form-control" id="login" name="login" value="<?= htmlspecialchars($login) ?>" required autofocus>
    </div>
    <div class="mb-3">
      <label class="form-label" for="password">Password</label>
      <input class="form-control" type="password" id="password" name="password" required>
    </div>
    <button class="btn btn-primary w-100">Masuk</button>
  </form>

  <p class="text-center small mt-3 mb-1">Belum punya akun? <a href="register.php">Daftar sebagai penumpang</a></p>
  <p class="text-center small mb-0"><a href="<?= BASE_URL ?>/penumpang/index.php">&larr; Kembali ke beranda</a></p>
</div>
</body>
</html>