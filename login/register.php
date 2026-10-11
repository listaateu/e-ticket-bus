<?php
/**
 * login/register.php — Daftar akun PENUMPANG.
 * Memakai registrasiPenumpang() dari admin/function/registrasi.php (role dikunci 'penumpang' di sana).
 */
require_once __DIR__ . '/../penumpang/database/koneksi.php';
require_once __DIR__ . '/../penumpang/function/auth.php';
require_once __DIR__ . '/../admin/function/registrasi.php';

if (sudah_login_penumpang()) {
    header('Location: /e-ticket-bus/penumpang/index.php');
    exit;
}

if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(16));
}

$error = '';
$old   = ['nama' => '', 'username' => '', 'email' => '', 'no_hp' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    if (!hash_equals($_SESSION['csrf_login'], $_POST['csrf'] ?? '')) {
        $error = 'Sesi form habis, silakan coba lagi.';
    } elseif (($_POST['password'] ?? '') !== ($_POST['password2'] ?? '')) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $hasil = registrasiPenumpang($conn, $_POST);   // true kalau berhasil, kalau gagal berisi teks error
        if ($hasil === true) {
            $_SESSION['pesan_login'] = 'Pendaftaran berhasil, silakan masuk.';
            header('Location: index.php');
            exit;
        }
        $error = $hasil;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar - E-Ticket Bus</title>
  <link rel="stylesheet" href="/e-ticket-bus/admin/assets/libs/bootstrap/css/bootstrap.min.css">
  <style>body{background:#f1f5f9}.kartu{max-width:460px;margin:5vh auto}</style>
</head>
<body>
<div class="kartu card shadow-sm border-0 p-4">
  <h4 class="mb-1">Daftar Penumpang</h4>
  <p class="text-muted small mb-3">Buat akun untuk memesan tiket</p>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_login'] ?>">
    <div class="mb-3">
      <label class="form-label" for="nama">Nama lengkap</label>
      <input class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($old['nama']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="username">Username</label>
      <input class="form-control" id="username" name="username" value="<?= htmlspecialchars($old['username']) ?>"
             pattern="[A-Za-z0-9_]{4,20}" title="4-20 karakter: huruf, angka, underscore" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="email">Email</label>
      <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="no_hp">No. HP</label>
      <input class="form-control" type="tel" id="no_hp" name="no_hp" value="<?= htmlspecialchars($old['no_hp']) ?>" required>
    </div>
    <div class="row">
      <div class="col-6 mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" minlength="6" required>
      </div>
      <div class="col-6 mb-3">
        <label class="form-label" for="password2">Ulangi password</label>
        <input class="form-control" type="password" id="password2" name="password2" minlength="6" required>
      </div>
    </div>
    <button class="btn btn-primary w-100">Daftar</button>
  </form>

  <p class="text-center small mt-3 mb-0">Sudah punya akun? <a href="index.php">Masuk</a></p>
</div>
</body>
</html>