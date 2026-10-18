<?php
/**
 * ============================================================
 * FILE         : penumpang/function/komponen_card_jadwal.php
 * FUNGSI       : Komponen: kartu satu jadwal bus gaya Traveloka (foto, jam berangkat & tiba, durasi,
 *                fasilitas, harga) + jendela "Detail Bus" (tab Fitur, Rute, Tiket).
 * DIPANGGIL DARI: pages/beranda/beranda.php, pages/jadwal/jadwal.php
 * CATATAN      : Teks tab "Tiket" bisa diubah di bagian $info_penting dan $syarat di bawah.
 * ============================================================
 */

require_once __DIR__ . '/../../includes/bus_helper.php';

// Gambar cadangan bila bus belum punya foto (relatif terhadap penumpang/index.php)
if (!defined('BUS_FOTO_DEFAULT')) {
    define('BUS_FOTO_DEFAULT', 'assets/img/bus-default.svg');
}

if (!function_exists('durasi_perjalanan')) {
    /** Selisih dua waktu jadi teks pendek, mis. "3j 45m". Kosong bila tidak valid. */
    function durasi_perjalanan(?string $berangkat, ?string $tiba): string
    {
        if (!$berangkat || !$tiba) {
            return '';
        }
        $selisih = strtotime($tiba) - strtotime($berangkat);
        if ($selisih <= 0) {
            return '';
        }
        $jam   = intdiv($selisih, 3600);
        $menit = intdiv($selisih % 3600, 60);

        if ($jam > 0 && $menit > 0) {
            return $jam . 'j ' . $menit . 'm';
        }
        return $jam > 0 ? $jam . 'j' : $menit . 'm';
    }
}

if (!function_exists('bus_tgl_singkat')) {
    /** "2026-10-20 08:00:00" -> "20 Okt" */
    function bus_tgl_singkat(string $waktu): string
    {
        static $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $t = strtotime($waktu);
        return date('j', $t) . ' ' . $bulan[(int) date('n', $t)];
    }
}

/** Kartu satu jadwal keberangkatan + jendela detailnya */
function komponen_card_jadwal(array $j, int $dewasa = 1, int $bayi = 0): void
{
    static $script_dimuat = false;

    $id        = (int) $j['id'];
    $sisa      = max(0, (int) $j['kapasitas_kursi'] - (int) $j['terisi']);
    $penuh     = $sisa === 0;
    $kurang    = !$penuh && $sisa < $dewasa;   // kursi masih ada, tapi kurang dari jumlah dewasa yang dicari
    $sedikit   = !$penuh && $sisa <= 5;
    $kelas_css = strtolower(preg_replace('/[^a-z]/i', '', (string) $j['kelas']));

    // Jam tiba & durasi (kosong bila admin belum mengisi jam tiba)
    $t_berangkat = strtotime($j['jam_berangkat']);
    $t_tiba      = !empty($j['jam_tiba']) ? strtotime($j['jam_tiba']) : false;
    $tiba_valid  = $t_tiba !== false && $t_tiba > $t_berangkat;
    $durasi      = $tiba_valid ? durasi_perjalanan($j['jam_berangkat'], $j['jam_tiba']) : '';
    $plus_hari   = 0;   // 1 = tiba di hari berikutnya
    if ($tiba_valid) {
        $plus_hari = (new DateTime(date('Y-m-d', $t_berangkat)))
            ->diff(new DateTime(date('Y-m-d', $t_tiba)))->days;
    }

    // Data bus: foto, fasilitas, tata letak
    $foto        = bus_url_foto($j['foto'] ?? '');
    $fasilitas   = bus_fasilitas_dari_teks($j['fasilitas'] ?? '');
    $tata_letak  = trim((string) ($j['tata_letak'] ?? ''));
    $ikon_tampil = array_slice($fasilitas, 0, 5);
    $lainnya     = count($fasilitas) - count($ikon_tampil);
    $modal_id    = 'detail-bus-' . $id;

    // Isi tab "Tiket" (silakan ubah sesuai aturan operator kamu)
    $info_penting = [
        'E-ticket terbit setelah pembayaranmu diverifikasi oleh admin.',
        'E-ticket bisa dibuka kapan saja di menu "Tiket Saya".',
        'Pada hari keberangkatan, tunjukkan e-ticket (QR code) kepada kondektur sebelum naik bus.',
        'Bawa kartu identitas dan datang lebih awal sebelum jam keberangkatan.',
        'Jam keberangkatan, jenis bus, dan rute dapat berubah karena alasan operasional.',
    ];
    $syarat = [
        'Satu pemesanan bisa untuk maksimal ' . MAKS_DEWASA . ' penumpang dewasa. Setiap dewasa mendapat satu kursi.',
        'Bayi gratis dan tidak mendapat kursi sendiri (dipangku). Satu dewasa maksimal membawa satu bayi.',
        'Satu e-ticket berlaku untuk semua penumpang dalam pemesanan tersebut, untuk satu kali perjalanan sesuai jadwal.',
        'Kursi yang sudah dipesan tidak dapat dipilih oleh penumpang lain.',
        'Tiket baru berlaku setelah status pembayaran lunas.',
        'Pembayaran harus diselesaikan dalam ' . teks_durasi_bayar() . ' setelah pemesanan. Setelah waktu habis, pesanan dibatalkan otomatis dan pembayaran tidak bisa dikirim lagi. Pembayaran yang dikirim setelah itu tidak dapat dikembalikan.',
        'Ketentuan lain (bagasi, perubahan jadwal, pembatalan) mengikuti kebijakan operator bus.',
    ];
    ?>
    <div class="tv-card" data-aos="fade-up">
      <div class="tv-card-body">

        <div class="tv-card-foto">
          <?php if ($foto !== ''): ?>
            <img src="<?= e($foto) ?>" alt="Foto <?= e($j['nama_bus']) ?>" loading="lazy">
          <?php else: ?>
            <img src="<?= e(BUS_FOTO_DEFAULT) ?>" alt="Ilustrasi <?= e($j['nama_bus']) ?>" loading="lazy">
          <?php endif; ?>
        </div>

        <div class="tv-card-info">
          <div class="tv-po"><?= e($j['nama_bus']) ?></div>
          <div class="tv-sub">
            <span class="tv-kelas <?= e($kelas_css) ?>"><?= e($j['kelas']) ?></span>
            <span><?= e($j['plat_nomor']) ?></span>
          </div>

          <div class="tv-trip">
            <div class="waktu">
              <strong><?= e(jam($j['jam_berangkat'])) ?></strong>
              <span><?= e($j['kota_asal']) ?></span>
            </div>

            <div class="tengah">
              <?php if ($durasi !== ''): ?><span class="durasi"><?= e($durasi) ?></span><?php endif; ?>
              <i class="bi bi-arrow-right panah"></i>
            </div>

            <div class="waktu">
              <?php if ($tiba_valid): ?>
                <strong><?= e(jam($j['jam_tiba'])) ?><?php if ($plus_hari > 0): ?> <small>+<?= (int) $plus_hari ?></small><?php endif; ?></strong>
              <?php else: ?>
                <strong class="belum">--:--</strong>
              <?php endif; ?>
              <span><?= e($j['kota_tujuan']) ?></span>
            </div>
          </div>

          <div class="tv-chips">
            <span class="tv-chip"><i class="bi bi-calendar-event"></i>Berangkat <?= e(tanggal_indo($j['jam_berangkat'], false)) ?></span>
            <?php if ($plus_hari > 0): ?>
              <span class="tv-chip"><i class="bi bi-flag"></i>Tiba <?= e(tanggal_indo($j['jam_tiba'], false)) ?></span>
            <?php endif; ?>
            <span class="tv-chip <?= $penuh ? 'penuh' : ($sedikit ? 'sedikit' : '') ?>">
              <i class="bi bi-person-fill"></i><?= $penuh ? 'Kursi penuh' : 'Sisa ' . $sisa . ' kursi' ?>
            </span>
            <?php if (!empty($j['sopir'])): ?>
              <span class="tv-chip"><i class="bi bi-person-badge"></i>Sopir: <?= e($j['sopir']) ?></span>
            <?php endif; ?>
          </div>

          <div class="tv-card-foot">
            <?php if ($ikon_tampil): ?>
              <div class="tv-fasil">
                <?php foreach ($ikon_tampil as $kode): ?>
                  <i class="fa-solid <?= e(FASILITAS_BUS[$kode][1]) ?>" title="<?= e(FASILITAS_BUS[$kode][0]) ?>"></i>
                <?php endforeach; ?>
                <?php if ($lainnya > 0): ?><span class="lainnya">+<?= (int) $lainnya ?></span><?php endif; ?>
              </div>
            <?php endif; ?>
            <button type="button" class="tv-detail-link" data-bs-toggle="modal" data-bs-target="#<?= e($modal_id) ?>">Lihat detail</button>
          </div>
        </div>

        <div class="tv-card-price">
          <div class="tv-harga"><?= e(rupiah($j['harga'])) ?> <small>/pax</small></div>
          <?php if ($dewasa > 1): ?>
            <div class="tv-harga-total">Total <?= e(rupiah((float) $j['harga'] * $dewasa)) ?> <small>untuk <?= e(teks_penumpang($dewasa, $bayi)) ?></small></div>
          <?php endif; ?>
          <?php if ($penuh): ?>
            <span class="tv-pesan disabled">Penuh</span>
          <?php elseif ($kurang): ?>
            <span class="tv-pesan disabled" title="Sisa kursi kurang dari jumlah penumpang">Kursi kurang</span>
          <?php else: ?>
            <a href="<?= url('pilih-kursi', ['id' => $j['id'], 'dewasa' => $dewasa, 'bayi' => $bayi]) ?>" class="tv-pesan">Pesan Sekarang</a>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <!-- Jendela Detail Bus (ditaruh di luar kartu supaya tidak terpengaruh animasi kartu) -->
    <div class="modal fade tv-modal" id="<?= e($modal_id) ?>" tabindex="-1" aria-labelledby="judul-<?= e($modal_id) ?>" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

          <div class="modal-header">
            <h4 class="modal-title" id="judul-<?= e($modal_id) ?>">Detail Bus</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>

          <div class="modal-body">
            <ul class="nav tv-dtabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#fitur-<?= $id ?>">Fitur</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#rute-<?= $id ?>">Rute</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#tiket-<?= $id ?>">Tiket</button>
              </li>
            </ul>

            <div class="tab-content">

              <!-- TAB FITUR -->
              <div class="tab-pane fade show active" id="fitur-<?= $id ?>" role="tabpanel">
                <div class="row g-4">
                  <div class="col-md-6">
                    <h6 class="tv-dtitle">Spesifikasi Armada</h6>
                    <div class="tv-spek">
                      <p><strong>Bus:</strong> <?= e($j['nama_bus']) ?> (<?= e($j['plat_nomor']) ?>)</p>
                      <p><strong>Kelas:</strong> <span class="tv-kelas <?= e($kelas_css) ?>"><?= e($j['kelas']) ?></span></p>
                      <p><strong>Kursi:</strong> <?= (int) $j['kapasitas_kursi'] ?> kursi</p>
                      <?php if ($tata_letak !== ''): ?>
                        <p><strong>Tata Letak Kursi:</strong> <?= e($tata_letak) ?></p>
                      <?php endif; ?>
                      <p class="mb-2"><strong>Fasilitas:</strong></p>
                      <?php if ($fasilitas): ?>
                        <div class="tv-fasil-grid">
                          <?php foreach ($fasilitas as $kode): ?>
                            <div><i class="fa-solid <?= e(FASILITAS_BUS[$kode][1]) ?>"></i><?= e(FASILITAS_BUS[$kode][0]) ?></div>
                          <?php endforeach; ?>
                        </div>
                      <?php else: ?>
                        <span class="text-muted">Data fasilitas belum diisi.</span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="tv-foto-besar">
                      <?php if ($foto !== ''): ?>
                        <img src="<?= e($foto) ?>" alt="Foto <?= e($j['nama_bus']) ?>" loading="lazy">
                      <?php else: ?>
                        <img src="<?= e(BUS_FOTO_DEFAULT) ?>" alt="Ilustrasi <?= e($j['nama_bus']) ?>" loading="lazy">
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <h6 class="tv-dtitle mt-4">Manfaat yang Termasuk</h6>
                <ul class="tv-manfaat">
                  <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <div><strong>Pilih Kursi Sendiri</strong><span>Lihat denah dan pilih nomor kursi yang masih kosong sebelum membayar.</span></div>
                  </li>
                  <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <div><strong>E-Ticket dengan QR Code</strong><span>Tiket digital terbit setelah pembayaran diverifikasi, cukup tunjukkan ke kondektur.</span></div>
                  </li>
                </ul>
              </div>

              <!-- TAB RUTE -->
              <div class="tab-pane fade" id="rute-<?= $id ?>" role="tabpanel">
                <div class="row g-4">
                  <div class="col-md-7">
                    <ul class="tv-tl">
                      <li>
                        <div class="baris">
                          <div class="waktu-col">
                            <div class="jam"><?= e(jam($j['jam_berangkat'])) ?></div>
                            <div class="tgl"><?= e(bus_tgl_singkat($j['jam_berangkat'])) ?></div>
                          </div>
                          <div>
                            <div class="kota"><?= e($j['kota_asal']) ?></div>
                            <div class="ket">Kota keberangkatan</div>
                          </div>
                        </div>
                        <?php if ($durasi !== ''): ?>
                          <div class="durasi"><i class="bi bi-clock"></i> <?= e($durasi) ?></div>
                        <?php endif; ?>
                      </li>
                      <li class="akhir">
                        <div class="baris">
                          <div class="waktu-col">
                            <?php if ($tiba_valid): ?>
                              <div class="jam"><?= e(jam($j['jam_tiba'])) ?></div>
                              <div class="tgl"><?= e(bus_tgl_singkat($j['jam_tiba'])) ?></div>
                            <?php else: ?>
                              <div class="jam text-muted">--:--</div>
                            <?php endif; ?>
                          </div>
                          <div>
                            <div class="kota"><?= e($j['kota_tujuan']) ?></div>
                            <div class="ket"><?= $tiba_valid ? 'Kota tujuan' : 'Jam tiba belum ditentukan' ?></div>
                          </div>
                        </div>
                      </li>
                    </ul>
                  </div>

                  <div class="col-md-5">
                    <h6 class="tv-dtitle">Rute Bus:</h6>
                    <p class="mb-3"><?= e($j['kota_asal']) ?> - <?= e($j['kota_tujuan']) ?></p>
                    <p class="text-muted mb-1">Bus: <?= e($j['nama_bus']) ?> (<?= e($j['plat_nomor']) ?>)</p>
                    <?php if (!empty($j['sopir'])): ?>
                      <p class="text-muted mb-0">Sopir: <?= e($j['sopir']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- TAB TIKET -->
              <div class="tab-pane fade" id="tiket-<?= $id ?>" role="tabpanel">
                <div class="tv-info">
                  <h5>Informasi Penting</h5>
                  <ul>
                    <?php foreach ($info_penting as $baris): ?><li><?= e($baris) ?></li><?php endforeach; ?>
                  </ul>

                  <h5 class="mt-4">Syarat &amp; Ketentuan</h5>
                  <ul>
                    <?php foreach ($syarat as $baris): ?><li><?= e($baris) ?></li><?php endforeach; ?>
                  </ul>
                </div>
              </div>

            </div>
          </div>

        </div>
      </div>
    </div>

    <?php if (!$script_dimuat): $script_dimuat = true; ?>
    <script>
      // Pindahkan semua jendela detail ke <body> supaya tampil tepat di tengah layar
      document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.tv-modal').forEach(function (m) { document.body.appendChild(m); });
      });
    </script>
    <?php endif; ?>
<?php }