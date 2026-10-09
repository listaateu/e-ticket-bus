<?php
// admin/pages/jadwal_dan_rute/update.php
// Halaman ubah jadwal (bagian U = Update dari CRUD).
// File ini mengurus SEMUANYA: tampilkan form + proses UPDATE ke database.

include_once __DIR__ . '/../../database/koneksi.php';

$kembali = '/e-ticket-bus/admin/index.php?page=jadwal_dan_rute';
$id      = (int) ($_GET['id'] ?? 0);
$error   = ''; // pesan kesalahan, kosong = tidak ada masalah

// ---------- 1. AMBIL DATA JADWAL YANG MAU DIUBAH ----------
$ambil = $koneksi->prepare("SELECT * FROM schedules WHERE id = ?");
$ambil->bind_param('i', $id);
$ambil->execute();
$jadwal = $ambil->get_result()->fetch_assoc();

// Kalau id tidak ditemukan, balik ke tabel
if (!$jadwal) {
    echo '<script>window.location.href = "' . $kembali . '";</script>';
    return;
}

// Daftar bus untuk dropdown (ikut ambil nama_bus)
$daftar_bus = $koneksi->query("SELECT id, nama_bus, plat_nomor, kelas FROM buses ORDER BY nama_bus");

// ---------- 2. PROSES UPDATE (jalan hanya saat form disubmit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus_id = (int) ($_POST['bus_id'] ?? 0);
    $sopir  = trim($_POST['sopir'] ?? '');
    $asal   = trim($_POST['kota_asal'] ?? '');
    $tujuan = trim($_POST['kota_tujuan'] ?? '');
    $jam    = trim($_POST['jam_berangkat'] ?? '');
    $harga  = (int) preg_replace('/\D/', '', $_POST['harga'] ?? ''); // buang titik pemisah ribuan

    // input datetime-local formatnya 2026-10-20T08:00 -> ubah ke 2026-10-20 08:00:00
    $jam = str_replace('T', ' ', $jam);
    if (strlen($jam) === 16) {
        $jam .= ':00';
    }

    // Cek isiannya masuk akal
    $valid = $bus_id > 0
        && $sopir !== ''                         // nama sopir wajib diisi
        && $asal !== '' && $tujuan !== ''
        && strcasecmp($asal, $tujuan) !== 0      // asal tidak boleh sama dengan tujuan
        && strtotime($jam) !== false
        && $harga > 0;

    if (!$valid) {
        $error = 'Data belum benar. Pastikan nama sopir terisi, kota asal berbeda dengan kota tujuan, jam terisi, dan harga lebih dari 0.';
    } else {
        // Simpan perubahan
        $ubah = $koneksi->prepare(
            "UPDATE schedules SET bus_id = ?, sopir = ?, kota_asal = ?, kota_tujuan = ?, jam_berangkat = ?, harga = ? WHERE id = ?"
        );
        $ubah->bind_param('issssii', $bus_id, $sopir, $asal, $tujuan, $jam, $harga, $id);
        $ubah->execute();

        // Berhasil -> balik ke tabel (pakai JS karena header() sudah tidak bisa di sini)
        echo '<script>window.location.href = "' . $kembali . '&pesan=update_ok";</script>';
        return;
    }

    // Kalau sampai sini berarti ada error: isi form dengan ketikan user,
    // supaya dia tidak perlu mengetik ulang dari awal
    $jadwal['bus_id']        = $bus_id;
    $jadwal['sopir']         = $sopir;
    $jadwal['kota_asal']     = $asal;
    $jadwal['kota_tujuan']   = $tujuan;
    $jadwal['jam_berangkat'] = $jam;
    $jadwal['harga']         = $harga;
}

// Ubah format jam dari database (2026-10-20 08:00:00) ke format input datetime-local (2026-10-20T08:00)
$jam_untuk_form = '';
if (strtotime($jadwal['jam_berangkat']) !== false) {
    $jam_untuk_form = date('Y-m-d\TH:i', strtotime($jadwal['jam_berangkat']));
}
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Ubah Jadwal</h1>
    <p class="page-subtitle">Perbarui bus, rute, jam berangkat, atau harga.</p>
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

<?php if ($error !== '') : ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- form -->
<div class="row">
  <div class="col-12 col-lg-6">
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Data Jadwal</h5>

      <!-- action kosong = kirim ke alamat halaman ini sendiri (update.php), bukan ke function -->
      <form action="" method="post">

        <div class="mb-3">
          <label for="bus_id" class="form-label-custom">Bus</label>
          <select class="form-select-custom" id="bus_id" name="bus_id" required>
            <?php while ($b = $daftar_bus->fetch_assoc()) : ?>
              <option value="<?= (int) $b['id'] ?>" <?= (int) $jadwal['bus_id'] === (int) $b['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['nama_bus']) ?> - <?= htmlspecialchars($b['plat_nomor']) ?> (<?= htmlspecialchars($b['kelas']) ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="mb-3">
          <label for="sopir" class="form-label-custom">Nama Sopir</label>
          <input type="text" class="form-control-custom" id="sopir" name="sopir"
            value="<?= htmlspecialchars($jadwal['sopir']) ?>" maxlength="255" required>
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
            value="<?= htmlspecialchars($jam_untuk_form) ?>" required>
        </div>

        <div class="mb-4">
          <label for="harga" class="form-label-custom">Harga (Rp)</label>
          <input type="text" inputmode="numeric" autocomplete="off" class="form-control-custom" id="harga" name="harga"
            value="<?= number_format((int) $jadwal['harga'], 0, ',', '.') ?>" required>
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