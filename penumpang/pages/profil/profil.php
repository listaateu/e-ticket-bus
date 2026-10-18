<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/profil/profil.php
 * FUNGSI       : Halaman PROFIL: foto profil, ubah data diri, dan password.
 * DIPANGGIL DARI: index.php (page=profil)
 * CATATAN      : URL: index.php?page=profil
 * ============================================================
 */

/**
 * Variabel ini dibuat di penumpang/index.php sebelum file ini di-include.
 * Deklarasi @var di bawah hanya memberi tahu editor (Intelephense), tidak mengubah cara kerja.
 *
 * @var mysqli     $conn
 * @var int        $user_id
 * @var array|null $user
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        set_flash('gagal', 'Sesi tidak valid, silakan ulangi.');
        redirect(url('profil'));
    }
    switch ($_POST['aksi'] ?? '') {
        case 'profil':
            [$ok, $pesan] = update_profil($conn, $user_id, $_POST['nama'] ?? '', $_POST['email'] ?? '', $_POST['no_hp'] ?? '');
            break;
        case 'foto':
            $err = simpan_foto_penumpang($user_id, $_FILES['foto'] ?? []);
            [$ok, $pesan] = $err === null ? [true, 'Foto profil berhasil diperbarui.'] : [false, $err];
            break;
        case 'hapus_foto':
            hapus_foto_penumpang($user_id);
            [$ok, $pesan] = [true, 'Foto profil dihapus.'];
            break;
        default:
            [$ok, $pesan] = ganti_password($conn, $user_id, $_POST['lama'] ?? '', $_POST['baru'] ?? '', $_POST['ulang'] ?? '');
    }
    set_flash($ok ? 'sukses' : 'gagal', $pesan);
    redirect(url('profil'));
}
$foto_url = foto_penumpang_url((int) $user_id);
?>
    <?php komponen_page_title('Profil Saya', 'Kelola data akun kamu.', ['Profil' => null]); ?>

    <section id="profil" class="section">
      <div class="container">
        <div class="row gy-4">

          <!-- Kiri: foto profil -->
          <div class="col-lg-4" data-aos="fade-up">
            <div class="panel profil-kartu text-center">
              <div class="avatar-besar">
                <?php if ($foto_url !== ''): ?>
                  <img id="foto-preview" src="<?= e($foto_url) ?>" alt="Foto profil <?= e($user['nama'] ?? '') ?>">
                <?php else: ?>
                  <img id="foto-preview" src="" alt="" hidden>
                  <span id="foto-inisial"><?= e(inisial_nama((string) ($user['nama'] ?? ''))) ?></span>
                <?php endif; ?>
              </div>
              <h4 class="profil-nama"><?= e($user['nama'] ?? '') ?></h4>
              <div class="profil-email"><i class="bi bi-envelope"></i><?= e($user['email'] ?? '') ?></div>

              <hr>

              <form method="post" enctype="multipart/form-data" class="text-start">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="foto">
                <label for="foto-input" class="form-label fw-semibold">Ganti foto profil</label>
                <input type="file" name="foto" id="foto-input" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                <button class="btn btn-primary w-100 mt-3"><i class="bi bi-camera me-1"></i>Simpan Foto</button>
              </form>
              <?php if ($foto_url !== ''): ?>
                <form method="post" class="mt-2" onsubmit="return confirm('Hapus foto profil?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="hapus_foto">
                  <button class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-trash3 me-1"></i>Hapus Foto</button>
                </form>
              <?php endif; ?>
            </div>
          </div>

          <!-- Kanan: data diri + password -->
          <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
            <div class="panel">
              <h5 class="mb-3">Data Diri</h5>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="profil">
                <div class="mb-3">
                  <label class="form-label">Username</label>
                  <input type="text" class="form-control" value="<?= e($user['username'] ?? '') ?>" disabled>
                </div>
                <div class="mb-3">
                  <label class="form-label">Nama Lengkap</label>
                  <input type="text" name="nama" class="form-control" value="<?= e($user['nama'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">No. HP</label>
                  <input type="tel" name="no_hp" class="form-control" value="<?= e($user['no_hp'] ?? '') ?>"
                         inputmode="numeric" autocomplete="tel" maxlength="14" pattern="(08|628)[1-9][0-9]{7,11}" placeholder="08xxxxxxxxxx"
                         title="Angka saja, diawali 08, 10-14 digit" oninput="this.value = this.value.replace(/\D/g, '')">
                </div>
                <button class="btn btn-primary">Simpan Perubahan</button>
              </form>
            </div>

            <div class="panel mt-4">
              <h5 class="mb-3">Ganti Password</h5>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="password">
                <div class="mb-3">
                  <label class="form-label">Password Lama</label>
                  <input type="password" name="lama" class="form-control" required>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="baru" class="form-control" minlength="6" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Ulangi Password Baru</label>
                    <input type="password" name="ulang" class="form-control" minlength="6" required>
                  </div>
                </div>
                <button class="btn btn-outline-primary mt-3">Ganti Password</button>
              </form>
            </div>
          </div>

        </div>
      </div>
    </section>

    <script>
      // Pratinjau foto yang baru dipilih (sebelum disimpan)
      document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('foto-input');
        var img = document.getElementById('foto-preview');
        var inisial = document.getElementById('foto-inisial');
        if (!input || !img) { return; }
        input.addEventListener('change', function () {
          var f = input.files && input.files[0];
          if (!f) { return; }
          if (f.size > 2 * 1024 * 1024) { alert('Ukuran foto maksimal 2 MB.'); input.value = ''; return; }
          img.src = URL.createObjectURL(f);
          img.hidden = false;
          if (inisial) { inisial.hidden = true; }
        });
      });
    </script>