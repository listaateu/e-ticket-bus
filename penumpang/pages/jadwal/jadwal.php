<?php
/**
 * ============================================================
 * FILE         : penumpang/pages/jadwal/jadwal.php
 * FUNGSI       : Halaman CARI JADWAL gaya Traveloka: banner rute + tab tanggal,
 *                filter di kiri (kelas, waktu, nama bus), dan daftar hasil.
 * DIPANGGIL DARI: index.php (page=jadwal)
 * CATATAN      : URL: index.php?page=jadwal&asal=..&tujuan=..&tanggal=..&kelas[]=..&bus[]=..&waktu[]=..&urut=..
 * ============================================================
 */

// ---------- 1. Ambil input dari alamat ----------
$asal    = trim($_GET['asal'] ?? '');
$tujuan  = trim($_GET['tujuan'] ?? '');
$tanggal = trim($_GET['tanggal'] ?? '');
if ($tanggal !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !strtotime($tanggal))) {
    $tanggal = '';
}

[$dewasa, $bayi] = jumlah_penumpang_dari($_GET);   // jumlah penumpang yang dicari (dewasa 1-4, bayi 0..dewasa)

$ambil_daftar = function (string $kunci): array {
    return array_values(array_filter((array) ($_GET[$kunci] ?? []), 'is_string'));
};
$f_kelas = $ambil_daftar('kelas');
$f_bus   = $ambil_daftar('bus');
$f_waktu = $ambil_daftar('waktu');
$urut    = in_array($_GET['urut'] ?? '', ['murah', 'mahal'], true) ? $_GET['urut'] : 'awal';

$daftar_waktu = [
    'dini'  => ['Dini hari', '00:00 - 06:00'],
    'pagi'  => ['Pagi', '06:00 - 12:00'],
    'siang' => ['Siang - sore', '12:00 - 18:00'],
    'malam' => ['Malam', '18:00 - 24:00'],
];
$kunci_waktu = function (array $j): string {
    $jam_ke = (int) date('G', strtotime($j['jam_berangkat']));
    return ['dini', 'pagi', 'siang', 'malam'][intdiv($jam_ke, 6)];
};

// ---------- 2. Ambil data, lalu saring & urutkan ----------
$kota  = daftar_kota($conn);
$semua = cari_jadwal($conn, $asal, $tujuan, $tanggal);   // sebelum filter samping

$hasil = array_values(array_filter($semua, function ($j) use ($f_kelas, $f_bus, $f_waktu, $kunci_waktu) {
    if ($f_kelas && !in_array($j['kelas'], $f_kelas, true)) {
        return false;
    }
    if ($f_bus && !in_array($j['nama_bus'], $f_bus, true)) {
        return false;
    }
    if ($f_waktu && !in_array($kunci_waktu($j), $f_waktu, true)) {
        return false;
    }
    return true;
}));

if ($urut === 'murah') {
    usort($hasil, fn($a, $b) => (float) $a['harga'] <=> (float) $b['harga']);
} elseif ($urut === 'mahal') {
    usort($hasil, fn($a, $b) => (float) $b['harga'] <=> (float) $a['harga']);
}   // 'awal' = sudah terurut jam berangkat dari query

// Jumlah per pilihan filter (dihitung dari hasil sebelum disaring)
$hit_kelas = array_count_values(array_column($semua, 'kelas'));
ksort($hit_kelas);
$hit_bus = array_count_values(array_column($semua, 'nama_bus'));
ksort($hit_bus);
$hit_waktu = array_fill_keys(array_keys($daftar_waktu), 0);
foreach ($semua as $j) {
    $hit_waktu[$kunci_waktu($j)]++;
}

$pil_kelas = [];
foreach ($hit_kelas as $k => $n) {
    $pil_kelas[$k] = [$k, $n];
}
$pil_waktu = [];
foreach ($daftar_waktu as $k => [$nama_w, $rentang]) {
    $pil_waktu[$k] = [$nama_w . ' (' . $rentang . ')', $hit_waktu[$k]];
}
$pil_bus = [];
foreach ($hit_bus as $k => $n) {
    $pil_bus[$k] = [$k, $n];
}
$grup_filter = [
    ['kelas', 'Kelas Bus', $pil_kelas, $f_kelas],
    ['waktu', 'Waktu Keberangkatan', $pil_waktu, $f_waktu],
    ['bus', 'Nama Bus', $pil_bus, $f_bus],
];

$harga_min  = $semua ? min(array_map('floatval', array_column($semua, 'harga'))) : 0;
$ada_filter = $f_kelas || $f_bus || $f_waktu;

// ---------- 3. Pembuat link (tanggal/filter berubah, sisanya tetap) ----------
$dasar        = ['asal' => $asal, 'tujuan' => $tujuan, 'tanggal' => $tanggal, 'dewasa' => $dewasa, 'bayi' => $bayi];
$filter_aktif = ['kelas' => $f_kelas, 'bus' => $f_bus, 'waktu' => $f_waktu, 'urut' => $urut === 'awal' ? '' : $urut];
$link = function (array $ubah = []) use ($dasar, $filter_aktif): string {
    $q = array_merge(['page' => 'jadwal'], $dasar, $filter_aktif, $ubah);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== []);
    return 'index.php?' . http_build_query($q);
};
$link_reset = 'index.php?' . http_build_query(array_filter(['page' => 'jadwal'] + $dasar, fn($v) => $v !== ''));

// ---------- 4. Tab tanggal: 7 hari ke depan ----------
$nama_hari  = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
$nama_bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$label_tgl  = fn(int $t): string => $nama_hari[(int) date('w', $t)] . ', ' . date('j', $t) . ' ' . $nama_bulan[(int) date('n', $t)];

$tab_tanggal = [];
for ($i = 0; $i < 7; $i++) {
    $t = strtotime("+$i day");
    $tab_tanggal[date('Y-m-d', $t)] = $label_tgl($t);
}
if ($tanggal !== '' && !isset($tab_tanggal[$tanggal])) {   // tanggal pilihan di luar 7 hari
    $tab_tanggal = [$tanggal => $label_tgl(strtotime($tanggal))] + $tab_tanggal;
}

$sub_tanggal = $tanggal !== '' ? tanggal_indo($tanggal, false) : 'Semua tanggal';
$rute_kosong = ($asal === '' && $tujuan === '');
?>
    <!-- Perbaikan: kotak pilihan Penumpang (Dewasa/Bayi) terpotong di tepi bawah banner biru.
         Banner dibiarkan meluap ke bawah (hanya sisi kiri-kanan yang tetap dipotong) dan diberi lapisan di atas bagian lain. -->
    <style>
      .tv-result-head { overflow-x: clip; overflow-y: visible; position: relative; z-index: 20; }
      .tv-result-head .tv-edit-card { overflow: visible; }
    </style>

    <!-- Banner biru: rute + tab tanggal -->
    <section class="tv-result-head">
      <div class="container">
        <div class="d-flex justify-content-between align-items-start gap-3">
          <div>
            <?php if ($rute_kosong): ?>
              <h1>Semua Jadwal Keberangkatan</h1>
            <?php else: ?>
              <h1>Berangkat: <?= e($asal !== '' ? $asal : 'Semua kota') ?> <i class="bi bi-arrow-right"></i> <?= e($tujuan !== '' ? $tujuan : 'Semua kota') ?></h1>
            <?php endif; ?>
            <div class="tv-meta"><i class="bi bi-calendar-event me-1"></i><?= e($sub_tanggal) ?><span class="mx-2">·</span><i class="bi bi-people me-1"></i><?= e(teks_penumpang($dewasa, $bayi)) ?></div>
          </div>
          <button class="tv-edit" type="button" data-bs-toggle="collapse" data-bs-target="#edit-cari" aria-expanded="<?= $rute_kosong ? 'true' : 'false' ?>">
            Edit <i class="bi bi-pencil ms-1"></i>
          </button>
        </div>

        <div class="collapse <?= $rute_kosong ? 'show' : '' ?>" id="edit-cari">
          <div class="tv-edit-card">
            <?php komponen_form_cari($kota, compact('asal', 'tujuan', 'tanggal', 'dewasa', 'bayi')); ?>
          </div>
        </div>

        <div class="tv-dates">
          <a href="<?= e($link(['tanggal' => ''])) ?>" class="tv-date <?= $tanggal === '' ? 'active' : '' ?>">Semua Tanggal</a>
          <?php foreach ($tab_tanggal as $tgl => $label): ?>
            <a href="<?= e($link(['tanggal' => $tgl])) ?>" class="tv-date <?= $tanggal === $tgl ? 'active' : '' ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Ringkasan -->
    <section class="tv-summary">
      <div class="container">
        <div class="in">
          <i class="bi bi-bus-front-fill"></i>
          <b>Bus</b>
          <span><?= count($semua) ?> jadwal</span>
          <?php if ($semua): ?><span>Dari: <b><?= e(rupiah($harga_min)) ?></b></span><?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Filter + hasil -->
    <section id="jadwal" class="tv-page">
      <div class="container">
        <div class="row g-4">

          <!-- Filter samping -->
          <div class="col-lg-3">
            <form id="form-filter" action="index.php" method="get" class="tv-filter">
              <input type="hidden" name="page" value="jadwal">
              <input type="hidden" name="asal" value="<?= e($asal) ?>">
              <input type="hidden" name="tujuan" value="<?= e($tujuan) ?>">
              <input type="hidden" name="tanggal" value="<?= e($tanggal) ?>">
              <input type="hidden" name="dewasa" value="<?= $dewasa ?>">
              <input type="hidden" name="bayi" value="<?= $bayi ?>">

              <div class="tv-filter-head">
                <div class="judul">
                  <h5>Filter Bus</h5>
                  <a href="<?= e($link_reset) ?>">Atur ulang filter</a>
                </div>
                <p>Menampilkan hasil berdasarkan kategori Anda</p>
              </div>

              <?php foreach ($grup_filter as [$nama, $judul, $pilihan, $terpilih]):
                  if (!$pilihan) { continue; }
                  $id   = 'fg-' . $nama;
                  $buka = ($nama === 'kelas') || !empty($terpilih);
              ?>
                <div class="tv-fgroup">
                  <button type="button" class="tv-fgroup-btn" data-bs-toggle="collapse" data-bs-target="#<?= $id ?>" aria-expanded="<?= $buka ? 'true' : 'false' ?>">
                    <?= e($judul) ?> <i class="bi bi-chevron-down"></i>
                  </button>
                  <div class="collapse <?= $buka ? 'show' : '' ?>" id="<?= $id ?>">
                    <div class="tv-fgroup-body">
                      <?php $no = 0; foreach ($pilihan as $nilai => [$label, $jml]): $no++; ?>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox"
                                 name="<?= $nama ?>[]" id="<?= $id . '-' . $no ?>"
                                 value="<?= e($nilai) ?>"
                                 <?= in_array((string) $nilai, $terpilih, true) ? 'checked' : '' ?>
                                 onchange="this.form.submit()">
                          <label class="form-check-label" for="<?= $id . '-' . $no ?>">
                            <span><?= e($label) ?></span>
                            <span class="jml"><?= (int) $jml ?></span>
                          </label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </form>
          </div>

          <!-- Hasil -->
          <div class="col-lg-9">
            <div class="tv-sortbar">
              <div>
                <strong><?= count($hasil) ?></strong> jadwal ditemukan<?= $ada_filter ? ' dari ' . count($semua) : '' ?>
              </div>
              <label class="d-flex align-items-center gap-2 mb-0">
                <span class="text-muted">Urutkan</span>
                <select name="urut" form="form-filter" onchange="this.form.submit()">
                  <option value="awal"  <?= $urut === 'awal'  ? 'selected' : '' ?>>Jam paling awal</option>
                  <option value="murah" <?= $urut === 'murah' ? 'selected' : '' ?>>Harga termurah</option>
                  <option value="mahal" <?= $urut === 'mahal' ? 'selected' : '' ?>>Harga termahal</option>
                </select>
              </label>
            </div>

            <?php if (!$hasil): ?>
              <div class="kosong text-center" data-aos="fade-up">
                <i class="fa-solid fa-bus-simple"></i>
                <h5>Jadwal tidak ditemukan</h5>
                <p class="text-muted"><?= $ada_filter ? 'Coba kurangi filter yang dipilih.' : 'Coba ubah kota atau tanggal pencarian.' ?></p>
                <a href="<?= e($ada_filter ? $link(['kelas' => [], 'bus' => [], 'waktu' => [], 'urut' => '']) : url('jadwal')) ?>" class="tv-btn-outline"><?= $ada_filter ? 'Atur Ulang Filter' : 'Reset Pencarian' ?></a>
              </div>
            <?php endif; ?>

            <?php foreach ($hasil as $j) { komponen_card_jadwal($j, $dewasa, $bayi); } ?>
          </div>

        </div>
      </div>
    </section>