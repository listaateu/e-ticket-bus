<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/pages/user/update.php
// Halaman ubah penumpang (bagian U = Update dari CRUD).
// File ini mengurus SEMUANYA: tampilkan form + proses UPDATE ke database.

include_once __DIR__ . '/../../database/koneksi.php';
require_once __DIR__ . '/../../function/registrasi.php';   // normalisasiNoHp()

$kembali = BASE_URL . '/admin/index.php?page=user';
$role    = 'penumpang';
$id      = param_id('id');
$error   = ''; // pesan kesalahan, kosong = tidak ada masalah

// ---------- 1. AMBIL DATA YANG MAU DIUBAH ----------
// "AND role = ?" supaya halaman ini tidak bisa dipakai membuka akun admin
$ambil = $koneksi->prepare("SELECT id, nama, username, email, no_hp FROM users WHERE id = ? AND role = ?");
$ambil->bind_param('is', $id, $role);
$ambil->execute();
$user = $ambil->get_result()->fetch_assoc();

// Kalau id tidak ditemukan, balik ke tabel
if (!$user) {
    echo '<script>window.location.href = "' . $kembali . '";</script>';
    return;
}

// ---------- 2. PROSES UPDATE (jalan hanya saat form disubmit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valid()) {
    $error = 'Sesi formulir tidak valid atau sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $no_hp    = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? ''; // boleh kosong = password tidak diganti

    $no_hp_rapi = normalisasiNoHp($no_hp);
    if ($nama === '' || $no_hp_rapi === null || !preg_match('/^[a-z0-9_]{4,20}$/', $username)
        || !filter_var($email, FILTER_VALIDATE_EMAIL) || ($password !== '' && strlen($password) < 6)) {
        $error = 'Data belum benar. Cek lagi nama, username (4-20 huruf/angka/underscore), email, no. HP (angka saja, diawali 08, 10-14 digit), dan password (minimal 6 karakter kalau diisi).';
    } else {
        $no_hp = $no_hp_rapi;   // sudah dirapikan jadi 08xxxxxxxxxx
        // Cek username / email tidak dipakai akun LAIN (id <> ? supaya akun ini sendiri tidak dihitung)
        $cek = $koneksi->prepare("SELECT username, email FROM users WHERE (username = ? OR email = ?) AND id <> ?");
        $cek->bind_param('ssi', $username, $email, $id);
        $cek->execute();
        $dipakai = $cek->get_result()->fetch_assoc();

        if ($dipakai) {
            $error = ($dipakai['username'] === $username)
                ? 'Username itu sudah dipakai akun lain.'
                : 'Email itu sudah dipakai akun lain. Pakai email lain.';
        } else {
            if ($password === '') {
                // Password dikosongkan -> password tidak diganti
                $ubah = $koneksi->prepare("UPDATE users SET nama = ?, username = ?, email = ?, no_hp = ? WHERE id = ? AND role = ?");
                $ubah->bind_param('ssssis', $nama, $username, $email, $no_hp, $id, $role);
            } else {
                // Password diisi -> ikut diganti (diacak dulu)
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ubah = $koneksi->prepare("UPDATE users SET nama = ?, username = ?, email = ?, no_hp = ?, password = ? WHERE id = ? AND role = ?");
                $ubah->bind_param('sssssis', $nama, $username, $email, $no_hp, $hash, $id, $role);
            }
            $ubah->execute();

            // Berhasil -> balik ke tabel (pakai JS karena header() sudah tidak bisa di sini)
            echo '<script>window.location.href = "' . $kembali . '&pesan=update_ok";</script>';
            return;
        }
    }

    // Kalau sampai sini berarti ada error: isi form dengan ketikan user
    $user['nama']     = $nama;
    $user['username'] = $username;
    $user['email']    = $email;
    $user['no_hp']    = $no_hp;
}
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Ubah User</h1>
    <p class="page-subtitle">Perbarui nama, username, email, no. HP, atau password.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php?page=user" class="text-decoration-none text-muted-green">Data User</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Ubah</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($error !== '') : ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- form -->
<div class="row">
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Data User</h5>

      <!-- action kosong = kirim ke alamat halaman ini sendiri (update.php), bukan ke function -->
      <form action="" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label for="nama" class="form-label-custom">Nama</label>
          <input type="text" class="form-control-custom" id="nama" name="nama"
            value="<?= htmlspecialchars($user['nama']) ?>" maxlength="150" required>
        </div>

        <div class="mb-3">
          <label for="username" class="form-label-custom">Username</label>
          <input type="text" class="form-control-custom" id="username" name="username"
            value="<?= htmlspecialchars($user['username']) ?>" pattern="[a-zA-Z0-9_]{4,20}" maxlength="20" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label-custom">Email</label>
          <input type="email" class="form-control-custom" id="email" name="email"
            value="<?= htmlspecialchars($user['email']) ?>" maxlength="150" required>
        </div>

        <div class="mb-3">
          <label for="no_hp" class="form-label-custom">No. HP</label>
          <input class="form-control-custom" id="no_hp" name="no_hp" type="tel" inputmode="numeric" autocomplete="tel" pattern="(08|62)[0-9]{8,12}" maxlength="14"
            title="Angka saja, diawali 08, 10-14 digit" oninput="this.value = this.value.replace(/\D/g, '')"
            value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" required>
        </div>

        <div class="mb-4">
          <label for="password" class="form-label-custom">Password Baru</label>
          <input type="password" class="form-control-custom" id="password" name="password"
            placeholder="Kosongkan jika tidak ingin mengganti password" minlength="6">
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn-custom btn-custom-primary">
            <i class="bi bi-check-lg"></i> Simpan Perubahan
          </button>
          <a href="<?= BASE_URL ?>/admin/index.php?page=user" class="btn-custom btn-custom-light">Batal</a>
        </div>

      </form>
    </div>
  </div>
</div>
<!-- form -->