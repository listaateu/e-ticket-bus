<?php
// admin/function/registrasi.php
// Dipakai di halaman admin (tambah user) dan nanti di halaman visitor (registrasi).

/**
 * Validasi & rapikan nomor HP Indonesia.
 * - Spasi dan tanda minus boleh diketik, lalu dibuang.
 * - Hanya ANGKA (boleh diawali + untuk +62). Huruf atau simbol lain -> ditolak.
 * - Harus nomor seluler: 08xx..., 628xx..., atau +628xx... (total 10-14 digit setelah dirapikan).
 * Hasil dirapikan jadi bentuk 08xxxxxxxxxx. Tidak valid -> null.
 */
function normalisasiNoHp(string $no): ?string {
    $no = preg_replace('/[\s\-]/', '', trim($no));
    if (!preg_match('/^(?:\+62|62|0)8[0-9]{8,12}$/D', $no)) {
        return null;
    }
    return preg_replace('/^(?:\+62|62)/', '0', $no);
}

function registrasiPenumpang($conn, $data) {
    $nama     = trim($data['nama'] ?? '');
    $username = strtolower(trim($data['username'] ?? ''));
    $email    = trim($data['email'] ?? '');
    $no_hp    = trim($data['no_hp'] ?? '');
    $password = $data['password'] ?? '';

    if ($nama === '' || $no_hp === '') {
        return "Nama dan No. HP wajib diisi.";
    }
    $no_hp_rapi = normalisasiNoHp($no_hp);
    if ($no_hp_rapi === null) {
        return "No. HP tidak valid. Isi angka saja, diawali 08 (contoh: 081234567890), 10-14 digit.";
    }
    $no_hp = $no_hp_rapi;
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