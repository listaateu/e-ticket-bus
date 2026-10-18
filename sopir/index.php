<?php
/**
 * ============================================================
 * FILE   : sopir/index.php
 * FUNGSI : PINTU MASUK satu-satunya untuk semua halaman sopir/kondektur.
 *          File ini tidak berisi tampilan; ia hanya menyusun halaman dari potongan-potongan file lain
 *          (sama seperti penumpang/index.php).
 *
 * URL yang tersedia (semuanya wajib login sebagai sopir/kondektur):
 *   index.php                                     -> pages/beranda/beranda.php  (ringkasan hari ini)
 *   index.php?page=jadwal                         -> pages/jadwal/jadwal.php    (daftar jadwal saya)
 *   index.php?page=jadwal&aksi=manifest&id=3      -> pages/jadwal/manifest.php  (daftar penumpang satu jadwal)
 *   index.php?page=scan&id=3                      -> pages/scan/scan.php        (scan QR e-ticket)
 *   index.php?page=profil                         -> pages/profil/profil.php    (foto, no HP, password; nama & email dikunci)
 *
 *   Endpoint (bukan halaman, hanya memproses lalu berhenti):
 *   index.php?page=scan&aksi=validasi             -> POST, balasan JSON hasil scan tiket (satu-satunya jalan mencatat naik)
 *
 * KETERHUBUNGAN DENGAN ADMIN & PENUMPANG (lewat database yang sama):
 *   - Jadwal sopir = schedules.sopir yang namanya sama dengan akun ini (diatur admin di Jadwal & Rute).
 *   - Manifest hanya berisi penumpang yang pembayarannya sudah LUNAS (diverifikasi admin).
 *   - QR e-ticket penumpang = booking_penumpang.kode_tiket. Setelah discan, status_naik/waktu_naik/
 *     diperiksa_oleh terisi; admin melihatnya di Detail Pemesanan, penumpang melihat "Sudah naik" di e-ticket.
 *
 * URUTAN TAMPILAN (dari atas ke bawah):
 *   partials/head.php -> components/header.php -> flash -> [isi pages/...]
 *   -> components/footer.php -> partials/script.php
 * ============================================================
 */

// Wajib paling atas: menahan output supaya redirect() / JSON tidak error "headers already sent".
ob_start();

// ---------- 1. Muat semua fungsi (folder function/) ----------
require_once __DIR__ . '/database/koneksi.php';       // membuat variabel $conn
require_once __DIR__ . '/function/helpers.php';       // e(), tanggal_indo(), url(), redirect(), flash, csrf
require_once __DIR__ . '/function/auth.php';          // cek login & role sopir (kondektur)

// ---------- Endpoint kecil: cek siapa yang sedang login (JSON) ----------
// Dipanggil otomatis oleh script di partials/script.php (tiap 5 detik & saat tab dibuka kembali).
// Tujuannya: kalau di tab lain login berganti (mis. sopir <-> penumpang) atau sudah logout,
// tab ini ikut refresh sendiri. Tidak menampilkan halaman, hanya mengirim JSON lalu berhenti.
if (isset($_GET['cek_sesi'])) {
    $sesi_sekarang = user_id_login() . '|' . role_login();
    session_write_close();                       // lepas kunci session supaya tab lain tidak tertahan
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['sesi' => $sesi_sekarang]);
    exit;
}
require_once __DIR__ . '/function/jadwal_sopir.php';  // query jadwal & manifest
require_once __DIR__ . '/function/scan_tiket.php';    // validasi tiket & status naik
require_once __DIR__ . '/function/profil.php';        // ubah no HP, foto, password sopir

// Komponen tampilan kecil. Semua file function/komponen_*.php dimuat otomatis.
foreach (glob(__DIR__ . '/function/komponen_*.php') as $file_komponen) {
    require_once $file_komponen;
}

// ---------- 2. Keamanan: hanya sopir yang sudah login ----------
wajib_sopir();   // bukan sopir -> dilempar ke login/index.php

$sopir = get_sopir($conn, user_id_login());
if (!$sopir) {
    // Akun sudah dihapus admin: bersihkan session lalu minta login lagi
    $_SESSION = [];
    session_destroy();
    redirect(LOGIN_URL);
}

// ---------- 3. Tentukan halaman mana yang dibuka ----------
// Menu baru: tambah di sini + buat foldernya di pages/ + tambah di peta url() (function/helpers.php).
$menu_boleh = ['beranda', 'jadwal', 'scan', 'profil'];

// Aksi yang diizinkan untuk tiap menu ('manifest' = halaman; 'validasi' = endpoint).
$aksi_boleh = [
    'jadwal' => ['manifest'],
    'scan'   => ['validasi'],
];

$judul_menu = [
    'beranda' => 'Beranda',
    'jadwal'  => 'Jadwal Saya',
    'scan'    => 'Scan Tiket',
    'profil'  => 'Profil Saya',
];

$page = $_GET['page'] ?? 'beranda';
$aksi = $_GET['aksi'] ?? '';

if (!in_array($page, $menu_boleh, true)) {
    $page = 'beranda';
}
if (!in_array($aksi, $aksi_boleh[$page] ?? [], true)) {
    $aksi = '';
}
$menu_aktif    = $page;               // dipakai components/header.php untuk menyalakan menu aktif
$judul_halaman = $judul_menu[$page];  // dipakai partials/head.php untuk <title>

$file_isi = __DIR__ . "/pages/$page/$page.php";            // default: halaman utama menu
if ($aksi === 'manifest') {
    $file_isi = __DIR__ . '/pages/jadwal/manifest.php';
}

$pakai_scanner = ($page === 'scan');   // partials/script.php memuat library kamera hanya di halaman Scan

// ---------- 4. Endpoint: validasi hasil scan (JSON) ----------
// Dipanggil sopir.js setiap kali QR terbaca atau kode diketik manual. Tidak menampilkan halaman.
if ($page === 'scan' && $aksi === 'validasi') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
        $hasil = ['ok' => false, 'status' => 'sesi', 'tone' => 'danger', 'judul' => 'Sesi habis',
                  'pesan' => 'Muat ulang halaman ini lalu coba lagi.', 'penumpang' => null, 'hitung' => null];
    } else {
        $jadwal_id = (int) ($_POST['jadwal'] ?? 0);
        if (!get_jadwal_sopir($conn, $jadwal_id, $sopir['nama'])) {
            $hasil = ['ok' => false, 'status' => 'jadwal_tidak_valid', 'tone' => 'danger', 'judul' => 'Jadwal tidak valid',
                      'pesan' => 'Pilih jadwal milik Anda terlebih dahulu.', 'penumpang' => null, 'hitung' => null];
        } else {
            $hasil = validasi_tiket($conn, (int) $sopir['id'], $sopir['nama'], $jadwal_id, (string) ($_POST['kode'] ?? ''));
            $hasil['hitung'] = hitung_naik($conn, $jadwal_id);
        }
    }
    ob_end_clean();                      // buang output apa pun yang sempat tertahan
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($hasil, JSON_UNESCAPED_UNICODE);
    exit;
}

// CATATAN: status naik HANYA berubah lewat endpoint validasi di atas (scan QR / ketik kode).
// Tidak ada endpoint naik / batal naik manual.
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