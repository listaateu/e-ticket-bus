<?php
// admin/function/registrasi.php
// Dipakai di halaman admin (tambah user) dan nanti di halaman visitor (registrasi).

function registrasiPenumpang($conn, $data) {
    $nama     = trim($data['nama'] ?? '');
    $username = strtolower(trim($data['username'] ?? ''));
    $email    = trim($data['email'] ?? '');
    $no_hp    = trim($data['no_hp'] ?? '');
    $password = $data['password'] ?? '';

    if ($nama === '' || $no_hp === '') {
        return "Nama dan No. HP wajib diisi.";
    }
    if (!preg_match('/^[a-z0-9_]{4,20}$/', $username)) {
        return "Username 4-20 karakter, hanya huruf, angka, dan underscore.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Format email tidak valid.";
    }
    if (strlen($password) < 6) {
        return "Password minimal 6 karakter.";
    }

    // cek username / email sudah dipakai
    $cek = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
    $cek->bind_param("ss", $username, $email);
    $cek->execute();
    $res = $cek->get_result();
    if ($row = $res->fetch_assoc()) {
        return ($row['username'] === $username)
            ? "Username sudah dipakai."
            : "Email sudah terdaftar.";
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'penumpang';

    $stmt = $conn->prepare(
        "INSERT INTO users (nama, username, email, no_hp, password, role) VALUES (?,?,?,?,?,?)"
    );
    $stmt->bind_param("ssssss", $nama, $username, $email, $no_hp, $hash, $role);

    return $stmt->execute() ? true : "Gagal menyimpan data.";
}