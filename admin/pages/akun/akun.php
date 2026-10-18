<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/pages/akun/akun.php
// Halaman "Akun Saya": ganti foto profil, ubah data diri, dan ganti password admin yang sedang login.
// Dibuka lewat admin/index.php?page=akun

require_once __DIR__ . '/../../database/koneksi.php';
require_once __DIR__ . '/../../function/akun.php';

/**
 * Variabel ini dibuat di admin/index.php sebelum file ini di-include.
 * @var mysqli $koneksi
 */

$id_admin = idLogin();
$kembali  = BASE_URL . '/admin/index.php?page=akun';
$pesan    = $_SESSION['flash_akun'] ?? null;   // ['tipe' => 'sukses'|'gagal', 'pesan' => '...']
unset($_SESSION['flash_akun']);
$isian    = null;   // isi form yang dikembalikan saat ada kesalahan

// ---------- PROSES FORM (hanya saat disubmit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi_form = (string) ($_POST['aksi_akun'] ?? '');

    if (!csrf_valid()) {
        $pesan = ['tipe' => 'gagal', 'pesan' => 'Sesi formulir tidak valid atau sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.'];
    } else {
        $ok = false;
        $teks = 'Permintaan tidak dikenal.';

        if ($aksi_form === 'foto') {
            $err = simpan_foto_admin($id_admin, $_FILES['foto'] ?? []);
            $ok = $err === null;
            $teks = $ok ? 'Foto profil berhasil diganti.' : $err;
        } elseif ($aksi_form === 'hapus_foto') {
            hapus_foto_admin($id_admin);
            $ok = true;
            $teks = 'Foto profil dihapus.';
        } elseif ($aksi_form === 'data') {
            [$ok, $teks] = ubah_data_admin(
                $koneksi, $id_admin,
                (string) ($_POST['nama'] ?? ''), (string) ($_POST['username'] ?? ''),
                (string) ($_POST['email'] ?? ''), (string) ($_POST['no_hp'] ?? '')
            );
            if (!$ok) {
                $isian = ['nama' => $_POST['nama'] ?? '', 'username' => $_POST['username'] ?? '',
                          'email' => $_POST['email'] ?? '', 'no_hp' => $_POST['no_hp'] ?? ''];
            }
        } elseif ($aksi_form === 'password') {
            [$ok, $teks] = ganti_password_admin(
                $koneksi, $id_admin,
                (string) ($_POST['lama'] ?? ''), (string) ($_POST['baru'] ?? ''), (string) ($_POST['ulang'] ?? '')
            );
        }

        if ($ok) {
            // Berhasil -> muat ulang halaman (tanpa kirim ulang form saat di-refresh)
            $_SESSION['flash_akun'] = ['tipe' => 'sukses', 'pesan' => $teks];
            echo '<script>window.location.href = ' . json_encode($kembali) . ';</script>';
            return;
        }
        $pesan = ['tipe' => 'gagal', 'pesan' => $teks];
    }
}

$admin = data_admin($koneksi, true);
if (!$admin) {
    echo '<div class="alert alert-danger">Data akun tidak ditemukan. Coba keluar lalu masuk lagi.</div>';
    return;
}
if ($isian !== null) {
    $admin = array_merge($admin, $isian);   // tampilkan ketikan terakhir kalau ada kesalahan
}
$punya_foto = path_foto_admin($id_admin) !== null;
?>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Akun Saya</h1>
    <p class="page-subtitle">Kelola foto profil, data diri, dan password kamu.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item active text-main" aria-current="page">Akun Saya</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan) : ?>
  <div class="alert alert-<?= $pesan['tipe'] === 'sukses' ? 'success' : 'danger' ?>"><?= htmlspecialchars((string) $pesan['pesan']) ?></div>
<?php endif; ?>

<div class="row g-4">

  <!-- Foto profil -->
  <div class="col-12 col-lg-4">
    <div class="card border-light shadow-sm p-4 text-center">
      <h5 class="card-title mb-4 text-start">Foto Profil</h5>

      <img id="foto-preview" src="<?= htmlspecialchars(foto_admin_url($id_admin)) ?>" alt="Foto profil"
           style="width:140px;height:140px;object-fit:cover;border-radius:50%;margin:0 auto 1rem;display:block;border:3px solid #e5e7eb">
      <div class="fw-semibold"><?= htmlspecialchars((string) $admin['nama']) ?></div>
      <div class="text-muted small mb-3"><?= htmlspecialchars((string) $admin['email']) ?></div>

      <form method="post" enctype="multipart/form-data" class="mb-2">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi_akun" value="foto">
        <input type="file" name="foto" id="foto-input" accept="image/jpeg,image/png,image/webp" class="form-control-custom mb-2" required>
        <div class="text-muted small mb-3">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
        <button type="submit" class="btn-custom btn-custom-primary w-100"><i class="bi bi-upload"></i> Simpan Foto</button>
      </form>

      <?php if ($punya_foto) : ?>
      <form method="post" onsubmit="return confirm('Hapus foto profil?')">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi_akun" value="hapus_foto">
        <button type="submit" class="btn-custom btn-custom-light w-100"><i class="bi bi-trash"></i> Hapus Foto</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <!-- Data diri -->
    <div class="card border-light shadow-sm p-4 mb-4">
      <h5 class="card-title mb-4">Data Diri</h5>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi_akun" value="data">

        <div class="mb-3">
          <label for="nama" class="form-label-custom">Nama</label>
          <input type="text" class="form-control-custom" id="nama" name="nama" maxlength="150" required
                 value="<?= htmlspecialchars((string) $admin['nama']) ?>">
        </div>
        <div class="mb-3">
          <label for="username" class="form-label-custom">Username</label>
          <input type="text" class="form-control-custom" id="username" name="username" maxlength="20" pattern="[A-Za-z0-9_]{4,20}" required
                 value="<?= htmlspecialchars((string) $admin['username']) ?>">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label-custom">Email</label>
          <input type="email" class="form-control-custom" id="email" name="email" maxlength="150" required
                 value="<?= htmlspecialchars((string) $admin['email']) ?>">
        </div>
        <div class="mb-4">
          <label for="no_hp" class="form-label-custom">No. HP <span class="text-muted">(opsional)</span></label>
          <input type="tel" class="form-control-custom" id="no_hp" name="no_hp" inputmode="numeric" maxlength="14"
                 pattern="(08|628)[1-9][0-9]{7,11}" title="Angka saja, diawali 08, 10-14 digit"
                 oninput="this.value = this.value.replace(/\D/g, '')"
                 value="<?= htmlspecialchars((string) ($admin['no_hp'] ?? '')) ?>">
        </div>
        <button type="submit" class="btn-custom btn-custom-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
      </form>
    </div>

    <!-- Ganti password -->
    <div class="card border-light shadow-sm p-4">
      <h5 class="card-title mb-4">Ganti Password</h5>
      <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi_akun" value="password">

        <div class="mb-3">
          <label for="lama" class="form-label-custom">Password Lama</label>
          <input type="password" class="form-control-custom" id="lama" name="lama" autocomplete="current-password" required>
        </div>
        <div class="mb-3">
          <label for="baru" class="form-label-custom">Password Baru</label>
          <input type="password" class="form-control-custom" id="baru" name="baru" minlength="6" autocomplete="new-password" required>
        </div>
        <div class="mb-4">
          <label for="ulang" class="form-label-custom">Ulangi Password Baru</label>
          <input type="password" class="form-control-custom" id="ulang" name="ulang" minlength="6" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn-custom btn-custom-primary"><i class="bi bi-key"></i> Ganti Password</button>
      </form>
    </div>
  </div>
</div>

<script>
// Pratinjau foto yang baru dipilih (sebelum disimpan)
(function () {
  var input = document.getElementById('foto-input');
  var img = document.getElementById('foto-preview');
  if (!input || !img) return;
  input.addEventListener('change', function () {
    var f = input.files && input.files[0];
    if (!f) return;
    if (f.size > 2 * 1024 * 1024) { alert('Ukuran foto maksimal 2 MB.'); input.value = ''; return; }
    img.src = URL.createObjectURL(f);
  });
})();
</script>