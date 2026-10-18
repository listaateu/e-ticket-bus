<?php
// admin/index.php
// Satu-satunya pintu masuk admin. Isi tengahnya ditentukan dari alamat (URL):
//   index.php                                  -> dashboard
//   index.php?page=bus                         -> pages/bus/bus.php         (tabel)
//   index.php?page=bus&aksi=tambah             -> pages/bus/tambah.php      (form tambah)
//   index.php?page=bus&aksi=update&id=3        -> pages/bus/update.php      (form ubah)
//   index.php?page=data_pemesanan&aksi=detail  -> pages/data_pemesanan/detail.php
//                                                 atau data_pemesanan_detail.php (dua-duanya boleh)

// Penanda "ini lewat index.php". File di pages/, components/, partials/ menolak dibuka langsung lewat alamat.
define('ADMIN_APP', true);

// Login & pengaman (CSRF, pembaca parameter aman). Halaman admin hanya untuk role admin.
require_once __DIR__ . '/login/auth.php';
wajibRole(['admin']);

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

// Batalkan pesanan yang melewati batas waktu bayar setiap halaman admin dibuka
// (sama seperti di sisi penumpang), supaya daftar & hitungan selalu terbaru.
require_once __DIR__ . '/database/koneksi.php';
require_once __DIR__ . '/../includes/batas_bayar.php';
require_once __DIR__ . '/function/akun.php';   // foto profil & data admin yang sedang login
batalkan_pesanan_kedaluwarsa($koneksi);

// DAFTAR PUTIH menu dan aksi yang diizinkan. Menu/aksi baru tinggal ditambah di sini.
// Nama file TIDAK PERNAH dibentuk dari teks mentah milik user: ?page= dan ?aksi= hanya
// dipakai sebagai kunci untuk mencari di daftar ini, jadi "../" dan sejenisnya tidak berguna.
$menu_boleh = [
    'dashboard'             => [],
    'akun'                  => [],   // Akun Saya (foto, data diri, password)
    'bus'                   => ['tambah', 'update'],
    'jadwal_dan_rute'       => ['tambah', 'update'],
    'user'                  => ['tambah', 'update'],
    'sopir'                 => ['tambah', 'update'],
    'verifikasi_pembayaran' => ['detail'],
    'data_pemesanan'        => ['detail'],
];

$page = param_str('page', 'dashboard', 'GET', 50);
if (!array_key_exists($page, $menu_boleh)) {
    $page = 'dashboard';
}
$aksi = param_str('aksi', '', 'GET', 20);
if (!in_array($aksi, $menu_boleh[$page], true)) {
    $aksi = '';
}
$menu_aktif = $page;          // dipakai sidebar untuk menyalakan menu yang sedang dibuka

// Tentukan file isi yang akan ditampilkan
$folder_pages = __DIR__ . '/pages';
if ($page === 'dashboard') {
    $file_isi = "$folder_pages/dashboard.php";
} else {
    $file_isi = "$folder_pages/$page/$page.php";   // default: tabel

    if ($aksi !== '') {
        // Coba dua kemungkinan nama file, pakai yang ada
        foreach (["$folder_pages/$page/$aksi.php", "$folder_pages/$page/{$page}_$aksi.php"] as $k) {
            if (is_file($k)) {
                $file_isi = $k;
                break;
            }
        }
    }
}

// Pengaman terakhir: file yang akan di-include harus benar-benar berada di dalam folder pages/
$nyata = realpath($file_isi);
if ($nyata === false || strpos($nyata, realpath($folder_pages) . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}
$file_isi = $nyata;
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
        <div id="isi-halaman" style="display:contents">
        <?php include $file_isi ?>
        </div>
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