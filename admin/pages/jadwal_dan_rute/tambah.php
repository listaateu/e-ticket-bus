<?php
// admin/pages/jadwal_dan_rute/tambah.php
// Form tambah jadwal (bagian C = Create dari CRUD).
// Form ini TIDAK menyimpan sendiri; datanya dikirim ke function/jadwal_dan_rute.php?aksi=simpan.

include_once __DIR__ . '/../../database/koneksi.php';

// Daftar bus untuk dropdown (ikut ambil nama_bus)
$daftar_bus = $koneksi->query("SELECT id, nama_bus, plat_nomor, kelas FROM buses ORDER BY nama_bus");

// Daftar sopir untuk dropdown
$daftar_sopir = $koneksi->query("SELECT nama FROM users WHERE role = 'kondektur' ORDER BY nama");

$pesan = $_GET['pesan'] ?? '';
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Tambah Jadwal</h1>
    <p class="page-subtitle">Isi rute dan jadwal keberangkatan baru.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute" class="text-decoration-none text-muted-green">Jadwal &amp; Rute</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Tambah</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'tidak_valid') : ?>
  <div class="alert alert-danger">Data belum benar. Pastikan nama sopir terisi, kota asal berbeda dengan kota tujuan, jam terisi, dan harga lebih dari 0.</div>
<?php endif; ?>

<!-- form -->
<div class="row">
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Data Jadwal</h5>

      <form action="/e-ticket-bus/admin/function/jadwal_dan_rute.php?aksi=simpan" method="post">

        <div class="mb-3">
          <label for="bus_id" class="form-label-custom">Bus</label>
          <select class="form-select-custom" id="bus_id" name="bus_id" required>
            <option value="" selected disabled>Pilih bus...</option>
            <?php while ($b = $daftar_bus->fetch_assoc()) : ?>
              <option value="<?= (int) $b['id'] ?>">
                <?= htmlspecialchars($b['nama_bus']) ?> - <?= htmlspecialchars($b['plat_nomor']) ?> (<?= htmlspecialchars($b['kelas']) ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="mb-3">
          <label for="sopir" class="form-label-custom">Nama Sopir</label>
          <select class="form-select-custom" id="sopir" name="sopir" required>
            <option value="" selected disabled>Pilih sopir...</option>
            <?php while ($s = $daftar_sopir->fetch_assoc()) : ?>
              <option value="<?= htmlspecialchars($s['nama']) ?>">
                <?= htmlspecialchars($s['nama']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="mb-3">
          <label for="kota_asal" class="form-label-custom">Kota Asal</label>
          <input type="text" class="form-control-custom" id="kota_asal" name="kota_asal"
            placeholder="Contoh: Magelang" maxlength="100" required>
        </div>

        <div class="mb-3">
          <label for="kota_tujuan" class="form-label-custom">Kota Tujuan</label>
          <input type="text" class="form-control-custom" id="kota_tujuan" name="kota_tujuan"
            placeholder="Contoh: Jakarta" maxlength="100" required>
        </div>

        <div class="mb-3">
          <label for="jam_berangkat" class="form-label-custom">Jam Berangkat</label>
          <input type="datetime-local" class="form-control-custom" id="jam_berangkat" name="jam_berangkat" required>
        </div>

        <div class="mb-4">
          <label for="harga" class="form-label-custom">Harga (Rp)</label>
          <input type="text" inputmode="numeric" autocomplete="off" class="form-control-custom" id="harga" name="harga"
            placeholder="Contoh: 150.000" required>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn-custom btn-custom-primary">
            <i class="bi bi-check-lg"></i> Simpan
          </button>
          <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute" class="btn-custom btn-custom-light">Batal</a>
        </div>

      </form>
    </div>
  </div>
</div>
<!-- form -->

<script>
  // Format ribuan otomatis di kolom Harga: 150000 -> 150.000
  const inputHarga = document.getElementById('harga');

  function formatRibuan() {
    // 1. buang semua yang bukan angka (huruf, titik, spasi)
    const angka = inputHarga.value.replace(/\D/g, '');
    // 2. sisipkan titik tiap 3 angka dari belakang
    inputHarga.value = angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  inputHarga.addEventListener('input', formatRibuan); // jalan tiap kali mengetik
  formatRibuan(); // jalan sekali saat halaman dibuka (untuk nilai lama di form ubah)
</script>