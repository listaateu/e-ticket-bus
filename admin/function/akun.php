<?php
// admin/function/akun.php
// Fungsi bantu untuk halaman "Akun Saya" admin: foto profil, data diri, dan ganti password.
// Foto disimpan sebagai file di folder uploads/profil/ (admin_<id>.jpg), jadi TIDAK perlu mengubah tabel database.

if (!defined('FOTO_ADMIN_MAKS')) {
    define('FOTO_ADMIN_MAKS', 2 * 1024 * 1024);   // maksimal 2 MB
}

/** Folder tempat foto profil disimpan (di folder uploads/ pada akar project). */
function folder_foto_admin(): string
{
    return realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profil';
}

/** Cari file foto milik admin. Hasil: path lengkap, atau null kalau belum punya foto. */
function path_foto_admin(int $id): ?string
{
    foreach (['jpg', 'png', 'webp'] as $ext) {
        $path = folder_foto_admin() . DIRECTORY_SEPARATOR . "admin_{$id}.{$ext}";
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

/** Alamat foto untuk <img>. Kalau belum punya foto -> gambar bawaan. ?v= supaya browser memuat foto terbaru. */
function foto_admin_url(int $id): string
{
    $path = $id > 0 ? path_foto_admin($id) : null;
    if ($path === null) {
        return BASE_URL . '/admin/assets/images/avatar.png';
    }
    return BASE_URL . '/uploads/profil/' . basename($path) . '?v=' . filemtime($path);
}

/** Hapus semua foto milik admin. */
function hapus_foto_admin(int $id): void
{
    foreach (['jpg', 'png', 'webp'] as $ext) {
        $path = folder_foto_admin() . DIRECTORY_SEPARATOR . "admin_{$id}.{$ext}";
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/**
 * Simpan foto yang diunggah dari $_FILES['foto'].
 * Hasil: null kalau berhasil, atau teks pesan kesalahan.
 * Keamanan: jenis file dicek dari ISI file (bukan nama), nama file dibuat sendiri, dan kalau library GD
 * tersedia gambar digambar ulang (dipotong persegi, maks 512 px) sehingga isi berbahaya ikut terbuang.
 */
function simpan_foto_admin(int $id, array $file): ?string
{
    $kode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($kode === UPLOAD_ERR_NO_FILE) {
        return 'Pilih foto dulu.';
    }
    if ($kode === UPLOAD_ERR_INI_SIZE || $kode === UPLOAD_ERR_FORM_SIZE) {
        return 'Ukuran foto terlalu besar. Maksimal 2 MB.';
    }
    if ($kode !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        return 'Foto gagal diunggah. Coba lagi.';
    }
    if ((int) $file['size'] > FOTO_ADMIN_MAKS) {
        return 'Ukuran foto terlalu besar. Maksimal 2 MB.';
    }

    $info = @getimagesize($file['tmp_name']);
    $tipe = $info[2] ?? 0;
    $ekstensi = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$tipe] ?? null;
    if ($info === false || $ekstensi === null) {
        return 'File harus berupa gambar JPG, PNG, atau WEBP.';
    }

    $folder = folder_foto_admin();
    if (!is_dir($folder) && !@mkdir($folder, 0755, true)) {
        return 'Folder uploads/profil tidak bisa dibuat. Buat manual foldernya.';
    }

    $baru = null;
    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $sumber = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if ($sumber !== false) {
            $lebar = imagesx($sumber);
            $tinggi = imagesy($sumber);
            $sisi = min($lebar, $tinggi);                      // potong jadi persegi dari tengah
            $ukuran = min(512, $sisi);
            $kanvas = imagecreatetruecolor($ukuran, $ukuran);
            imagefill($kanvas, 0, 0, imagecolorallocate($kanvas, 255, 255, 255));   // latar putih untuk PNG transparan
            imagecopyresampled($kanvas, $sumber, 0, 0, intdiv($lebar - $sisi, 2), intdiv($tinggi - $sisi, 2), $ukuran, $ukuran, $sisi, $sisi);
            $baru = $folder . DIRECTORY_SEPARATOR . "admin_{$id}.jpg";
            hapus_foto_admin($id);
            $ok = imagejpeg($kanvas, $baru, 88);
            imagedestroy($sumber);
            imagedestroy($kanvas);
            return $ok ? null : 'Foto gagal disimpan.';
        }
    }

    // Cadangan kalau GD tidak aktif: simpan apa adanya dengan nama & ekstensi yang kita tentukan sendiri
    hapus_foto_admin($id);
    $baru = $folder . DIRECTORY_SEPARATOR . "admin_{$id}.{$ekstensi}";
    return move_uploaded_file($file['tmp_name'], $baru) ? null : 'Foto gagal disimpan.';
}

/** Data admin yang sedang login (nama, username, email, no_hp). Disimpan sementara supaya tidak query berulang. */
function data_admin(mysqli $koneksi, bool $segarkan = false): ?array
{
    static $cache = null;
    if ($cache !== null && !$segarkan) {
        return $cache;
    }
    $id = idLogin();
    $stmt = $koneksi->prepare("SELECT id, nama, username, email, no_hp FROM users WHERE id = ? AND role = 'admin'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $cache = $stmt->get_result()->fetch_assoc() ?: null;
    return $cache;
}

/** Ubah data diri admin. Hasil: [berhasil, pesan]. */
function ubah_data_admin(mysqli $koneksi, int $id, string $nama, string $username, string $email, string $no_hp): array
{
    $nama = trim($nama);
    $username = trim($username);
    $email = strtolower(trim($email));
    $no_hp = trim($no_hp);

    if ($nama === '' || mb_strlen($nama, 'UTF-8') > 150) {
        return [false, 'Nama wajib diisi (maksimal 150 karakter).'];
    }
    if (!preg_match('/^[A-Za-z0-9_]{4,20}$/', $username)) {
        return [false, 'Username 4-20 karakter, hanya huruf, angka, dan underscore.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Format email tidak valid.'];
    }
    if ($no_hp !== '') {
        require_once __DIR__ . '/registrasi.php';
        $rapi = normalisasiNoHp($no_hp);
        if ($rapi === null) {
            return [false, 'No. HP tidak valid. Isi angka saja, diawali 08 (contoh: 081234567890), 10-14 digit.'];
        }
        $no_hp = $rapi;
    }

    $cek = $koneksi->prepare('SELECT username, email FROM users WHERE (username = ? OR email = ?) AND id <> ?');
    $cek->bind_param('ssi', $username, $email, $id);
    $cek->execute();
    if ($dipakai = $cek->get_result()->fetch_assoc()) {
        return [false, strcasecmp((string) $dipakai['username'], $username) === 0
            ? 'Username itu sudah dipakai akun lain.'
            : 'Email itu sudah dipakai akun lain.'];
    }

    $ubah = $koneksi->prepare("UPDATE users SET nama = ?, username = ?, email = ?, no_hp = ? WHERE id = ? AND role = 'admin'");
    $ubah->bind_param('ssssi', $nama, $username, $email, $no_hp, $id);
    $ubah->execute();
    $_SESSION['nama'] = $nama;   // supaya nama di pojok kanan atas ikut berubah
    return [true, 'Data akun berhasil diperbarui.'];
}

/** Ganti password admin (wajib mengisi password lama). Hasil: [berhasil, pesan]. */
function ganti_password_admin(mysqli $koneksi, int $id, string $lama, string $baru, string $ulang): array
{
    if ($lama === '') {
        return [false, 'Isi password lama dulu.'];
    }
    if (strlen($baru) < 6) {
        return [false, 'Password baru minimal 6 karakter.'];
    }
    if ($baru !== $ulang) {
        return [false, 'Konfirmasi password baru tidak sama.'];
    }
    $stmt = $koneksi->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $hash = (string) ($stmt->get_result()->fetch_column() ?: '');
    if ($hash === '' || !password_verify($lama, $hash)) {
        return [false, 'Password lama salah.'];
    }
    $hash_baru = password_hash($baru, PASSWORD_DEFAULT);
    $up = $koneksi->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
    $up->bind_param('si', $hash_baru, $id);
    $up->execute();
    return [true, 'Password berhasil diganti.'];
}