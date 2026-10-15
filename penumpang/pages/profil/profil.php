<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/profil/profil.php
 * FUNGSI       : Halaman PROFIL: ubah data diri dan password.
 * DIPANGGIL DARI: index.php (page=profil)
 * CATATAN      : URL: index.php?page=profil
 * ============================================================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        set_flash('gagal', 'Sesi tidak valid, silakan ulangi.');
        redirect(url('profil'));
    }
    if (($_POST['aksi'] ?? '') === 'profil') {
        [$ok, $pesan] = update_profil($conn, $user_id, $_POST['nama'] ?? '', $_POST['email'] ?? '', $_POST['no_hp'] ?? '');
    } else {
        [$ok, $pesan] = ganti_password($conn, $user_id, $_POST['lama'] ?? '', $_POST['baru'] ?? '', $_POST['ulang'] ?? '');
    }
    set_flash($ok ? 'sukses' : 'gagal', $pesan);
    redirect(url('profil'));
}
?>
    <?php komponen_page_title('Profil Saya', 'Kelola data akun kamu.', ['Profil' => null]); ?>

    <section id="profil" class="section">
      <div class="container">
        <div class="row gy-4">

          <div class="col-lg-6" data-aos="fade-up">
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
                         inputmode="numeric" autocomplete="tel" maxlength="14" pattern="(08|62)[0-9]{8,12}" placeholder="08xxxxxxxxxx"
                         title="Angka saja, diawali 08, 10-14 digit" oninput="this.value = this.value.replace(/\D/g, '')">
                </div>
                <button class="btn btn-primary">Simpan Perubahan</button>
              </form>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
            <div class="panel">
              <h5 class="mb-3">Ganti Password</h5>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="password">
                <div class="mb-3">
                  <label class="form-label">Password Lama</label>
                  <input type="password" name="lama" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Password Baru</label>
                  <input type="password" name="baru" class="form-control" minlength="6" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Ulangi Password Baru</label>
                  <input type="password" name="ulang" class="form-control" minlength="6" required>
                </div>
                <button class="btn btn-outline-primary">Ganti Password</button>
              </form>
            </div>
          </div>

        </div>
      </div>
    </section>