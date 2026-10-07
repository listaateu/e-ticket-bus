<?php
// admin/index.php
// Satu-satunya pintu masuk admin. Isi tengahnya ditentukan dari alamat (URL):
//   index.php                                  -> dashboard
//   index.php?page=bus                         -> pages/bus/bus.php         (tabel)
//   index.php?page=bus&aksi=tambah             -> pages/bus/tambah.php      (form tambah)
//   index.php?page=bus&aksi=update&id=3        -> pages/bus/update.php      (form ubah)
//   index.php?page=data_pemesanan&aksi=detail  -> pages/data_pemesanan/detail.php
//                                                 atau data_pemesanan_detail.php (dua-duanya boleh)

// Daftar menu yang diizinkan. Menu baru tinggal ditambah di sini.
$menu_boleh = ['dashboard', 'bus', 'jadwal_dan_rute', 'user', 'verifikasi_pembayaran', 'data_pemesanan'];

$page = $_GET['page'] ?? 'dashboard';
$aksi = $_GET['aksi'] ?? '';

if (!in_array($page, $menu_boleh)) {
    $page = 'dashboard';
}
$menu_aktif = $page;          // dipakai sidebar untuk menyalakan menu yang sedang dibuka

// Tentukan file isi yang akan ditampilkan
if ($page === 'dashboard') {
    $file_isi = 'pages/dashboard.php';
} else {
    $file_isi = "pages/$page/$page.php";   // default: tabel

    if (in_array($aksi, ['tambah', 'update', 'detail'])) {
        // Coba dua kemungkinan nama file, pakai yang ada
        $kandidat = [
            "pages/$page/$aksi.php",            // contoh: detail.php
            "pages/$page/{$page}_$aksi.php",    // contoh: data_pemesanan_detail.php
        ];
        foreach ($kandidat as $k) {
            if (file_exists($k)) {
                $file_isi = $k;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<!-- head section -->
<?php include 'partials/head.php' ?>
<!-- head section -->

<body>

    <!-- sidebar -->
    <?php include 'components/sidebar.php' ?>
    <!-- sidebar -->

    <!-- area utama (kanan sidebar) -->
    <div class="main-wrapper">

        <!-- topbar -->
        <?php include 'components/topbar.php' ?>
        <!-- topbar -->

        <!-- isi halaman -->
        <?php include $file_isi ?>
        <!-- isi halaman -->

        <!-- footer -->
        <?php include 'components/footer.php' ?>
        <!-- footer -->

    </div>
    <!-- area utama -->

    <!-- java script -->
    <?php include 'partials/script.php' ?>
    <!-- java script -->

</body>

</html>