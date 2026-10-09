<?php
// sopir/index.php
// Halaman awal sopir (role 'kondektur'). Sementara masih kerangka;
// nanti diisi daftar jadwal/penumpang yang dibawa sopir.

require_once __DIR__ . '/../login/auth.php';
wajibRole(['kondektur']);

$user = userSekarang();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sopir - E-Ticket Bus</title>
    <link rel="stylesheet" href="/e-ticket-bus/admin/assets/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/e-ticket-bus/admin/assets/libs/bootstrap-icons/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">Halo, <?= htmlspecialchars($user['nama']) ?> 👋</h1>
            <a href="/e-ticket-bus/login/logout.php" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                Halaman sopir. Daftar jadwal dan penumpang akan ditampilkan di sini.
            </div>
        </div>
    </div>
</body>
</html>