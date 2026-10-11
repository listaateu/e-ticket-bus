<?php
// admin/pages/bus/update.php
// Halaman ubah bus (bagian U = Update dari CRUD).
// File ini mengurus SEMUANYA: tampilkan form + proses UPDATE ke database.

include_once __DIR__ . '/../../database/koneksi.php';
require_once __DIR__ . '/../../../includes/bus_helper.php';

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

    $tata_letak = in_array($_POST['tata_letak'] ?? '', TATA_LETAK_KURSI, true) ? $_POST['tata_letak'] : '';
    $fasilitas  = array_filter((array) ($_POST['fasilitas'] ?? []), fn($k) => is_string($k) && isset(FASILITAS_BUS[$k]));
    $fasilitas_teks = implode(',', $fasilitas);
    $hapus_foto = isset($_POST['hapus_foto']);

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
            // Proses foto baru (kalau ada) setelah semua cek lolos
            $pesan_foto = '';
            $foto_baru  = bus_simpan_foto($_FILES['foto'] ?? null, $pesan_foto);

            if ($foto_baru === false) {
                $error = 'Foto tidak valid: ' . $pesan_foto;
            } else {
                // Tentukan foto akhir: lama dipertahankan, dihapus, atau diganti baru
                $foto_akhir = $bus['foto'];
                if ($hapus_foto) {
                    $foto_akhir = null;
                }
                if ($foto_baru !== '') {
                    $foto_akhir = $foto_baru;
                }

                // Simpan perubahan (tipe: s=teks, i=angka -> nama, plat, kursi, kelas, tata letak, fasilitas, foto, id)
                $ubah = $koneksi->prepare(
                    "UPDATE buses SET nama_bus = ?, plat_nomor = ?, kapasitas_kursi = ?, kelas = ?, tata_letak = ?, fasilitas = ?, foto = ? WHERE id = ?"
                );
                $ubah->bind_param('ssissssi', $nama, $plat, $kursi, $kelas, $tata_letak, $fasilitas_teks, $foto_akhir, $id);
                $ubah->execute();

                // Foto lama sudah tidak dipakai -> hapus filenya dari folder
                if ($foto_akhir !== $bus['foto']) {
                    bus_hapus_foto($bus['foto']);
                }

                // Berhasil -> balik ke tabel (pakai JS karena header() sudah tidak bisa di sini)
                echo '<script>window.location.href = "' . $kembali . '&pesan=update_ok";</script>';
                return;
            }
        }
    }

    // Kalau sampai sini berarti ada error: isi form dengan ketikan user,
    // supaya dia tidak perlu mengetik ulang dari awal
    $bus['nama_bus']        = $nama;
    $bus['plat_nomor']      = $plat;
    $bus['kapasitas_kursi'] = $kursi;
    $bus['kelas']           = $kelas;
    $bus['tata_letak']      = $tata_letak;
    $bus['fasilitas']       = $fasilitas_teks;
}

$fasilitas_terpilih = bus_fasilitas_dari_teks($bus['fasilitas'] ?? '');
$foto_sekarang      = bus_url_foto($bus['foto'] ?? '');
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Ubah Bus</h1>
    <p class="page-subtitle">Perbarui data bus, fasilitas, dan foto bus.</p>
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
      <form action="" method="post" enctype="multipart/form-data">

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

        <div class="mb-3">
          <label for="kelas" class="form-label-custom">Kelas</label>
          <select class="form-select-custom" id="kelas" name="kelas" required>
            <?php foreach ($daftar_kelas as $k) : ?>
              <option value="<?= $k ?>" <?= $bus['kelas'] === $k ? 'selected' : '' ?>><?= $k ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label for="tata_letak" class="form-label-custom">Tata Letak Kursi</label>
          <select class="form-select-custom" id="tata_letak" name="tata_letak">
            <option value="">Belum diatur</option>
            <?php foreach (TATA_LETAK_KURSI as $t) : ?>
              <option value="<?= $t ?>" <?= ($bus['tata_letak'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
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
                  <input class="form-check-input" type="checkbox" name="fasilitas[]" value="<?= $kode ?>" id="f-<?= $kode ?>"
                    <?= in_array($kode, $fasilitas_terpilih, true) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="f-<?= $kode ?>"><?= htmlspecialchars($label) ?></label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mb-4">
          <label for="foto" class="form-label-custom">Foto Bus</label>

          <?php if ($foto_sekarang !== '') : ?>
            <div class="mb-2">
              <img src="<?= htmlspecialchars($foto_sekarang) ?>" alt="Foto bus saat ini" class="rounded border" style="max-width: 100%; max-height: 220px;">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="hapus_foto" value="1" id="hapus_foto">
                <label class="form-check-label" for="hapus_foto">Hapus foto ini</label>
              </div>
            </div>
          <?php else : ?>
            <div class="text-muted small mb-2">Bus ini belum punya foto.</div>
          <?php endif; ?>

          <input type="file" class="form-control-custom" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
          <small class="text-muted">Pilih file baru untuk mengganti foto. Kosongkan kalau tidak ingin mengganti. JPG, PNG, atau WEBP, maksimal 2 MB.</small>
          <img id="preview-foto" class="d-none mt-2 rounded border" alt="Pratinjau foto baru" style="max-width: 100%; max-height: 220px;">
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

<script>
  // Tampilkan pratinjau foto baru begitu dipilih
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