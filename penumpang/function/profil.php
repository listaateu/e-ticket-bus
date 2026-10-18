<?php
/**
 * ============================================================
 * FILE         : penumpang/function/profil.php
 * FUNGSI       : Ubah data diri, foto profil, dan ganti password penumpang.
 *                Foto disimpan sebagai file uploads/profil/penumpang_<id>.jpg (tanpa mengubah tabel database).
 * DIPANGGIL DARI: index.php; dipakai pages/profil/profil.php
 * ============================================================
 */
/** Ubah profil & password penumpang */

function update_profil(mysqli $conn, int $id, string $nama, string $email, string $no_hp): array
{
    $nama = trim($nama); $email = trim($email); $no_hp = trim($no_hp);
    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Nama wajib diisi dan email harus valid.'];
    }
    if ($no_hp !== '') {
        $no_hp_rapi = normalisasi_no_hp($no_hp);
        if ($no_hp_rapi === null) {
            return [false, 'Nomor HP tidak valid. Isi angka saja, diawali 08 (contoh: 081234567890), 10-14 digit.'];
        }
        $no_hp = $no_hp_rapi;   // disimpan dalam bentuk 08xxxxxxxxxx
    }
    $cek = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
    $cek->bind_param('si', $email, $id);
    $cek->execute();
    if ($cek->get_result()->num_rows > 0) {
        return [false, 'Email sudah dipakai akun lain.'];
    }
    $stmt = $conn->prepare('UPDATE users SET nama = ?, email = ?, no_hp = ? WHERE id = ?');
    $stmt->bind_param('sssi', $nama, $email, $no_hp, $id);
    $stmt->execute();
    return [true, 'Profil berhasil diperbarui.'];
}

function ganti_password(mysqli $conn, int $id, string $lama, string $baru, string $ulang): array
{
    if (strlen($baru) < 6) {
        return [false, 'Password baru minimal 6 karakter.'];
    }
    if ($baru !== $ulang) {
        return [false, 'Konfirmasi password tidak sama.'];
    }
    $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $hash = (string) ($stmt->get_result()->fetch_column() ?: '');

    // Mendukung password_hash, md5, maupun plain dari data lama
    $cocok = password_verify($lama, $hash) || hash_equals($hash, md5($lama)) || hash_equals($hash, $lama);
    if (!$cocok) {
        return [false, 'Password lama salah.'];
    }
    $baru_hash = password_hash($baru, PASSWORD_DEFAULT);
    $up = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    $up->bind_param('si', $baru_hash, $id);
    $up->execute();
    return [true, 'Password berhasil diganti.'];
}

/* ---------- Foto profil ---------- */

if (!defined('FOTO_PENUMPANG_MAKS')) {
    define('FOTO_PENUMPANG_MAKS', 2 * 1024 * 1024);   // maksimal 2 MB
}

function folder_foto_penumpang(): string
{
    return realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profil';
}

function path_foto_penumpang(int $id): ?string
{
    foreach (['jpg', 'png', 'webp'] as $ext) {
        $path = folder_foto_penumpang() . DIRECTORY_SEPARATOR . "penumpang_{$id}.{$ext}";
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

/** Alamat foto untuk <img>, atau '' kalau belum punya foto (tampilan memakai inisial nama). */
function foto_penumpang_url(int $id): string
{
    $path = $id > 0 ? path_foto_penumpang($id) : null;
    if ($path === null) {
        return '';
    }
    return BASE_URL . '/uploads/profil/' . basename($path) . '?v=' . filemtime($path);
}

function hapus_foto_penumpang(int $id): void
{
    foreach (['jpg', 'png', 'webp'] as $ext) {
        $path = folder_foto_penumpang() . DIRECTORY_SEPARATOR . "penumpang_{$id}.{$ext}";
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/** Huruf awal nama untuk avatar bila belum ada foto: "lista ateu ambarianti" -> "LA" */
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
function simpan_foto_penumpang(int $id, array $file): ?string
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
    if ((int) $file['size'] > FOTO_PENUMPANG_MAKS) {
        return 'Ukuran foto terlalu besar. Maksimal 2 MB.';
    }

    $info = @getimagesize($file['tmp_name']);
    $tipe = $info[2] ?? 0;
    $ekstensi = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$tipe] ?? null;
    if ($info === false || $ekstensi === null) {
        return 'File harus berupa gambar JPG, PNG, atau WEBP.';
    }

    $folder = folder_foto_penumpang();
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
            hapus_foto_penumpang($id);
            $ok = imagejpeg($kanvas, $folder . DIRECTORY_SEPARATOR . "penumpang_{$id}.jpg", 88);
            imagedestroy($sumber);
            imagedestroy($kanvas);
            return $ok ? null : 'Foto gagal disimpan.';
        }
    }

    // Cadangan kalau GD tidak aktif
    hapus_foto_penumpang($id);
    return move_uploaded_file($file['tmp_name'], $folder . DIRECTORY_SEPARATOR . "penumpang_{$id}.{$ekstensi}") ? null : 'Foto gagal disimpan.';
}