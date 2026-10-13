<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/jadwal/pilih_kursi.php
 * FUNGSI       : Halaman PEMESANAN gaya Traveloka: data pemesan, detail penumpang, pilih kursi lewat
 *                jendela denah bus, ringkasan harga, lalu simpan pemesanan dan pindah ke pembayaran.
 * DIPANGGIL DARI: index.php (page=jadwal&aksi=pilih_kursi)
 * CATATAN      : URL: index.php?page=jadwal&aksi=pilih_kursi&id=ID_JADWAL
 *                Susunan kursi mengikuti kolom buses.tata_letak (mis. "2-2"); kalau kosong dipakai 2-2.
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
$total  = max(1, (int) $jadwal['kapasitas_kursi']);

// Susunan kursi: "2-2" = 2 kursi kiri, lorong, 2 kursi kanan
$kiri = 2; $kanan = 2;
if (preg_match('/^(\d)-(\d)$/', trim((string) ($jadwal['tata_letak'] ?? '')), $m) && ((int) $m[1] + (int) $m[2]) > 0) {
    $kiri = (int) $m[1]; $kanan = (int) $m[2];
}
$per_baris = $kiri + $kanan;
$baris     = array_chunk(range(1, $total), $per_baris);

// Jam tiba & durasi (kosong bila admin belum mengisi jam tiba)
$tiba_valid = !empty($jadwal['jam_tiba']) && strtotime($jadwal['jam_tiba']) > strtotime($jadwal['jam_berangkat']);
$durasi     = $tiba_valid ? durasi_perjalanan($jadwal['jam_berangkat'], $jadwal['jam_tiba']) : '';

$nama_user = (string) ($user['nama'] ?? '');
$sisa      = max(0, $total - count($terisi));
?>
    <?php komponen_stepper(1); ?>

    <section id="pilih-kursi" class="tv-page tv-book">
      <div class="container">
        <form method="post" id="form-kursi">
          <?= csrf_field() ?>
          <input type="hidden" name="kursi" id="input-kursi" value="">

          <div class="tv-book-head">
            <h1>Pemesanan Anda</h1>
            <p>Cek datamu, pilih kursi, lalu lanjut bayar. Gampang kok.</p>
          </div>

          <div class="row g-4">

            <!-- ===== Kolom kiri: data & kursi ===== -->
            <div class="col-lg-8">

              <!-- Data pemesan -->
              <div class="tv-bcard">
                <div class="tv-bcard-head">
                  <span class="tv-bicon"><i class="bi bi-person-fill"></i></span>
                  <div>
                    <div class="tv-btitle">Data Pemesan</div>
                    <div class="tv-bsub">Tiket terhubung ke akun ini</div>
                  </div>
                  <a href="<?= url('profil') ?>" class="tv-blink ms-auto">Ubah Detail</a>
                </div>
                <div class="tv-bdata">
                  <div>
                    <small>Nama</small>
                    <strong><?= e($nama_user !== '' ? $nama_user : '-') ?></strong>
                  </div>
                  <div>
                    <small>No. handphone</small>
                    <strong><?= !empty($user['no_hp']) ? e($user['no_hp']) : '<span class="text-muted fw-normal">Belum diisi</span>' ?></strong>
                  </div>
                  <div>
                    <small>Email</small>
                    <strong><?= e($user['email'] ?? '-') ?></strong>
                  </div>
                </div>
              </div>

              <!-- Detail penumpang -->
              <div class="tv-bcard">
                <div class="tv-bcard-head">
                  <span class="tv-bicon"><i class="bi bi-people-fill"></i></span>
                  <div>
                    <div class="tv-btitle">Detail Penumpang</div>
                    <div class="tv-bsub">Satu pemesanan untuk satu kursi</div>
                  </div>
                </div>
                <div class="tv-pax">
                  <span class="no">1</span>
                  <div>
                    <strong><?= e($nama_user !== '' ? $nama_user : 'Penumpang') ?></strong>
                    <small>Dewasa</small>
                  </div>
                </div>
              </div>

              <!-- Pilih kursi -->
              <div class="tv-bcard">
                <div class="tv-bcard-head">
                  <span class="tv-bicon"><i class="fa-solid fa-chair"></i></span>
                  <div>
                    <div class="tv-btitle">Pemilihan Kursi</div>
                    <div class="tv-bsub"><?= e($sisa) ?> dari <?= e($total) ?> kursi masih tersedia</div>
                  </div>
                </div>
                <button type="button" class="tv-kursi-btn" id="btn-buka-kursi" data-bs-toggle="modal" data-bs-target="#modal-kursi">
                  <span class="ikon"><i class="fa-solid fa-couch"></i></span>
                  <span class="teks">
                    <strong>Nomor Kursi</strong>
                    <small id="status-kursi">Belum dipilih. Ketuk untuk memilih kursi.</small>
                  </span>
                  <span class="angka" id="angka-kursi" hidden></span>
                  <i class="bi bi-chevron-right panah"></i>
                </button>
              </div>

              <div class="tv-note">
                <i class="bi bi-clock-history"></i>
                <div>
                  Setelah memesan, bayar dalam <strong><?= e(teks_durasi_bayar()) ?></strong>.
                  Lewat dari itu, pesanan dibatalkan otomatis dan kursi dibuka lagi untuk penumpang lain.
                </div>
              </div>
            </div>

            <!-- ===== Kolom kanan: ringkasan ===== -->
            <div class="col-lg-4">
              <div class="tv-side">
                <div class="tv-side-head">
                  <i class="fa-solid fa-bus"></i>
                  <strong><?= e($jadwal['kota_asal']) ?></strong>
                  <i class="bi bi-arrow-right-short"></i>
                  <strong><?= e($jadwal['kota_tujuan']) ?></strong>
                </div>

                <div class="tv-side-body">
                  <div class="tv-side-date"><?= e(tanggal_indo($jadwal['jam_berangkat'], false)) ?></div>
                  <div class="tv-side-bus">
                    <?= e($jadwal['nama_bus']) ?>
                    <span class="tv-kelas <?= e(strtolower(preg_replace('/[^a-z]/i', '', (string) $jadwal['kelas']))) ?>"><?= e($jadwal['kelas']) ?></span>
                  </div>
                  <div class="tv-side-plat"><i class="bi bi-credit-card-2-front"></i> <?= e($jadwal['plat_nomor']) ?></div>

                  <ul class="tv-tl tv-tl-kecil mt-3">
                    <li>
                      <div class="baris">
                        <div class="waktu-col">
                          <div class="jam"><?= e(jam($jadwal['jam_berangkat'])) ?></div>
                          <div class="tgl"><?= e(bus_tgl_singkat($jadwal['jam_berangkat'])) ?></div>
                        </div>
                        <div><div class="kota"><?= e($jadwal['kota_asal']) ?></div></div>
                      </div>
                      <?php if ($durasi !== ''): ?><div class="durasi"><i class="bi bi-hourglass-split"></i> <?= e($durasi) ?></div><?php endif; ?>
                    </li>
                    <li class="akhir">
                      <div class="baris">
                        <div class="waktu-col">
                          <?php if ($tiba_valid): ?>
                            <div class="jam"><?= e(jam($jadwal['jam_tiba'])) ?></div>
                            <div class="tgl"><?= e(bus_tgl_singkat($jadwal['jam_tiba'])) ?></div>
                          <?php else: ?>
                            <div class="jam text-muted">--:--</div>
                            <div class="tgl">Estimasi</div>
                          <?php endif; ?>
                        </div>
                        <div><div class="kota"><?= e($jadwal['kota_tujuan']) ?></div></div>
                      </div>
                    </li>
                  </ul>
                </div>

                <div class="tv-side-price">
                  <h6>Rincian harga</h6>
                  <div class="baris-harga">
                    <span><?= e($jadwal['nama_bus']) ?> · Dewasa x1</span>
                    <span id="teks-harga" data-harga="<?= (float) $jadwal['harga'] ?>"><?= e(rupiah($jadwal['harga'])) ?></span>
                  </div>
                  <div class="baris-harga muted">
                    <span>Kursi</span>
                    <span id="teks-kursi">Belum dipilih</span>
                  </div>
                  <div class="baris-harga total">
                    <span>Harga tiket</span>
                    <span><?= e(rupiah($jadwal['harga'])) ?></span>
                  </div>
                  <small class="tv-kode-info">
                    <i class="bi bi-info-circle"></i>
                    Kode unik (maksimal Rp999) ditambahkan di halaman pembayaran supaya transfermu cepat dikenali admin.
                  </small>
                </div>

                <button type="submit" id="btn-lanjut" class="tv-cta" disabled>
                  Lanjut ke Pembayaran <i class="bi bi-arrow-right ms-1"></i>
                </button>
                <div class="tv-cta-hint" id="hint-lanjut">Pilih kursi dulu ya.</div>
              </div>
            </div>

          </div>

          <!-- ===== Jendela pilih kursi ===== -->
          <div class="modal fade tv-modal tv-seat-modal" id="modal-kursi" tabindex="-1" aria-labelledby="judul-modal-kursi" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
              <div class="modal-content">
                <div class="modal-header">
                  <div>
                    <h5 class="modal-title" id="judul-modal-kursi">Pemilihan Kursi</h5>
                    <div class="tv-modal-rute">
                      <?= e($jadwal['kota_asal']) ?> <i class="bi bi-arrow-right"></i> <?= e($jadwal['kota_tujuan']) ?>
                      <span class="titik">·</span> <?= e($jadwal['nama_bus']) ?><?= $durasi !== '' ? ' · ' . e($durasi) : '' ?>
                    </div>
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                  <div class="row g-4">
                    <div class="col-md-4">
                      <div class="tv-pax aktif">
                        <span class="no">1</span>
                        <div>
                          <strong><?= e($nama_user !== '' ? $nama_user : 'Penumpang') ?></strong>
                          <small id="modal-kursi-teks">Belum pilih kursi</small>
                        </div>
                      </div>

                      <div class="tv-legend">
                        <span><i class="kotak bebas"></i> Tersedia</span>
                        <span><i class="kotak dipilih"></i> Dipilih</span>
                        <span><i class="kotak terisi"></i> Terisi</span>
                      </div>
                    </div>

                    <div class="col-md-8">
                      <div class="tv-denah">
                        <div class="bus-body">
                          <div class="bus-depan">
                            <span class="sopir" title="Sopir"><i class="fa-solid fa-user-tie"></i></span>
                          </div>
                          <?php foreach ($baris as $row): ?>
                            <div class="seat-row">
                              <?php for ($idx = 0; $idx < $per_baris; $idx++): ?>
                                <?php if ($idx === $kiri && $kanan > 0 && $kiri > 0): ?><span class="lorong"></span><?php endif; ?>
                                <?php if (isset($row[$idx])): $no = $row[$idx]; $penuh = in_array((string) $no, $terisi, true); ?>
                                  <button type="button" class="seat <?= $penuh ? 'terisi' : '' ?>" data-no="<?= $no ?>" aria-label="Kursi <?= $no ?><?= $penuh ? ' (terisi)' : '' ?>" <?= $penuh ? 'disabled' : '' ?>><?= $penuh ? '<i class="bi bi-x-lg"></i>' : $no ?></button>
                                <?php else: ?>
                                  <span class="seat-kosong"></span>
                                <?php endif; ?>
                              <?php endfor; ?>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="modal-footer">
                  <button type="button" class="tv-cta tv-cta-modal" id="btn-konfirmasi-kursi" disabled>Lanjutkan</button>
                </div>
              </div>
            </div>
          </div>

        </form>
      </div>
    </section>