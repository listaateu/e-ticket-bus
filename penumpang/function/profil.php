<?php
/**
 * ============================================================
 * FILE         : penumpang/function/profil.php
 * FUNGSI       : Ubah data diri dan ganti password penumpang.
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
    if ($no_hp !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $no_hp)) {
        return [false, 'Nomor HP tidak valid.'];
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