<?php
// admin/pages/sopir/tambah.php
// Form tambah sopir (bagian C = Create dari CRUD).
// Form ini TIDAK menyimpan sendiri; datanya dikirim ke function/sopir.php?aksi=simpan.
// Tidak ada pilihan Role: role 'kondektur' otomatis diisi oleh function.

$pesan = $_GET['pesan'] ?? '';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Tambah Sopir</h1>
    <p class="page-subtitle">Buat akun baru untuk sopir/kondektur.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php?page=sopir" class="text-decoration-none text-muted-green">Data Sopir</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Tambah</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'email_ada') : ?>
  <div class="alert alert-danger">Email itu sudah terdaftar. Pakai email lain.</div>
<?php elseif ($pesan === 'tidak_valid') : ?>
  <div class="alert alert-danger">Data belum benar. Cek lagi nama, email, dan password (minimal 6 karakter).</div>
<?php endif; ?>

<!-- form -->
<div class="row">
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Data Sopir</h5>

      <form action="/e-ticket-bus/admin/function/sopir.php?aksi=simpan" method="post">

        <div class="mb-3">
          <label for="nama" class="form-label-custom">Nama</label>
          <input type="text" class="form-control-custom" id="nama" name="nama"
            placeholder="Nama lengkap" maxlength="150" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label-custom">Email</label>
          <input type="email" class="form-control-custom" id="email" name="email"
            placeholder="contoh@email.com" maxlength="150" required>
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
          <a href="/e-ticket-bus/admin/index.php?page=sopir" class="btn-custom btn-custom-light">Batal</a>
        </div>

      </form>
    </div>
  </div>
</div>
<!-- form -->