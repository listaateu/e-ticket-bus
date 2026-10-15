<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/pages/jadwal_dan_rute/jadwal_dan_rute.php
// Tabel jadwal & rute (bagian R = Read dari CRUD), bisa difilter per kelas bus.

include_once __DIR__ . '/../../database/koneksi.php';
include_once __DIR__ . '/../../components/pagination.php';

// Filter kelas dari alamat (?kelas=Bisnis). Nilai di luar daftar dianggap "semua".
$daftar_kelas = ['Eksekutif', 'Bisnis', 'Ekonomi'];
$kelas = param_enum('kelas', $daftar_kelas);   // hanya nilai dari daftar resmi, selain itu = semua

// Tab waktu: yang AKAN DATANG (bawaan), yang SUDAH BERANGKAT, atau SEMUA.
// "Sudah berangkat" = jam berangkat sudah lewat (dibandingkan dengan jam MySQL, NOW()).
$daftar_tab = ['akan_datang' => 'Akan Datang', 'sudah_berangkat' => 'Sudah Berangkat', 'semua' => 'Semua'];
$tab = param_enum('tab', array_keys($daftar_tab), 'akan_datang');   // hanya nilai dari daftar resmi

// Jumlah jadwal per tab (ikut filter kelas) untuk angka di tombol tab
$hitung = ['akan_datang' => 0, 'sudah_berangkat' => 0, 'semua' => 0];
$sql_hitung = "SELECT SUM(s.jam_berangkat >= NOW()) AS depan, SUM(s.jam_berangkat < NOW()) AS lewat
               FROM schedules s JOIN buses b ON b.id = s.bus_id";
if ($kelas !== '') {
    $st = $koneksi->prepare($sql_hitung . " WHERE b.kelas = ?");
    $st->bind_param('s', $kelas);
    $st->execute();
    $h = $st->get_result()->fetch_assoc();
} else {
    $h = $koneksi->query($sql_hitung)->fetch_assoc();
}
$hitung['akan_datang']     = (int) $h['depan'];
$hitung['sudah_berangkat'] = (int) $h['lewat'];
$hitung['semua']           = $hitung['akan_datang'] + $hitung['sudah_berangkat'];

// Syarat query (kelas + tab)
$where = ' WHERE 1=1';
$tipe  = '';
$param = [];
if ($kelas !== '') {
    $where .= ' AND b.kelas = ?';
    $tipe .= 's'; $param[] = $kelas;
}
if ($tab === 'akan_datang') {
    $where .= ' AND s.jam_berangkat >= NOW()';
} elseif ($tab === 'sudah_berangkat') {
    $where .= ' AND s.jam_berangkat < NOW()';
}

// Urutan:
//  - Akan Datang     : keberangkatan paling awal di atas
//  - Sudah Berangkat : yang paling baru berangkat di atas
//  - Semua           : yang akan datang dulu (paling awal di atas), lalu yang sudah berangkat (paling baru di atas)
if ($tab === 'sudah_berangkat') {
    $urut = 's.jam_berangkat DESC';
} elseif ($tab === 'semua') {
    $urut = '(s.jam_berangkat < NOW()) ASC, IF(s.jam_berangkat >= NOW(), UNIX_TIMESTAMP(s.jam_berangkat), -UNIX_TIMESTAMP(s.jam_berangkat)) ASC';
} else {
    $urut = 's.jam_berangkat ASC';
}

$total = $hitung[$tab];
[$per_halaman, $offset] = paginasi($total);

$stmt = $koneksi->prepare(
    "SELECT s.*, b.nama_bus, b.plat_nomor, b.kelas, (s.jam_berangkat < NOW()) AS sudah_lewat
     FROM schedules s
     JOIN buses b ON b.id = s.bus_id
     $where
     ORDER BY $urut
     LIMIT ? OFFSET ?"
);
// LIMIT & OFFSET ikut di-bind sebagai angka, bukan disambung ke teks query
$tipe .= 'ii'; $param[] = (int) $per_halaman; $param[] = (int) $offset;
$stmt->bind_param($tipe, ...$param);
$stmt->execute();
$hasil = $stmt->get_result();

// Judul mengikuti kelas yang dipilih
$judul   = $kelas !== '' ? "Jadwal & Rute Kelas $kelas" : 'Jadwal & Rute';
$q_kelas = $kelas !== '' ? '&kelas=' . urlencode($kelas) : '';
$url_tab = '/e-ticket-bus/admin/index.php?page=jadwal_dan_rute' . $q_kelas;

$pesan = $_GET['pesan'] ?? '';
?>
<!-- warna badge kelas bus: tiap kelas punya warna sendiri -->
<style>
  /* tab waktu yang sedang dibuka: hijau tua, tulisan putih */
  .table-filter-group .btn-table-action.tab-aktif {
    background: #0b2e22 !important;
    color: #fff !important;
    border-color: #0b2e22 !important;
    font-weight: 700;
  }
  .badge-kelas {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
  }
  /* titik kecil di depan tulisan, warnanya ikut warna teks */
  .badge-kelas::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
  }
  .kelas-ekonomi   { background: #dcfce7; color: #15803d; } /* hijau */
  .kelas-bisnis    { background: #dbeafe; color: #1d4ed8; } /* biru */
  .kelas-eksekutif { background: #fef3c7; color: #b45309; } /* emas */
</style>
<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title"><?= htmlspecialchars($judul) ?></h1>
    <p class="page-subtitle">Kelola rute perjalanan, jadwal keberangkatan, dan harga tiket.</p>
  </div>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/e-ticket-bus/admin/index.php" class="text-decoration-none text-muted-green">Dashboard</a></li>
      <li class="breadcrumb-item text-muted-green">Master Data</li>
      <li class="breadcrumb-item active text-main" aria-current="page"><?= htmlspecialchars($judul) ?></li>
    </ol>
  </nav>
</div>
<!-- page header -->

<?php if ($pesan === 'tambah_ok') : ?>
  <div class="alert alert-success">Jadwal baru berhasil ditambahkan.</div>
<?php elseif ($pesan === 'update_ok') : ?>
  <div class="alert alert-success">Jadwal berhasil diubah.</div>
<?php elseif ($pesan === 'hapus_ok') : ?>
  <div class="alert alert-success">Jadwal berhasil dihapus.</div>
<?php elseif ($pesan === 'jadwal_lewat') : ?>
  <div class="alert alert-warning">Jadwal yang sudah berangkat tidak bisa diubah. Kalau perlu jadwal baru, pakai tombol Tambah Jadwal.</div>
<?php elseif ($pesan === 'jadwal_dipakai') : ?>
  <div class="alert alert-danger">Jadwal tidak bisa dihapus karena sudah ada pemesanan tiket.</div>
<?php endif; ?>

<!-- kartu tabel -->
<div class="table-card-custom">

  <!-- bar atas tabel -->
  <div class="table-header-control">
    <div class="table-pagination-info">
      Menampilkan: <strong><?= $total ?></strong> jadwal<?= $kelas !== '' ? ' kelas ' . htmlspecialchars($kelas) : '' ?>
    </div>
    <div class="table-filter-group">
      <?php foreach ($daftar_tab as $kode => $label) : ?>
        <a href="<?= $url_tab ?>&tab=<?= $kode ?>" class="btn-table-action<?= $tab === $kode ? ' tab-aktif' : '' ?>"<?= $tab === $kode ? ' aria-current="page"' : '' ?>><?= $label ?> (<?= $hitung[$kode] ?>)</a>
      <?php endforeach; ?>
    </div>
        <?php if ($kelas === '') : ?>
      <div class="table-filter-group">
        <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute&aksi=tambah" class="btn-table-action">
          <i class="bi bi-plus-lg"></i> Tambah Jadwal
        </a>
      </div>
    <?php endif; ?>
  </div>

  <!-- tabel -->
  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No</th>
          <th>Rute</th>
          <th>Bus</th>
          <th>Sopir</th>
          <th>Jam Berangkat</th>
          <th>Harga</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr>
            <td colspan="7" class="text-center"><?= $tab === 'sudah_berangkat' ? 'Belum ada jadwal yang sudah berangkat' : ($tab === 'akan_datang' ? 'Tidak ada jadwal yang akan datang' : 'Belum ada data jadwal') ?><?= $kelas !== '' ? ' untuk kelas ' . htmlspecialchars($kelas) : '' ?>.</td>
          </tr>
        <?php endif; ?>

        <?php $no = $offset + 1; while ($j = $hasil->fetch_assoc()) : $lewat = (bool) $j['sudah_lewat']; ?>
          <tr<?= $lewat ? ' style="opacity:.7"' : '' ?>>
            <td><?= $no++ ?></td>
            <td class="table-order-id">
              <?= htmlspecialchars($j['kota_asal']) ?>
              <i class="bi bi-arrow-right mx-1"></i>
              <?= htmlspecialchars($j['kota_tujuan']) ?>
            </td>
            <td>
              <?= htmlspecialchars($j['nama_bus']) ?>
              <small class="text-muted">(<?= htmlspecialchars($j['plat_nomor']) ?>)</small>
              <span class="badge-kelas kelas-<?= strtolower($j['kelas']) ?>"><?= htmlspecialchars($j['kelas']) ?></span>
            </td>
            <td><?= $j['sopir'] !== '' ? htmlspecialchars($j['sopir']) : '-' ?></td>
            <td>
              <?= date('d M Y, H:i', strtotime($j['jam_berangkat'])) ?>
              <?php if ($lewat) : ?><span class="badge-kelas" style="background:#e5e7eb;color:#4b5563">Selesai</span><?php endif; ?>
            </td>
            <td>Rp <?= number_format((int) $j['harga'], 0, ',', '.') ?></td>
            <td>
              <div class="d-flex justify-content-center gap-1">
                <?php if ($lewat) : ?>
                  <span class="table-btn-action" title="Jadwal yang sudah berangkat tidak bisa diubah" style="opacity:.35;cursor:not-allowed"><i class="bi bi-pencil"></i></span>
                <?php else : ?>
                  <a href="/e-ticket-bus/admin/index.php?page=jadwal_dan_rute&aksi=update&id=<?= (int) $j['id'] ?>" class="table-btn-action" title="Ubah"><i class="bi bi-pencil"></i></a>
                <?php endif; ?>
                <form action="/e-ticket-bus/admin/function/jadwal_dan_rute.php?aksi=hapus" method="post" class="d-inline" onsubmit="return confirm('Yakin hapus jadwal ini?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $j['id'] ?>"><?php if ($kelas !== '') : ?><input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas) ?>"><?php endif; ?><input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>"><button type="submit" class="table-btn-action delete" title="Hapus"><i class="bi bi-trash"></i></button></form>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- tombol halaman (muncul hanya kalau data lebih dari 10) -->
  <?php render_pagination($total); ?>

</div>
<!-- kartu tabel -->