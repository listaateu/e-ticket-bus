<?php
// admin/pages/bus/tambah.php
// Form tambah bus (bagian C = Create dari CRUD).
// Form ini TIDAK menyimpan sendiri; datanya dikirim ke function/bus.php?aksi=simpan.

$pesan = $_GET['pesan'] ?? '';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Tambah Bus</h1>
    <p class="page-subtitle">Isi data bus baru untuk armada.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php?page=bus" class="text-decoration-none text-muted-green">Data Bus</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Tambah</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'plat_ada') : ?>
  <div class="alert alert-danger">Plat nomor itu sudah terdaftar. Pakai plat nomor lain.</div>
<?php elseif ($pesan === 'tidak_valid') : ?>
  <div class="alert alert-danger">Data belum benar. Cek lagi plat nomor, kapasitas kursi, dan kelas.</div>
<?php endif; ?>

<!-- form -->
<div class="row">
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Data Bus</h5>

      <form action="/e-ticket-bus/admin/function/bus.php?aksi=simpan" method="post">

        <div class="mb-3">
          <label for="plat_nomor" class="form-label-custom">Plat Nomor</label>
          <input type="text" class="form-control-custom" id="plat_nomor" name="plat_nomor"
            placeholder="Contoh: AB 1234 CD" maxlength="50" required>
        </div>

        <div class="mb-3">
          <label for="kapasitas_kursi" class="form-label-custom">Kapasitas Kursi</label>
          <input type="number" class="form-control-custom" id="kapasitas_kursi" name="kapasitas_kursi"
            placeholder="Contoh: 40" min="1" max="60" required>
        </div>

        <div class="mb-4">
          <label for="kelas" class="form-label-custom">Kelas</label>
          <select class="form-select-custom" id="kelas" name="kelas" required>
            <option value="" selected disabled>Pilih kelas...</option>
            <option value="Ekonomi">Ekonomi</option>
            <option value="Bisnis">Bisnis</option>
            <option value="Eksekutif">Eksekutif</option>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn-custom btn-custom-primary">
            <i class="bi bi-check-lg"></i> Simpan
          </button>
          <a href="/e-ticket-bus/admin/index.php?page=bus" class="btn-custom btn-custom-light">Batal</a>
        </div>

      </form>
    </div>
  </div>
</div>
<!-- form -->