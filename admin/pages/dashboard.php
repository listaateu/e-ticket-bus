<?php
// admin/pages/dashboard.php
// Dashboard E-Ticket Bus: tata letak template Spark Admin, isinya data asli dari database.

include_once __DIR__ . '/../database/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

function rp($n) { return 'Rp ' . number_format((int) $n, 0, ',', '.'); }
$hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$jam   = (int) date('G');
$sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
$tgl_panjang = $hari[(int) date('w')] . ', ' . date('j') . ' ' . $bulan[(int) date('n')] . ' ' . date('Y');

$STATUS = [
    'belum'   => ['label' => 'Belum Bayar', 'warna' => '#94A3B8', 'ikon' => 'bi-clock'],
    'pending' => ['label' => 'Menunggu',    'warna' => '#F97316', 'ikon' => 'bi-hourglass-split'],
    'lunas'   => ['label' => 'Lunas',       'warna' => '#22C55E', 'ikon' => 'bi-check-circle-fill'],
    'ditolak' => ['label' => 'Ditolak',     'warna' => '#EF4444', 'ikon' => 'bi-x-circle-fill'],
];
$PAY = "LEFT JOIN payments p ON p.id = (SELECT MAX(id) FROM payments WHERE booking_id = b.id)";

// ---------- Jumlah per status + pendapatan ----------
$jml = ['belum' => 0, 'pending' => 0, 'lunas' => 0, 'ditolak' => 0];
$pendapatan = 0;
$q = $koneksi->query("SELECT COALESCE(p.status,'belum') AS st, COUNT(*) AS n, SUM(s.harga) AS rp
                      FROM bookings b JOIN schedules s ON s.id = b.schedule_id $PAY GROUP BY st");
while ($r = $q->fetch_assoc()) {
    if (isset($jml[$r['st']])) { $jml[$r['st']] = (int) $r['n']; }
    if ($r['st'] === 'lunas')  { $pendapatan = (int) $r['rp']; }
}
$total = array_sum($jml);

// ---------- Pendapatan bulan ini vs bulan lalu (berdasarkan tanggal berangkat) ----------
$bln_ini  = date('Y-m');
$bln_lalu = date('Y-m', strtotime('first day of last month'));
$st = $koneksi->prepare("SELECT DATE_FORMAT(s.jam_berangkat,'%Y-%m') AS ym, SUM(s.harga) AS rp
                         FROM bookings b JOIN schedules s ON s.id = b.schedule_id $PAY
                         WHERE p.status = 'lunas' AND DATE_FORMAT(s.jam_berangkat,'%Y-%m') IN (?, ?) GROUP BY ym");
$st->bind_param('ss', $bln_ini, $bln_lalu);
$st->execute();
$per_bulan = [$bln_ini => 0, $bln_lalu => 0];
foreach ($st->get_result() as $r) { $per_bulan[$r['ym']] = (int) $r['rp']; }
$ini = $per_bulan[$bln_ini]; $lalu = $per_bulan[$bln_lalu];
if ($lalu > 0) {
    $persen = round(($ini - $lalu) / $lalu * 100);
    $naik = $persen >= 0;
    $teks_tren = ($naik ? '+' : '') . $persen . '% dari bulan lalu';
} else {
    $naik = true;
    $teks_tren = $ini > 0 ? 'Pendapatan mulai masuk bulan ini' : 'Belum ada pembanding bulan lalu';
}

// ---------- Grafik harian: 7 hari lalu s/d 7 hari ke depan (tanggal berangkat) ----------
$awal = date('Y-m-d', strtotime('-7 days')); $akhir = date('Y-m-d', strtotime('+7 days'));
$st = $koneksi->prepare("SELECT DATE(s.jam_berangkat) AS tgl, COALESCE(p.status,'belum') AS st, COUNT(*) AS n, SUM(s.harga) AS rp
                         FROM bookings b JOIN schedules s ON s.id = b.schedule_id $PAY
                         WHERE DATE(s.jam_berangkat) BETWEEN ? AND ? GROUP BY tgl, st");
$st->bind_param('ss', $awal, $akhir);
$st->execute();
$harian = [];
foreach ($st->get_result() as $r) { $harian[$r['tgl']][$r['st']] = ['n' => (int) $r['n'], 'rp' => (int) $r['rp']]; }
$label = []; $lunas_rp = []; $belum_rp = []; $lunas_n = [];
for ($t = strtotime($awal); $t <= strtotime($akhir); $t += 86400) {
    $d = date('Y-m-d', $t);
    $label[]    = date('j', $t) . ' ' . substr($bulan[(int) date('n', $t)], 0, 3);
    $lunas_rp[] = $harian[$d]['lunas']['rp'] ?? 0;
    $lunas_n[]  = $harian[$d]['lunas']['n'] ?? 0;
    $belum_rp[] = ($harian[$d]['pending']['rp'] ?? 0) + ($harian[$d]['belum']['rp'] ?? 0);
}

// ---------- Pemesanan terbaru & keberangkatan terdekat ----------
$terbaru = $koneksi->query("SELECT b.id, b.kode_booking, u.nama, s.kota_asal, s.kota_tujuan, s.harga, COALESCE(p.status,'belum') AS st
                            FROM bookings b JOIN users u ON u.id = b.user_id JOIN schedules s ON s.id = b.schedule_id $PAY
                            ORDER BY b.id DESC LIMIT 5");
$berangkat = $koneksi->query("SELECT s.kota_asal, s.kota_tujuan, s.jam_berangkat, bs.kapasitas_kursi,
                                (SELECT COUNT(*) FROM bookings b $PAY WHERE b.schedule_id = s.id AND COALESCE(p.status,'belum') <> 'ditolak') AS terisi
                              FROM schedules s JOIN buses bs ON bs.id = s.bus_id
                              WHERE s.jam_berangkat >= NOW() ORDER BY s.jam_berangkat ASC LIMIT 6");
$A = '/e-ticket-bus/admin/index.php';
?>
<!-- START: Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle"><?= $sapaan ?>, Admin 👋 Berikut ringkasan pemesanan tiket bus kamu.</p>
  </div>
  <span class="btn-date-picker"><i class="bi bi-calendar4-event"></i> <span><?= $tgl_panjang ?></span></span>
</div>

<div class="row g-4">

  <!-- ===== Baris atas: 3 kartu ringkas ===== -->
  <div class="col-12">
    <div class="row g-4">

      <!-- Perlu dikerjakan -->
      <div class="col-md-4">
        <div class="card alert-green-card">
          <div class="position-relative z-index-2">
            <span class="alert-green-badge">Perlu Dikerjakan</span>
            <div class="alert-green-date"><?= $tgl_panjang ?></div>
            <div class="alert-green-text">
              <?= $jml['pending'] > 0 ? $jml['pending'] . ' pembayaran menunggu kamu periksa' : 'Semua pembayaran sudah diperiksa. Kerja bagus! 🎉' ?>
            </div>
          </div>
          <a href="<?= $A ?>?page=verifikasi_pembayaran" class="alert-green-link z-index-2">
            <span><?= $jml['pending'] > 0 ? 'Periksa sekarang' : 'Lihat pembayaran' ?></span>
            <i class="bi bi-arrow-right"></i>
          </a>
          <svg class="alert-green-bg-shape" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g transform="translate(50,50)">
              <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" />
              <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" transform="rotate(60)" />
              <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" transform="rotate(120)" />
            </g>
          </svg>
        </div>
      </div>

      <!-- Total pendapatan -->
      <div class="col-md-4">
        <div class="card card-stat d-flex flex-column justify-content-between">
          <div>
            <div class="card-header">
              <span class="stat-label">Total Pendapatan</span>
              <i class="bi bi-wallet2 text-muted-green"></i>
            </div>
            <div class="stat-value"><?= rp($pendapatan) ?></div>
            <div class="trend-badge <?= $naik ? 'trend-up' : 'trend-down' ?>">
              <i class="bi <?= $naik ? 'bi-arrow-up-right' : 'bi-arrow-down-left' ?>"></i>
              <span><?= $teks_tren ?></span>
            </div>
          </div>
          <div class="sparkline-container sparkline-card-footer"><div id="spark-pendapatan"></div></div>
        </div>
      </div>

      <!-- Tiket terjual -->
      <div class="col-md-4">
        <div class="card card-stat d-flex flex-column justify-content-between">
          <div>
            <div class="card-header">
              <span class="stat-label">Tiket Terjual</span>
              <i class="bi bi-ticket-perforated text-muted-green"></i>
            </div>
            <div class="stat-value"><?= $jml['lunas'] ?></div>
            <div class="trend-badge trend-up">
              <i class="bi bi-check2-circle"></i>
              <span>Dari <?= $total ?> total pemesanan</span>
            </div>
          </div>
          <div class="sparkline-container sparkline-card-footer"><div id="spark-tiket"></div></div>
        </div>
      </div>

    </div>
  </div>

  <!-- ===== Kiri: grafik + daftar ===== -->
  <div class="col-xl-9 col-lg-8">
    <div class="row g-4">

      <!-- Grafik pendapatan -->
      <div class="col-12">
        <div class="card mb-0">
          <div class="card-header mb-2">
            <h2 class="card-title">Pendapatan Harian</h2>
            <div class="d-flex gap-3 align-items-center">
              <div class="chart-legend-item"><span class="legend-dot bg-forest-medium"></span><span class="chart-legend-label">Sudah lunas</span></div>
              <div class="chart-legend-item"><span class="legend-dot bg-lime-accent"></span><span class="chart-legend-label">Belum lunas</span></div>
            </div>
          </div>
          <div class="d-flex align-items-baseline gap-2 mb-1">
            <span class="stat-value-amount"><?= rp($ini) ?></span>
            <span class="trend-badge <?= $naik ? 'trend-up' : 'trend-down' ?> fs-xs"><?= $teks_tren ?></span>
          </div>
          <p class="text-muted-green mb-3 small">Pendapatan bulan ini. Grafik dihitung per tanggal keberangkatan (7 hari lalu sampai 7 hari ke depan).</p>
          <div id="chart-pendapatan"></div>
        </div>
      </div>

      <!-- Pemesanan terbaru -->
      <div class="col-md-7 d-flex flex-column">
        <div class="card h-100 flex-grow-1">
          <div class="card-header">
            <h2 class="card-title">Pemesanan Terbaru</h2>
            <div class="dropdown">
              <button class="card-more-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Opsi"><i class="bi bi-three-dots"></i></button>
              <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom">
                <li><a class="dropdown-item" href="<?= $A ?>?page=data_pemesanan"><i class="bi bi-list-ul"></i> Lihat semua pemesanan</a></li>
                <li><a class="dropdown-item" href="<?= $A ?>?page=verifikasi_pembayaran"><i class="bi bi-cash-coin"></i> Verifikasi pembayaran</a></li>
              </ul>
            </div>
          </div>
          <div class="transaction-list">
            <?php if ($terbaru->num_rows === 0) : ?>
              <p class="text-muted-green text-center py-4 mb-0">Belum ada pemesanan.</p>
            <?php endif; ?>
            <?php while ($b = $terbaru->fetch_assoc()) : $s = $STATUS[$b['st']] ?? $STATUS['belum']; ?>
              <div class="transaction-item">
                <div class="transaction-icon bg-forest-light text-lime"><i class="bi <?= $s['ikon'] ?>"></i></div>
                <div class="transaction-info">
                  <a class="transaction-name text-decoration-none" href="<?= $A ?>?page=data_pemesanan&aksi=detail&id=<?= (int) $b['id'] ?>"><?= htmlspecialchars($b['nama']) ?></a>
                  <div class="transaction-date"><?= htmlspecialchars($b['kota_asal']) ?> → <?= htmlspecialchars($b['kota_tujuan']) ?> • <?= htmlspecialchars($b['kode_booking']) ?></div>
                </div>
                <div class="text-end">
                  <div class="transaction-amount <?= $b['st'] === 'lunas' ? 'text-success' : 'text-main' ?>"><?= rp($b['harga']) ?></div>
                  <small style="color:<?= $s['warna'] ?>;font-weight:600"><?= $s['label'] ?></small>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      </div>

      <!-- Keberangkatan terdekat -->
      <div class="col-md-5 d-flex flex-column">
        <div class="card h-100 flex-grow-1">
          <div class="card-header">
            <h2 class="card-title">Kursi Terisi</h2>
            <a href="<?= $A ?>?page=jadwal_dan_rute" class="text-muted-green small text-decoration-none">Semua jadwal</a>
          </div>
          <p class="text-muted-green small mb-3">Keberangkatan terdekat. Oranye berarti hampir penuh.</p>
          <?php if ($berangkat->num_rows === 0) : ?>
            <p class="text-muted-green text-center py-4 mb-0">Belum ada jadwal mendatang.</p>
          <?php endif; ?>
          <?php while ($j = $berangkat->fetch_assoc()) :
            $kap = max(1, (int) $j['kapasitas_kursi']); $isi = (int) $j['terisi'];
            $pct = min(100, round($isi / $kap * 100));
          ?>
            <div class="progress-container">
              <div class="progress-label-row">
                <span class="progress-label"><?= htmlspecialchars($j['kota_asal']) ?> → <?= htmlspecialchars($j['kota_tujuan']) ?>
                  <small class="text-muted-green d-block"><?= date('j M, H:i', strtotime($j['jam_berangkat'])) ?></small></span>
                <span class="progress-value"><?= $isi ?>/<?= $kap ?></span>
              </div>
              <div class="progress" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar <?= $pct >= 80 ? 'bg-brand-orange' : 'bg-lime-accent' ?>" style="width:<?= $pct ?>%"></div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

    </div>
  </div>

  <!-- ===== Kanan: status pembayaran + ajakan ===== -->
  <div class="col-xl-3 col-lg-4">
    <div class="right-panel-wrapper d-flex flex-column gap-4 h-100">

      <div class="card flex-grow-1 d-flex flex-column justify-content-between mb-0">
        <div class="card-header mb-1"><h2 class="card-title">Status Pembayaran</h2></div>
        <?php if ($total === 0) : ?>
          <p class="text-muted-green text-center py-5 mb-0">Belum ada pemesanan.</p>
        <?php else : ?>
          <div id="chart-status"></div>
        <?php endif; ?>
        <div class="chart-legends-container">
          <?php foreach ($STATUS as $k => $s) : ?>
            <div class="chart-legend-item">
              <span class="legend-dot" style="background:<?= $s['warna'] ?>"></span>
              <span class="text-muted-green"><?= $s['label'] ?></span>
              <strong class="ms-auto"><?= $jml[$k] ?></strong>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="promo-banner-card">
        <svg class="promo-banner-bg-shape" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
          <g transform="translate(50,50)">
            <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" />
            <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" transform="rotate(60)" />
            <rect x="-6" y="-45" width="12" height="90" rx="6" ry="6" fill="#B4F105" transform="rotate(120)" />
          </g>
        </svg>
        <h3 class="promo-title">Tambah jadwal baru, tiket makin laris.</h3>
        <p class="promo-desc">Atur rute dan jam berangkat supaya penumpang bisa langsung memesan.</p>
        <a href="<?= $A ?>?page=jadwal_dan_rute&aksi=tambah" class="btn-promo text-decoration-none d-inline-block">Tambah jadwal</a>
      </div>

    </div>
  </div>

</div>

<script>
// Jalan setelah ApexCharts dimuat (script.php dipanggil sesudah halaman ini)
document.addEventListener('DOMContentLoaded', function () {
  if (typeof ApexCharts === 'undefined') return;
  var font = 'Plus Jakarta Sans, sans-serif';
  var label = <?= json_encode($label) ?>;
  var rupiah = function (v) { return 'Rp ' + Math.round(v).toLocaleString('id-ID'); };
  var ringkas = function (v) { return v >= 1e6 ? (v / 1e6).toFixed(1).replace('.0', '') + ' jt' : (v >= 1e3 ? Math.round(v / 1e3) + ' rb' : Math.round(v)); };

  function spark(el, nama, data, warna, fmt) {
    new ApexCharts(document.querySelector(el), {
      chart: { type: 'area', height: 70, sparkline: { enabled: true }, fontFamily: font },
      series: [{ name: nama, data: data }], xaxis: { categories: label },
      stroke: { curve: 'smooth', width: 2 }, colors: [warna],
      fill: { type: 'gradient', gradient: { opacityFrom: .35, opacityTo: .02 } },
      tooltip: { y: { formatter: fmt } }
    }).render();
  }
  spark('#spark-pendapatan', 'Pendapatan', <?= json_encode($lunas_rp) ?>, '#22C55E', rupiah);
  spark('#spark-tiket', 'Tiket terjual', <?= json_encode($lunas_n) ?>, '#072F1F', function (v) { return v + ' tiket'; });

  new ApexCharts(document.querySelector('#chart-pendapatan'), {
    chart: { type: 'bar', stacked: true, height: 320, toolbar: { show: false }, fontFamily: font },
    series: [{ name: 'Sudah lunas', data: <?= json_encode($lunas_rp) ?> }, { name: 'Belum lunas', data: <?= json_encode($belum_rp) ?> }],
    xaxis: { categories: label, labels: { style: { colors: '#6C7E75', fontSize: '11px' } }, axisBorder: { show: false } },
    yaxis: { labels: { formatter: ringkas, style: { colors: '#6C7E75' } } },
    colors: ['#072F1F', '#B4F105'],
    plotOptions: { bar: { borderRadius: 6, columnWidth: '48%', borderRadiusApplication: 'end' } },
    dataLabels: { enabled: false }, legend: { show: false },
    grid: { borderColor: '#E9EFEF', strokeDashArray: 4 },
    tooltip: { y: { formatter: rupiah } }
  }).render();

  var st = document.querySelector('#chart-status');
  if (st) {
    new ApexCharts(st, {
      chart: { type: 'donut', height: 230, fontFamily: font },
      series: <?= json_encode(array_values($jml)) ?>,
      labels: <?= json_encode(array_column($STATUS, 'label')) ?>,
      colors: <?= json_encode(array_column($STATUS, 'warna')) ?>,
      legend: { show: false }, dataLabels: { enabled: false },
      stroke: { width: 3, colors: ['#fff'] },
      plotOptions: { pie: { donut: { size: '72%', labels: { show: true, total: { show: true, label: 'Total', color: '#6C7E75', fontSize: '12px' } } } } }
    }).render();
  }
});
</script>