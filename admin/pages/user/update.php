<?php
// admin/pages/user/update.php
// Form ubah user (bagian U = Update dari CRUD).
// Datanya dikirim ke function/user.php?aksi=ubah.

include_once __DIR__ . '/../../database/koneksi.php';

$id = (int) ($_GET['id'] ?? 0);

$ambil = $koneksi->prepare("SELECT id, nama, email, role FROM users WHERE id = ?");
$ambil->bind_param('i', $id);
$ambil->execute();
$user = $ambil->get_result()->fetch_assoc();

// Kalau id tidak ditemukan, balik ke tabel
if (!$user) {
    echo '<script>window.location.href = "/e-ticket-bus/admin/index.php?page=user";</script>';
    return;
}

$pesan = $_GET['pesan'] ?? '';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Ubah User</h1>
    <p class="page-subtitle">Perbarui data akun. Kosongkan password jika tidak ingin menggantinya.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php?page=user" class="text-decoration-none text-muted-green">Data User</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Ubah</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'email_ada') : ?>
  <div class="alert alert-danger">Email itu sudah dipakai user lain. Pakai email lain.</div>
<?php elseif ($pesan === 'tidak_valid') : ?>
  <div class="alert alert-danger">Data belum benar. Cek nama, format email, role, dan password (minimal 6 karakter jika diisi).</div>
<?php endif; ?>

<!-- form -->
<div class="row">
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Data User</h5>

      <form action="/e-ticket-bus/admin/function/user.php?aksi=ubah" method="post">
        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">

        <div class="mb-3">
          <label for="nama" class="form-label-custom">Nama</label>
          <input type="text" class="form-control-custom" id="nama" name="nama"
            value="<?= htmlspecialchars($user['nama']) ?>" maxlength="100" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label-custom">Email</label>
          <input type="email" class="form-control-custom" id="email" name="email"
            value="<?= htmlspecialchars($user['email']) ?>" maxlength="100" required>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label-custom">Password Baru <span class="text-muted">(opsional)</span></label>
          <input type="password" class="form-control-custom" id="password" name="password"
            placeholder="Kosongkan jika tidak diganti" minlength="6">
        </div>

        <div class="mb-4">
          <label for="role" class="form-label-custom">Role</label>
          <select class="form-select-custom" id="role" name="role" required>
            <?php foreach (['admin' => 'Admin', 'kondektur' => 'Kondektur', 'penumpang' => 'Penumpang'] as $nilai => $label) : ?>
              <option value="<?= $nilai ?>" <?= $user['role'] === $nilai ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn-custom btn-custom-primary">
            <i class="bi bi-check-lg"></i> Simpan Perubahan
          </button>
          <a href="/e-ticket-bus/admin/index.php?page=user" class="btn-custom btn-custom-light">Batal</a>
        </div>

      </form>
    </div>
  </div>
</div>
<!-- form -->