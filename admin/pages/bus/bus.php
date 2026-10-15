<?php
defined('ADMIN_APP') || exit('Akses langsung tidak diizinkan.');   // hanya boleh dibuka lewat index.php
// admin/pages/bus/bus.php
// Tabel daftar bus (bagian R = Read dari CRUD), bisa difilter per kelas.

include_once __DIR__ . '/../../database/koneksi.php';
include_once __DIR__ . '/../../components/pagination.php';

// Filter kelas dari alamat (?kelas=Bisnis). Nilai di luar daftar dianggap "semua".
$daftar_kelas = ['Eksekutif', 'Bisnis', 'Ekonomi'];
$kelas = param_enum('kelas', $daftar_kelas);

// Hitung total bus (sesuai filter)
if ($kelas !== '') {
    $stmt = $koneksi->prepare("SELECT COUNT(*) AS t FROM buses WHERE kelas = ?");
    $stmt->bind_param('s', $kelas);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['t'];
} else {
    $total = (int) $koneksi->query("SELECT COUNT(*) AS t FROM buses")->fetch_assoc()['t'];
}

[$per_halaman, $offset] = paginasi($total);

// Ambil 10 baris untuk halaman yang dibuka (terbaru di atas)
if ($kelas !== '') {
    $stmt = $koneksi->prepare("SELECT * FROM buses WHERE kelas = ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('sii', $kelas, $per_halaman, $offset);
    $stmt->execute();
    $hasil = $stmt->get_result();
} else {
    $stmt = $koneksi->prepare("SELECT * FROM buses ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $per_halaman, $offset);
    $stmt->execute();
    $hasil = $stmt->get_result();
}

// Judul mengikuti kelas yang dipilih
$judul   = $kelas !== '' ? "Data Bus Kelas $kelas" : 'Data Bus';
$q_kelas = $kelas !== '' ? '&kelas=' . urlencode($kelas) : '';

// Pesan hasil proses (dikirim lewat alamat: ...&pesan=tambah_ok)
$pesan = $_GET['pesan'] ?? '';
?>
<!-- warna badge kelas bus: tiap kelas punya warna sendiri -->
<style>
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
  .badge-kelas::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
  }
  .kelas-ekonomi   { background: #dcfce7; color: #15803d; }
  .kelas-bisnis    { background: #dbeafe; color: #1d4ed8; }
  .kelas-eksekutif { background: #fef3c7; color: #b45309; }

  /* penanda submenu sidebar yang sedang aktif */
  .sidebar-submenu-link.active { color: #fff; font-weight: 600; }
  .sidebar-submenu-link.active::before { content: "• "; color: var(--brand-lime); }
</style>

<!-- page header -->
<div class="page-header">
  <div>
    <h1 class="page-title"><?= htmlspecialchars($judul) ?></h1>
    <p class="page-subtitle">Kelola armada bus: nama bus, plat nomor, kapasitas kursi, dan kelas.</p>
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
  <div class="alert alert-success">Bus baru berhasil ditambahkan.</div>
<?php elseif ($pesan === 'update_ok') : ?>
  <div class="alert alert-success">Data bus berhasil diubah.</div>
<?php elseif ($pesan === 'hapus_ok') : ?>
  <div class="alert alert-success">Bus berhasil dihapus.</div>
<?php elseif ($pesan === 'bus_dipakai') : ?>
  <div class="alert alert-danger">Bus tidak bisa dihapus karena masih dipakai di jadwal.</div>
<?php endif; ?>

<!-- kartu tabel -->
<div class="table-card-custom">

  <!-- bar atas tabel -->
  <div class="table-header-control">
    <div class="table-pagination-info">
      Total: <strong><?= $total ?></strong> bus<?= $kelas !== '' ? ' kelas ' . htmlspecialchars($kelas) : '' ?>
    </div>
    <div class="table-filter-group">
      <a href="/e-ticket-bus/admin/index.php?page=bus&aksi=tambah" class="btn-table-action">
        <i class="bi bi-plus-lg"></i> Tambah Bus
      </a>
    </div>
  </div>

  <!-- tabel -->
  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Bus</th>
          <th>Plat Nomor</th>
          <th>Kapasitas Kursi</th>
          <th>Kelas</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total === 0) : ?>
          <tr>
            <td colspan="6" class="text-center">Belum ada data bus<?= $kelas !== '' ? ' untuk kelas ' . htmlspecialchars($kelas) : '' ?>.</td>
          </tr>
        <?php endif; ?>

        <?php $no = $offset + 1; while ($bus = $hasil->fetch_assoc()) : ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($bus['nama_bus']) ?></td>
            <td class="table-order-id"><?= htmlspecialchars($bus['plat_nomor']) ?></td>
            <td><?= (int) $bus['kapasitas_kursi'] ?> kursi</td>
            <td><span class="badge-kelas kelas-<?= strtolower($bus['kelas']) ?>"><?= htmlspecialchars($bus['kelas']) ?></span></td>
            <td>
              <div class="d-flex justify-content-center gap-1">
                <a href="/e-ticket-bus/admin/index.php?page=bus&aksi=update&id=<?= (int) $bus['id'] ?>" class="table-btn-action" title="Ubah"><i class="bi bi-pencil"></i></a>
                <form action="/e-ticket-bus/admin/function/bus.php?aksi=hapus" method="post" class="d-inline" onsubmit="return confirm('Yakin hapus bus ini?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $bus['id'] ?>"><?php if ($kelas !== '') : ?><input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas) ?>"><?php endif; ?><button type="submit" class="table-btn-action delete" title="Hapus"><i class="bi bi-trash"></i></button></form>
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