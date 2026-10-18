<?php
/**
 * ============================================================
 * FILE         : sopir/pages/profil/profil.php
 * FUNGSI       : Halaman PROFIL sopir: foto, data akun, dan ganti password.
 *                Nama, username, dan email DIKUNCI (hanya admin yang bisa mengubah).
 *                Sopir hanya bisa mengubah nomor HP, foto, dan password.
 * DIPANGGIL DARI: sopir/index.php (page=profil)
 * CATATAN      : URL: index.php?page=profil
 * ============================================================
 */

/** @var mysqli $conn */
/** @var array  $sopir  (id, nama, username, email, no_hp) */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        set_flash('gagal', 'Sesi tidak valid, silakan ulangi.');
        redirect(url('profil'));
    }
    $id_sopir = (int) $sopir['id'];
    switch ($_POST['aksi'] ?? '') {
        case 'kontak':
            [$ok, $pesan] = update_kontak_sopir($conn, $id_sopir, (string) ($_POST['no_hp'] ?? ''));
            break;
        case 'foto':
            $err = simpan_foto_sopir($id_sopir, $_FILES['foto'] ?? []);
            [$ok, $pesan] = $err === null ? [true, 'Foto profil berhasil diperbarui.'] : [false, $err];
            break;
        case 'hapus_foto':
            hapus_foto_sopir($id_sopir);
            [$ok, $pesan] = [true, 'Foto profil dihapus.'];
            break;
        case 'password':
            [$ok, $pesan] = ganti_password_sopir($conn, $id_sopir, (string) ($_POST['lama'] ?? ''), (string) ($_POST['baru'] ?? ''), (string) ($_POST['ulang'] ?? ''));
            break;
        default:
            [$ok, $pesan] = [false, 'Permintaan tidak dikenal.'];
    }
    set_flash($ok ? 'sukses' : 'gagal', $pesan);
    redirect(url('profil'));
}

$foto_url = foto_sopir_url((int) $sopir['id']);
?>
    <?php komponen_page_title('Profil Saya', 'Kelola kontak dan keamanan akunmu.', ['Profil' => null]); ?>

    <section id="profil" class="section">
      <div class="container">
        <div class="row gy-4">

          <!-- Kiri: kartu identitas + foto -->
          <div class="col-lg-4">
            <div class="panel profil-kartu text-center">
              <div class="avatar-besar">
                <?php if ($foto_url !== ''): ?>
                  <img id="foto-preview" src="<?= e($foto_url) ?>" alt="Foto profil <?= e($sopir['nama']) ?>">
                <?php else: ?>
                  <img id="foto-preview" src="" alt="" hidden>
                  <span id="foto-inisial"><?= e(inisial_nama($sopir['nama'])) ?></span>
                <?php endif; ?>
              </div>
              <h4 class="profil-nama"><?= e($sopir['nama']) ?></h4>
              <span class="chip-peran"><i class="fa-solid fa-bus"></i>Sopir / Kondektur</span>
              <div class="profil-email"><i class="bi bi-envelope"></i><?= e($sopir['email']) ?></div>

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

          <!-- Kanan: data akun + password -->
          <div class="col-lg-8">

            <div class="panel">
              <h5><i class="bi bi-person-vcard me-2"></i>Data Akun</h5>

              <div class="info-kunci">
                <i class="bi bi-shield-lock-fill"></i>
                <div>
                  <strong>Sebagian data dikunci.</strong>
                  Nama, username, dan email dipakai untuk login serta pencocokan jadwal tugas, jadi hanya admin yang bisa mengubahnya.
                  Hubungi admin bila ada yang perlu diperbaiki.
                </div>
              </div>

              <div class="row g-3 mb-1">
                <div class="col-md-6">
                  <label class="form-label">Nama Lengkap <span class="badge-kunci"><i class="bi bi-lock-fill"></i>Admin</span></label>
                  <input type="text" class="form-control field-kunci" value="<?= e($sopir['nama']) ?>" disabled>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Username <span class="badge-kunci"><i class="bi bi-lock-fill"></i>Admin</span></label>
                  <input type="text" class="form-control field-kunci" value="<?= e($sopir['username'] ?: '-') ?>" disabled>
                </div>
                <div class="col-12">
                  <label class="form-label">Email <span class="badge-kunci"><i class="bi bi-lock-fill"></i>Admin</span></label>
                  <input type="email" class="form-control field-kunci" value="<?= e($sopir['email']) ?>" disabled>
                </div>
              </div>

              <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="kontak">
                <label for="no_hp" class="form-label">No. HP <span class="badge-bebas"><i class="bi bi-pencil-fill"></i>Bisa diubah</span></label>
                <input type="tel" id="no_hp" name="no_hp" class="form-control" value="<?= e($sopir['no_hp'] ?? '') ?>"
                       inputmode="numeric" autocomplete="tel" maxlength="14" pattern="(08|628)[1-9][0-9]{7,11}" placeholder="08xxxxxxxxxx"
                       title="Angka saja, diawali 08, 10-14 digit" oninput="this.value = this.value.replace(/\D/g, '')">
                <div class="form-text">Nomor yang bisa dihubungi admin saat bertugas.</div>
                <button class="btn btn-primary mt-3">Simpan Perubahan</button>
              </form>
            </div>

            <div class="panel mt-4">
              <h5><i class="bi bi-key me-2"></i>Ganti Password</h5>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="password">
                <div class="mb-3">
                  <label class="form-label">Password Lama</label>
                  <input type="password" name="lama" class="form-control" autocomplete="current-password" required>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="baru" class="form-control" minlength="6" autocomplete="new-password" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Ulangi Password Baru</label>
                    <input type="password" name="ulang" class="form-control" minlength="6" autocomplete="new-password" required>
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