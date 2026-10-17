<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/pages/bus/tambah.php
// Form tambah bus (bagian C = Create dari CRUD).
// Form ini TIDAK menyimpan sendiri; datanya dikirim ke function/bus.php?aksi=simpan.

require_once __DIR__ . '/../../../includes/bus_helper.php';

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
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php?page=bus" class="text-decoration-none text-muted-green">Data Bus</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Tambah</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'plat_ada') : ?>
  <div class="alert alert-danger">Plat nomor itu sudah terdaftar. Pakai plat nomor lain.</div>
<?php elseif ($pesan === 'tidak_valid') : ?>
  <div class="alert alert-danger">Data belum benar. Cek lagi nama bus, plat nomor, kapasitas kursi, dan kelas.</div>
<?php elseif ($pesan === 'foto_tidak_valid') : ?>
  <div class="alert alert-danger">Foto tidak valid. Pakai file JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB, lalu pilih ulang fotonya.</div>
<?php endif; ?>

<!-- form -->
<div class="row">
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Data Bus</h5>

      <!-- enctype multipart/form-data WAJIB supaya file foto ikut terkirim -->
      <form action="<?= BASE_URL ?>/admin/function/bus.php?aksi=simpan" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label for="nama_bus" class="form-label-custom">Nama Bus</label>
          <input type="text" class="form-control-custom" id="nama_bus" name="nama_bus"
            placeholder="Contoh: Sinar Jaya 01" maxlength="255" required>
        </div>

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

        <div class="mb-3">
          <label for="kelas" class="form-label-custom">Kelas</label>
          <select class="form-select-custom" id="kelas" name="kelas" required>
            <option value="" selected disabled>Pilih kelas...</option>
            <option value="Ekonomi">Ekonomi</option>
            <option value="Bisnis">Bisnis</option>
            <option value="Eksekutif">Eksekutif</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="tata_letak" class="form-label-custom">Tata Letak Kursi</label>
          <select class="form-select-custom" id="tata_letak" name="tata_letak">
            <option value="">Belum diatur</option>
            <?php foreach (TATA_LETAK_KURSI as $t) : ?>
              <option value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Contoh: 2-2 artinya dua kursi di kiri, dua kursi di kanan lorong.</small>
        </div>

        <div class="mb-3">
          <label class="form-label-custom">Fasilitas</label>
          <div class="row row-cols-2 g-2">
            <?php foreach (FASILITAS_BUS as $kode => [$label, $ikon]) : ?>
              <div class="col">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="fasilitas[]" value="<?= $kode ?>" id="f-<?= $kode ?>">
                  <label class="form-check-label" for="f-<?= $kode ?>"><?= htmlspecialchars($label) ?></label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mb-4">
          <label for="foto" class="form-label-custom">Foto Bus</label>
          <input type="file" class="form-control-custom" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
          <small class="text-muted">JPG, PNG, atau WEBP, maksimal 2 MB. Paling bagus foto landscape (lebar).</small>
          <img id="preview-foto" class="d-none mt-2 rounded border" alt="Pratinjau foto" style="max-width: 100%; max-height: 220px;">
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn-custom btn-custom-primary">
            <i class="bi bi-check-lg"></i> Simpan
          </button>
          <a href="<?= BASE_URL ?>/admin/index.php?page=bus" class="btn-custom btn-custom-light">Batal</a>
        </div>

      </form>
    </div>
  </div>
</div>
<!-- form -->

<script>
  // Tampilkan pratinjau foto begitu dipilih
  document.getElementById('foto').addEventListener('change', function () {
    const gambar = document.getElementById('preview-foto');
    if (this.files && this.files[0]) {
      gambar.src = URL.createObjectURL(this.files[0]);
      gambar.classList.remove('d-none');
    } else {
      gambar.classList.add('d-none');
    }
  });
</script>