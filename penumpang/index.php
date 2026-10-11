<?php
/**
 * ============================================================
 * FILE   : penumpang/index.php
 * FUNGSI : PINTU MASUK satu-satunya untuk semua halaman penumpang.
 *          File ini tidak berisi tampilan; ia hanya menyusun
 *          halaman dari potongan-potongan file lain.
 *
 * URL yang tersedia:
 *   (Beranda & Jadwal terbuka untuk tamu; halaman lain wajib login)
 *   index.php                                    -> pages/beranda/beranda.php
 *   index.php?page=jadwal                        -> pages/jadwal/jadwal.php
 *   index.php?page=jadwal&aksi=pilih_kursi&id=3  -> pages/jadwal/pilih_kursi.php
 *   index.php?page=tiket                         -> pages/tiket/tiket.php
 *   index.php?page=tiket&aksi=pembayaran&id=5    -> pages/tiket/pembayaran.php
 *   index.php?page=tiket&aksi=e_ticket&id=5      -> pages/tiket/e_ticket.php
 *   index.php?page=profil                        -> pages/profil/profil.php
 *
 * URUTAN TAMPILAN (dari atas ke bawah):
 *   partials/head.php -> components/header.php -> flash -> [isi pages/...]
 *   -> components/footer.php -> partials/script.php
 * ============================================================
 */

// Wajib paling atas: menahan output supaya redirect() di dalam halaman
// (mis. setelah klik "Lanjut ke Pembayaran") tidak error "headers already sent".
ob_start();

// ---------- 1. Muat semua fungsi (folder function/) ----------
require_once __DIR__ . '/database/koneksi.php';    // membuat variabel $conn (koneksi MySQL)
require_once __DIR__ . '/function/helpers.php';    // e(), rupiah(), url(), redirect(), flash, csrf
require_once __DIR__ . '/function/auth.php';       // cek login & role penumpang
require_once __DIR__ . '/function/jadwal.php';     // query jadwal & kursi
require_once __DIR__ . '/function/booking.php';    // simpan pemesanan & daftar tiket
require_once __DIR__ . '/function/pembayaran.php'; // simpan bukti pembayaran
require_once __DIR__ . '/function/profil.php';     // ubah profil & password

// Komponen tampilan kecil (kartu jadwal, badge, dll).
// Semua file function/komponen_*.php dimuat otomatis.
foreach (glob(__DIR__ . '/function/komponen_*.php') as $file_komponen) {
    require_once $file_komponen;
}

// ---------- 2. Tentukan halaman mana yang dibuka ----------
// Menu yang diizinkan. Menu baru tinggal ditambah di sini + buat foldernya di pages/.
$menu_boleh = ['beranda', 'jadwal', 'tiket', 'profil'];

// Aksi (file tambahan) yang diizinkan untuk tiap menu.
$aksi_boleh = [
    'jadwal' => ['pilih_kursi'],
    'tiket'  => ['pembayaran', 'e_ticket'],
];

// Judul yang tampil di tab browser.
$judul_menu = [
    'beranda' => 'Beranda',
    'jadwal'  => 'Cari Jadwal',
    'tiket'   => 'Tiket Saya',
    'profil'  => 'Profil Saya',
];

$page = $_GET['page'] ?? 'beranda';
$aksi = $_GET['aksi'] ?? '';

if (!in_array($page, $menu_boleh, true)) {
    $page = 'beranda';
}
$menu_aktif    = $page;               // dipakai components/header.php untuk menyalakan menu aktif
$judul_halaman = $judul_menu[$page];  // dipakai partials/head.php untuk <title>

// File isi yang akan ditampilkan
$file_isi = __DIR__ . "/pages/$page/$page.php";        // default: halaman utama menu
if ($aksi !== '' && in_array($aksi, $aksi_boleh[$page] ?? [], true)) {
    $file_isi = __DIR__ . "/pages/$page/$aksi.php";    // contoh: pages/jadwal/pilih_kursi.php
}

// ---------- 3. Keamanan: halaman mana yang butuh login ----------
// Terbuka untuk TAMU : beranda dan daftar jadwal (lihat-lihat saja).
// Wajib login        : pilih kursi (mulai memesan), tiket saya, pembayaran, e-ticket, profil.
$halaman_publik = ($page === 'beranda') || ($page === 'jadwal' && $file_isi === __DIR__ . '/pages/jadwal/jadwal.php');
if (!$halaman_publik) {
    wajib_penumpang();   // tamu -> dilempar ke login, nanti dikembalikan ke halaman ini
}

// ---------- 4. Data umum yang dipakai banyak halaman ----------
$sudah_login = sudah_login_penumpang();                   // true / false
$user_id     = $sudah_login ? user_id_login() : 0;        // 0 = tamu
$user        = $sudah_login ? get_user($conn, $user_id) : null;  // data penumpang (null bila tamu)
$pakai_qr = false;                      // pages/tiket/e_ticket.php mengubahnya jadi true (memuat library QR)
?>
<!-- head: <!DOCTYPE>, <html>, <head>, dan pembuka <body> -->
<?php include __DIR__ . '/partials/head.php' ?>

<!-- navbar atas + pembuka <main> -->
<?php include __DIR__ . '/components/header.php' ?>

<!-- pesan sukses / gagal (muncul sekali setelah redirect) -->
<?php komponen_flash() ?>

<!-- isi halaman (berganti-ganti sesuai URL) -->
<?php include $file_isi ?>

<!-- penutup </main> + footer -->
<?php include __DIR__ . '/components/footer.php' ?>

<!-- javascript + penutup </body></html> -->
<?php include __DIR__ . '/partials/script.php' ?>