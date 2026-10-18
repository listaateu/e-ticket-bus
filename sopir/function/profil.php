<?php
/**
 * ============================================================
 * FILE         : sopir/function/profil.php
 * FUNGSI       : Profil sopir/kondektur: ubah nomor HP, foto profil, dan ganti password.
 * DIPANGGIL DARI: sopir/index.php; dipakai pages/profil/profil.php
 * CATATAN      : DATA YANG DIKUNCI (hanya admin yang boleh ubah, lewat menu Sopir di admin):
 *                nama, username, email, dan role.
 *                Alasannya: jadwal sopir dicocokkan lewat NAMA (schedules.sopir = users.nama) dan email dipakai login,
 *                jadi kalau sopir bebas mengubahnya, jadwalnya bisa hilang / akunnya bisa disalahgunakan.
 *                Data yang BOLEH diubah sopir sendiri: no_hp, foto profil, password.
 *                Foto disimpan sebagai file uploads/profil/sopir_<id>.jpg (tanpa mengubah tabel database).
 * ============================================================
 */

if (!defined('FOTO_SOPIR_MAKS')) {
    define('FOTO_SOPIR_MAKS', 2 * 1024 * 1024);   // maksimal 2 MB
}

/* ---------- Nomor HP ---------- */

if (!function_exists('normalisasi_no_hp')) {
    /** "0812-3456-7890" / "+62812..." -> "081234567890". Tidak valid -> null. (Aturannya sama dengan halaman penumpang.) */
    function normalisasi_no_hp(string $no): ?string
    {
        $no = preg_replace('/[\s\-]/', '', trim($no));
        if (!preg_match('/^(?:\+62|62|0)(8[1-9][0-9]{7,11})$/D', $no, $m)) {
            return null;
        }
        $rapi = '0' . $m[1];
        if (preg_match('/(\d)\1{6,}/', $rapi)) {
            return null;
        }
        if (count(array_unique(str_split(substr($rapi, 4)))) < 3) {
            return null;
        }
        return $rapi;
    }
}

/** Ubah HANYA nomor HP. Nama, username, dan email sengaja tidak ikut diubah. */
function update_kontak_sopir(mysqli $conn, int $id, string $no_hp): array
{
    $no_hp = trim($no_hp);
    if ($no_hp !== '') {
        $rapi = normalisasi_no_hp($no_hp);
        if ($rapi === null) {
            return [false, 'Nomor HP tidak valid. Isi angka saja, diawali 08 (contoh: 081234567890), 10-14 digit.'];
        }
        $no_hp = $rapi;
    }
    $stmt = $conn->prepare("UPDATE users SET no_hp = ? WHERE id = ? AND role = 'kondektur'");
    $stmt->bind_param('si', $no_hp, $id);
    $stmt->execute();
    return [true, 'Nomor HP berhasil diperbarui.'];
}

/* ---------- Password ---------- */

function ganti_password_sopir(mysqli $conn, int $id, string $lama, string $baru, string $ulang): array
{
    if (strlen($baru) < 6) {
        return [false, 'Password baru minimal 6 karakter.'];
    }
    if ($baru !== $ulang) {
        return [false, 'Konfirmasi password tidak sama.'];
    }
    if ($baru === $lama) {
        return [false, 'Password baru harus berbeda dari password lama.'];
    }
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? AND role = 'kondektur'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $hash = (string) ($stmt->get_result()->fetch_column() ?: '');

    // Mendukung password_hash, md5, maupun plain dari data lama
    $cocok = password_verify($lama, $hash) || hash_equals($hash, md5($lama)) || hash_equals($hash, $lama);
    if (!$cocok) {
        return [false, 'Password lama salah.'];
    }
    $baru_hash = password_hash($baru, PASSWORD_DEFAULT);
    $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'kondektur'");
    $up->bind_param('si', $baru_hash, $id);
    $up->execute();
    return [true, 'Password berhasil diganti.'];
}

/* ---------- Foto profil ---------- */

function folder_foto_sopir(): string
{
    return realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profil';
}

function path_foto_sopir(int $id): ?string
{
    foreach (['jpg', 'png', 'webp'] as $ext) {
        $path = folder_foto_sopir() . DIRECTORY_SEPARATOR . "sopir_{$id}.{$ext}";
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

/** Alamat foto untuk <img>, atau '' kalau belum punya foto (tampilan memakai inisial nama). */
function foto_sopir_url(int $id): string
{
    $path = $id > 0 ? path_foto_sopir($id) : null;
    if ($path === null) {
        return '';
    }
    return BASE_URL . '/uploads/profil/' . basename($path) . '?v=' . filemtime($path);
}

function hapus_foto_sopir(int $id): void
{
    foreach (['jpg', 'png', 'webp'] as $ext) {
        $path = folder_foto_sopir() . DIRECTORY_SEPARATOR . "sopir_{$id}.{$ext}";
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/** Huruf awal nama untuk avatar bila belum ada foto: "Agus Kurniadi" -> "AK" */
function inisial_nama(string $nama): string
{
    $kata = preg_split('/\s+/', trim($nama)) ?: [];
    $hasil = '';
    foreach (array_slice(array_filter($kata), 0, 2) as $k) {
        $hasil .= mb_strtoupper(mb_substr($k, 0, 1));
    }
    return $hasil !== '' ? $hasil : '?';
}

/**
 * Simpan foto dari $_FILES['foto']. Hasil: null kalau berhasil, atau teks pesan kesalahan.
 * Jenis file dicek dari ISI file; bila GD aktif gambar digambar ulang (persegi, maks 512 px).
 */
function simpan_foto_sopir(int $id, array $file): ?string
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
    if ((int) $file['size'] > FOTO_SOPIR_MAKS) {
        return 'Ukuran foto terlalu besar. Maksimal 2 MB.';
    }

    $info = @getimagesize($file['tmp_name']);
    $tipe = $info[2] ?? 0;
    $ekstensi = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$tipe] ?? null;
    if ($info === false || $ekstensi === null) {
        return 'File harus berupa gambar JPG, PNG, atau WEBP.';
    }

    $folder = folder_foto_sopir();
    if (!is_dir($folder) && !@mkdir($folder, 0755, true)) {
        return 'Folder uploads/profil tidak bisa dibuat. Buat manual foldernya.';
    }

    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $sumber = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if ($sumber !== false) {
            $lebar  = imagesx($sumber);
            $tinggi = imagesy($sumber);
            $sisi   = min($lebar, $tinggi);
            $ukuran = min(512, $sisi);
            $kanvas = imagecreatetruecolor($ukuran, $ukuran);
            imagefill($kanvas, 0, 0, imagecolorallocate($kanvas, 255, 255, 255));
            imagecopyresampled($kanvas, $sumber, 0, 0, intdiv($lebar - $sisi, 2), intdiv($tinggi - $sisi, 2), $ukuran, $ukuran, $sisi, $sisi);
            hapus_foto_sopir($id);
            $ok = imagejpeg($kanvas, $folder . DIRECTORY_SEPARATOR . "sopir_{$id}.jpg", 88);
            imagedestroy($sumber);
            imagedestroy($kanvas);
            return $ok ? null : 'Foto gagal disimpan.';
        }
    }

    // Cadangan kalau GD tidak aktif
    hapus_foto_sopir($id);
    return move_uploaded_file($file['tmp_name'], $folder . DIRECTORY_SEPARATOR . "sopir_{$id}.{$ekstensi}") ? null : 'Foto gagal disimpan.';
}