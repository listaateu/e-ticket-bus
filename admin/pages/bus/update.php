<?php
// admin/pages/bus/update.php
// Halaman ubah bus (bagian U = Update dari CRUD).
// File ini mengurus SEMUANYA: tampilkan form + proses UPDATE ke database.

include_once __DIR__ . '/../../database/koneksi.php';

$kembali      = '/e-ticket-bus/admin/index.php?page=bus';
$daftar_kelas = ['Ekonomi', 'Bisnis', 'Eksekutif'];
$id           = (int) ($_GET['id'] ?? 0);
$error        = ''; // pesan kesalahan, kosong = tidak ada masalah

// ---------- 1. AMBIL DATA BUS YANG MAU DIUBAH ----------
$ambil = $koneksi->prepare("SELECT * FROM buses WHERE id = ?");
$ambil->bind_param('i', $id);
$ambil->execute();
$bus = $ambil->get_result()->fetch_assoc();

// Kalau id tidak ditemukan, balik ke tabel
if (!$bus) {
    echo '<script>window.location.href = "' . $kembali . '";</script>';
    return;
}

// ---------- 2. PROSES UPDATE (jalan hanya saat form disubmit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = trim($_POST['nama_bus'] ?? '');
    $plat  = strtoupper(trim($_POST['plat_nomor'] ?? ''));
    $kursi = (int) ($_POST['kapasitas_kursi'] ?? 0);
    $kelas = $_POST['kelas'] ?? '';

    // Cek isiannya masuk akal
    if ($nama === '' || $plat === '' || $kursi < 1 || $kursi > 60 || !in_array($kelas, $daftar_kelas, true)) {
        $error = 'Data belum benar. Cek lagi nama bus, plat nomor, kapasitas kursi, dan kelas.';
    } else {
        // Cek plat tidak dipakai bus LAIN (id <> ? supaya bus ini sendiri tidak dihitung)
        $cek = $koneksi->prepare("SELECT id FROM buses WHERE plat_nomor = ? AND id <> ?");
        $cek->bind_param('si', $plat, $id);
        $cek->execute();

        if ($cek->get_result()->num_rows > 0) {
            $error = 'Plat nomor itu sudah dipakai bus lain. Pakai plat nomor lain.';
        } else {
            // Simpan perubahan (tipe data: s=teks, i=angka -> nama, plat, kursi, kelas, id)
            $ubah = $koneksi->prepare("UPDATE buses SET nama_bus = ?, plat_nomor = ?, kapasitas_kursi = ?, kelas = ? WHERE id = ?");
            $ubah->bind_param('ssisi', $nama, $plat, $kursi, $kelas, $id);
            $ubah->execute();

            // Berhasil -> balik ke tabel (pakai JS karena header() sudah tidak bisa di sini)
            echo '<script>window.location.href = "' . $kembali . '&pesan=update_ok";</script>';
            return;
        }
    }

    // Kalau sampai sini berarti ada error: isi form dengan ketikan user,
    // supaya dia tidak perlu mengetik ulang dari awal
    $bus['nama_bus']        = $nama;
    $bus['plat_nomor']      = $plat;
    $bus['kapasitas_kursi'] = $kursi;
    $bus['kelas']           = $kelas;
}
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Ubah Bus</h1>
    <p class="page-subtitle">Perbarui nama bus, plat nomor, kapasitas kursi, atau kelas bus.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php?page=bus" class="text-decoration-none text-muted-green">Data Bus</a></li>
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
      <h5 class="card-title mb-4">Data Bus</h5>

      <!-- action kosong = kirim ke alamat halaman ini sendiri (update.php), bukan ke function -->
      <form action="" method="post">

        <div class="mb-3">
          <label for="nama_bus" class="form-label-custom">Nama Bus</label>
          <input type="text" class="form-control-custom" id="nama_bus" name="nama_bus"
            value="<?= htmlspecialchars($bus['nama_bus']) ?>" maxlength="255" required>
        </div>

        <div class="mb-3">
          <label for="plat_nomor" class="form-label-custom">Plat Nomor</label>
          <input type="text" class="form-control-custom" id="plat_nomor" name="plat_nomor"
            value="<?= htmlspecialchars($bus['plat_nomor']) ?>" maxlength="50" required>
        </div>

        <div class="mb-3">
          <label for="kapasitas_kursi" class="form-label-custom">Kapasitas Kursi</label>
          <input type="number" class="form-control-custom" id="kapasitas_kursi" name="kapasitas_kursi"
            value="<?= (int) $bus['kapasitas_kursi'] ?>" min="1" max="60" required>
        </div>

        <div class="mb-4">
          <label for="kelas" class="form-label-custom">Kelas</label>
          <select class="form-select-custom" id="kelas" name="kelas" required>
            <?php foreach ($daftar_kelas as $k) : ?>
              <option value="<?= $k ?>" <?= $bus['kelas'] === $k ? 'selected' : '' ?>><?= $k ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn-custom btn-custom-primary">
            <i class="bi bi-check-lg"></i> Simpan Perubahan
          </button>
          <a href="/e-ticket-bus/admin/index.php?page=bus" class="btn-custom btn-custom-light">Batal</a>
        </div>

      </form>
    </div>
  </div>
</div>
<!-- form -->