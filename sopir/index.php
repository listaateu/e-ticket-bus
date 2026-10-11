<?php
/**
 * sopir/index.php — SEMENTARA. Hanya untuk mengetes alur login sopir.
 * Nanti diganti dengan halaman sopir yang sebenarnya.
 */
session_start();

// Penjaga: hanya role 'kondektur' (sopir) yang boleh masuk
if ((int) ($_SESSION['user_id'] ?? 0) <= 0 || ($_SESSION['role'] ?? '') !== 'kondektur') {
    header('Location: /e-ticket-bus/login/index.php');
    exit;
}

$nama = htmlspecialchars($_SESSION['nama'] ?? 'Sopir', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Halaman Sopir</title>
  <link rel="stylesheet" href="/e-ticket-bus/admin/assets/libs/bootstrap/css/bootstrap.min.css">
</head>
<body class="bg-light">
  <div class="container py-5 text-center">
    <h3>Halo, <?= $nama ?> 👋</h3>
    <p class="text-muted">Halaman sopir sedang dalam pengembangan.</p>
    <a href="/e-ticket-bus/login/logout.php" class="btn btn-outline-danger">Keluar</a>
  </div>
</body>
</html>