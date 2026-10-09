<?php
// admin/pages/sopir/update.php
// Halaman ubah sopir (bagian U = Update dari CRUD).
// File ini mengurus SEMUANYA: tampilkan form + proses UPDATE ke database.

include_once __DIR__ . '/../../database/koneksi.php';

$kembali = '/e-ticket-bus/admin/index.php?page=sopir';
$role    = 'kondektur';
$id      = (int) ($_GET['id'] ?? 0);
$error   = ''; // pesan kesalahan, kosong = tidak ada masalah

// ---------- 1. AMBIL DATA YANG MAU DIUBAH ----------
// "AND role = ?" supaya halaman ini tidak bisa dipakai membuka akun admin
$ambil = $koneksi->prepare("SELECT id, nama, email FROM users WHERE id = ? AND role = ?");
$ambil->bind_param('is', $id, $role);
$ambil->execute();
$user = $ambil->get_result()->fetch_assoc();

// Kalau id tidak ditemukan, balik ke tabel
if (!$user) {
    echo '<script>window.location.href = "' . $kembali . '";</script>';
    return;
}

// ---------- 2. PROSES UPDATE (jalan hanya saat form disubmit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? ''; // boleh kosong = password tidak diganti

    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || ($password !== '' && strlen($password) < 6)) {
        $error = 'Data belum benar. Cek lagi nama, email, dan password (minimal 6 karakter kalau diisi).';
    } else {
        // Cek email tidak dipakai akun LAIN (id <> ? supaya akun ini sendiri tidak dihitung)
        $cek = $koneksi->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $cek->bind_param('si', $email, $id);
        $cek->execute();

        if ($cek->get_result()->num_rows > 0) {
            $error = 'Email itu sudah dipakai akun lain. Pakai email lain.';
        } else {
            if ($password === '') {
                // Password dikosongkan -> ubah nama & email saja
                $ubah = $koneksi->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ? AND role = ?");
                $ubah->bind_param('ssis', $nama, $email, $id, $role);
            } else {
                // Password diisi -> ikut diganti (diacak dulu)
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ubah = $koneksi->prepare("UPDATE users SET nama = ?, email = ?, password = ? WHERE id = ? AND role = ?");
                $ubah->bind_param('sssis', $nama, $email, $hash, $id, $role);
            }
            $ubah->execute();

            // Berhasil -> balik ke tabel (pakai JS karena header() sudah tidak bisa di sini)
            echo '<script>window.location.href = "' . $kembali . '&pesan=update_ok";</script>';
            return;
        }
    }

    // Kalau sampai sini berarti ada error: isi form dengan ketikan user
    $user['nama']  = $nama;
    $user['email'] = $email;
}
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Ubah Sopir</h1>
    <p class="page-subtitle">Perbarui nama, email, atau password.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php?page=sopir" class="text-decoration-none text-muted-green">Data Sopir</a></li>
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
      <h5 class="card-title mb-4">Data Sopir</h5>

      <!-- action kosong = kirim ke alamat halaman ini sendiri (update.php), bukan ke function -->
      <form action="" method="post">

        <div class="mb-3">
          <label for="nama" class="form-label-custom">Nama</label>
          <input type="text" class="form-control-custom" id="nama" name="nama"
            value="<?= htmlspecialchars($user['nama']) ?>" maxlength="150" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label-custom">Email</label>
          <input type="email" class="form-control-custom" id="email" name="email"
            value="<?= htmlspecialchars($user['email']) ?>" maxlength="150" required>
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
          <a href="/e-ticket-bus/admin/index.php?page=sopir" class="btn-custom btn-custom-light">Batal</a>
        </div>

      </form>
    </div>
  </div>
</div>
<!-- form -->