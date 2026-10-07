<?php
// admin/pages/jadwal_dan_rute/update.php
// Form ubah jadwal (bagian U = Update dari CRUD).
// Datanya dikirim ke function/jadwal_dan_rute.php?aksi=ubah.

include_once __DIR__ . '/../../database/koneksi.php';

$id = (int) ($_GET['id'] ?? 0);

// Ambil data jadwal yang mau diubah
$ambil = $koneksi->prepare("SELECT * FROM schedules WHERE id = ?");
$ambil->bind_param('i', $id);
$ambil->execute();
$jadwal = $ambil->get_result()->fetch_assoc();

// Kalau id tidak ditemukan, balik ke tabel
if (!$jadwal) {
    echo '<script>window.location.href = "/e-ticket-bus/admin/index.php?page=jadwal_dan_rute";</script>';
    return;
}

$daftar_bus = $koneksi->query("SELECT id, plat_nomor, kelas FROM buses ORDER BY plat_nomor");
$pesan = $_GET['pesan'] ?? '';

// Ubah format DB (2026-10-20 08:00:00) ke format input datetime-local (2026-10-20T08:00)
$jam_input = date('Y-m-d\TH:i', strtotime($jadwal['jam_berangkat']));
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Ubah Jadwal</h1>
    <p class="page-subtitle">Perbarui rute, jadwal, atau harga.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute" class="text-decoration-none text-muted-green">Jadwal &amp; Rute</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Ubah</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'tidak_valid') : ?>
  <div class="alert alert-danger">Data belum benar. Pastikan kota asal berbeda dengan kota tujuan, jam terisi, dan harga lebih dari 0.</div>
<?php endif; ?>

<!-- form -->
<div class="row">
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Data Jadwal</h5>

      <form action="/e-ticket-bus/admin/function/jadwal_dan_rute.php?aksi=ubah" method="post">
        <input type="hidden" name="id" value="<?= (int) $jadwal['id'] ?>">

        <div class="mb-3">
          <label for="bus_id" class="form-label-custom">Bus</label>
          <select class="form-select-custom" id="bus_id" name="bus_id" required>
            <?php while ($b = $daftar_bus->fetch_assoc()) : ?>
              <option value="<?= (int) $b['id'] ?>" <?= (int) $b['id'] === (int) $jadwal['bus_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['plat_nomor']) ?> (<?= htmlspecialchars($b['kelas']) ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="mb-3">
          <label for="kota_asal" class="form-label-custom">Kota Asal</label>
          <input type="text" class="form-control-custom" id="kota_asal" name="kota_asal"
            value="<?= htmlspecialchars($jadwal['kota_asal']) ?>" maxlength="100" required>
        </div>

        <div class="mb-3">
          <label for="kota_tujuan" class="form-label-custom">Kota Tujuan</label>
          <input type="text" class="form-control-custom" id="kota_tujuan" name="kota_tujuan"
            value="<?= htmlspecialchars($jadwal['kota_tujuan']) ?>" maxlength="100" required>
        </div>

        <div class="mb-3">
          <label for="jam_berangkat" class="form-label-custom">Jam Berangkat</label>
          <input type="datetime-local" class="form-control-custom" id="jam_berangkat" name="jam_berangkat"
            value="<?= $jam_input ?>" required>
        </div>

        <div class="mb-4">
          <label for="harga" class="form-label-custom">Harga (Rp)</label>
          <input type="number" class="form-control-custom" id="harga" name="harga"
            value="<?= (int) $jadwal['harga'] ?>" min="1" required>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn-custom btn-custom-primary">
            <i class="bi bi-check-lg"></i> Simpan Perubahan
          </button>
          <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute" class="btn-custom btn-custom-light">Batal</a>
        </div>

      </form>
    </div>
  </div>
</div>
<!-- form -->