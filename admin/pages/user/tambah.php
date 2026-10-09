<?php
// admin/pages/user/tambah.php
// Form tambah penumpang (bagian C = Create dari CRUD).
// Role 'penumpang' otomatis diisi oleh registrasiPenumpang().

include_once __DIR__ . '/../../database/koneksi.php';
require_once __DIR__ . '/../../function/registrasi.php';

$kembali = '/e-ticket-bus/admin/index.php?page=user';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hasil = registrasiPenumpang($koneksi, $_POST);
    if ($hasil === true) {
        // pakai JS karena header() sudah tidak bisa di sini (HTML sudah mulai tampil)
        echo '<script>window.location.href = "' . $kembali . '&pesan=tambah_ok";</script>';
        return;
    }
    $error = $hasil;
}
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Tambah User</h1>
    <p class="page-subtitle">Buat akun baru untuk penumpang.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php?page=user" class="text-decoration-none text-muted-green">Data User</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Tambah</li>
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

      <form action="" method="post">

        <div class="mb-3">
          <label for="nama" class="form-label-custom">Nama</label>
          <input type="text" class="form-control-custom" id="nama" name="nama"
            placeholder="Nama lengkap" maxlength="150"
            value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label for="username" class="form-label-custom">Username</label>
          <input type="text" class="form-control-custom" id="username" name="username"
            placeholder="Untuk login, tanpa spasi" pattern="[a-zA-Z0-9_]{4,20}" maxlength="20"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label-custom">Email</label>
          <input type="email" class="form-control-custom" id="email" name="email"
            placeholder="contoh@email.com" maxlength="150"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label for="no_hp" class="form-label-custom">No. HP</label>
          <input type="text" class="form-control-custom" id="no_hp" name="no_hp"
            placeholder="08xxxxxxxxxx" maxlength="20"
            value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
        </div>

        <div class="mb-4">
          <label for="password" class="form-label-custom">Password</label>
          <input type="password" class="form-control-custom" id="password" name="password"
            placeholder="Minimal 6 karakter" minlength="6" required>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn-custom btn-custom-primary">
            <i class="bi bi-check-lg"></i> Simpan
          </button>
          <a href="/e-ticket-bus/admin/index.php?page=user" class="btn-custom btn-custom-light">Batal</a>
        </div>

      </form>
    </div>
  </div>
</div>
<!-- form -->