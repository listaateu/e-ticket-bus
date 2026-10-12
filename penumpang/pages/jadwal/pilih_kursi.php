<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/jadwal/pilih_kursi.php
 * FUNGSI       : Halaman PILIH KURSI: denah bus, pilih 1 kursi, simpan pemesanan lalu pindah ke pembayaran.
 * DIPANGGIL DARI: index.php (page=jadwal&aksi=pilih_kursi)
 * CATATAN      : URL: index.php?page=jadwal&aksi=pilih_kursi&id=ID_JADWAL
 * ============================================================
 */
$id     = (int) ($_GET['id'] ?? 0);
$jadwal = get_jadwal($conn, $id);
if (!$jadwal || strtotime($jadwal['jam_berangkat']) < time()) {
    set_flash('gagal', 'Jadwal tidak tersedia.');
    redirect(url('jadwal'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        set_flash('gagal', 'Sesi tidak valid, silakan ulangi.');
        redirect(url('pilih-kursi', ['id' => $id]));
    }
    [$ok, $hasil] = simpan_booking($conn, $user_id, $id, $_POST['kursi'] ?? '');
    if ($ok) {
        set_flash('sukses', 'Kursi berhasil dipesan. Selesaikan pembayaran dalam ' . teks_durasi_bayar() . ' agar kursimu tidak dilepas.');
        redirect(url('pembayaran', ['id' => $hasil]));
    }
    set_flash('gagal', $hasil);
    redirect(url('pilih-kursi', ['id' => $id]));
}

$terisi = array_map('strval', kursi_terisi($conn, $id));
$total  = (int) $jadwal['kapasitas_kursi'];
$baris  = array_chunk(range(1, $total), 4); // susunan 2 - lorong - 2
?>
    <?php komponen_page_title('Pilih Kursi', $jadwal['kota_asal'] . ' → ' . $jadwal['kota_tujuan'], ['Cari Jadwal' => url('jadwal'), 'Pilih Kursi' => null]); ?>

    <section id="pilih-kursi" class="section">
      <div class="container">
        <form method="post" id="form-kursi">
          <?= csrf_field() ?>
          <input type="hidden" name="kursi" id="input-kursi" value="">
          <div class="row gy-4">

            <!-- Denah kursi -->
            <div class="col-lg-7" data-aos="fade-up">
              <div class="panel">
                <div class="d-flex flex-wrap gap-3 legend mb-3">
                  <span><i class="kotak kosong"></i> Tersedia</span>
                  <span><i class="kotak dipilih"></i> Dipilih</span>
                  <span><i class="kotak terisi"></i> Terisi</span>
                </div>

                <div class="bus-body">
                  <div class="bus-depan"><i class="fa-solid fa-user-tie"></i><span>Sopir</span></div>
                  <?php foreach ($baris as $row): ?>
                    <div class="seat-row">
                      <?php foreach ($row as $idx => $no): ?>
                        <?php if ($idx === 2): ?><span class="lorong"></span><?php endif; ?>
                        <?php $penuh = in_array((string) $no, $terisi, true); ?>
                        <button type="button" class="seat <?= $penuh ? 'terisi' : '' ?>" data-no="<?= $no ?>" <?= $penuh ? 'disabled' : '' ?>><?= $no ?></button>
                      <?php endforeach; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Ringkasan -->
            <div class="col-lg-5" data-aos="fade-up" data-aos-delay="100">
              <div class="panel ringkasan">
                <h5 class="mb-3">Ringkasan Perjalanan</h5>
                <dl class="detail-list">
                  <dt>Rute</dt><dd><?= e($jadwal['kota_asal']) ?> → <?= e($jadwal['kota_tujuan']) ?></dd>
                  <dt>Berangkat</dt><dd><?= e(tanggal_indo($jadwal['jam_berangkat'])) ?></dd>
                  <dt>Bus</dt><dd><?= e($jadwal['nama_bus']) ?> (<?= e($jadwal['kelas']) ?>)</dd>
                  <dt>Plat Nomor</dt><dd><?= e($jadwal['plat_nomor']) ?></dd>
                  <dt>Kursi Dipilih</dt><dd id="teks-kursi" class="fw-bold text-primary">Belum dipilih</dd>
                  <dt>Harga</dt><dd id="teks-harga" data-harga="<?= (float) $jadwal['harga'] ?>"><?= e(rupiah($jadwal['harga'])) ?></dd>
                </dl>
                <button type="submit" id="btn-lanjut" class="btn btn-primary w-100" disabled>
                  Lanjut ke Pembayaran <i class="bi bi-arrow-right ms-1"></i>
                </button>
                <small class="text-muted d-block mt-2">Satu pemesanan untuk satu kursi.</small>
                <small class="text-muted d-block mt-1">
                  <i class="bi bi-clock-history"></i>
                  Setelah memesan, bayar dalam <strong><?= e(teks_durasi_bayar()) ?></strong>.
                  Lewat dari itu, pesanan dibatalkan otomatis dan kursi dibuka lagi.
                </small>
              </div>
            </div>

          </div>
        </form>
      </div>
    </section>