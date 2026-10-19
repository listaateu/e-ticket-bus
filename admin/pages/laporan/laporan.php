<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/pages/laporan/laporan.php
// Laporan penjualan per jadwal. Mengambil SEMUA jadwal (akan datang, berjalan, selesai, dibatalkan)
// dalam rentang tanggal berangkat yang dipilih. Jadwal lama tidak pernah dihapus, jadi laporan lama tetap utuh.
// Pendapatan dihitung sama seperti dashboard & Data Pemesanan: harga x jumlah dewasa, hanya pembayaran berstatus lunas.

include_once __DIR__ . '/../../database/koneksi.php';
include_once __DIR__ . '/../../components/pagination.php';

// ---------- Tanggal acuan dari MySQL (supaya sama dengan NOW() di halaman lain) ----------
$ac = $koneksi->query(
    "SELECT CURDATE() AS hari_ini,
            DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) - 1 DAY) AS awal_bulan,
            LAST_DAY(CURDATE()) AS akhir_bulan,
            DATE_SUB(DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) - 1 DAY), INTERVAL 1 MONTH) AS awal_lalu,
            LAST_DAY(DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) DAY)) AS akhir_lalu,
            DATE_SUB(CURDATE(), INTERVAL 6 DAY) AS tujuh_hari,
            (SELECT DATE(MIN(jam_berangkat)) FROM schedules) AS paling_awal,
            (SELECT DATE(MAX(jam_berangkat)) FROM schedules) AS paling_akhir"
)->fetch_assoc();

// Tanggal dari alamat (?dari=2026-10-01&sampai=2026-10-31). Format salah / tanggal tidak ada = pakai bawaan (bulan ini).
function tanggal_valid(string $t): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $t);
    return $d !== false && $d->format('Y-m-d') === $t;
}
$dari   = param_str('dari', '', 'GET', 10);
$sampai = param_str('sampai', '', 'GET', 10);
if (!tanggal_valid($dari))   { $dari   = $ac['awal_bulan']; }
if (!tanggal_valid($sampai)) { $sampai = $ac['akhir_bulan']; }
if ($dari > $sampai) { [$dari, $sampai] = [$sampai, $dari]; }   // kebalik -> ditukar

// Syarat rentang: dari 00:00 hari pertama sampai sebelum 00:00 sehari setelah hari terakhir
$syarat_rentang = 's.jam_berangkat >= ? AND s.jam_berangkat < DATE_ADD(?, INTERVAL 1 DAY)';

// ---------- Ringkasan ----------
$st = $koneksi->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(s.jam_berangkat < NOW()), 0) AS sudah_berangkat FROM schedules s WHERE $syarat_rentang");
$st->bind_param('ss', $dari, $sampai);
$st->execute();
$r1 = $st->get_result()->fetch_assoc();
$total          = (int) $r1['total'];
$sudah_berangkat = (int) $r1['sudah_berangkat'];

$st = $koneksi->prepare(
    "SELECT COUNT(*) AS jml_booking,
            COALESCE(SUM(bk.jumlah_dewasa), 0) AS dewasa,
            COALESCE(SUM(bk.jumlah_bayi), 0) AS bayi,
            COALESCE(SUM(bk.jumlah_dewasa * s.harga), 0) AS rupiah
     FROM bookings bk
     JOIN schedules s ON s.id = bk.schedule_id
     JOIN payments p ON p.id = (SELECT MAX(id) FROM payments WHERE booking_id = bk.id)
     WHERE p.status = 'lunas' AND $syarat_rentang"
);
$st->bind_param('ss', $dari, $sampai);
$st->execute();
$r2 = $st->get_result()->fetch_assoc();

// ---------- Tabel per jadwal (semua jadwal dalam rentang, termasuk yang sudah selesai) ----------
$per_halaman_laporan = 20;
[$per_halaman, $offset] = paginasi($total, $per_halaman_laporan);

$st = $koneksi->prepare(
    "SELECT s.id, s.kota_asal, s.kota_tujuan, s.jam_berangkat, s.jam_tiba, s.harga,
            b.nama_bus, b.plat_nomor, b.kelas,
            (s.jam_berangkat < NOW()) AS sudah_lewat,
            (s.jam_berangkat <= NOW() AND COALESCE(s.jam_tiba, s.jam_berangkat) > NOW()) AS sedang_jalan,
            COUNT(CASE WHEN p.status = 'lunas' THEN bk.id END) AS jml_lunas,
            COALESCE(SUM(CASE WHEN p.status = 'lunas' THEN bk.jumlah_dewasa END), 0) AS dewasa,
            COALESCE(SUM(CASE WHEN p.status = 'lunas' THEN bk.jumlah_bayi END), 0) AS bayi,
            COALESCE(SUM(CASE WHEN p.status = 'lunas' THEN bk.jumlah_dewasa * s.harga END), 0) AS rupiah
     FROM schedules s
     JOIN buses b ON b.id = s.bus_id
     LEFT JOIN bookings bk ON bk.schedule_id = s.id
     LEFT JOIN payments p ON p.id = (SELECT MAX(id) FROM payments WHERE booking_id = bk.id)
     WHERE $syarat_rentang
     GROUP BY s.id, s.kota_asal, s.kota_tujuan, s.jam_berangkat, s.jam_tiba, s.harga, b.nama_bus, b.plat_nomor, b.kelas
     ORDER BY s.jam_berangkat DESC, s.id DESC
     LIMIT ? OFFSET ?"
);
$st->bind_param('ssii', $dari, $sampai, $per_halaman, $offset);
$st->execute();
$hasil = $st->get_result();

// ---------- Tombol rentang cepat ----------
$preset = [
    'Bulan Ini'     => [$ac['awal_bulan'], $ac['akhir_bulan']],
    'Bulan Lalu'    => [$ac['awal_lalu'],  $ac['akhir_lalu']],
    '7 Hari Terakhir' => [$ac['tujuh_hari'], $ac['hari_ini']],
    'Semua Data'    => [$ac['paling_awal'] ?: $ac['hari_ini'], $ac['paling_akhir'] ?: $ac['hari_ini']],
];
$url = BASE_URL . '/admin/index.php?page=laporan';
$tgl = function (string $t): string { return date('d M Y', strtotime($t)); };
?>
<style>
  .btn-table-action.tab-aktif { background:#B94E77 !important; color:#fff !important; border-color:#B94E77 !important; font-weight:700; }
  .badge-kelas { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:.75rem; font-weight:600; white-space:nowrap; }
  .badge-kelas::before { content:""; width:6px; height:6px; border-radius:50%; background:currentColor; }
  .form-rentang { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
  .form-rentang label { font-size:.8rem; color:#6b7280; display:block; margin-bottom:2px; }
  @media print {
    .sidebar-wrapper, .navbar-custom, .footer-custom, .no-print, .pagination-custom { display:none !important; }
    .main-wrapper { margin:0 !important; padding:0 !important; width:100% !important; }
  }
</style>

<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Laporan Penjualan</h1>
    <p class="page-subtitle">Rekap per jadwal, termasuk jadwal yang sudah selesai. Pilih rentang tanggal keberangkatan.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Transaksi</li>
      <li class="breadcrumb-item active text-main" aria-current="page">Laporan</li>
    </ol>
  </nav>
</div>
<!-- page header -->

<!-- filter tanggal -->
<div class="card border-light shadow-sm p-3 mb-3 no-print">
  <form method="get" action="<?= BASE_URL ?>/admin/index.php" class="form-rentang">
    <input type="hidden" name="page" value="laporan">
    <div>
      <label for="dari">Dari tanggal</label>
      <input type="date" id="dari" name="dari" class="form-control" value="<?= htmlspecialchars($dari) ?>" required>
    </div>
    <div>
      <label for="sampai">Sampai tanggal</label>
      <input type="date" id="sampai" name="sampai" class="form-control" value="<?= htmlspecialchars($sampai) ?>" required>
    </div>
    <button type="submit" class="btn-table-action"><i class="bi bi-funnel"></i> Terapkan</button>
    <button type="button" class="btn-table-action" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
  </form>
  <div class="table-filter-group mt-3">
    <?php foreach ($preset as $label => [$a, $b]) : ?>
      <a href="<?= $url ?>&dari=<?= $a ?>&sampai=<?= $b ?>" class="btn-table-action<?= ($dari === $a && $sampai === $b) ? ' tab-aktif' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
</div>

<p class="text-muted small mb-2">Periode: <strong><?= $tgl($dari) ?></strong> s/d <strong><?= $tgl($sampai) ?></strong></p>

<!-- ringkasan -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card border-light shadow-sm p-3">
      <div class="text-muted small">Jumlah Jadwal</div>
      <div class="fs-4 fw-bold"><?= $total ?></div>
      <div class="text-muted small"><?= $sudah_berangkat ?> sudah berangkat, <?= $total - $sudah_berangkat ?> akan datang</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-light shadow-sm p-3">
      <div class="text-muted small">Pemesanan Lunas</div>
      <div class="fs-4 fw-bold"><?= (int) $r2['jml_booking'] ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-light shadow-sm p-3">
      <div class="text-muted small">Penumpang</div>
      <div class="fs-4 fw-bold"><?= (int) $r2['dewasa'] + (int) $r2['bayi'] ?></div>
      <div class="text-muted small"><?= (int) $r2['dewasa'] ?> dewasa, <?= (int) $r2['bayi'] ?> bayi</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-light shadow-sm p-3">
      <div class="text-muted small">Pendapatan (Lunas)</div>
      <div class="fs-4 fw-bold">Rp <?= number_format((int) $r2['rupiah'], 0, ',', '.') ?></div>
    </div>
  </div>
</div>
<!-- ringkasan -->

<!-- kartu tabel -->
<div class="table-card-custom">
  <div class="table-header-control">
    <div class="table-pagination-info">Menampilkan: <strong><?= $total ?></strong> jadwal</div>
  </div>

  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No</th>
          <th>Rute</th>
          <th>Bus</th>
          <th>Jam Berangkat</th>
          <th>Status</th>
          <th class="text-center">Pemesanan Lunas</th>
          <th class="text-center">Penumpang</th>
          <th>Pendapatan</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr><td colspan="8" class="text-center">Tidak ada jadwal pada periode ini.</td></tr>
        <?php endif; ?>

        <?php $no = $offset + 1; while ($j = $hasil->fetch_assoc()) : $lewat = (bool) $j['sudah_lewat']; ?>
          <tr>
            <td><?= $no++ ?></td>
            <td class="table-order-id">
              <?= htmlspecialchars($j['kota_asal']) ?>
              <i class="bi bi-arrow-right mx-1"></i>
              <?= htmlspecialchars($j['kota_tujuan']) ?>
            </td>
            <td>
              <?= htmlspecialchars($j['nama_bus']) ?>
              <small class="text-muted">(<?= htmlspecialchars($j['plat_nomor']) ?>)</small>
            </td>
            <td><?= date('d M Y, H:i', strtotime($j['jam_berangkat'])) ?></td>
            <td>
              <?php if (!$lewat) : ?>
                <span class="badge-kelas" style="background:#dcfce7;color:#15803d">Akan Datang</span>
              <?php elseif ((int) $j['jml_lunas'] === 0) : ?>
                <span class="badge-kelas" style="background:#fee2e2;color:#b91c1c">Dibatalkan</span>
              <?php elseif ((int) $j['sedang_jalan'] === 1) : ?>
                <span class="badge-kelas" style="background:#fef3c7;color:#b45309">Sedang Berjalan</span>
              <?php else : ?>
                <span class="badge-kelas" style="background:#e5e7eb;color:#4b5563">Selesai</span>
              <?php endif; ?>
            </td>
            <td class="text-center"><?= (int) $j['jml_lunas'] ?></td>
            <td class="text-center"><?= (int) $j['dewasa'] + (int) $j['bayi'] ?></td>
            <td>Rp <?= number_format((int) $j['rupiah'], 0, ',', '.') ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <?php render_pagination($total, $per_halaman_laporan); ?>
</div>
<!-- kartu tabel -->